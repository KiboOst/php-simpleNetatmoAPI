<?php
/*

https://github.com/KiboOst/php-simpleNetatmoAPI

*/

class splNetatmoAPI {

	public $_APIversion = "1.2";

	//user functions======================================================
	public function getFullHomeDatas() //mainly for debug testing
	{
		$this->getWeatherDatas();

		$this->getCameras();
		$this->getPersons();

		$this->getThermoDevices();

		$this->getCoachs();

		return $this;
	}


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
			if (!isset($module['dashboard_data']["Temperature"])) continue;

			$name = $module['module_name'];
			$temp = $module['dashboard_data']["Temperature"];
			$jsonDatas[$name] = $temp;
		}
		//add main station:
		$jsonDatas[ $this->_weatherDatas['body']['devices'][0]['station_name'] ] = $this->_weatherDatas['body']['devices'][0]['dashboard_data']["Temperature"];
		return $jsonDatas;
	}

	public function getWeatherBatteries($lowLevel=100)
	{
		if (is_null($this->_weatherDatas)) $this->getWeatherDatas();
		$modules = $this->_weatherDatas['body']['devices'][0]['modules'];

		$jsonDatas = array();
		foreach ($modules as $module)
		{
			if (!isset($module["battery_percent"])) continue;

			$name = $module['module_name'];
			$batlevel = $module["battery_percent"];
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
			if (!isset($module["firmware"])) continue;

			$name = $module['module_name'];
			$firmVer = $module["firmware"];
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
			if (!isset($module["rf_status"])) continue;

			$name = $module['module_name'];
			$rf = $module["rf_status"];
			$jsonDatas[$name] = $rf;
		}
		//add main station:
		$jsonDatas[ $this->_weatherDatas['body']['devices'][0]['station_name'] ] = $this->_weatherDatas['body']['devices'][0]['firmware'];
		return $jsonDatas;
	}

	public function getWeatherDatas() //request full weather datas
	{
		$api_url = "https://api.netatmo.net/api/getstationsdata?access_token=" . $this->_accesstoken;
		$response = file_get_contents($api_url, false);
		$jsonDatas = json_decode($response, true);
		$this->_weatherDatas = $jsonDatas;
		if( isset($jsonDatas['body']['devices'][0]['place']['timezone']) ) $this->_timezone = $jsonDatas['body']['devices'][0]['place']['timezone'];
		return $jsonDatas;
	}


	//PRESENCE / WELCOME:
	public function getOutdoorEvents($requestType='All', $num=1) //human, animal, vehicle, All
	{
		//will return the last event of defined type as array of [title, snapshotURL, vignetteURL]
		if (is_null($this->_camerasDatas)) $this->getCamerasDatas($num);
		if (is_null($this->_cameras)) $this->getCameras();

		$cameraEvents = $this->_camerasDatas['body']['homes'][0]['events'];
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

	public function getIndoorEvents($requestType='All', $num=1) //person, person_away, movement, All  //--------Untested!!!
	{
		//will return the last event of defined type as array of [title, snapshotURL, vignetteURL]
		if (is_null($this->_camerasDatas)) $this->getCamerasDatas(10);
		if (is_null($this->_cameras)) $this->getCameras();

		$cameraEvents = $this->_camerasDatas['body']['homes'][0]['events'];
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
			//get only indoor events:
			if ($camType != 'Welcome') continue;

			$type = $thisEvent['type'];
			$welcomeTypes = ['person', 'person_away', 'movement'];

			if  (!in_array($welcomeTypes, $type) and $requestType=='All')
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

			if ($type == $requestType)
			{
				$time = $thisEvent['time'];
				$time = date("d-m-Y H:i:s", $time);
				$msg = $thisEvent['message'];
				$returnThis = array();
				$returnThis['title'] = $msg;
				$returnThis['camera'] = $camName;
				$returnThis['time'] = $time;
				$returnThis['type'] = $thisEvent['type'];
				$returnThis['is_arrival'] = false;
				if( isset($thisEvent['is_arrival']) ) $returnThis['is_arrival'] = $thisEvent['is_arrival'];
				if( isset($thisEvent['person_id']) )
				{
					$thisPerson = $this->getPersonById();
					if( isset($thisPerson['name']) ) $returnThis['person'] = $thisPerson['name'];
				}

				//snapshot and vignette ??

				array_push($returnEvents, $returnThis);
			}
		}

		return $returnEvents;
	}

	public function getCameras()
	{
		if (is_null($this->_camerasDatas)) $this->getCamerasDatas();
		$cameraList = $this->_camerasDatas['body']['homes'][0]['cameras']; //may support several homes ?
		$CamerasArray = array();

		foreach ($cameraList as $camera)
		{
			$thisCam = array();

			if( $camera['type'] == 'NOC' ) //Presence
			{
				$cameraVPN = $camera['vpn_url'];
				$thisCam['snapshot'] = $cameraVPN.'/live/snapshot_720.jpg';

				if ($camera['is_local'] == false) $cameraVPN = $cameraVPN."/live/index.m3u8";
				else $cameraVPN = $cameraVPN."/live/index_local.m3u8";

				$thisCam['name'] = $camera['name'];
				$thisCam['id'] = $camera['id'];
				$thisCam['vpn'] = $cameraVPN;
				$thisCam['status'] = $camera['status'];
				$thisCam['sd_status'] = $camera['sd_status'];
				$thisCam['alim_status'] = $camera['alim_status'];
				$thisCam['light_mode_status'] = $camera['light_mode_status'];
				$thisCam['is_local'] = $camera['is_local'];
				$thisCam['type'] = 'Presence';
			}
			else //Welcome
			{
				$thisCam['name'] = $camera['name'];
				$thisCam['id'] = $camera['id'];
				$thisCam['vpn'] = $cameraVPN;
				$thisCam['status'] = $camera['status'];
				$thisCam['sd_status'] = $camera['sd_status'];
				$thisCam['alim_status'] = $camera['alim_status'];
				$thisCam['is_local'] = $camera['is_local'];
				$thisCam['type'] = 'Welcome';
			}
			$CamerasArray[$camera['name']] = $thisCam;
		}

		$this->_cameras = $CamerasArray;
		return $CamerasArray;
	}

	public function getPersons() //--------Untested!!!
	{
		if (is_null($this->_camerasDatas)) $this->getCamerasDatas(10);
		$homeDatas = $this->_camerasDatas;

		$personsArray = array();
		if ( isset($homeDatas['body']['homes'][0]['persons']) )
		{
			$persons = $homeDatas['body']['homes'][0]['persons'];
			foreach ($persons as $person)
			{
				$thisPerson = array();
				$thisPerson['name'] = $person['pseudo'];
				$thisPerson['id'] = $person['id'];
				$thisPerson['last_seen'] = gmdate("d-m-Y H:i:s", $person['last_seen']);
				$thisPerson['out_of_sight'] = $person['out_of_sight'];
				$thisPerson['face'] = $person['face'];
				array_push($personsArray, $thisPerson);
			}

			$this->_cameraPersons = $personsArray;
			return $personsArray;
		}
		else return array('None');
	}

	public function getPersonById($id) //--------Untested!!!
	{
		if (is_null($this->_cameraPersons)) $this->getPersons();
		if (is_null($this->_cameraPersons)) return array('No person defined in this home.');

		foreach ($this->_cameraPersons as $person)
		{
			if($person['id'] == $id) return $person;
		}
		return array('Unfound person.');
	}

	public function getPersonByName($name) //--------Untested!!!
	{
		if (is_null($this->_cameraPersons)) $this->getPersons();
		if (is_null($this->_cameraPersons)) return array('No person defined in this home.');

		foreach ($this->_cameraPersons as $person)
		{
			if($person['name'] == $name) return $person;
		}
		return array('Unfound person.');
	}

	public function getCamerasDatas($eventNum=10) //request full Presence/Welcome datas
	{
		$api_url = $this->_apiurl."/api/gethomedata?access_token=" . $this->_accesstoken ."&size=".$eventNum;
		$response = file_get_contents($api_url, false);

		$jsonDatas = json_decode($response, true);
		$this->_camerasDatas = $jsonDatas;
		$this->_home = $jsonDatas['body']['homes'][0]['name'];
		if( isset($jsonDatas['body']['homes'][0]['place']['timezone']) ) $this->_timezone = $jsonDatas['body']['homes'][0]['place']['timezone'];
		return $jsonDatas;
	}


	//THERMOSTAT:
	public function getThermoDevices() //--------Untested!!!
	{
		if (is_null($this->_thermoDatas)) $this->getThermoDatas();
		if( !isset($this->_thermoDatas['body']['devices']) ) return array();

		$thermosList = $this->_thermoDatas['body']['devices'];
		$thermosArray = array();

		//get thermostats
		foreach ($thermosList as $thermo)
		{
			$thisThermo = array();

			$thisThermo['name'] = $thermo['station_name'];
			$thisThermo['id'] = $thermo['id'];
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
		$therm = $this->getThermByName($thermName);
		if( isset($therm['id']) ) $thermID = $therm['id'];
		else return array('Unfound thermostat.');

		$valve = $this->getThermValveByName($valveName);
		if( isset($valve['id']) ) $valveID = $valve['id'];
		else return array('Unfound valve.');

		$api_url = $this->_apiurl."/api/createnewschedule" . $this->_accesstoken;
		$url = $api_url.'&device_id='.$thermID.'&module_id='.$valveID.'&zones='.$zonesArray.'&timetable='.$timesArray.'&name='.$schedName;
		$response = file_get_contents($api_url, false);

		$jsonDatas = json_decode($response, true);
		return $jsonDatas;
	}

	public function switchThermoSched($thermName, $valveName, $schedName) //--------Untested!!!
	{
		$therm = $this->getThermByName($thermName);
		if( isset($therm['id']) ) $thermID = $therm['id'];
		else return array('Unfound thermostat.');

		$valve = $this->getThermValveByName($valveName);
		if( isset($valve['id']) ) $valveID = $valve['id'];
		else return array('Unfound valve.');

		$api_url = $this->_apiurl."/api/Switchschedule" . $this->_accesstoken;
		$url = $api_url.'&device_id='.$thermID.'&module_id='.$valveID.'&schedule_id='.$scheID;

		$response = file_get_contents($api_url, false);

		$jsonDatas = json_decode($response, true);
		return $jsonDatas;
	}

	public function syncThermoSched($thermName, $valveName, $zonesArray, $timesArray) //--------Untested!!!
	{
		$therm = $this->getThermByName($thermName);
		if( isset($therm['id']) ) $thermID = $therm['id'];
		else return array('Unfound thermostat.');

		$valve = $this->getThermValveByName($valveName);
		if( isset($valve['id']) ) $valveID = $valve['id'];
		else return array('Unfound valve.');

		$api_url = $this->_apiurl."/api/syncschedule" . $this->_accesstoken;
		$url = $api_url.'&device_id='.$thermID.'&module_id='.$valveID.'&zones='.$zonesArray.'&timetable='.$timesArray;

		$response = file_get_contents($api_url, false);

		$jsonDatas = json_decode($response, true);
		return $jsonDatas;
	}

	public function setThermoPoint($thermName, $valveName, $mode) //--------Untested!!!
	{
		$therm = $this->getThermByName($thermName);
		if( isset($therm['id']) ) $thermID = $therm['id'];
		else return array('Unfound thermostat.');

		$valve = $this->getThermValveByName($valveName);
		if( isset($valve['id']) ) $valveID = $valve['id'];
		else return array('Unfound valve.');

		$api_url = $this->_apiurl."/api/setthermpoint" . $this->_accesstoken;
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
		$api_url = $this->_apiurl."/api/addwebhook?access_token=" . $this->_accesstoken . "&url=".$endpoint."&app_type=app_security";
		$requete = @file_get_contents($api_url);
		$jsonDatas = json_decode($requete,true);
		return $jsonDatas;
	}

	public function dropWebhook()
	{
		$api_url = $this->_apiurl."/api/dropwebhook?access_token=" . $this->_accesstoken ."&app_type=app_security";
		$requete = @file_get_contents($api_url);
		$jsonDatas = json_decode($requete,true);
		return $jsonDatas;
	}

	//internal functions==================================================

	//home:
	public $_home;
	public $_timezone;

	//API:
	public $_scope;
	public $error;

	//devices:
	public $_cameras; //both Presences and
	public $_cameraPersons;

	public $_thermos;
	public $_thermoValves;
	public $_thermoZones;
	public $_thermoPrograms;

	public $_homecoachs;

	//datas:
	public $_camerasDatas;
	public $_thermoDatas;
	public $_weatherDatas;
	public $_homecoachDatas;

	protected $_apiurl = "https://api.netatmo.net/";
	protected $_Netatmo_user;
	protected $_Netatmo_pass;
	protected $_Netatmo_app_id;
	protected $_Netatmo_app_secret;
	protected $_accesstoken;
	protected $_refreshtoken;

	public function connect()
	{
		$token_url = $this->_apiurl."/oauth2/token";
		$postdata = http_build_query(
									array(
										'grant_type' => "password",
										'client_id' => $this->_Netatmo_app_id,
										'client_secret' => $this->_Netatmo_app_secret,
										'username' => $this->_Netatmo_user,
										'password' => $this->_Netatmo_pass,
										'scope' => 'read_station read_thermostat write_thermostat read_camera read_presence access_presence write_presence read_homecoach'
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
		if (isset($jsonDatas["access_token"]))
		{
			$this->_accesstoken = $jsonDatas["access_token"];
			$this->_refreshtoken = $jsonDatas["refresh_token"];
			$this->_scope = $jsonDatas["scope"];
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
		$this->connect();
	}


//splNetatmoAPI end
}

?>
