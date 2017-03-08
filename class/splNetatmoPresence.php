<?php
/*

https://github.com/KiboOst/php-simpleNetatmoPresence
v 2017.2.0

*/

class NetatmoPresence {

	public $_home, $_scope, $_fullDatas;
	public $_cameras;
	protected $_apiurl = "https://api.netatmo.net";
	protected $_Netatmo_user;
	protected $_Netatmo_pass;
	protected $_Netatmo_app_id;
	protected $_Netatmo_app_secret;
	protected $_token;

	function __construct($Netatmo_user, $Netatmo_pass, $Netatmo_app_id, $Netatmo_app_secret)
	{
		$this->_Netatmo_user = $Netatmo_user;
		$this->_Netatmo_pass = $Netatmo_pass;
		$this->_Netatmo_app_id = $Netatmo_app_id;
		$this->_Netatmo_app_secret = $Netatmo_app_secret;
		$this->connect();
	}

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
										'scope' => 'read_presence access_presence write_presence'
				)
			);
		$opts = array('http' =>
							array(
								'method'  => 'POST',
								'header'  => "Content-type: application/x-www-form-urlencoded\r\n".
											"User-Agent: netatmoclient",
								'content' => $postdata
				)
			);
		$context  = stream_context_create($opts);

		$response = @file_get_contents($token_url, false, $context);

		//netatmo server sometimes give 500, always works second time:
		if ($response === false) {
			$response = @file_get_contents($token_url, false, $context);
			if ($response === false) {
				die("ERROR, can't connect to Netatmo Server<br>");
			}
		}
		$jsonDatas = json_decode($response, true);
		if (isset($jsonDatas["access_token"]))
		{
			$this->_token = $jsonDatas["access_token"];
			$this->_scope = $jsonDatas["scope"];
			return true;
		}

		return false;
	}

	public function getDatas($eventNum=10)
	{
		$api_url = $this->_apiurl."/api/gethomedata?access_token=" . $this->_token ."&size=".$eventNum;
		$response = file_get_contents($api_url, false);
		$jsonDatas = json_decode($response, true);
		$this->_fullDatas = $jsonDatas;
		$this->_home = $jsonDatas['body']['homes'][0]['name'];
		return $jsonDatas;
	}

	public function getCameras()
	{
		if (is_null($this->_fullDatas)) $this->getDatas(10);
		$cameraList = $this->_fullDatas['body']['homes'][0]['cameras']; //may support several homes ?
		$CamerasArray = array();
		for ($i=0; $i < count($cameraList) ;$i++)
		{
			$camera = $cameraList[$i];
			$cameraVPN = $camera["vpn_url"];
			$CamerasArray[$i]['snapshot'] = $cameraVPN.'/live/snapshot_720.jpg';

			if ($camera['is_local'] == false)
			{
				$cameraVPN = $cameraVPN."/live/index.m3u8";
			}
			else
			{
				$cameraVPN = $cameraVPN."/live/index_local.m3u8";
			}

			$CamerasArray[$i]['name'] = $camera["name"];
			$CamerasArray[$i]['id'] = $camera["id"];
			$CamerasArray[$i]['vpn'] = $cameraVPN;
			$CamerasArray[$i]['status'] = $camera["status"];
			$CamerasArray[$i]['sd_status'] = $camera["sd_status"];
			$CamerasArray[$i]['alim_status'] = $camera["alim_status"];
			$CamerasArray[$i]['light_mode_status'] = $camera["light_mode_status"];
			$CamerasArray[$i]['is_local'] = $camera["is_local"];

		}
		$this->_cameras = $CamerasArray;
		return $CamerasArray;
	}

	public function getEvents($requestType="All", $num=1) //human, animal, vehicle, All
	{
		//will return the last event of defined type as array of [title, snapshotURL, vignetteURL]
		if (is_null($this->_fullDatas)) $this->getDatas(10);
		if (is_null($this->_cameras)) $this->getCameras();

		$cameraEvents = $this->_fullDatas['body']['homes'][0]['events'];
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
			foreach ($this->_cameras as $cam)
				{
					if ($cam['id'] == $camId)
					{
						$camName = $cam['name'];
						$camVPN = $cam['vpn'];
						break;
					}
				}

			$eventList = $thisEvent["event_list"];
			$isAvailable = $thisEvent["video_status"];
			if ($isAvailable == "available")
				{
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

//NetatmoPresence end
}

?>