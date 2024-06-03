<?php

$settings = include('settings.php');

// JSON stuff
const JSON_URL = "https://api.v2.sondehub.org/amateur/telemetry";

// Software parameters (please leave this unchanged)
const SOFTWARE_NAME = "wspr2sondehub";
const SOFTWARE_VERSION = "v0.0.2";

/***********************************************************************
 * Init some important parameters
 * ********************************************************************/
function init_setup()
{ 
    echo "Setting UTC timezone\n\n";
    // We need the program to run in UTC time
    date_default_timezone_set('UTC');
    echo "Current date/time: ";
    // Just for checking the correct time
    echo date('Y-m-d H:i:s T', time()) . "\n\n\n";
}

/***********************************************************************
 * Convert query results to 
 * $source is an array indexed with numbers
 * ********************************************************************/
function convert_qry_result($source, $dest)
{
    // Convert the query results to a associative array
    // SELECT time, band, tx_sign, tx_loc, tx_lat, tx_lon, power, frequency, time
    $dest['time'] = $source[0];
    $dest['band'] = $source[1];
    $dest['tx_sign'] = $source[2];
    $dest['tx_loc'] = $source[3];
    $dest['tx_lat'] = $source[4];
    $dest['tx_lon'] = $source[5];
    $dest['power'] = $source[6];
    $dest['frequency'] = $source[7];
    $dest['time_long'] = $source[8];
    return $dest;
}

/***********************************************************************
 * Perform a query at the wspr database
 * ********************************************************************/
function perform_query($aQuery)
{
    $baseurl = 'http://db1.wspr.live/?';
    $query = http_build_query(array(
        'query' => $aQuery
    ));
    $url = $baseurl . $query;

    // Init curl session and add parameters
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $content = curl_exec($ch);
    curl_close($ch);

    return $content;
}

/************************************************************************************
* JSON structure setup for sondehub. 
*****************************************************************************************/
function decodeTelemetry($res1, $res2, $balloon, $uploader_call)
{
    // Reused parts of the python code created by sm3ulc
    // see: https://github.com/sm3ulc/hab-wspr 

    $tracker_type = $balloon['tracker_type'];

    if ($tracker_type == 'traquito') {
        decodeTelemetryTraquito($res1, $res2, $balloon, $uploader_call);
    } elseif ($tracker_type == 'zachtek1') {
        decodeTelemetryZachtek1($res1, $res2, $balloon, $uploader_call);
    } else {
        echo "Unknown tracker type: " . $tracker_type . "\n";
    }
}

function decodeTelemetryTraquito($res1, $res2, $balloon, $uploader_call) {
    // Traquito tracker specific decoding logic
    // These are the fields we need to decode
    $json = array(
        //'dev' => 'true', 
        'software_name' => SOFTWARE_NAME,
        'software_version' => SOFTWARE_VERSION,
        'uploader_callsign' => $uploader_call,
        'frequency' => 0.0,
        'modulation' => 'WSPR',
        'comment' => $balloon['comment'],
        'detail' => $balloon['detail'],
        'device' => $balloon['device'],
        'type' => $balloon['type'],
        'time_received' => '2023-01-01',
        'datetime' => '2023-01-01',
        'payload_callsign' => $balloon['payload'],
        'lat' => '0.0',
        'lon' => '0.0',
        'alt' => '0',
        'temp' => '0',
        'gps' => '0',
        'batt' => '0.0'
    );

    // Standard Traquito decoding logic here
    $json['frequency'] = floatval($res1['frequency']) / 1000000;
    $json['time_received'] = substr($res2['time'],0,10) . "T" . substr($res2['time'],11,8) . ".000000Z";
    $json['datetime'] = $json['time_received'];

    // Display all the known values on the console
    echo "\nSoftware name:      " . $json['software_name'];
    echo "\nSoftware version:   " . $json['software_version'];
    echo "\nUploader callsign:  " . $json['uploader_callsign'];
    echo "\nFrequency:          " . $json['frequency'];
    echo "\nModulation:         " . $json['modulation'];
    echo "\nComment:            " . $json['comment'];
    echo "\nDetail:             " . $json['detail'];
    echo "\nDevice:             " . $json['device'];
    echo "\nType:               " . $json['type'];
    echo "\nTime received:      " . $json['time_received'];
    echo "\nDate/Time reported: " . $json['datetime'];
    echo "\nPayload callsign:   " . $json['payload_callsign'];

    // Calculate the other values and display them
    $pow2dec = array("0"=>0,"3"=>1,"7"=>2,"10"=>3,"13"=>4,"17"=>5,"20"=>6,
                     "23"=>7,"27"=>8,"30"=>9,"33"=>10,"37"=>11,"40"=>12,
                     "43"=>13,"47"=>14,"50"=>15,"53"=>16,"57"=>17,"60"=>18);
    
    $maidenHead = substr($res1['tx_loc'],0,4);
    $c1 = $res2['tx_sign'][1];
    $c1 = preg_match("/^[a-zA-Z]$/", $c1) ? ord($c1)-55 : ord($c1)-48;
    $c2 = ord($res2['tx_sign'][3])-65;
    $c3 = ord($res2['tx_sign'][4])-65;
    $c4 = ord($res2['tx_sign'][5])-65;
    $l1 = ord($res2['tx_loc'][0])-65;
    $l2 = ord($res2['tx_loc'][1])-65;
    $l3 = ord($res2['tx_loc'][2])-48;
    $l4 = ord($res2['tx_loc'][3])-48;
    $p = $pow2dec[$res2["power"]];
    $sum1 = $c1*26*26*26 + $c2*26*26 + $c3*26 + $c4;
    $sum2 = $l1*18*10*10*19 + $l2*10*10*19 + $l3*10*19 + $l4*19 + $p;
    $lsub1 = intval($sum1/25632);
    $lsub2_tmp = $sum1 - $lsub1 * 25632;
    $lsub2 = intval($lsub2_tmp / 1068);

    $alt = ($lsub2_tmp - $lsub2 * 1068) * 20;
    $lsub1 += 65;
    $lsub2 += 65;
    $subloc = strtolower(chr($lsub1) . chr($lsub2));
    $maidenHead = $maidenHead . $subloc;

    echo "\nMaidenhead:         " . $maidenHead;

    $temp_1 = intval($sum2 / 6720);
    $temp_2 = $temp_1 * 2 + 457;
    $temp_3 = $temp_2 * 5 / 1024;
    $temp = ($temp_2 * 500 / 1024) - 273;

    $json['temp'] = round($temp);
    echo "\nTemperature:        " . $json['temp'];

    $batt_1 = intval($sum2 - $temp_1 * 6720);
    $batt_2 = intval($batt_1 / 168);
    $batt_3 = $batt_2 * 10 + 614;
    $batt = $batt_3 * 5 / 1024;

    $json['batt'] = $batt;
    echo "\nVoltage:            " . $json['batt'];

    $t1 = $sum2 - $temp_1 * 6720;
    $t2 = intval($t1 / 168);
    $t3 = $t1 - $t2 * 168;
    $t4 = intval($t3 / 4);
    $speed = $t4 * 2;
    $r7 = $t3 - $t4 * 4;
    $gps = intval($r7 / 2);
    $sats = $r7 % 2;

    echo "\nSpeed:              " . $speed;
    $json['gps'] = $gps;
    echo "\nGPS:                " . $json['gps'];

    $maidenHead = strtoupper($maidenHead);
    $lon = -180 + (ord($maidenHead[0]) - ord('A')) * 20 + intval($maidenHead[2]) * 2 + (ord($maidenHead[4]) - ord('A')) * 5. / 60 + 2.5 / 60;
    $lat = -90 + (ord($maidenHead[1]) - ord('A')) * 10 + intval($maidenHead[3]) * 1 + (ord($maidenHead[5]) - ord('A')) * 2.5 / 60 + 1.25 / 60;

    $json['lon'] = $lon;
    $json['lat'] = $lat;
    $json['alt'] = $alt;

    echo "\nLatitude:           " . $json['lat'];
    echo "\nLongitude:          " . $json['lon'];
    echo "\nAltitude:           " . $json['alt'];

    if ($GLOBALS['settings']['uploadspots']) {
        $json_headers = array("Content-Type: application/json", "accept: text/plain");
        $json_encoded = json_encode($json);
        $json_encoded = '[' . $json_encoded . ']';

        $channel = curl_init(JSON_URL);   
        curl_setopt($channel, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($channel, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($channel, CURLOPT_HTTPHEADER, $json_headers);
        curl_setopt($channel, CURLOPT_POSTFIELDS, $json_encoded);
        curl_setopt($channel, CURLOPT_CONNECTTIMEOUT, 10);
        $response = curl_exec($channel);
        $statusCode = curl_getInfo($channel, CURLINFO_HTTP_CODE);
        echo "\nUpload to Sondehub: " . $statusCode;
        echo "\nUpload to Sondehub: " . $response;
        curl_close($channel);  
    }
    echo "\n====================================================\n\n";
    if ($GLOBALS['settings']['logspots']) {
        $logfile = fopen(strtolower($balloon['payload']) . "_spotslog.csv", "a");
        fwrite($logfile, date('Y-m-d H:i:s T', time()));fwrite($logfile, ",");
        fwrite($logfile, $balloon['payload']);fwrite($logfile, ",");
        fwrite($logfile, $json['lat']);fwrite($logfile, ",");
        fwrite($logfile, $json['lon']);fwrite($logfile, ",");
        fwrite($logfile, $json['alt']);
        fwrite($logfile, "\n");
        fclose($logfile);
    }
}

function decodeTelemetryZachtek1($res1, $res2, $balloon, $uploader_call) {
    // Zachtek1 tracker specific decoding logic
    $json = array(
        'dev' => 'true', 
        'software_name' => SOFTWARE_NAME,
        'software_version' => SOFTWARE_VERSION,
        'uploader_callsign' => $uploader_call,
        'frequency' => 0.0,
        'modulation' => 'WSPR',
        'comment' => $balloon['comment'],
        'detail' => $balloon['detail'],
        'device' => $balloon['device'],
        'type' => $balloon['type'],
        'time_received' => '2023-01-01',
        'datetime' => '2023-01-01',
        'payload_callsign' => $balloon['payload'],
        'lat' => '0.0',
        'lon' => '0.0',
        'alt' => '0',
    );

    $json['frequency'] = floatval($res1['frequency']) / 1000000;
    $json['time_received'] = substr($res2['time'], 0, 10) . "T" . substr($res2['time'], 11, 8) . ".000000Z";
    $json['datetime'] = $json['time_received'];

    echo "\nSoftware name:      " . $json['software_name'];
    echo "\nSoftware version:   " . $json['software_version'];
    echo "\nUploader callsign:  " . $json['uploader_callsign'];
    echo "\nFrequency:          " . $json['frequency'];
    echo "\nModulation:         " . $json['modulation'];
    echo "\nComment:            " . $json['comment'];
    echo "\nDetail:             " . $json['detail'];
    echo "\nDevice:             " . $json['device'];
    echo "\nType:               " . $json['type'];
    echo "\nTime received:      " . $json['time_received'];
    echo "\nDate/Time reported: " . $json['datetime'];
    echo "\nPayload callsign:   " . $json['payload_callsign'];

    $p1 = $res1["power"];
    $p2 = $res2["power"];
    $alt = ($p1 * 300) + ($p2 * 20);
    $maidenHead = $res2["tx_loc"];

    echo "\nMaidenhead:         " . $maidenHead;

    $maidenHead = strtoupper($maidenHead);
    $O = ord('A');
    $lon = -180 + (ord($maidenHead[0]) - $O) * 20 + intval($maidenHead[2]) * 2 + (ord($maidenHead[4]) - $O) * 5. / 60 + 2.5 / 60;
    $lat = -90 + (ord($maidenHead[1]) - $O) * 10 + intval($maidenHead[3]) * 1 + (ord($maidenHead[5]) - $O) * 2.5 / 60 + 1.25 / 60;

    $json['lon'] = $lon;
    $json['lat'] = $lat;
    $json['alt'] = $alt;

    echo "\nLatitude:           " . $json['lat'];
    echo "\nLongitude:          " . $json['lon'];
    echo "\nAltitude:           " . $json['alt'];

    if ($GLOBALS['settings']['uploadspots']) {
        $json_headers = array("Content-Type: application/json", "accept: text/plain");
        $json_encoded = json_encode($json);
        $json_encoded = '[' . $json_encoded . ']';

        $channel = curl_init(JSON_URL);
        curl_setopt($channel, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($channel, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($channel, CURLOPT_HTTPHEADER, $json_headers);
        curl_setopt($channel, CURLOPT_POSTFIELDS, $json_encoded);
        curl_setopt($channel, CURLOPT_CONNECTTIMEOUT, 10);
        $response = curl_exec($channel);
        $statusCode = curl_getInfo($channel, CURLINFO_HTTP_CODE);
        echo "\nUpload to Sondehub: " . $statusCode;
        echo "\nUpload to Sondehub: " . $response;
        curl_close($channel);
    }

    echo "\n====================================================\n\n";
    if ($GLOBALS['settings']['logspots']) {
        $logfile = fopen(strtolower($balloon['payload']) . "_spotslog.csv", "a");
        fwrite($logfile, date('Y-m-d H:i:s T', time()));
        fwrite($logfile, ",");
        fwrite($logfile, $balloon['payload']);
        fwrite($logfile, ",");
        fwrite($logfile, $json['lat']);
        fwrite($logfile, ",");
        fwrite($logfile, $json['lon']);
        fwrite($logfile, ",");
        fwrite($logfile, $json['alt']);
        fwrite($logfile, "\n");
        fclose($logfile);
    }
}

// Do setup
init_setup();

// Repeat until forever
while (true) {
    if (!isset($settings['balloons']) || !is_array($settings['balloons'])) {
        echo "No balloons found in settings\n";
        sleep(300);
        continue;
    }

    foreach ($settings['balloons'] as $balloon) {
        // Create two empty arrays
        $msg1_results = array();
        $msg2_results = array();

        // Create query parameters
        $queryTime = strtotime('now -20 minutes');
        $band = $balloon['freq_band'];
        $callsign_timeslot = "____-__-__ __:_" . $balloon['callsign_slot'] . "%";
        $telemetry_timeslot = "____-__-__ __:_" . $balloon['telemetry_slot'] . "%";
        $flightID = isset($balloon['flight_id_1']) && isset($balloon['flight_id_3']) ? $balloon['flight_id_1'] . "_" . $balloon['flight_id_3'] . "%" : "%";
        $myCall = $balloon['ham_call'];

        // Create the query for the first message
        $msg1 = perform_query("SELECT toString(time) as stime, band, tx_sign, tx_loc, tx_lat, tx_lon, power, frequency, time FROM wspr.rx WHERE (band='$band') AND (stime LIKE '$callsign_timeslot') AND (time > $queryTime) AND (tx_sign='$myCall') ORDER BY time DESC LIMIT 1");

        // Create the query for the second message
        if ($balloon['tracker_type'] == 'traquito') {
            $msg2 = perform_query("SELECT toString(time) as stime, band, tx_sign, tx_loc, tx_lat, tx_lon, power, frequency, time FROM wspr.rx WHERE (band='$band') AND (stime LIKE '$telemetry_timeslot') AND (time > $queryTime) AND (tx_sign LIKE '$flightID') ORDER BY time DESC LIMIT 1");
        } else {
            $msg2 = perform_query("SELECT toString(time) as stime, band, tx_sign, tx_loc, tx_lat, tx_lon, power, frequency, time FROM wspr.rx WHERE (band='$band') AND (stime LIKE '$telemetry_timeslot') AND (time > $queryTime) AND (tx_sign='$myCall') ORDER BY time DESC LIMIT 1");
        }

        // Display query results on screen
        echo $msg1;
        echo $msg2;

        // Log the database query results to a file
        if ($settings['lograwspots']) {
            $rawfile = fopen(strtolower($balloon['payload']) . "_rawlog.csv", "a");
            fwrite($rawfile, $msg1);
            fwrite($rawfile, $msg2);
            fclose($rawfile);
        }

        // Put the query results in arrays for easy reference
        // First message 1
        if (!empty(trim($msg1))) {
            $array1 = explode("\t", trim($msg1));
            if (count($array1) == 9) {
                $msg1_results = convert_qry_result($array1, $msg1_results);
            }
        }
        // Second message 2
        if (!empty(trim($msg2))) {
            $array2 = explode("\t", trim($msg2));
            if (count($array2) == 9) {
                $msg2_results = convert_qry_result($array2, $msg2_results);
            }
        }

        // Only bother decoding if both queries came back with valid results
        if (count($msg1_results) == 9 && count($msg2_results) == 9) {
            if ($msg2_results['time_long'] > $msg1_results['time_long']) {
                decodeTelemetry($msg1_results, $msg2_results, $balloon, $settings['uploader_call']);
            } else {
                echo "No upload: Time Received in message2 is older than Time Received in message1\n\n\n";
            }
        } else {
            echo "\n\nFound no new spots for payload " . $balloon['payload'] . "...\n";
        }
    }
    // Scrape the WSPR database every 300 seconds
    sleep(300);
}

?>

