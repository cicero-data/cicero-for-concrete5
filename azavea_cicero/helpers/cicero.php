<?php

/* Copyright 2010-2012 Azavea, Inc.

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
 */

defined('C5_EXECUTE') or die(_("Access Denied."));
	class CiceroHelper {
        protected $config;
		protected $eh;
        public $url_base;
        public $url_base_rest;

        function __construct() {
            $config = new Config();
            $pkg = Package::getByHandle("azavea_cicero");
            $config->setPackageObject($pkg);
            $this->config = $config;
			$this->eh = Loader::helper('encryption');
            $this->url_base = 'http://cicero.azavea.com/'; # live server
            $this->url_base_rest = 'https://cicero.azavea.com/v3.1/';
            $this->api_user_email = 'cicero_email_username@example.com'; # for SOAP
        }

        /**
         * Get token from Cicero SOAP API using email and password stored in
         * config table.
		 * Used by the Cicero Live block.
         *
         * @return string token
         */        
        public function authenticate() {
            $needs_new_token = FALSE;
			$token = $this->config->get('CICERO_TOKEN', TRUE);
            if ( empty($token->value) ) {
                $needs_new_token = TRUE;
            } else {
                $token_saved = strtotime($token->timestamp);
                $token_age = time() - $token_saved;
                if ($token_age > $this->token_expiry) {
                    $needs_new_token = TRUE;
                } else {
                    // Working with a ConfigValue - we want the string
                    $token = $token->value;
                }
            }

            if ($needs_new_token == TRUE) {
				$client = new SoapClient ($this->url_base . "azavea.cicero.webservice.v2/AuthenticationService.asmx?wsdl");
                $credentials = $this->getUserNameAndPassword();
                $param = array(
                    'userName'=>$this->api_user_email,
                    'password'=>$credentials['password']
                );
                try {
                    $result = $client->GetToken($param);
                    $token = $result->GetTokenResult;
					if( strstr( $token, 'ERROR:' ) === false ){
						$this->config->save('CICERO_TOKEN', $token);
					}
                } catch (SoapFault $e) {
                    error_log("message: " . $e->getMessage() );
                    return FALSE;
                }
            }
            return $token;
        }
        
        /**
         * Gets a PHP object created from JSON (or returns False).
		 * Used by the Cicero Elections block.
         *
         * @param string $url API endpoint
         * @param string $postfields 
         * @return object The response body object converted from JSON
         */
        public function get_response($url, $postfields=''){
            $ch = curl_init();
            curl_setopt ($ch, CURLOPT_URL, $url);
            curl_setopt ($ch, CURLOPT_RETURNTRANSFER, True);
            if($postfields !== ''):
                curl_setopt ($ch, CURLOPT_POST, True);
                curl_setopt ($ch, CURLOPT_POSTFIELDS, $postfields);
            endif;
            $json = curl_exec($ch);
            //error_log($json);
            curl_close($ch);
            return json_decode($json);
        }
        
        /**
         * Returns a PHP response object after trying to authenticate via the
         * Cicero REST service.
		 * This authentication method is used by the Cicero Elections block.
         *
         * @return object PHP object converted from JSON response
         */
        public function authenticateViaREST() {
            $credentials = $this->getUserNameAndPassword();
            $adminuser = $credentials['user_name'];
            $adminpass = $credentials['password'];
            error_log("Authenticating with credentials:");
            $postbody = "username=$adminuser&password=$adminpass";
            error_log("".$postbody);
            error_log("At: ".$this->url_base_rest.'token/new.json');
            $response = $this->get_response($this->url_base_rest.'token/new.json', $postbody);
            return $response;
        }

        /**
         * Gets credentials from the config table.
         *
         * @return array A dictionary containing "user_name" and "password" strings
         */
        public function getUserNameAndPassword() {
			$this->config->get('CICERO_PASSWORD') ? $p = $this->eh->decrypt($this->config->get('CICERO_PASSWORD')) : $p = null;
			return array('user_name'=>$this->config->get('CICERO_USER_NAME'), 'password'=>$p);
        }
        
        /**
         * Set credentials in the config table.
         *
         * @param string $user_name Email address associated w/ Cicero account
         * @param string $password Raw password associated w/ Cicero account
         * @param string $bing_key Microsoft Bing API Key to use for Cicero Live
         * @return null
         */
        public function setUserNameAndPassword($user_name, $password, $bing_key = null) {
            $this->config->save('CICERO_USER_NAME', $user_name);
            $this->config->save('CICERO_PASSWORD', $this->eh->encrypt( $password ) );
        }
	}
?>
