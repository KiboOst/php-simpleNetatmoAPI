<?php
/*

https://github.com/KiboOst/php-simpleNetatmoAPI

*/

class splNetatmoAPI {

	public $_version = "1.0";

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
		$api_url = "https://api.netatmo.net/api/getstationsdata?access_token=" . $this->_token;
		$response = file_get_contents($api_url, false);
		$jsonDatas = json_decode($response, true);
		$this->_weatherDatas = $jsonDatas;
		return $jsonDatas;
	}

	//PRESENCE:
	public function getPresenceCameras()
	{
		if (is_null($this->_presenceDatas)) $this->getPresenceDatas(10);
		$cameraList = $this->_presenceDatas['body']['homes'][0]['cameras']; //may support several homes ?
		$CamerasArray = array();

		foreach ($cameraList as $camera)
		{
			$thisCam = array();
			$cameraVPN = $camera["vpn_url"];
			$thisCam['snapshot'] = $cameraVPN.'/live/snapshot_720.jpg';

			if ($camera['is_local'] == false)
			{
				$cameraVPN = $cameraVPN."/live/index.m3u8";
			}
			else
			{
				$cameraVPN = $cameraVPN."/live/index_local.m3u8";
			}

			$thisCam['name'] = $camera['name'];
			$thisCam['id'] = $camera['id'];
			$thisCam['vpn'] = $cameraVPN;
			$thisCam['status'] = $camera['status'];
			$thisCam['sd_status'] = $camera['sd_status'];
			$thisCam['alim_status'] = $camera['alim_status'];
			$thisCam['light_mode_status'] = $camera['light_mode_status'];
			$thisCam['is_local'] = $camera['is_local'];

			$CamerasArray[$camera['name']] = $thisCam;
		}
		$this->_presenceCameras = $CamerasArray;
		return $CamerasArray;
	}

	public function getPresenceEvents($requestType='All', $num=1) //human, animal, vehicle, All
	{
		//will return the last event of defined type as array of [title, snapshotURL, vignetteURL]
		if (is_null($this->_presenceDatas)) $this->getPresenceDatas(10);
		if (is_null($this->_presenceCameras)) $this->getPresenceCameras();

		$cameraEvents = $this->_presenceDatas['body']['homes'][0]['events'];
		$numEvents = count($cameraEvents);
		$counts = $num;
		if ($numEvents < $counts) $counts == $numEvents;

		$returnEvents = array();
		for ($i=0; $i < $counts ;$i++)
		{
			$thisEvent = $cameraEvents[$i];

			$id = $thisEvent["id"];
			$time = $thisEvent["time"];
			$camId = $thisEvent["camera_id"];

			$c = count($this->_presenceCameras);
			for ($ic=0; $ic < $c; $ic++)
			{
				$cam = array_values($this->_presenceCameras)[$ic];
				if ($cam['id'] == $camId)
				{
					$camName = $cam['name'];
					$camVPN = $cam['vpn'];
					break;
				}
			}

			$type = $thisEvent["type"];

			if  ($type != "outdoor" and $requestType=='All')
			{
				$time = $thisEvent["time"];
				$time = date("d-m-Y H:i:s", $time);
				$msg = $thisEvent["message"];
				$returnThis = array();
				$returnThis['title'] = $msg . " | ".$time." | ".$camName;
				array_push($returnEvents, $returnThis);
			}

			if ($type == "outdoor")
				{
					$eventList = $thisEvent["event_list"];
					$isAvailable = $thisEvent["video_status"];
					for ($j=0; $j < count($eventList) ;$j++)
					{
						$thisSubEvent = $thisEvent["event_list"][$j];
						$subType = $thisSubEvent["type"];
						$subMsg = $thisSubEvent["message"];
						if (strpos($subType, $requestType) !== false OR $requestType=="All")
							{
								$subTime = $thisSubEvent["time"];
								$subTime = date("d-m-Y H:i:s", $subTime);

								if (isset($thisSubEvent["snapshot"]["filename"]))  //other vignette of same event!
								{
									$snapshotURL = $camVPN."/".$thisSubEvent["snapshot"]["filename"];
									$vignetteURL = $camVPN."/".$thisSubEvent["vignette"]["filename"];
								}else{
									$snapshotID = $thisSubEvent["snapshot"]["id"];
									$snapshotKEY = $thisSubEvent["snapshot"]["key"];
									$snapshotURL = "https://api.netatmo.com/api/getcamerapicture?image_id=".$snapshotID."&key=".$snapshotKEY;

									$vignetteID = $thisSubEvent["vignette"]["id"];
									$vignetteKEY = $thisSubEvent["vignette"]["key"];
									$vignetteURL = "https://api.netatmo.com/api/getcamerapicture?image_id=".$vignetteID."&key=".$vignetteKEY;
								}
								//echo '<img src="'.$snapshotURL.'" height="219" width="350" </img>'.'<br>';
								//echo '<img src="'.$vignetteURL.'" height="166" width="166" </img>'.'<br>';

								$returnThis = array();
								$returnThis['title'] = $subMsg . " | ".$subTime." | ".$camName;
								$returnThis['snapshotURL'] = $snapshotURL;
								$returnThis['vignetteURL'] = $vignetteURL;
								array_push($returnEvents, $returnThis);
							}
					}
				}
		}

		return $returnEvents;
	}

	public function getPresenceDatas($eventNum=10)
	{
		$api_url = $this->_apiurl."/api/gethomedata?access_token=" . $this->_token ."&size=".$eventNum;
		$response = file_get_contents($api_url, false);

		$jsonDatas = json_decode($response, true);
		$this->_presenceDatas = $jsonDatas;
		$this->_home = $jsonDatas['body']['homes'][0]['name'];
		return $jsonDatas;
	}


	//WELLCOME:


	//THERMOSTAT:


	//HOMECOACH:


	//WEBHOOK:
	public function setWebhook($endpoint)
	{
		$api_url = $this->_apiurl."/api/addwebhook?access_token=" . $this->_token . "&url=".$endpoint."&app_type=app_security";
		$requete = @file_get_contents($api_url);
		$jsonDatas = json_decode($requete,true);
		return $jsonDatas;
	}

	public function dropWebhook()
	{
		$api_url = $this->_apiurl."/api/dropwebhook?access_token=" . $this->_token ."&app_type=app_security";
		$requete = @file_get_contents($api_url);
		$jsonDatas = json_decode($requete,true);
		return $jsonDatas;
	}

	//internal functions==================================================

	public $_home;
	public $_scope;
	public $error;

	public $_weatherDatas;
	public $_thermoDatas;
	public $_presenceDatas;
	public $_presenceCameras;
	public $_wellcomeDatas;
	public $_wellcomeCameras;
	public $_homecoachDatas;


	protected $_apiurl = "https://api.netatmo.net";
	protected $_Netatmo_user;
	protected $_Netatmo_pass;
	protected $_Netatmo_app_id;
	protected $_Netatmo_app_secret;
	protected $_token;

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
								'header'  => 'Content-type: application/x-www-form-urlencoded'."\r\n".
											'Accept-Charset: UTF-8, *;q=0'."\r\n".
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
			$this->_token = $jsonDatas["access_token"];
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
