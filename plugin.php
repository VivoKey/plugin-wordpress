<?php
/*
Plugin Name: VivoKey OpenID Connect
Plugin URI:
Description: This plugin allows users to authenticate using the VivoKey OpenID Connect API
Version: 1.1
Author: VivoKey
Author URI:
Author Email: amal@vivokey.com
License:

* Licensed under the Apache License, Version 2.0 (the "License"); you may
* not use this file except in compliance with the License. You may obtain
* a copy of the License at
*
*      http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
* WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
* License for the specific language governing permissions and limitations
* under the License.

*/

if (!class_exists('VivoKeyLoginPlugin')):
    require('lib/helper/MY_Plugin.php');

    class VivoKeyLoginPlugin extends MY_Plugin
    {

        /**
         * Initializes the plugin by setting localization, filters, and administration functions.
         */
        public function __construct()
        {

            // Call the parent constructor
            parent::__construct(dirname(__FILE__));

            /**
             * Register settings options
             */
            add_action('admin_init', array(
                $this,
                'register_plugin_settings_api_init'
            ));
            
            add_action('admin_menu', array(
                $this,
                'register_plugin_admin_add_page'
            ));

            add_action('show_user_profile', array(
                $this,
                'custom_user_profile_fields'
            ), 10, 1);

            add_action('edit_user_profile', array(
                $this,
                'custom_user_profile_fields'
            ), 10, 1);


            /**
             * Custom Login page
             */
            add_action('login_form', array(
                $this,
                'add_button_to_login'
            ));

            /**
             * Add a plugin settings link on the plugins page
             */
            $plugin = plugin_basename(__FILE__);
            add_filter("plugin_action_links_$plugin", function ($links) {
                $settings_link = '<a href="options-general.php?page=VivoKey+OpenID+Connect">Settings</a>';
                array_unshift($links, $settings_link);
                return $links;
            });

            /**
             * Add Register + Auth Endpoint
             */
            add_filter('query_vars', function ($vars) {
                $vars[] = 'state';
                return $vars;
            });
            $self = $this;
            add_action('parse_request', function ($wp) use ($self) {
                if (array_key_exists('state', $wp->query_vars)) {
                    $self->registerAndAuth();
                }
                if ($wp->request == 'setup') {
                    $self->registerClientID();
                }
                if ($wp->request == 'disconnect') {
                    $self->disconnectUser();
                }
            });

            add_filter("authenticate", function ($user, $username, $password) {
                $id_token = get_user_meta($user->ID, 'id_token', true);
                $VivoKey_only_sign_on = $this->get_option('VivoKey_Login_Only');

                
                if (/*$VivoKey_only_sign_on == 1 &&*/ $id_token != null) { // Defaulting so that if they are linked they must use vivokey
                    return new WP_Error('VivoKey', __('<strong>ERROR</strong>: You must log in using your VivoKey.'));
                }
                return $user;
            }, 30, 3);
        } // end constructor

        public function custom_user_profile_fields()
        {
            echo $user_id;
            $link = "https://api.vivokey.com/openid/authorize?response_type=code&scope=openid&client_id=" . $this->get_option('VivoKey_Client_ID') . "&state=register&redirect_uri=" . get_site_url();

            $user_id = $_GET['user_id'];
            if (!$user_id) {
                $user_id = get_current_user_id();
            }

            $id_token = get_user_meta($user_id, 'id_token', true);
            echo '<h3>VivoKey OpenID Connect</h3>';
            if ($id_token != null) {
                echo ' <table class="form-table">
            <tbody><tr id="password" class="user-pass1-wrap">
                <th><label for="pass1-text">Disconnect VivoKey Profile</label></th>
                <td>
                    <button type="button" onclick="location.href=\''. get_site_url() .'/disconnect?userID='. $user_id .'\'" class="button">Disconnect</button>
                </td>
            </tr>
            
                </tbody></table>';
            } else {
                echo ' <table class="form-table">
            <tbody><tr id="password" class="user-pass1-wrap">
                <th><label for="pass1-text">Connect VivoKey Profile</label></th>
                <td>
                    <button type="button" onclick="location.href=\'' . $link . '\'" class="button">Connect</button>
                </td>
            </tr>
            
                </tbody></table>';
            }
        }
        public function registerClientID()
        {
            $json = file_get_contents('php://input');
            $data = json_decode($json);


            $given_client_id = $data->{'client_id'};

            $new_id = $data->{'client_id'};
            $new_secret = $data->{'client_secret'};
           
            // Get ID and Secret
            $given_client_id = $this->get_option('VivoKey_Client_ID');
            $given_client_secret = $this->get_option('VivoKey_Client_Secret');


            if ($new_id == '' || $new_secret == '') {
                $json = array();
                $json[] = array('success' => false, 'error' => 'Malformed Request', 'code' => 'malformed-request');
                wp_send_json($json[0], 200);
            }

            if ($given_client_id != '' && $given_client_secret != '') {
                $json = array();
                $json[] = array('success' => false, 'error' => 'Client ID/Secret already set', 'code' => 'already-set');
                wp_send_json($json[0], 200);
            } else {
                $this->update_option('VivoKey_Client_ID', $new_id);
                $this->update_option('VivoKey_Client_Secret', $new_secret);

                $json = array();
                $json[] = array('success' => true, 'message' => 'Client ID/Secret set');
                wp_send_json($json[0], 200);
            }
        }


        public function disconnectUser()
        {
            $givenUserID = $_GET['userID'];

            if (!$givenUserID) {
                wp_die("No UserID supplied. No changes have been made!");
            }

            $user = wp_get_current_user();
            $allowed_roles = array('administrator');

            $isadmin;

            if (array_intersect($allowed_roles, $user->roles)) {
                $isadmin = true;
            } else {
                $isadmin = false;
            }

            $real_user_id = get_current_user_id();

            if (!$real_user_id) {
                wp_die("You are not signed in.");
            }

            // If you're removing your own account or you are an admin then you can perform this action
            if ($givenUserID == $real_user_id || $isadmin == true) {
                // remove the value from db
                update_user_meta($givenUserID, 'id_token', '');

                // redirect to user page
				$url = "/wp-admin/user-edit.php?user_id=" . $givenUserID;
                // redirect to user page
  			    header( "refresh:5;url=" . $url ); 
				wp_die("Your VivoKey Profile is disconnected! \n Redirecting in 5 secs.");
            } else {
                wp_die("You do not have permission to disconnect this account");
            }
        }

        public function registerAndAuth()
        {
            $action = $_GET['state'];

            if ($action == 'register') {
                // Check user is logged in
                $user_id = get_current_user_id();

                if (!$user_id) {
                    wp_die("You are not signed in.");
                }
            }

            // Get code from url param
            $code = $_GET['code'];

            if (!$code) {
                wp_die("No code supplied. Your VivoKey Profile is not linked.");
            }

            // Do second request to get ID TOKEN
            

            $curl = curl_init();

            $given_client_id = $this->get_option('VivoKey_Client_ID');
            $given_client_secret = $this->get_option('VivoKey_Client_Secret');

            // Live Details
            $authHeader = base64_encode($given_client_id . ':' . $given_client_secret);
            $url = "https://api.vivokey.com/openid/token/";
            $PORT = "443";
            $redirecturl = get_site_url();
            ;

            curl_setopt_array($curl, array(
                CURLOPT_PORT => $PORT,
                CURLOPT_URL => $url,

                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_SSLVERSION => 4,

                CURLOPT_POSTFIELDS => 'redirect_uri=' . $redirecturl . '&grant_type=authorization_code&code=' . $code,

                CURLOPT_HTTPHEADER => array(
                    "Authorization: Basic " . $authHeader,
                    "Content-type: application/x-www-form-urlencoded"
                )
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                echo "errored here";
                wp_die("Error: " . $err);
            } else {
                $response = json_decode($response, true);
                if (!$response["access_token"]) {
                    wp_die("Error: " . $response["error"] . "</br>" . $response["error_description"]);
                }
            }

            $token = $response["id_token"];
            $tokenarray = base64_decode(str_replace('_', '/', str_replace('-', '+', explode('.', $token) [1])));
            $tokenarray = json_decode($tokenarray, true);
            $id_token_sub = $tokenarray['sub'];
            $iss_token = $tokenarray['iss'];

            // Store Id Token to var
            $id_token = $id_token_sub . $iss_token;

            if ($action == 'register') {

                // Update User Meta table with new token
                update_user_meta($user_id, 'id_token', $id_token);

                header( "refresh:5;url=/wp-admin/profile.php" ); 
				wp_die("Your VivoKey Profile is Linked! \n Redirecting in 5 secs.");
            }

            if ($action == 'auth') {
                $lh_users = get_users(array(
                    "meta_key" => "id_token",
                    "meta_value" => $id_token,
                    "fields" => "id_token"
                ));

                $foundId = reset($lh_users);

                if (!$foundId) {
                    wp_die("No VivoKey Profile linked! Please re-link your VivoKey Profile!");
                }

                // Login User
                wp_set_auth_cookie($foundId);
                wp_safe_redirect(home_url());
                exit();
            }
            return;
        }

        /*  login Button    */
        public function add_button_to_login()
        {
            if ($this->get_option('VivoKey_Client_ID')) {
                $this->load_view('button', null, true);
            }
        }

        /*  Settings Content    */

        public function register_plugin_settings_api_init()
        {
            register_setting($this->get_option_name(), $this->get_option_name());



            add_settings_section('VivoKey-connect-clienta', 'Welcome!', function () {
                echo '<div style="max-width: 50%; min-width: 411px;">';
                echo "<p>Thank you for installing the VivoKey OpenID Connect plugin for WordPress! This plugin will enable you to link your VivoKey cryptobionic implant with your WordPress account and allow you to scan your VivoKey to log in, rather than enter your username and password.</p>";
            }, 'VivoKey-connect');


            if (!$this->get_option('VivoKey_Client_ID')) {
                add_settings_section('VivoKey-connect-clientb', 'Quick Start Guide', function () {
                    echo "

                Connecting WordPress site to VivoKey Connect is easy! You just need to create an OpenID Connect application in your VivoKey Profile. Don't worry, we've made it easy! Just follow these steps;
                <ul>
                <li>1. Open the VivoKey app on your smartphone and scan your VivoKey to get to the dashboard.</li>
                <li>2. Tap the QR code icon in the upper right corner of the app, then scan this QR code.</li>
                </ul>
                <br>
                <div><center>";

                    $qr_name = get_option('blogname');
                    $qr_description = get_option('blogdescription');
                    $qr_redirect = get_site_url();
                
             
                    $qr_data = 'vivokey://oauth/app?n=' . $qr_name . '&d=' . $qr_description . '&r=' . $qr_redirect;
             
                    $qr_data_encoded = urlencode($qr_data);
                 
                    echo '<img src="https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . $qr_data_encoded . '&choe=UTF-8" title="Setup VivoKey App" /> </center> </div> Once you scan the above QR code with your VivoKey app, the Client ID and Secret settings below should automatically populate. If they do not, or you receive a "push error" when attempting to create the OpenID Connect application, you can manually enter your Client ID and Secret.';
                }, 'VivoKey-connect');
            }
            add_settings_section('VivoKey-connect-clientc', 'VivoKey OpenID Connect Settings', function () {
            }, 'VivoKey-connect');

            // Add a Client ID setting
            $this->add_settings_field('VivoKey_Client_ID', 'VivoKey-connect', 'VivoKey-connect-clientc');
            // Add a Client Secret setting
            $this->add_settings_field('VivoKey_Client_Secret', 'VivoKey-connect', 'VivoKey-connect-clientc');
/* TODO: fix checkbox saving
            add_settings_section('VivoKey-connect-clientd', 'Optional Settings', function () {
                echo "You can configure additional settings that affect how your WordPress site will treat VivoKey authentications. </br></br>";
                
                $options = $this->get_option('VivoKey_Login_Only');
                if (!isset($options['VivoKey_Login_Only'])) {
                    $options['VivoKey_Login_Only'] = 0;
                }
    
                $html = '<input type="checkbox" id="wpt_VivoKey_Login_Only" name="wpt_settings[VivoKey_Login_Only]" value="1"' . checked(1, $options['VivoKey_Login_Only'], false) . '/>';
                $html .= '<label for="wpt_VivoKey_Login_Only">Check to disable email & password login for VivoKey linked accounts.</label>';
                echo $html;
            }, 'VivoKey-connect');

            //Add checkbox shit
            $this->add_settings_field('VivoKey_Login_Only', 'Checkbox Element', 'VivoKey-connect-clientd');
*/
            add_settings_section('VivoKey-connect-cliente', 'How to connect your VivoKey Profile to WordPress', function () {
                echo "Any WordPress users who want to connect their VivoKey Profile to their WordPress account must first log in using their username and password, then navigate to the <a href=\"" . get_site_url() ."/wp-admin/profile.php\">Profile page</a>. Toward the bottom of the Profile page there will be a VivoKey Profile section with a button that says \"Connect VivoKey\". When the button is clicked, they will be prompted to authenticate with their VivoKey and grant authorization to WordPress to access their OpenID Connect profile information. Once connected, the VivoKey member may then use the \"Log in with VivoKey\" button on the WordPress Login screen. </div>";
            }, 'VivoKey-connect');

            // <?php add_settings_field( $id, $title, $callback, $page, $section, $args );
        }

        /*  Settings Dropdown and URL    */
        public function register_plugin_admin_add_page()
        {
            $self = $this;
            add_options_page('VivoKey OpenID', 'VivoKey OpenID', 'manage_options', 'VivoKey OpenID Connect', function () use ($self) {
                $self->load_view('settings', null);
            });
        }
    } // end class
    // Init plugin
    $plugin_name = new VivoKeyLoginPlugin();
endif;
