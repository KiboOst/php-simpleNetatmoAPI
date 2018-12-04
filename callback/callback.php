<?php
/*
Webhook called from Netatmo server on Home events

While testing:
error_reporting(E_ALL);
ini_set('display_errors', true);
*/

//__________________________Get the post JSON sent by Netatmo server
$jsonData = file_get_contents('php://input');

//__________________________send ok answer to netatmo immediately to not be banned:
ob_end_clean();
ignore_user_abort(true);
ob_start();
header('Content-Encoding: none');
header("Content-Length: " . ob_get_length());
header("Connection: close");
ob_end_flush();
ob_flush();
flush();

//get data as json:
$data = json_decode($jsonData, true);

//Now you have event data, do what you want with it!
//If message AND known person seen by Welcome:
if(isset($data['message']) && isset($data['persons']) && ($eventType == 'person'))
{
    try
        {
            //Can have more than one person:
            if (strpos($data['message'], 'ont') == true)
            {
                $who = explode(' ont ', $data['message'])[0];
                $who = explode(' et ', $who);
            }
            else
            {
                $who = array(explode(' a ', $data['message'])[0]);
            }
            foreach ($who as $person)
            {
                echo 'Hello '.$who.' welcome home!';
            }
        }
    catch (Exception $e)
        {
            logAnswer('Error checking person: '.$e->getMessage());
        }
}

//If message AND person seen by Presence
if(isset($data['message']) && isset($data['snapshot_id']) && ($eventType == 'human'))
{
    $snapshotID = $data['snapshot_id'];
    $snapshotKEY = $data['snapshot_key'];
    $snapshotURL = 'https://api.netatmo.com/api/getcamerapicture?image_id='.$snapshotID.'&key='.$snapshotKEY;

    echo 'Someone has been seen outdoor, I may arm the gatling.';
}

?>
