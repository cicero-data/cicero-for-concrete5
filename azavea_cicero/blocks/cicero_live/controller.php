<?php

/* Copyright 2010-2012 Azavea, Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *     http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */


defined('C5_EXECUTE') or die(_("Access Denied."));
class CiceroLiveBlockController extends BlockController {
    protected $btDescription = "Elected Official lookup based on Cicero";
    protected $btName = "Cicero Live";
    protected $btTable = "btCicero";
    protected $btInterfaceWidth = "350";
    protected $btInterfaceHeight = "500";

    protected $token_expiry = 86400; /* A day in seconds */

    public function on_start() {
        error_log("CiceroLive Block starting...");
        $cicero = Loader::helper('cicero', 'azavea_cicero');
        $configs = $cicero->getUserNameAndPassword();
        $this->set('user_name', $configs['user_name']);
        $this->set('password', $configs['password']);
        $config = new Config();
        $this->set('bing_key', $config->get('CICERO_BING_KEY') );
    }

    public function save() {
        $cicero = Loader::helper('cicero', 'azavea_cicero');
        $cicero->setUserNameAndPassword($_REQUEST['user_name'], $_REQUEST['password']);
        $config = new Config();
        $config->save('CICERO_BING_KEY', $_REQUEST['bing_key']);
    }

    public function on_page_view() {
        $html = Loader::helper('html');
        $this->addHeaderItem($html->css('jquery.ui.css'));
        $this->addHeaderItem($html->css('ccm.dialog.css'));
        $this->addHeaderItem($html->javascript('jquery.ui.js'));
        $this->addHeaderItem($html->javascript('ccm.dialog.js'));
    }

    public function action_get_elected_officials() {
        //error_log("Getting officials");
        $cicero = Loader::helper('cicero', 'azavea_cicero');
        $js = Loader::helper('json');

        /* method in azavea_cicero/helpers/cicero.php */
        //$token = $cicero->authenticate();
        $authResponse = $cicero->authenticateViaREST();
        //error_log("Authentication response received.");
        if (is_null($_REQUEST['latitude'])) {
            header("HTTP/1.0 400 Bad Request");
            exit;
        }
        if($authResponse->success === True) {
            //error_log("Authentication succeeded");
            $params['token'] = $authResponse->token;
            $params['user'] = $authResponse->user;
            $params['lat'] = $_REQUEST['latitude'];
            $params['lon'] = $_REQUEST['longitude'];

            $queryString = http_build_query($params);
            $url = $cicero->url_base_rest . 'official?' . $queryString;
            $officialResponse = $cicero->get_response($url);

            if($officialResponse->response->results->count->total == 0) {
                error_log("No officials found for the given address.");
                print $js->encode(array('success'=>FALSE, 'message'=>'No officials found for the given location.'));
            }
            //error_log("Printing official results");

            $officialResult = $officialResponse->response->results->officials;
            print $js->encode($officialResult);//this is probably right.
            exit;

        } else {
            throw new Exception('Could not authenticate Cicero REST API user.');
        }
        exit;
    }
    ////////////////////// Not sure this needs to be new districts anymore
    public function action_get_new_legislative_districts() {
        //error_log("Getting new districts");
        $cicero = Loader::helper('cicero', 'azavea_cicero');
        $js = Loader::helper('json');
        //$token = $cicero->authenticate();
        $authResponse = $cicero->authenticateViaREST();
        if($authResponse->success === True) {
            $params['token'] = $authResponse->token;
            $params['user'] = $authResponse->user;
            $params['lat'] = $_REQUEST['latitude'];
            $params['lon'] = $_REQUEST['longitude'];
            // If all 2010 districts have gone into effect, omit this
            // next parameter.
            $params['district_type'] = 'ALL_2010';

            $queryString = http_build_query($params);
            $url = $cicero->url_base_rest . 'legislative_district?' . $queryString;
            $legislativeDistrictResponse = $cicero->get_response($url);

            if($legislativeDistrictResponse->response->results->count->total == 0) {
                error_log('No location found for the given address.');
                print $js->encode(array('success'=>FALSE, 'message'=>'No districts found for the given location.'));
            }

            $legislativeDistrictResult = $legislativeDistrictResponse->response->results->districts;
            print $js->encode($legislativeDistrictResult);
        } else {
            throw new Exception('Could not authenticate Cicero REST API user.');
        }
        exit;
    }

    public function action_get_nonlegislative_districts() {
        //error_log("Getting non-legislative districts.");
        $cicero = Loader::helper('cicero', 'azavea_cicero');
        $js = Loader::helper('json');
        //$token = $cicero->authenticate(); SOAP
        $authResponse = $cicero->authenticateViaREST();
        $district_types = array('SCHOOL', 'WATERSHED', 'COUNTY', 'POLICE', 'CENSUS');
        if ($authResponse->success === True) {
            $token = $authResponse->token;
            $user = $authResponse->user;
            try {
                $latitude = $_REQUEST['latitude'];
                $longitude = $_REQUEST['longitude'];
                $districts = array();
                foreach ($district_types as $type) {
                    $new_results = $this->get_nonlegislative_districts($token, $user, $latitude, $longitude, $type);
                    //error_log("Found new results ".$js->encode($new_results));
                    $districts = array_merge($districts, $new_results);
                }
                //error_log("Found nonleg districts: ".$js->encode($districts));
                print $js->encode($districts);
            } catch (Exception $e) {
                error_log('Problem in getting nonlegislative_district: '.$e->getMessage()." using params:\n".print_r($param, TRUE));
                print $js->encode(array('success'=>FALSE, 'message'=>$e->getMessage()));
            }
        }
        exit;
    }

    protected function get_nonlegislative_districts($token, $user, $latitude, $longitude, $type) {
        $cicero = Loader::helper('cicero', 'azavea_cicero');
        $js = Loader::helper('json');
        $param = array(
            'user'=>$user,
            'token'=>$token,
            'lat'=>$latitude,
            'lon'=>$longitude,
            'district_type'=>$type
        );
        $queryString = http_build_query($param);
        $url = $cicero->url_base_rest . 'nonlegislative_district?' . $queryString;
        //error_log("Querying for districts with query: ".$url);
        $result = $cicero->get_response($url);
        $districts = $result->response->results->districts;

        return $districts;
        exit;
    }

    public function action_get_maps() {
        //error_log("Getting maps.");
        $cicero = Loader::helper('cicero', 'azavea_cicero');
        $js = Loader::helper('json');

        $authResponse = $cicero->authenticateViaREST();
        if ($authResponse->success === True) {
            $mapExtentUS = array(
                "x_min"=>-171.5625,
                "x_max"=>-66.884766,
                "y_min"=>24.4415,
                "y_max"=>71.746432,
            );

            $mapExtentAK = array(
                "x_min"=>-179.9999,
                "x_max"=>-129.9,
                "y_min"=>50,
                "y_max"=>71.8,
            );

            $imageSpec = array(
                "boundary_color"=>$_REQUEST['boundaryColor']?$_REQUEST['boundaryColor']:"#000000",
                // Opacity specifications seem to result in 500 errors.
                "boundary_opacity"=>$_REQUEST['boundaryOpacity']?$_REQUEST['boundaryOpacity']:"80",
                "boundary_width"=>$_REQUEST['boundaryWidth']?$_REQUEST['boundaryWidth']:"3",
                "fill_color"=>$_REQUEST['fillColor']?$_REQUEST['fillColor']:"#53A8C8",
                "fill_opacity"=>$_REQUEST['fillOpacity']?$_REQUEST['fillOpacity']:"25",
                "height"=>$_REQUEST['imgHeight']?$_REQUEST['imgHeight']:200, //change defaults here
                "width"=>$_REQUEST['imgWidth']?$_REQUEST['imgWidth']:200, //change defaults here
                "include_image_data"=>"true",
                "srs"=>"3785"
            );

            $param = array(
                'user'=>$authResponse->user,
                'token'=>$authResponse->token,
                // If you put the ID as a query parameter, you'll get error
                // 400, bad request, requiring "district_type". But if you
                // add district_type, you'll get 404.
                // However, adding the district ID as part of the path works
                // (see below).
                //'id'=>$_REQUEST['ID'], // Database ID of the district.
                //'district_type'=>$_REQUEST['districtType'] // API Bug?
            );
            $param = array_merge($param, $imageSpec);
            
            $queryString = http_build_query($param);
            //error_log("Getting map with parameters ".$queryString);
            $url = $cicero->url_base_rest . 'map/' . $_REQUEST['ID'] . '?' . $queryString;
            $result = $cicero->get_response($url);
            //error_log("Map query result: ".$js->encode($result));
            $map_data = $result->response->results->maps;
            print $js->encode($map_data);

        } else {
            throw new Exception("Could not authenticate Cicero REST API user.");
        }
        exit;
    }
}
?>
