<?php
/*

https://github.com/KiboOst/php-simpleNetatmoAPI

*/

class splNetatmoAPI {

    public $_APIversion = "1.2dev";

    //user functions======================================================


    //WEATHER STATION:
    public function getWeatherStationDatas()
    {
        if (is_null($this->_weatherDatas)) $this->getWeatherDatas();
        return $this->_weatherDatas['body']['devices'][0]['dashboard_data'];
    }

    public function getWeatherModuleDatas($Mname='')
    {
        if (is_null($this->_weatherDatas)) $this->getWeatherDatas();
        $modules = $this->_weatherDatas['body']['devices'][0]['modules'];
        foreach ($modules as $module)
        {
            $name = $module['module_name'];
            if ($Mname == $name) return $module['dashboard_data'];
        }
        return false;
    }

    public function getWeatherTemperatures()
    {
        if (is_null($this->_weatherDatas)) $this->getWeatherDatas();

        $modules = $this->_weatherDatas['body']['devices'][0]['modules'];

        $jsonDatas = array();
        foreach ($modules as $module)
        {
            if (!isset($module['dashboard_data']['Temperature'])) continue;

            $name = $module['module_name'];
            $temp = $module['dashboard_data']['Temperature'];
            $jsonDatas[$name] = $temp;
        }
        //add main station:
        $jsonDatas[ $this->_weatherDatas['body']['devices'][0]['station_name'] ] = $this->_weatherDatas['body']['devices'][0]['dashboard_data']['Temperature'];
        return $jsonDatas;
    }

    public function getWeatherBatteries($lowLevel=100)
    {
        if (is_null($this->_weatherDatas)) $this->getWeatherDatas();
        $modules = $this->_weatherDatas['body']['devices'][0]['modules'];

        $jsonDatas = array();
        foreach ($modules as $module)
        {
            if (!isset($module['battery_percent'])) continue;

            $name = $module['module_name'];
            $batlevel = $module['battery_percent'];
            if ($batlevel <= $lowLevel) $jsonDatas[$name] = $batlevel;
        }
        return $jsonDatas;
    }

    public function getWeatherFirmVer()
    {
        if (is_null($this->_weatherDatas)) $this->getWeatherDatas();
        $modules = $this->_weatherDatas['body']['devices'][0]['modules'];

        $jsonDatas = array();
        foreach ($modules as $module)
        {
            if (!isset($module['firmware'])) continue;

            $name = $module['module_name'];
            $firmVer = $module['firmware'];
            $jsonDatas[$name] = $firmVer;
        }
        //add main station:
        $jsonDatas[ $this->_weatherDatas['body']['devices'][0]['station_name'] ] = $this->_weatherDatas['body']['devices'][0]['firmware'];
        return $jsonDatas;
    }

    public function getWeatherRFs()
    {
        if (is_null($this->_weatherDatas)) $this->getWeatherDatas();
        $modules = $this->_weatherDatas['body']['devices'][0]['modules'];

        $jsonDatas = array();
        foreach ($modules as $module)
        {
            if (!isset($module['rf_status'])) continue;

            $name = $module['module_name'];
            $rf = $module['rf_status'];
            $jsonDatas[$name] = $rf;
        }
        //add main station:
        $jsonDatas[ $this->_weatherDatas['body']['devices'][0]['station_name'] ] = $this->_weatherDatas['body']['devices'][0]['firmware'];
        return $jsonDatas;
    }


    //PRESENCE / WELCOME:
    public function getOutdoorEvents($requestType='All', $num=1) //human, animal, vehicle, All
    {
        //will return the last event of defined type as array of [title, snapshotURL, vignetteURL]
        if (is_null($this->_camerasDatas)) $this->getCamerasDatas($num);
        if (is_null($this->_cameras)) $this->getCameras();

        $cameraEvents = $this->_camerasDatas['body']['homes'][$this->_homeID]['events'];
        $numEvents = count($cameraEvents);
        $counts = $num;
        if ($numEvents < $counts) $counts == $numEvents;

        $returnEvents = array();
        for ($i=0; $i < $counts ;$i++)
        {
            $thisEvent = $cameraEvents[$i];

            $id = $thisEvent['id'];
            $time = $thisEvent['time'];
            $camId = $thisEvent['camera_id'];

            //get camera name:
            $c = count($this->_cameras);
            for ($ic=0; $ic < $c; $ic++)
            {
                $cam = array_values($this->_cameras)[$ic];
                if ($cam['id'] == $camId)
                {
                    $camName = $cam['name'];
                    $camVPN = $cam['vpn'];
                    $camType = $cam['type'];
                    break;
                }
            }
            //get only outdoors events:
            if ($camType != 'Presence') continue;

            $type = $thisEvent['type'];

            if  ($type != 'outdoor' and $requestType=='All')
            {
                $time = $thisEvent['time'];
                $time = date("d-m-Y H:i:s", $time);
                $msg = $thisEvent['message'];
                $returnThis = array();
                $returnThis['title'] = $msg;
                $returnThis['camera'] = $camName;
                $returnThis['time'] = $time;
                $returnThis['type'] = $thisEvent['type'];
                array_push($returnEvents, $returnThis);
            }

            if ($type == 'outdoor')
            {
                $eventList = $thisEvent['event_list'];
                $isAvailable = $thisEvent['video_status'];
                for ($j=0; $j < count($eventList) ;$j++)
                {
                    $thisSubEvent = $thisEvent['event_list'][$j];
                    $subType = $thisSubEvent['type'];
                    $subMsg = $thisSubEvent['message'];
                    if (strpos($subType, $requestType) !== false OR $requestType=='All')
                        {
                            $subTime = $thisSubEvent['time'];
                            $subTime = date("d-m-Y H:i:s", $subTime);

                            if (isset($thisSubEvent['snapshot']['filename']))  //other vignette of same event!
                            {
                                $snapshotURL = $camVPN.'/'.$thisSubEvent['snapshot']['filename'];
                                $vignetteURL = $camVPN.'/'.$thisSubEvent['vignette']['filename'];
                            }else{
                                $snapshotID = $thisSubEvent['snapshot']['id'];
                                $snapshotKEY = $thisSubEvent['snapshot']['key'];
                                $snapshotURL = 'https://api.netatmo.com/api/getcamerapicture?image_id='.$snapshotID.'&key='.$snapshotKEY;

                                $vignetteID = $thisSubEvent['vignette']['id'];
                                $vignetteKEY = $thisSubEvent['vignette']['key'];
                                $vignetteURL = 'https://api.netatmo.com/api/getcamerapicture?image_id='.$vignetteID.'&key='.$vignetteKEY;
                            }
                            //echo '<img src=''.$snapshotURL.'' height='219' width='350' </img>'.'<br>';
                            //echo '<img src=''.$vignetteURL.'' height='166' width='166' </img>'.'<br>';

                            $returnThis = array();
                            $returnThis['title'] = $subMsg;
                            $returnThis['camera'] = $camName;
                            $returnThis['time'] = $subTime;
                            $returnThis['snapshotURL'] = $snapshotURL;
                            $returnThis['vignetteURL'] = $vignetteURL;
                            array_push($returnEvents, $returnThis);
                        }
                }
            }
        }

        return $returnEvents;
    }

    public function getIndoorEvents($num=5)
    {
        if (is_null($this->_camerasDatas)) $this->getCamerasDatas(10);
        if (is_null($this->_cameras)) $this->getCameras();

        $cameraEvents = $this->_camerasDatas['body']['homes'][$this->_homeID]['events'];
        $returnEvents = array();
        for ($i=0; $i <= $num ;$i++)
        {
            //avoid iterating more than there is!
            if (isset($cameraEvents[$i])) $thisEvent = $cameraEvents[$i];
            else break;

            $camId = $thisEvent['camera_id'];
            foreach ($this->_cameras as $cam)
                {
                    if ($cam['id'] == $camId)
                    {
                        $camName = $cam['name'];
                        $camType = $cam['type'];
                        break;
                    }
                }
            //get only indoor events:
            if ($camType != 'Welcome')
            {
                continue;
            }

            $id = $thisEvent['id'];
            $type = $thisEvent['type'];
            $time = $thisEvent['time'];
            $date = date('d-m-Y H:i:s', $time);
            $message = $thisEvent['message'];

            $returnThis = array();
            $returnThis['title'] = $message . ' | '.$date.' | '.$camName;
            $returnThis['type'] = $type;
            $returnThis['time'] = $thisEvent['time'];
            $returnThis['date'] = $date;


            if (isset($thisEvent['person_id'])) $returnThis['person_id'] = $thisEvent['person_id'];

            if (isset($thisEvent['snapshot']))
            {
                $snapshot = $thisEvent['snapshot'];
                $snapshotID = $snapshot['id'];
                $snapshotKEY = $snapshot['key'];
                $snapshotURL = 'https://api.netatmo.com/api/getcamerapicture?image_id='.$snapshotID.'&key='.$snapshotKEY;
                $returnThis['snapshotURL'] = $snapshotURL;
            }

            if (isset($thisEvent['is_arrival'])) $returnThis['is_arrival'] = $thisEvent['is_arrival'];
            $returnThis['camera_id'] = $camId;
            $returnThis['event_id'] = $id;

            array_push($returnEvents, $returnThis);
        }
        return $returnEvents;
    }

    public function getPerson($name) //Welcome
    {
        if ( is_string($name) ) $person = $this->getPersonByName($name);
        return $person;
    }

    public function getPersonsAtHome() //Welcome
    {
        $atHome = array();
        foreach ($this->_persons as $thisPerson)
        {
            if ($thisPerson['out_of_sight'] == false) array_push($atHome, $thisPerson);
        }
        return array('result'=>$atHome);
    }

    public function isHomeEmpty() //Welcome
    {
        $atHome = $this->getPersonsAtHome();
        if (count($atHome)==0) return true;
        return false;
    }

    public function setPersonAway($person) //Welcome
    {
        if ( is_string($person) ) $person = $this->getPersonByName($person);
        if ( isset($person['error']) ) return $person;
        $personID = $person['id'];
        $homeID = $this->_camerasDatas['body']['homes'][$this->_homeID]['id'];

        $api_url = $this->_apiurl.'/api/setpersonsaway?access_token=' . $this->_accesstoken .'&home_id='.$homeID.'&person_id='.$personID .'&size=2';
        $response = file_get_contents($api_url, false);

        $jsonDatas = json_decode($response, true);
        return $jsonDatas;
    }

    public function setHomeEmpty() //Welcome
    {
        $homeID = $this->_camerasDatas['body']['homes'][$this->_homeID]['id'];

        $api_url = $this->_apiurl.'/api/setpersonsaway?access_token=' . $this->_accesstoken .'&home_id='.$homeID.'&size=2';
        $response = file_get_contents($api_url, false);

        $jsonDatas = json_decode($response, true);
        return $jsonDatas;
    }

    //for sake of retro-compatibility:
    public function getPresenceCameras()
    {
        $camArray = array();
        foreach ($this->_cameras as $camera) {
            if ($camera['type'] == 'Presence') $camArray[$camera['name']] = $camera;;
        }
        return $camArray;
    }

    public function getWelcomeCameras()
    {
        $camArray = array();
        foreach ($this->_cameras as $camera) {
            if ($camera['type'] == 'Welcome') $camArray[$camera['name']] = $camera;;
        }
        return $camArray;
    }

    //THERMOSTAT:
    public function getThermoDevices() //--------Untested!!!
    {
        if (is_null($this->_thermoDatas)) $this->getThermoDatas();
        if( !isset($this->_thermoDatas['body']['devices']) ) return array('No thermostats found.');

        $thermosList = $this->_thermoDatas['body']['devices'];
        $thermosArray = array();

        //get thermostats
        foreach ($thermosList as $thermo)
        {
            $thisThermo = array();

            $thisThermo['name'] = $thermo['station_name'];
            $thisThermo['id'] = $thermo['_id'];
            $thisThermo['firmware'] = $thermo['firmware'];
            $thisThermo['place'] = $thermo['place'];
            $thisThermo['wifi_status'] = $thermo['wifi_status'];
            $thisThermo['last_plug_seen'] = gmdate("d-m-Y H:i:s", $thermo['last_plug_seen']);
            $thisThermo['last_status_store'] = gmdate("d-m-Y H:i:s", $thermo['last_status_store']);

            //get valves for this thermostat:
            $thisThermo['valves'] = array();
            $valves = $thermo['modules'];

            foreach ($valves as $valve)
            {
                $thisValve = array();

                $thisValve['name'] = $valve['module_name'];
                $thisValve['id'] = $valve['_id'];
                $thisValve['last_message'] = gmdate("d-m-Y H:i:s", $valve['last_message']);
                $thisValve['orientation'] = $valve['therm_orientation'];
                $thisValve['cmd'] = $valve['therm_relay_cmd'];
                $thisValve['measured'] = $valve['measured'];
                $thisValve['program_list'] = $valve['therm_program_list'];
                $thisValve['battery'] = $valve['battery_vp'];
                $thisValve['rf'] = $valve['rf_status'];
                $thisValve['firmware'] = $valve['firmware'];
                $thisValve['type'] = $valve['type'];

                array_push($thisThermo['valves'], $thisValve);
            }

            $thermosArray[$thisThermo['name']] = $thisThermo;
        }

        $this->_thermos = $thermosArray;
        return $thermosArray;
    }

    public function createThermoSched($thermName, $valveName, $zonesArray, $timesArray, $schedName) //--------Untested!!!
    {
        if (is_null($this->_thermos)) $this->getThermoDevices();
        if( !isset($this->_thermoDatas['body']['devices']) ) return array('No thermostats found.');

        $therm = $this->getThermByName($thermName);
        if( isset($therm['id']) ) $thermID = $therm['id'];
        else return array('Unfound thermostat.');

        $valve = $this->getThermValveByName($thermName, $valveName);
        if( isset($valve['id']) ) $valveID = $valve['id'];
        else return array('Unfound valve.');

        $api_url = $this->_apiurl."/api/createnewschedule?access_token=" . $this->_accesstoken;
        $url = $api_url.'&device_id='.$thermID.'&module_id='.$valveID.'&zones='.$zonesArray.'&timetable='.$timesArray.'&name='.$schedName;
        $response = file_get_contents($api_url, false);

        $jsonDatas = json_decode($response, true);
        return $jsonDatas;
    }

    public function switchThermoSched($thermName, $valveName, $schedName) //--------Untested!!!
    {
        if (is_null($this->_thermos)) $this->getThermoDevices();
        if( !isset($this->_thermoDatas['body']['devices']) ) return array('No thermostats found.');

        $therm = $this->getThermByName($thermName);
        if( isset($therm['id']) ) $thermID = $therm['id'];
        else return array('Unfound thermostat.');

        $valve = $this->getThermValveByName($thermName, $valveName);
        if( isset($valve['id']) ) $valveID = $valve['id'];
        else return array('Unfound valve.');

        $api_url = $this->_apiurl."/api/Switchschedule?access_token=" . $this->_accesstoken;
        $url = $api_url.'&device_id='.$thermID.'&module_id='.$valveID.'&schedule_id='.$scheID;

        $response = file_get_contents($api_url, false);

        $jsonDatas = json_decode($response, true);
        return $jsonDatas;
    }

    public function syncThermoSched($thermName, $valveName, $zonesArray, $timesArray) //--------Untested!!!
    {
        if (is_null($this->_thermos)) $this->getThermoDevices();
        if( !isset($this->_thermoDatas['body']['devices']) ) return array('No thermostats found.');

        $therm = $this->getThermByName($thermName);
        if( isset($therm['id']) ) $thermID = $therm['id'];
        else return array('Unfound thermostat.');

        $valve = $this->getThermValveByName($thermName, $valveName);
        if( isset($valve['id']) ) $valveID = $valve['id'];
        else return array('Unfound valve.');

        $api_url = $this->_apiurl."/api/syncschedule?access_token=" . $this->_accesstoken;
        $url = $api_url.'&device_id='.$thermID.'&module_id='.$valveID.'&zones='.$zonesArray.'&timetable='.$timesArray;

        $response = file_get_contents($api_url, false);

        $jsonDatas = json_decode($response, true);
        return $jsonDatas;
    }

    public function setThermoPoint($thermName, $valveName, $mode) //--------Untested!!!
    {
        if (is_null($this->_thermos)) $this->getThermoDevices();
        if( !isset($this->_thermoDatas['body']['devices']) ) return array('No thermostats found.');

        $therm = $this->getThermByName($thermName);
        if( isset($therm['id']) ) $thermID = $therm['id'];
        else return array('Unfound thermostat.');

        $valve = $this->getThermValveByName($thermName, $valveName);
        if( isset($valve['id']) ) $valveID = $valve['id'];
        else return array('Unfound valve.');

        $api_url = $this->_apiurl."/api/setthermpoint?access_token=" . $this->_accesstoken;
        $url = $api_url.'&device_id='.$thermID.'&module_id='.$valveID.'&setpoint_mode='.$mode;

        $response = file_get_contents($api_url, false);

        $jsonDatas = json_decode($response, true);
        return $jsonDatas;
    }

    public function getThermByName($thermName) //--------Untested!!!
    {
        $c = count($this->_thermos);
        for ($i=0; $i < $c; $i++)
        {
            $therm = array_values($this->_thermos)[$i];
            if ($therm['name'] == $thermName) return $therm;
        }

        return array('Unfound thermostat.');
    }

    public function getThermValveByName($thermName, $valveName) //--------Untested!!!
    {
        $c = count($this->_thermos);
        for ($i=0; $i < $c; $i++)
        {
            $therm = array_values($this->_thermos)[$i];
            if ($therm['name'] == $thermName)
            {
                foreach ($therm['valves'] as $valve)
                {
                    if($valve['name'] == $valveName) return $valve;
                }
            }
        }

        return array('Unfound valve.');
    }

    public function getThermoDatas() //request full thermostats datas //--------Untested!!!
    {
        $api_url = $this->_apiurl."/api/getthermostatsdata?access_token=" . $this->_accesstoken;
        $response = file_get_contents($api_url, false);

        $jsonDatas = json_decode($response, true);
        $this->_thermoDatas = $jsonDatas;
        return $jsonDatas;
    }


    //HOMECOACH: --------Untested!!!
    public function getCoachs()
    {
        if (is_null($this->_homecoachDatas)) $this->getCoachDatas();
        $coachList = $this->_homecoachDatas['body']['devices'];
        $coachsArray = array();

        foreach ($coachList as $coach)
        {
            $thisCoach = array();

            $thisCoach['name'] = $coach['name'];
            $thisCoach['id'] = $coach['id'];
            $thisCoach['firmware'] = $coach['firmware'];
            $thisCoach['wifi_status'] = $coach['wifi_status'];
            $thisCoach['datas'] = $coach['dashboard_data'];

            $coachsArray[$thisCoach['name']] = $thisCoach;
        }
        $this->_homecoachs = $coachsArray;
        return $coachsArray;
    }

    public function getCoachDatas() //request full homecoach datas
    {
        $api_url = $this->_apiurl."/api/gethomecoachsdata?access_token=" . $this->_accesstoken;
        $response = file_get_contents($api_url, false);

        $jsonDatas = json_decode($response, true);
        $this->_homecoachDatas = $jsonDatas;
        if( isset($jsonDatas['body']['homes'][0]['name']) ) $this->_home = $jsonDatas['body']['homes'][0]['name'];
        return $jsonDatas;
    }


    //WEBHOOK:
    public function setWebhook($endpoint)
    {
        $api_url = $this->_apiurl.'/api/addwebhook?access_token=' . $this->_accesstoken . '&url='.$endpoint.'&app_type=app_security';
        $requete = @file_get_contents($api_url);
        $jsonDatas = json_decode($requete,true);
        return $jsonDatas;
    }

    public function dropWebhook()
    {
        $api_url = $this->_apiurl.'/api/dropwebhook?access_token=' . $this->_accesstoken .'&app_type=app_security';
        $requete = @file_get_contents($api_url);
        $jsonDatas = json_decode($requete,true);
        return $jsonDatas;
    }


    //internal functions==================================================
    protected function getCamerasDatas($eventNum=50) //request full Presence/Welcome datas
    {
        $api_url = $this->_apiurl.'/api/gethomedata?access_token=' . $this->_accesstoken .'&size='.$eventNum;
        $response = file_get_contents($api_url, false);

        $jsonDatas = json_decode($response, true);
        $this->_camerasDatas = $jsonDatas;
        $this->_home = $jsonDatas['body']['homes'][$this->_homeID]['name'];
        if( isset($jsonDatas['body']['homes'][$this->_homeID]['place']['timezone']) ) $this->_timezone = $jsonDatas['body']['homes'][$this->_homeID]['place']['timezone'];
        return $jsonDatas;
    }

    protected function getWeatherDatas() //request full weather datas
    {
        $api_url = 'https://api.netatmo.net/api/getstationsdata?access_token=' . $this->_accesstoken;
        $response = file_get_contents($api_url, false);
        $jsonDatas = json_decode($response, true);
        $this->_weatherDatas = $jsonDatas;
        if( isset($jsonDatas['body']['devices'][$this->_homeID]['place']['timezone']) ) $this->_timezone = $jsonDatas['body']['devices'][$this->_homeID]['place']['timezone'];
        return $jsonDatas;
    }

    protected function getCameras()
    {
        if (is_null($this->_camerasDatas)) $this->getCamerasDatas();
        $allCameras = array();
        foreach ($this->_camerasDatas['body']['homes'][$this->_homeID]['cameras'] as $thisCamera)
        {
            //live and snapshots:
            $cameraVPN = (isset($thisCamera['vpn_url']) ? $thisCamera['vpn_url'] : null);
            $isLocal = (isset($thisCamera['is_local']) ? $thisCamera['is_local'] : false);

            $cameraSnapshot = null;
            $cameraLive = null;

            if ($cameraVPN != null)
            {
                $cameraLive = ($isLocal == false ? $cameraVPN.'/live/index.m3u8' : $cameraVPN.'/live/index_local.m3u8');
                $cameraSnapshot = $cameraVPN.'/live/snapshot_720.jpg';
            }

            //which camera model:
            if ($thisCamera['type'] == 'NOC') //Presence
            {
                $camera = array('name' => $thisCamera['name'],
                                'id' => $thisCamera['id'],
                                'vpn' => $cameraVPN,
                                'snapshot' => $cameraSnapshot,
                                'live' => $cameraLive,
                                'status' => $thisCamera['status'],
                                'sd_status' => $thisCamera['sd_status'],
                                'alim_status' => $thisCamera['alim_status'],
                                'light_mode_status' => $thisCamera['light_mode_status'],
                                'is_local' => $isLocal,
                                'type' => 'Presence'
                                );

                array_push($allCameras, $camera);
            }
            elseif ($thisCamera['type'] == 'NACamera') //Welcome:
            {
                $camera = array('name' => $thisCamera['name'],
                                'id' => $thisCamera['id'],
                                'status' => $thisCamera['status'],
                                'sd_status' => $thisCamera['sd_status'],
                                'alim_status' => $thisCamera['alim_status'],
                                'type' => 'Welcome'
                                );

                array_push($allCameras, $camera);
            }
        }
        $this->_cameras = $allCameras;
    }

    protected function getPersons() //Welcome
    {
        if (is_null($this->_camerasDatas)) $this->getCamerasDatas();
        $homeDatas = $this->_camerasDatas;

        $personsArray = array();
        if ( isset($homeDatas['body']['homes'][$this->_homeID]['persons']) )
        {
            $persons = $homeDatas['body']['homes'][$this->_homeID]['persons'];
            foreach ($persons as $person)
            {
                //echo "<pre>person:<br>".json_encode($person, JSON_PRETTY_PRINT)."</pre><br>";
                $thisPerson = array();
                $pseudo = 'Unknown';
                if ( isset($person['pseudo']) ) $pseudo = $person['pseudo'];
                $thisPerson['pseudo'] = $pseudo;
                $thisPerson['id'] = $person['id'];
                $lastseen = $person['last_seen'];
                if ($lastseen == 0) $thisPerson['last_seen'] = 'Been long';
                else $thisPerson['last_seen'] = date("d-m-Y H:i:s", $person['last_seen']);
                $thisPerson['out_of_sight'] = $person['out_of_sight'];
                if ( isset($person['is_arrival']) ) $thisPerson['is_arrival'] = $person['is_arrival'];
                array_push($personsArray, $thisPerson);
            }

            $this->_persons = $personsArray;
            return $personsArray;
        }
        else return array('None');
    }

    protected function getPersonByName($name) //Welcome
    {
        if (empty($this->_persons)) return array('result'=>null, 'error' => 'No person defined in this home.');

        foreach ($this->_persons as $thisPerson)
        {
            if ($thisPerson['pseudo'] == $name) return $thisPerson;
        }
        return array('result'=>null, 'error' => 'Unfound person');
    }

    //home:
    public $_home;
    public $_timezone;

    //API:
    public $_scope;
    public $error;
    public $_homeID = 0; //will support several homes later

    //API dev:
    public $_thermos;
    public $_thermoValves;
    public $_thermoZones;
    public $_thermoPrograms;
    public $_homecoachs;

    public $_thermoDatas;
    public $_homecoachDatas;

    //devices:
    public $_cameras; //both Presences and Welcome
    public $_persons;

    //datas:
    protected $_camerasDatas;
    protected $_weatherDatas;

    protected $_apiurl = 'https://api.netatmo.net/';
    protected $_Netatmo_user;
    protected $_Netatmo_pass;
    protected $_Netatmo_app_id;
    protected $_Netatmo_app_secret;
    protected $_accesstoken;
    protected $_refreshtoken;

    public function connect()
    {
        $token_url = $this->_apiurl.'/oauth2/token';
        $postdata = http_build_query(
                                    array(
                                        'grant_type' => 'password',
                                        'client_id' => $this->_Netatmo_app_id,
                                        'client_secret' => $this->_Netatmo_app_secret,
                                        'username' => $this->_Netatmo_user,
                                        'password' => $this->_Netatmo_pass,
                                        'scope' => 'read_station read_thermostat write_thermostat read_camera write_camera access_camera read_presence access_presence write_presence read_homecoach'
                )
            );
        $opts = array('http' =>
                            array(
                                'method'  => 'POST',
                                'header'  => 'Content-type: application/x-www-form-urlencoded;charset=UTF-8'."\r\n".
                                            'User-Agent: netatmoclient',
                                'content' => $postdata
                )
            );
        $context  = stream_context_create($opts);

        $response = @file_get_contents($token_url, false, $context);

        //netatmo server sometimes give 500, always works second time:
        if ($response === false) {
            $response = @file_get_contents($token_url, false, $context);
            if ($response === false) {
                $this->error = "Can't connect to Netatmo Server.";
                return false;
            }
        }
        $jsonDatas = json_decode($response, true);
        if (isset($jsonDatas['access_token']))
        {
            $this->_accesstoken = $jsonDatas['access_token'];
            $this->_refreshtoken = $jsonDatas['refresh_token'];
            $this->_scope = $jsonDatas['scope'];
            return true;
        }
        else
        {
            $this->error = "Can't get Netatmo token.";
            return false;
        }

        return true;
    }

    function __construct($Netatmo_user, $Netatmo_pass, $Netatmo_app_id, $Netatmo_app_secret)
    {
        $this->_Netatmo_user = $Netatmo_user;
        $this->_Netatmo_pass = $Netatmo_pass;
        $this->_Netatmo_app_id = $Netatmo_app_id;
        $this->_Netatmo_app_secret = $Netatmo_app_secret;
        $var = $this->connect();
        if ($var == true)
        {
            $this->getWeatherDatas();
            $this->getCameras();
            $this->getPersons();
        }
    }
}//splNetatmoAPI end
?>
