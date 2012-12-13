<?php if (!defined('WP_WIZIAPP_BASE')) exit();
/**
* @package WiziappWordpressPlugin
* @subpackage Cache
* @author comobix.com plugins@comobix.com
*
* @todo add support for memcache configuration, will require configuration, maybe from wordpress cache plugins?
*/

class WiziappCacheFile extends WiziappCache
{
	/**
	* path to directory with cache files
	* @var string
	*/
	private $_fileDir = 'WP_ROOT_PATH/wp-content/uploads/wiziapp_cache/';

	public function __construct( array $options = array()) {
		parent::__construct($options);

		WiziappLog::getInstance()->write('INFO', "Did not find APC installed, using File caching", "WiziappCacheFile.__construct");

		$uploads_dir = wp_upload_dir();
		$this->_fileDir = str_replace('WP_ROOT_PATH/wp-content/uploads', $uploads_dir['basedir'], $this->_fileDir);

		if ( ! file_exists($this->_fileDir)) {
			if ( ! @mkdir($this->_fileDir, 0777, true)) {
				WiziappLog::getInstance()->write('ERROR', 'Could not create the wiziapp file caching directory: '.$this->_fileDir, "WiziappCacheFile.__construct");
				$this->_is_enabled = FALSE;
			}
		} elseif ( ! @is_readable($this->_fileDir) || ! @is_writable($this->_fileDir)) {
			if ( ! @chmod($this->_fileDir, 0777)) {
				WiziappLog::getInstance()->write('ERROR', 'The upload directory exists, but its not readable or not writable: '.$this->_fileDir, "WiziappCacheFile.__construct");
				$this->_is_enabled = FALSE;
			}
		}

		if ( ! WiziappConfig::getInstance()->wiziapp_cache_enabled ) {
			$this->_is_enabled = FALSE;
		}
	}

	public function delete_old_files() {
		if ( ! ( file_exists($this->_fileDir) && is_readable($this->_fileDir) && is_writable($this->_fileDir) ) ) {
			return;
		}

		if ($handle = opendir($this->_fileDir))	{
			// loop over the directory.
			while (($file = readdir($handle)) !== false) {
				if ($file != "." && $file != "..")
				{
					if ((time() - filemtime($this->_fileDir.$file)) > $this->_options['duration']) {
						@unlink($this->_fileDir.$file);
					}
				}
			}
			closedir($handle);
		}
	}

	/**
	* start cache if need of print content form cache
	* @param array $output
	* @return void
	*/
	protected function _getFromCache( & $output) {
		if ( ! $this->_is_enabled )	return;

		$file = $this->_fileDir.$output['key'];

		if ( ! file_exists($file) || ((time() - filemtime($file)) >= $this->_options['duration'])) return;

		$output['headers'] = @unserialize( @file_get_contents($file.'_headers') );
		$output['content'] = @unserialize( @file_get_contents($file) );
	}

	/**
	* @param array $output
	* @return void
	*/
	protected function _renew_cache($output) {
		if ( ! $this->_is_enabled )	return;

		WiziappLog::getInstance()->write('DEBUG', 'Saving cache key: '.$output['key'],
			"WiziappCacheFile._renew_cache");

		if (
			! @file_put_contents( $this->_fileDir.$output['key'].'_headers', serialize($output['headers']) ) ||
			! @file_put_contents( $this->_fileDir.$output['key'], 			 serialize($output['content']) )
		) {
			WiziappLog::getInstance()->write('ERROR', 'Can\'t write file.', "WiziappCacheFile._renew_cache");
		}
	}

}