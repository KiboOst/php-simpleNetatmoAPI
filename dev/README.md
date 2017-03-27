# php-simpleNetatmoAPI dev version

## Simple php API to get data from your Netatmo devices.
(C) 2017, KiboOst

## Feature development

- Netatmo Welcome Cameras
- Netatmo Home Coach
- Netatmo Thermostat

Dev version to help supporting full Netatmo devices.

As I don't have *Wellcome*, *Homecoach* and *Thermostat*, I can't develop robust functions for these devices.

## How-to

- Download /dev/splNetatmoAPI.php
- Check your devices functions and fix/develop new funtions
- Pull request to merge your development into this version

Initialize:

```php
require($_SERVER['DOCUMENT_ROOT']."/path/to/dev/splNetatmoAPI.php");
$_splNetatmo = new splNetatmoAPI($Netatmo_user, $Netatmo_pass, $Netatmo_app_id, $Netatmo_app_secret);
if (isset($_splNetatmo->error)) die($_splNetatmo->error);
```

## Features to test:

Cameras:

```php
//get all cameras datas:
$Cameras = $_splNetatmo->getCameras();
echo "<pre>Cameras:<br>".json_encode($Cameras, JSON_PRETTY_PRINT)."</pre><br>";

//should return both Presence and Welcome cameras, with differentiating ['type']

//get 10 last event of defined outdoor type:
//can request 'human', 'animal', 'vehicle', 'movement', 'All'
$events = $_splNetatmo->getOutdoorEvents('All', 10);
echo "<pre>events:<br>".json_encode($events, JSON_PRETTY_PRINT)."</pre><br>";

//get 10 last event of defined indoor type:
//can request 'person', 'animal', 'person_away', 'movement', 'All'
$events = $_splNetatmo->getIndoorEvents('All', 10);
echo "<pre>events:<br>".json_encode($events, JSON_PRETTY_PRINT)."</pre><br>";

//get home persons:
$persons = $_splNetatmo->getPersons('All', 10);
echo "<pre>persons:<br>".json_encode($persons, JSON_PRETTY_PRINT)."</pre><br>";

//get all untreated datas:
$datas = $_splNetatmo->getCamerasDatas();
echo "<pre>datas:<br>".json_encode($datas, JSON_PRETTY_PRINT)."</pre><br>";
```

Thermostat:

```php
$thermos = $_splNetatmo->getThermostats();
echo "<pre>thermos:<br>".json_encode($thermos, JSON_PRETTY_PRINT)."</pre><br>";

echo "<pre>thermosDatas:<br>".json_encode($_splNetatmo->_thermoDatas, JSON_PRETTY_PRINT)."</pre><br>";
```

Home Coach:

```php
$coachs = $_splNetatmo->getCoachs();
echo "<pre>coachs:<br>".json_encode($coachs, JSON_PRETTY_PRINT)."</pre><br>";

echo "<pre>coachsDatas:<br>".json_encode($_splNetatmo->_homecoachDatas, JSON_PRETTY_PRINT)."</pre><br>";
```

## Changes

#### v1.2dev (2017-03-27)
- First public dev version.

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
