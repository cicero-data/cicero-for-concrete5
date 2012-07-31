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
	class CiceroElectionsBlockController extends BlockController {
		protected $btDescription = "Election Events from Cicero";
		protected $btName = "Cicero Elections";
		protected $btTable = 'btCicero';
		protected $btInterfaceWidth = "350";
		protected $btInterfaceHeight = "300";

        protected $cacheTime = 86400; /* cache the elections, 86400 = 1 day */
        
        /* The maximum number of election events you want in the response */
        protected $maxEvents = 5;
		
		/* The query argument you want to order the results by.
		 * See http://cicero.azavea.com/docs/election_event.html
		 * for arguments allowed.
		 * Also see http://cicero.azavea.com/docs/sorting_paging.html
		 * for other options. */
		protected $orderBy = 'election_expire_date';
		
		/* Return elections finishing on or after a certain date.
		 * Takes dates of format '2008-11-04 00:00:00.000'.
		 * Also see http://cicero.azavea.com/docs/query.html#querying-dates
		 * for other query options. Default option 'today' uses current UTC date */
		protected $electionExpireDateOnOrAfter = 'today';
		
        public function on_start() {
            /* this is in the helper file "azavea_cicero/helpers/cicero.php" */
            $cicero = Loader::helper('cicero', 'azavea_cicero');
            $configs = $cicero->getUserNameAndPassword();
            $this->set('user_name', $configs['user_name']);
            $this->set('password', $configs['password']);
        }

        public function save() {
            $cicero = Loader::helper('cicero', 'azavea_cicero');
            $cicero->setUserNameAndPassword($_REQUEST['user_name'], $_REQUEST['password']);
        }

        public function view() {
            try{
                $events = Cache::get('ciceroElectionEvents', FALSE);
                if ($events == NULL) { 
                    $events = $this->action_refresh_elections();
                }
                $this->set('events', $events);
            } catch (Exception $e) {
                error_log('Problem in GetElectionEvents: '.$e->getMessage());
            }
        }

        public function action_refresh_elections() {
            $cicero = Loader::helper('cicero', 'azavea_cicero');
            $authResponse = $cicero->authenticateViaREST();
            if($authResponse->success === True) {
                $params['token'] = $authResponse->token;
                $params['user'] = $authResponse->user;

                $params['election_expire_date_on_or_after'] = $electionExpireDateOnOrAfter; 
                
                /* other options see
                http://cicero.azavea.com/docs/sorting_paging.html */
                $params['order'] = $orderBy; 
                
                /* see http://cicero.azavea.com/docs/sorting_paging.html */
                $params['max'] = $maxevents; 
                
                $queryString = http_build_query($params);
                $url = $cicero->url_base_rest . 'election_event?' . $queryString;
                $EEResponse = $cicero->get_response($url);
                $events = $EEResponse->response->results->election_events;
                Cache::set('ciceroElectionEvents', FALSE, $events, time() + $this->cacheTime);
                return $events;
            }
            throw new Exception('Could not authenticate Cicero REST API user.');
        }
	}
?>
