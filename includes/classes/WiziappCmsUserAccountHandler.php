<?php if (!defined('WP_WIZIAPP_BASE')) exit();

class WiziappCmsUserAccountHandler{
    public function registration() {
        if(!empty($_POST)) {
            $_REQUEST['action'] = '';
            $username = $_REQUEST['user_login'];
            $email = $_REQUEST['user_email'];

            ob_start();
            require_once ABSPATH . 'wp-includes/registration.php';
            require_once ABSPATH . 'wp-login.php';
            ob_end_clean();

            WiziappLog::getInstance()->write('info', 'Before register user: ' . $username, 'account.wiziapp_user_registration');
            $user_id = register_new_user($username, $email);
            WiziappLog::getInstance()->write('info', 'After register user: ' . $username, 'account.wiziapp_user_registration');

            if (is_int($user_id)) {
    //            $status = TRUE;
                $result = __('Registration successfull', 'wiziapp');
            } else {
                $result = implode('<br>', $user_id->get_error_messages());
    //            $status = FALSE;
            }
        } else {
            $result = '';
        }

    //    $header = array(
    //        'action' => 'register',
    //        'status' => $status,
    //        'code' => ($status) ? 200 : 4004,
    //        'message' => ($status) ? '' : 'Invalid registartion',
    //    );
    //    echo json_encode(array_merge(array('header' => $header), $result));
    //    exit;
        return ($result);
    }
    public function forgotPassword() {
        if(!empty($_POST)) {
            $_REQUEST['action'] = '';
            $_POST['user_login'] = $_REQUEST['user_login'];
            $_POST['user_email'] = $_REQUEST['user_email'];

            ob_start();
            require_once ABSPATH . 'wp-includes/registration.php';
            require_once ABSPATH . 'wp-login.php';
            ob_end_clean();

            $status = retrieve_password();
            if($status === true) {
    //            $status = TRUE;
                $result = __("Success", 'wiziapp');
            } else {
                $result = implode('<br>', $status->get_error_messages());
    //            $status = FALSE;
            }
        } else {
          $result = '';
        }

    //    $header = array(
    //        'action' => 'forgot_password',
    //        'status' => $status,
    //        'code' => ($status) ? 200 : 4004,
    //        'message' => ($status) ? '' : 'Invalid forgot password',
    //    );
        return ($result);
    }

    /*
    * wiziapp_user_push_subscription
    *
    * Handles the push notifications subscriptions for the user
    * POST /user/track/{key}/{value}
    * DELETE /user/track/{key}/{value}
    *
    */
    public static function pushSubscription($key, $val=''){
        // Validate the user
        $ls = new WiziappLoginServices();
        $user = $ls->check(TRUE);
        // @todo add validation to key and val
        if ( $user != null && $user !== FALSE){
            // The user is valid and can login to the service,
            // set his options for him
            if ( !empty($key) ){
                $settings = get_usermeta($user->ID, 'wiziapp_push_settings');
                if ( $_SERVER['REQUEST_METHOD'] == 'POST' ){
                    if (!isset($settings[$key])){
                        $settings[$key] = array();
                    }
                    $settings[$key][$val] = TRUE;
                } elseif ($_SERVER['REQUEST_METHOD'] == 'DELETE' ){
                    if ( isset($settings[$key]) && isset($settings[$key][$val]) ){
                        unset($settings[$key][$val]);
                    }
                }
                update_usermeta($user->ID, 'wiziapp_push_settings', $settings);
                // If we are here everything is ok
                $status = TRUE;
                echo json_encode(array('status'=>$status));
                exit;
            }
        }
    }
}