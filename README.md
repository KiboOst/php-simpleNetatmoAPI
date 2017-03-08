#php-simpleNetatmoPresence

##Simple php functions to get datas from your Netatmo Presence cameras
(C) 2017, KiboOst

Need a simple way to get your Netatmo Presence datas with a few lines of php? Here you are!

No need for official Netatmo SDK or any other ressources!

Feel free to submit an issue or pull request to add more.

##Requirements

- A Netatmo Weather Station and eventually additionnal modules.
- Your Netatmo login and password.
- Your Netatmo Connect application client_id and client_secret.
If you don't have Netatmo App yet, just create one, it's simple and free:
- Register at https://dev.netatmo.com
- Create an app at https://dev.netatmo.com/dev/createanapp (Enter any name)
- After successfully created your app, just get client_id and client_secret


##How-to

All function return a json array, you can echo it to get the data you want.

```
<?php

require($_SERVER['DOCUMENT_ROOT']."/path/to/splNetatmoPresence.php");

//get your connection variable from other file or write them, and initilize:
require($_SERVER['DOCUMENT_ROOT']."/path/to/myloginfile.php");
$_Presence = new NetatmoPresence($Netatmo_user, $Netatmo_pass, $Netatmo_app_id, $Netatmo_app_secret);

//get all datas from camera(s), containing last 10 events:
//$datas = $_Presence->getDatas(10);
//echo "<pre>".json_encode($datas, JSON_PRETTY_PRINT)."</pre><br>";

//get infos for all cameras:
$myCameras = $_Presence->getCameras();
for($i=0; $i<count($myCameras); $i++)
	{
		$thisCam = $myCameras[$i];
		echo "name: ".$thisCam['name']."<br>";
		echo "id: ".$thisCam['id']."<br>";
		echo "vpn url:<br><a href=\"".$thisCam['vpn']."\">".$thisCam['vpn']."</a>";
		echo "<br>snapshot url:<br><a href=\"".$thisCam['snapshot']."\">".$thisCam['snapshot']."</a><br>";
		echo "status: ".$thisCam['status']."<br>";
		echo "sd_status: ".$thisCam['sd_status']."<br>";
		echo "alim_status: ".$thisCam['alim_status']."<br>";
		echo "light_mode_status: ".$thisCam['light_mode_status']."<br>";
		echo "<br>";
	}

//show snapshot:
$snapshot = $myCameras[0]['snapshot'];
//echo "<br><img src=\"$snapshot\" width=\"350\" height=\"219\">";

//get 10 last event of defined type as array of [title, snapshotURL, vignetteURL]
//if you have modified or deleted some event in the Netatmo app, these won't show the snapshot/vignette
$lastEvents = $_Presence->getEvents('All', 10); //can request 'human', 'animal', 'vehicle', 'movement', 'All'
for($i=0; $i<count($lastEvents); $i++)
	{
		$thisEvent = $lastEvents[$i];
		echo $thisEvent['title']."<br>";
		echo $thisEvent['snapshotURL']."<br>";
		echo $thisEvent['vignetteURL']."<br>";
		$var = $thisEvent['vignetteURL'];
		echo "<br><img src=\"$var\" width=\"80\" height=\"80\">";
	}

?>
```

Support setting/dropping webhooks:
```
<?php
//set webhook:
$endpoint = 'http://www.mydomain.com/myscripts/myPresenceWebhook.php';
$answer = $_Presence->setWebhook($endpoint);
print_r($answer);

//drop webhook:
$_Presence->dropWebhook();

```

##Changes

####v2017.2.0 (2017-03-08)
- Code breaking: all now is in a php class to avoid variable mess with your own script.

####v2017.1.0 (2017-03-07)
- First public version.

##ToDo

Waiting for Netatmo SDK write_presence scope to be able to change settings on cameras!

##License

The MIT License (MIT)

Copyright (c) 2017 KiboOst

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
