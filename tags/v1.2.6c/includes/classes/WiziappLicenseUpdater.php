<?php

class WiziappLicenseUpdater{
    public function register(){
        $success = FALSE;
        $licenseKey = $_POST['key'];

        if ( !empty($licenseKey) ){
            // We have a license, try to inform the admin
            $r = new WiziappHTTPRequest();
            $params = array(
                'key' => $licenseKey,
            );
            $response = $r->api($params, '/cms/license?app_id=' . WiziappConfig::getInstance()->app_id, 'POST');
            if ( is_wp_error($response) ) {
                WiziappLog::getInstance()->write('error', 'There was a problem trying to register the license: '.print_r($response, TRUE), 'WiziappLicenseUpdater.register');
            } else {
                $result = json_decode($response['body'], TRUE);
                if ( $result ){
                    if ( $result['header']['status'] ){
                        $success = TRUE;
                    } else {
                        WiziappLog::getInstance()->write('error', 'The admin returned an error: '.print_r($response, TRUE), 'WiziappLicenseUpdater.register');
                    }
                } else {
                    WiziappLog::getInstance()->write('error', 'There was an error parsing the results: '.print_r($response, TRUE), 'WiziappLicenseUpdater.register');
                }
            }
        }

        $header = array(
            'action' => 'registerLicense',
            'status' => $success,
            'code' => $success ? 200 : 500,
            'message' => '',
        );

        echo json_encode(array('header' => $header));
        exit;
    }
}