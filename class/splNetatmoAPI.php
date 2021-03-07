<?php
/*

https://github.com/KiboOst/php-simpleNetatmoAPI

*/

class splNetatmoAPI {

    public $_APIversion = '1.6';

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
                    if (isset($cam['vpn'])) $camVPN = $cam['vpn'];
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

                            if (isset($thisSubEvent['snapshot']['url']))
                            {
                                $snapshotURL = $thisSubEvent['snapshot']['url'];
                            }
                            else if (isset($thisSubEvent['snapshot']['filename']))  //other vignette of same event!
                            {
                                $snapshotURL = $camVPN.'/'.$thisSubEvent['snapshot']['filename'];
                            }
                            else
                            {
                                $snapshotID = $thisSubEvent['snapshot']['id'];
                                $snapshotKEY = $thisSubEvent['snapshot']['key'];
                                $snapshotURL = 'https://api.netatmo.com/api/getcamerapicture?image_id='.$snapshotID.'&key='.$snapshotKEY;
                            }

                            if (isset($thisSubEvent['vignette']['url']))
                            {
                                $vignetteURL = $thisSubEvent['vignette']['url'];
                            }
                            else if (isset($thisSubEvent['vignette']['filename']))  //other vignette of same event!
                            {
                                $vignetteURL = $camVPN.'/'.$thisSubEvent['vignette']['filename'];
                            }
                            else
                            {
                                $vignetteID = $thisSubEvent['vignette']['id'];
                                $vignetteKEY = $thisSubEvent['vignette']['key'];
                                $vignetteURL = 'https://api.netatmo.com/api/getcamerapicture?image_id='.$vignetteID.'&key='.$vignetteKEY;
                            }

                            //echo '<img src='.$snapshotURL.' height="219" width="350" </img>'.'<br>';
                            //echo '<img src='.$vignetteURL.' height="166" width="166" </img>'.'<br>';

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
                if (isset($snapshot['url']))
                {
                    $snapshotURL = $snapshot['url'];
                }
                else
                {
                    $snapshotID = $snapshot['id'];
                    $snapshotKEY = $snapshot['key'];
                    $snapshotURL = 'https://api.netatmo.com/api/getcamerapicture?image_id='.$snapshotID.'&key='.$snapshotKEY;
                }
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
        if (count($atHome['result'])==0) return true;
        return false;
    }

    public function setPersonAway($person) //Welcome
    {
        if ( is_string($person) ) $person = $this->getPersonByName($person);
        if ( isset($person['error']) ) return $person;
        $personID = $person['id'];
        $homeID = $this->_camerasDatas['body']['homes'][$this->_homeID]['id'];

        $url = $this->_apiurl.'/api/setpersonsaway?access_token=' . $this->_accesstoken .'&home_id='.$homeID.'&person_id='.$personID .'&size=2';
        $response = file_get_contents($url, false);

        $jsonDatas = json_decode($response, true);
        return $jsonDatas;
    }

    public function setPersonsAtHome($persons) //Welcome
    {
        if ( is_string($persons) )
            {
                $arID = $this->getPersonByName($persons);
                if ( isset($arString['error']) ) return $arString;
                $arString = $arID['id'];
                $arString = '["'.$arString.'"]';
            }

        if ( is_array($persons) )
            {
                $arIDs = array();
                foreach ($persons as $name)
                {
                    $personID = $this->getPersonByName($name);
                    if ( !isset($personID['error']) ) array_push($arIDs, $personID['id']);
                }
                $arString = implode('","', $arIDs);
                $arString = '["'.$arString.'"]';
            }

        $homeID = $this->_camerasDatas['body']['homes'][$this->_homeID]['id'];

        $url = $this->_apiurl.'/api/setpersonshome?access_token=' . $this->_accesstoken .'&home_id='.$homeID.'&person_ids='.$arString;
        $response = file_get_contents($url, false);

        $jsonDatas = json_decode($response, true);
        return $jsonDatas;
    }

    public function setHomeEmpty() //Welcome
    {
        $homeID = $this->_camerasDatas['body']['homes'][$this->_homeID]['id'];

        $url = $this->_apiurl.'/api/setpersonsaway?access_token=' . $this->_accesstoken .'&home_id='.$homeID.'&size=2';
        $response = file_get_contents($url, false);

        $jsonDatas = json_decode($response, true);
        return $jsonDatas;
    }

    public function setLightMode($camName, $mode='auto') //Presence
    {

        if ( is_string($camName) ) $camera = $this->getCamByName($camName);
        if ( isset($camera['error']) ) return $camera;

        $value = null;
        if (in_array($mode, array('on', '1', 1, true), true)) $value = 'on';
        if (in_array($mode, array('off', '0', 0, false), true)) $value = 'off';
        if (in_array($mode, array('auto', 2), true)) $value = 'auto';
        if (!isset($value)) return array('error'=>'Unsupported value');

        $command = '/command/floodlight_set_config?config=';
        $config = '{"mode":"'.$value.'"}';

        $url = $camera['vpn'].$command.urlencode($config);
        $response = file_get_contents($url, false);

        $jsonDatas = json_decode($response, true);
        return $jsonDatas;
    }

    public function setMonitoring($camName, $mode='on') //Presence - Welcome
    {
        if ( is_string($camName) ) $camera = $this->getCamByName($camName);
        if ( isset($camera['error']) ) return $camera;

        $value = null;
        if (in_array($mode, array('on', '1', 1, true), true)) $value = 'on';
        if (in_array($mode, array('off', '0', 0, false), true)) $value = 'off';
        if (!isset($value)) return array('error'=>'Unsupported value');

        $command = '/command/changestatus?status='.$value;
        $url = $camera['vpn'].$command;

        $response = file_get_contents($url, false);

        $jsonDatas = json_decode($response, true);
        return $jsonDatas;
    }

    public function setLightIntensity($camName, $intensity=100) //Presence
    {
        if ( is_string($camName) ) $camera = $this->getCamByName($camName);
        if ( isset($camera['error']) ) return $camera;

        if ($camera['type'] != 'Presence') return array('result'=>null, 'error' => 'Unsupported camera for setLightIntensity()');

        $intensity = intval($intensity);
        if ($intensity > 100 or $intensity < 0) return array('result'=>null, 'error' => 'Presence Light use intensity range from 0 to 100');

        $config = '{"intensity":"'.$intensity.'"}';
        $command = '/command/floodlight_set_config?config=';
        $url = $camera['vpn'].$command.urlencode($config);

        $response = file_get_contents($url, false);

        $jsonDatas = json_decode($response, true);
        return $jsonDatas;
    }

    //COMMON:
    public function getHomes()
    {
        $api_url = $this->_apiurl.'/api/gethomedata?access_token=' . $this->_accesstoken .'&size=0';
        $response = file_get_contents($api_url, false);

        $jsonDatas = json_decode($response, true);
        $homes = $jsonDatas['body']['homes'];

        $n = count($homes);
        $datas = [];
        for ($i = 0; $i < $n; $i++) {
            $data = [
                        'ID' => $i,
                        'name' => $homes[$i]['name'],
                        'place' => $homes[$i]['place'],
                        'cameras' => count($homes[$i]['cameras'])
                    ];

            array_push($datas, $data);
        }
        return $datas;
    }


    //for sake of retro-compatibility:
    public function getPresenceCameras()
    {
        $camArray = array();
        foreach ($this->_cameras as $camera) {
            if ($camera['type'] == 'Presence') $camArray[$camera['name']] = $camera;
        }
        return $camArray;
    }

    public function getWelcomeCameras()
    {
        $camArray = array();
        foreach ($this->_cameras as $camera) {
            if ($camera['type'] == 'Welcome') $camArray[$camera['name']] = $camera;
        }
        return $camArray;
    }

    public function getDoorbells()
    {
        $camArray = array();
        foreach ($this->_cameras as $camera) {
            if ($camera['type'] == 'Doorbell') $camArray[$camera['name']] = $camera;
        }
        return $camArray;
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
    protected function getCamByName($name) //Presence - Welcome
    {
        foreach ($this->_cameras as $thisCamera)
        {
            if ($thisCamera['name'] == $name) return $thisCamera;
        }
        return array('result'=>null, 'error' => 'Unfound camera');
    }

    protected function getCamerasDatas($eventNum=50) //request full Presence/Welcome datas
    {
        $api_url = $this->_apiurl.'/api/gethomedata?access_token=' . $this->_accesstoken .'&size='.$eventNum;
        $response = file_get_contents($api_url, false);

        $jsonDatas = json_decode($response, true);
        $this->_camerasDatas = $jsonDatas;
        if (isset($jsonDatas['body']['homes'][$this->_homeID]))
        {
            $this->_home = $jsonDatas['body']['homes'][$this->_homeID]['name'];
            if( isset($jsonDatas['body']['homes'][$this->_homeID]['place']['timezone']) ) $this->_timezone = $jsonDatas['body']['homes'][$this->_homeID]['place']['timezone'];
            return $jsonDatas;
        }
        else
        {
            $this->error = 'Unfound home with ID '.$this->_homeID;
            return false;
        }

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

        if (!isset($this->_camerasDatas['body']['homes'][$this->_homeID])) return false;

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
                                'vpn' => $cameraVPN,
                                'snapshot' => $cameraSnapshot,
                                'status' => $thisCamera['status'],
                                'sd_status' => $thisCamera['sd_status'],
                                'alim_status' => $thisCamera['alim_status'],
                                'is_local' => $isLocal,
                                'type' => 'Welcome'
                                );

                array_push($allCameras, $camera);
            }
            elseif ($thisCamera['type'] == 'NDB') //Doorbell:
            {
                $camera = array('name' => $thisCamera['name'],
                                'id' => $thisCamera['id'],
                                'vpn' => $cameraVPN,
                                'snapshot' => $cameraSnapshot,
                                'status' => $thisCamera['status'],
                                'sd_status' => $thisCamera['sd_status'],
                                'alim_status' => $thisCamera['alim_status'],
                                'is_local' => $isLocal,
                                'type' => 'Doorbell'
                                );

                array_push($allCameras, $camera);
            }
        }
        $this->_cameras = $allCameras;
    }

    public function getPersons() //Welcome
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
    public $_homeID;

    //devices:
    public $_cameras = []; //both Presences and Welcome
    public $_persons = [];

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
                                        'scope' => 'read_home write_home read_camera write_camera access_camera read_presence write_presence access_presence read_doorbell write_doorbell access_doorbell read_station write_station read_thermostat write_thermostat read_smokedetector write_smokedetector read_homecoach write_homecoach read_june write_june access_velux read_velux write_velux read_muller write_muller read_smarther write_smarther read_magellan write_magellan'
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

    function __construct($Netatmo_user, $Netatmo_pass, $Netatmo_app_id, $Netatmo_app_secret, $homeID=0)
    {
        $this->_Netatmo_user = $Netatmo_user;
        $this->_Netatmo_pass = $Netatmo_pass;
        $this->_Netatmo_app_id = $Netatmo_app_id;
        $this->_Netatmo_app_secret = $Netatmo_app_secret;

        $this->_homeID = $homeID;
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
