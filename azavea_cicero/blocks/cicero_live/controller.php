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
            $cicero = Loader::helper('cicero', 'azavea_cicero');
            $js = Loader::helper('json');

            /* method in azavea_cicero/helpers/cicero.php */
            //$token = $cicero->authenticate();
            $authResponse = $cicero->authenticateViaREST();
			if (is_null($_REQUEST['latitude'])) {
                    header("HTTP/1.0 400 Bad Request");
					exit;
            }
            if($authResponse->success === True) {
                $params['token'] = $authResponse->token;
                $params['user'] = $authResponse->user;
			    $params['lat'] = $_REQUEST['latitude'];
			    $params['lon'] = $_REQUEST['longitude'];
				//$params['district_type'] = 'all';
				//$params['includeAtLarge'] = True;
				
				$queryString = http_build_query($params);
				$url = $cicero->url_base_rest . 'official?' . $queryString;
				$officialResponse = $cicero->get_response($url);
				
				if($officialResponse->response->results->count->total) == 0) {
					//error_log('No location found for the given address.');
                	print $js->encode(array('success'=>FALSE, 'message'=>'No officials found for the given location.'));
                }
				
				$officialResult = $officialResponse->response->results->officials;
				//print $officialResult;//return $officialResult;
				print $js->encode($officialResult);//this is probably right.

                /*
			
			START OF OLD CICERO_LIVE SOAP CODE	  *
			
            try {
                $officials = new SoapClient ($cicero->url_base . "azavea.cicero.webservice.v2/ElectedOfficialQueryService.asmx?wsdl");

                if (is_null($_REQUEST['latitude'])) {
                    header("HTTP/1.0 400 Bad Request");
					exit;
                }

                $param = array(
                    'authToken'=>$token,
                    'latitude'=>$_REQUEST['latitude'],
                    'longitude'=>$_REQUEST['longitude'],
                    'districtType'=>'all',
                    'includeAtLarge'=>true
                );
                $result = $officials->GetOfficialsByCoordinates($param);
                $officials = $result->GetOfficialsByCoordinatesResult;
                print $js->encode($officials);
            } catch (Exception $e) {
                error_log('Problem in GetElectedOfficials: '.$e->getMessage()." using params:\n".print_r($param, TRUE));
                print $js->encode(array('success'=>FALSE, 'message'=>$e->getMessage()));
            }END OLD CICERO_LIVE SOAP CODE */
            } else {
				throw new Exception('Could not authenticate Cicero REST API user.');
            }//exit;
        }
////////////////////// Not sure this needs to be new districts anymore
        public function action_get_new_legislative_districts() {
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
				
				if($legislativeDistrictResponse->response->results->count->total) == 0):
					//error_log('No location found for the given address.');
                	print $js->encode(array('success'=>FALSE, 'message'=>'No districts found for the given location.'));
				endif;
				
				$legislativeDistrictResult = $legislativeDistrictResponse->response->results->candidates->officials;
				print $js->encode($legislativeDistrictResult);
			} else {
				throw new Exception('Could not authenticate Cicero REST API user.');
			}
            /*OLD SOAP CODE try {
                $client = new SoapClient($cicero->url_base . "azavea.cicero.webservice.v2/GeocodingService.asmx?wsdl");
                $param = array(
                    'authToken'=>$token,
                    'latitude'=>$_REQUEST['latitude'],
                    'longitude'=>$_REQUEST['longitude'],
                    'districtType'=>'ALL_2010'
                );
                $result = $client->GetDistrictsByCoordinates($param);
                $districts = $result->GetDistrictsByCoordinatesResult->DistrictInfo;
                if (!is_array($districts)) {
                    $districts = array($districts);
                }                
                print $js->encode($districts);
            } catch (Exception $e) {
                error_log('Problem with getting new districts: '.$e->getMessage()." using params:\n".print_r($param, TRUE));
                print $js->encode(array('success'=>FALSE, 'message'=>$e->getMessage()));
            }
            exit;*/
        }

        public function action_get_nonlegislative_districts() {
            $cicero = Loader::helper('cicero', 'azavea_cicero');
            $js = Loader::helper('json');
            //$token = $cicero->authenticate(); SOAP
            $authResponse = $cicero->authenticateViaREST();
            $district_types = array('SCHOOL', 'WATERSHED', 'COUNTY', 'POLICE', 'CENSUS');
			if ($authResponse->success === True) {
				$token = $authResponse->token;
	            try {
	                $latitude = $_REQUEST['latitude'];
	                $longitude = $_REQUEST['longitude'];
	                $districts = array();
	                foreach ($district_types as $type) {
	                    $new_results = $this->get_nonlegislative_districts($token, $latitude, $longitude, $type);
	                    $districts = array_merge($districts, $new_results);
	                }
	                print $js->encode($districts);
	            } catch (Exception $e) {
	                error_log('Problem in getting nonlegislative_district: '.$e->getMessage()." using params:\n".print_r($param, TRUE));
	                print $js->encode(array('success'=>FALSE, 'message'=>$e->getMessage()));
	            }
			}
            exit;
        }

        protected function get_nonlegislative_districts($token, $latitude, $longitude, $type) {
            $cicero = Loader::helper('cicero', 'azavea_cicero');
            //SOAP $districts = new SoapClient ($cicero->url_base . "azavea.cicero.webservice.v2/NonLegislativeDistrictService.asmx?wsdl");
            $param = array(
                'authToken'=>$token,
                'lat'=>$latitude,
                'lon'=>$longitude,
                'district_type'=>$type
            );
			$queryString = http_build_query($params);
			$url = $cicero->url_base_rest . 'nonlegislative_district?' . $queryString;
			$result = $cicero->get_response($url);
			$districts = $result->results->candidates->districts;
            // TODO: Make error handling make sense here.
            //SOAP $result = $districts->GetDistrictsByCoordinates($param);
            //SOAP $districts = $result->GetDistrictsByCoordinatesResult->NonLegDistrictInfo;
            if (is_array($districts)) {
                return $districts;
            } else {
                return array($districts);
            }
        }

        public function action_get_maps() {
            $cicero = Loader::helper('cicero', 'azavea_cicero');
            $js = Loader::helper('json');

            //SOAP $token = $cicero->authenticate();
            //SOAP $client = new SoapClient($cicero->url_base . "/azavea.cicero.webservice.v2/MapGenerationService.asmx?wsdl");
            $authResponse = $cicero->authenticateViaREST();
			if ($authResponse->success === True) {
			$mapExtentUS = array(
                "x_min"=>-171.5625,
				"x_max"=>-66.884766,
				"y_min"=>24.4415,
				"y_max"=>71.746432,
			/*	"MinXMeters"=>0, // these dont really do anything but the SOAP
				"MaxXMeters"=>0, // API will get mad if they're not here
				"MinYMeters"=>0,
				"MaxYMeters"=>0 */
            );
			
			$mapExtentAK = array(
                "x_min"=>-179.9999,
				"x_max"=>-129.9,
				"y_min"=>50,
				"y_max"=>71.8,
			/*	"MinXMeters"=>0, // these dont really do anything but the SOAP
				"MaxXMeters"=>0, // API will get mad if they're not here
				"MinYMeters"=>0,
				"MaxYMeters"=>0 */
            );
				
            $imageSpec = array(
                "boundary_color"=>$_REQUEST['boundaryColor']?$_REQUEST['boundaryColor']:"#000000",
                "boundary_opacity"=>$_REQUEST['boundaryOpacity']?$_REQUEST['boundaryOpacity']:"0.8",
                "boundary_width"=>$_REQUEST['boundaryWidth']?$_REQUEST['boundaryWidth']:"3",
                "fill_color"=>$_REQUEST['fillColor']?$_REQUEST['fillColor']:"#53A8C8",
                "fill_opacity"=>$_REQUEST['fillOpacity']?$_REQUEST['fillOpacity']:"0.25",
				"height"=>$_REQUEST['imgHeight']?$_REQUEST['imgHeight']:200, //change defaults here
                "width"=>$_REQUEST['imgWidth']?$_REQUEST['imgWidth']:200, //change defaults here
                "format"=>"img",
                "srs"=>"3785"
            );
			
			$param = array(
                'token'=>$authResponse->token,
                'district_id'=>$_REQUEST['districtID'],
                'city'=>$_REQUEST['city'],
                'state'=>$_REQUEST['state'],
                'country'=>$_REQUEST['country'],
                'district_type'=>$_REQUEST['districtType'],
            );
            $param = array_merge($param, $imageSpec)
            //$param['image_spec'] = $imageSpec

			if( $_REQUEST['districtID'] == 'United States' ) {
				$param = array_merge($param, $mapExtentUS);
				//$method = 'GetMapByExtent';
			} elseif (
				(	
					$_REQUEST['districtID'] == 'AK' &&
					( $_REQUEST['districtType'] == 'NATIONAL_LOWER' 
						|| $_REQUEST['districtType'] == 'NATIONAL_UPPER' )
				) || ( 
					$_REQUEST['districtID'] == '19' && $_REQUEST['districtType'] == 'watershed'	//Alaska HUC2
				)
			)
			{
				$param = array_merge($param, $mapExtentAK);
				//$param['map_extent'] = $mapExtentAK;
				//$method = 'GetMapByExtent';
			} else {
				//$method = 'GetMapByDistrictID';
			}
			
			$queryString = http_build_query($params);
			$url = $cicero->url_base_rest . 'map?' . $queryString;
			$result = $cicero->get_response($url);
			print $js->encode($result);
			
          /*  try {
                $result = $client->$method($param);
                $map_result = $result->{$method.'Result'};
                print $js->encode($map_result);
            } catch (SoapFault $e) {
                print "message: " + $e->getMessage();
                error_log('Problem getting map: '.$e->getMessage()." using params:\n".print_r($param, TRUE));
            }
            exit;
        } */
	} else {
		throw new Exception("Could not authenticate Cicero REST API user.");
	}
	}
?>
