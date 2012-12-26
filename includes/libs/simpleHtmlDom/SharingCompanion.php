<?php

/**
* Description of SharingCompanion
*
* @author Avi
*/

require_once(dirname(__FILE__) . '/simple_html_dom.php');

class SharingCompanion {

	private $html;
	private $flag;

	public function __construct() {
		$this->html = new simple_html_dom_wiziapp();
		$this->flag = false;
	}

	public function removeSharing($htmlString, $pid) {
		$this->html->load($htmlString);
		$this->removeAddToAny();
		$this->removeSexyBookmarks($pid);
		// $this->removeShareThis();
		$response = ($this->flag) ? $this->html->save() : $htmlString;
		$this->html->clear();
		return $response;
	}

	private function removeAddToAny() {
            for ($i=1;$i<=10;$i++){
                $divName = "div[id=wpa2a_{$i}]";
		$e = $this->html->find($divName, 0);
		if ( isset($e->outertext) ) {
			$e->outertext = '';
			$this->flag = true;
		}
            }
	}

	private function removeSexyBookmarks($pid){
//            foreach ($postIds as $pid){
                $className = '.shr-publisher-'.$pid;
		$e = $this->html->find($className);
		if ( isset($e->outertext) ) {
			$e->outertext = '';
			$this->flag = true;
		}
//           }
        }

	private function removeShareThis(){}
}