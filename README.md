# php-simpleNetatmoAPI

## Simple php API to get data from your Netatmo devices.

## Supported devices

- Netatmo Weather Station
- Netatmo Presence Cameras
- Netatmo Welcome Cameras

This is a simple stand-alone API to get your Netatmo devices data in a more easy and more readable way.

It does rely on official Netatmo SDK, even if no other resources are needed to get your data. Just download and use one single php file!

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

If you have several homes, you can specify a homeID as last argument.
```php
require($_SERVER['DOCUMENT_ROOT']."/path/to/splNetatmoAPI.php");
$_splNetatmo = new splNetatmoAPI($Netatmo_user, $Netatmo_pass, $Netatmo_app_id, $Netatmo_app_secret, 1);
if (isset($_splNetatmo->error)) die($_splNetatmo->error);

//You can also check homes configured on your account to connect to the right one
//It will return all homes with name, id and camera number found.
$homes = $_splNetatmo->getHomes();
echo "<pre><br>homes:<br>".json_encode($homes, JSON_PRETTY_PRINT)."</pre><br>";
```

#### Weather Station:

```php
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

#### Netatmo Cameras:

##### Get some datas:

```php
//get all Presence cameras datas:
$Cameras = $_splNetatmo->getPresenceCameras();
echo "<pre>Cameras:<br>".json_encode($Cameras, JSON_PRETTY_PRINT)."</pre><br>";
echo "<pre>light_mode: ".json_encode($Cameras['Cam_Terrasse']['light_mode_status'], JSON_PRETTY_PRINT)."</pre><br>";

//get 10 last event of defined type:
//can request 'human', 'animal', 'vehicle', 'movement', 'All'
$events = $_splNetatmo->getOutdoorEvents('All', 10);
echo "<pre>events:<br>".json_encode($events, JSON_PRETTY_PRINT)."</pre><br>";

//get all Welcome cameras datas:
$Cameras = $_splNetatmo->getWelcomeCameras();
echo "<pre>Cameras:<br>".json_encode($Cameras, JSON_PRETTY_PRINT)."</pre><br>";

//get 10 last indoor events:
$events = $_splNetatmo->getIndoorEvents(10);
echo "<pre>events:<br>".json_encode($events, JSON_PRETTY_PRINT)."</pre><br>";

//get all persons at home:
$atHome = $_splNetatmo->getPersonsAtHome();
echo "<pre>atHome :<br>".json_encode($atHome , JSON_PRETTY_PRINT)."</pre><br>";

//get John datas:
$John = $_splNetatmo->getPerson('John');
echo "<pre>John :<br>".json_encode($John , JSON_PRETTY_PRINT)."</pre><br>";

//is home empty ?
echo $_splNetatmo->isHomeEmpty();
```

##### Change some settings:

```php
//You can also get return value to know if all went fine.

//set John away from home:
$_splNetatmo->setPersonAway('John');

//set home empty:
$_splNetatmo->setHomeEmpty();

//change Presence light intensity:
$_splNetatmo->setLightIntensity('MyCam', 85);

//change Presence Light mode (use either 'auto', 'on', 'off':
$_splNetatmo->setLightMode('MyCam', 'auto');

//change Presence or Welcome monitoring (use either 'on', 'off':
$_splNetatmo->setMonitoring('MyCam', 'on');

```

#### Setting/dropping webhooks:

```php
//set webhook:
$endpoint = 'http://www.mydomain.com/myscripts/myPresenceWebhook.php';
$answer = $_splNetatmo->setWebhook($endpoint);
print_r($answer);

//drop webhook:
$_splNetatmo->dropWebhook();
```

## Changes

#### v1.31 (2017-11-29)
- New: getHomes() return all found homes with their id and name
- New: pass home id as last argument: new splNetatmoAPI($user, $pass, $app_id, $app_secret, 1);

#### v1.3 (2017-11-18)
- New: setMonitoring('camName', 'on')
- New: setLightMode('camName', 'auto')
- New: setLightIntensity('camName', 100)

#### v1.2 (2017-05-24)
- New: Welcome cameras support!
- Warning: check Presence functions for name changes.

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
