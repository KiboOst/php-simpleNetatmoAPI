# php-simpleNetatmoAPI

## Simple php API to get data from your Netatmo devices.
(C) 2017, KiboOst

## Supported devices

- Netatmo Weather Station
- Netatmo Presence Cameras

This is a simple stand-alone API to get your Netatmo devices data.

It does rely on official Netatmo SDK, even if no other ressources are needed to get your datas. Just download and use one single php file!

If you need a fully feature custom API to change your Presence cameras settings, alerts and such, check here: https://github.com/KiboOst/php-NetatmoPresenceAPI Anyway, as it doesn't rely on official Netatmo API (which doesn't provide editing camera settings), it can't set/drop webhooks.

Feel free to submit an issue or pull request to add more (*Wellcome*, *Homecoach* and *Thermostat* support by someone having these could be great).

## Requirements

- Your Netatmo login and password.
- Your Netatmo Connect application client_id and client_secret.

If you don't have Netatmo App yet, just create one, it's simple and free:

- Register at https://dev.netatmo.com
- Create an app at https://dev.netatmo.com/dev/createanapp (Enter any name)
- After successfully created your app, just get client_id and client_secret


## How-to

All functions return a json array, you can echo it to see which key to get.

Initialize:
```php
require($_SERVER['DOCUMENT_ROOT']."/path/to/splNetatmoAPI.php");
$_splNetatmo = new splNetatmoAPI($Netatmo_user, $Netatmo_pass, $Netatmo_app_id, $Netatmo_app_secret);
if (isset($_splNetatmo->error)) die($_splNetatmo->error);
```

Weather Station:

```php
//get main weather station datas:
$getWeatherStationDatas = $_splNetatmo->getWeatherStationDatas();
echo "<pre>getWeatherStationDatas:<br>".json_encode($getWeatherStationDatas, JSON_PRETTY_PRINT)."</pre><br>";

//get module datas by its name:
$getWeatherModuleDatas = $_splNetatmo->getWeatherModuleDatas('Exterieur');
echo "<pre>getWeatherModuleDatas:<br>".json_encode($getWeatherModuleDatas, JSON_PRETTY_PRINT)."</pre><br>";

//get all temperatures from all modules:
$getWeatherTemperatures = $_splNetatmo->getWeatherTemperatures();
echo "<pre>getWeatherTemperatures:<br>".json_encode($getWeatherTemperatures, JSON_PRETTY_PRINT)."</pre><br>";
echo $getWeatherTemperatures['Exterieur'];

//get all modules batteries:
//If you specify a number under 100, it will return only modules under this number so you can get low batteries modules.
$getWeatherBatteries = $_splNetatmo->getWeatherBatteries();
echo "<pre>getWeatherBatteries:<br>".json_encode($getWeatherBatteries, JSON_PRETTY_PRINT)."</pre><br>";

//get all modules firmwares versions:
$getWeatherFirmVer = $_splNetatmo->getWeatherFirmVer();
echo "<pre>getWeatherFirmVer:<br>".json_encode($getWeatherFirmVer, JSON_PRETTY_PRINT)."</pre><br>";

//get all modules radio signal statut:
$getWeatherRFs = $_splNetatmo->getWeatherRFs();
echo "<pre>getWeatherRFs:<br>".json_encode($getWeatherRFs, JSON_PRETTY_PRINT)."</pre><br>";
```

Presence Cameras:

```php
//get all cameras datas:
$Cameras = $_splNetatmo->getPresenceCameras();
echo "<pre>Cameras:<br>".json_encode($Cameras, JSON_PRETTY_PRINT)."</pre><br>";
echo "<pre>light_mode: ".json_encode($Cameras['Cam_Terrasse']['light_mode_status'], JSON_PRETTY_PRINT)."</pre><br>";

//get 10 last event of defined type:
//can request 'human', 'animal', 'vehicle', 'movement', 'All'
$events = $_splNetatmo->getPresenceEvents('All', 10);
echo "<pre>events:<br>".json_encode($events, JSON_PRETTY_PRINT)."</pre><br>";

//get all untreated datas:
$datas = $_splNetatmo->getPresenceDatas();
echo "<pre>datas:<br>".json_encode($datas, JSON_PRETTY_PRINT)."</pre><br>";
```

Setting/dropping webhooks:

```php
//set webhook:
$endpoint = 'http://www.mydomain.com/myscripts/myPresenceWebhook.php';
$answer = $_splNetatmo->setWebhook($endpoint);
print_r($answer);

//drop webhook:
$_splNetatmo->dropWebhook();
```

## Changes

#### v1.0 (2017-03-24)
- First public version.

## ToDo

Waiting for Netatmo SDK write_presence scope to be able to change settings on cameras!
If someone have *Wellcome*, *Homecoach* or *Thermostat*, feel free to ask for pull request.


## License

The MIT License (MIT)

Copyright (c) 2017 KiboOst

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sub-license, and/or sell
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
