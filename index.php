<?php

// Clear the variables we'll use
$trainLineName = '';
$stationName = '';
$destinationStationName = '';
$numberOfTrainsToDisplay = 3;
$response = array(
    'response_type' => 'ephemeral',
    'text' => 'blank');

$finalResponse = '';
$slashCommand = 'septa';
$commands = array(
    'help' => array('name' => 'help', 'function' => 'help', 'description' => 'Returns a list of available commands. You just used this command. ;)'),
    'trains' => array('name' => 'trains', 'function' => 'trainLines', 'description' => 'Returns a list of train lines'),
    'lines' => array('name' => 'lines', 'function' => 'trainLines', 'description' => 'Returns a list of train lines'),
    'train' => array('name' => 'train', 'function' => 'train', 'description' => '{Line Name} Returns current trains running on given line'),
    'route' => array('name' => 'route', 'function' => 'route', 'description' => '{Station} Returns current trains from Jefferson to your station'),
    //'train' => 'train-number'
);

$trainLines = getTrainLinesFromAPI();

function help(){

    global $commands, $slashCommand;

    $helpResponse = 'Available Commands:' . PHP_EOL;

    foreach ($commands as $name => $command) {

        $helpResponse .= '`/' . $slashCommand . ' ' . $command['name'] . '` : ' . $command['description'] . PHP_EOL;

    }
    

    return $helpResponse;
}

function getTrainLinesFromAPI(){
// Set the train lines
$ch = curl_init();
curl_setopt_array($ch, array(
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => 'http://www.septastats.com/api/current/lines')
);
$res = curl_exec($ch);

if(curl_errno($ch)){

die('cUrl Error: ' . curl_errno($ch));
}

curl_close($ch);

$decoded = json_decode($res, TRUE);

$allTrains = $decoded['data'];

return $allTrains;
}

function getTrainsFromAPI($lineKey){
// Set the train lines
$ch = curl_init();
curl_setopt_array($ch, array(
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => 'http://www.septastats.com/api/current/line/'.$lineKey.'/outbound/latest')
);
$res = curl_exec($ch);

if(curl_errno($ch)){

die('cUrl Error: ' . curl_errno($ch));
}

curl_close($ch);

$decoded = json_decode($res, TRUE);

$allTrains = $decoded['data'];

return $allTrains;
}

function trainLines($text){

    global $trainLines;

    $allTrainsResponse = $trainLines;

    //var_dump($allTrainsResponse);

    return $allTrainsResponse;
}

function checkTrainNames($text, $trains){
    foreach($trains as $trainKey => $trainValue){
        if(stripos($trainValue, $text) !== false)
            return $trainKey;
    }

    return null;
}

function route($stationName){

$routeInfo = getRouteInfoFromSeptaAPI($stationName);

if(array_key_exists('error', $routeInfo)){
    $routeResponse = "Station '" . $stationName . "' not found. Try using /septa stations";
}
else {
    $routeResponse = 'I see ' . count($routeInfo) . ' trains from Center City. ';
    // For each train
    foreach ($routeInfo as $key => $trainArray) {
        // Let's get the train number and destination
        $routeResponse .= 'Train ' . $trainArray['orig_train'];
        // Let's get the next stop
        $routeResponse .= ' scheduled to depart Jefferson at ' . $trainArray['orig_departure_time'] . ' ';
        // Let's get the lateness
        if( $trainArray['orig_delay'] == 'On time'){
            $routeResponse .= '(On Time). ' . PHP_EOL;
        } else {
           $routeResponse .= '(' . $trainArray['orig_delay'] . '). :fire_septa:' . PHP_EOL;
        }
    }
    //parse data in readable format
}
return $routeResponse;
}

function getRouteInfoFromSeptaAPI($stationName){
    $name = rawurlencode($stationName);
$ch = curl_init();
curl_setopt_array($ch, array(
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => 'http://www3.septa.org/hackathon/NextToArrive/Jefferson%20Station/' . $name. '/')
);
$res = curl_exec($ch);

if(curl_errno($ch)){

die('cUrl Error: ' . curl_errno($ch));
}

curl_close($ch);

$decoded = json_decode($res, TRUE);

//var_dump($decoded);
//die();
$routeData = $decoded;

return $routeData;


}

function getStationsFromAPI($lineKey){
// Set the train lines
$ch = curl_init();
curl_setopt_array($ch, array(
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => 'http://www.septastats.com/api/current/stations/')
);
$res = curl_exec($ch);

if(curl_errno($ch)){

die('cUrl Error: ' . curl_errno($ch));
}

curl_close($ch);

$decoded = json_decode($res, TRUE);

$allTrains = $decoded['data'];

return $allTrains;
}

function train($text){
    global $trainLines;

// Get train key from text sent in
    $trainResponse = '';
    $trainName = checkTrainNames($text, $trainLines);

    if($trainName === null){
        $trainResponse  = "Train Line '" . $text . "' not found. Try using /septa lines";
    }
    else {
        //$trainResponse = "Found '" . $text . "' As Line " . $trainName;

        // Call SeptaStats API using cURL
        // Create reply
        $data = getTrainsFromAPI($trainName);
        // If we got a response (the array is not null)

        // If we got a response (the array is not null)
if (!is_null($data)) {
    // Let's get a count of trains
    $trainResponse = 'I see ' . count($data) . ' trains. ';
    // For each train
    foreach ($data as $key => $trainArray) {
        // Let's get the train number and destination
        $trainResponse .= 'Train ' . $trainArray['id'] . '\'s ';
        // Let's get the next stop
        $trainResponse .= 'next stop is ' . $trainArray['nextstop'] . '. ';
        // Let's get the lateness
        if( $trainArray['late'] == 1){
            $trainResponse .= 'It is ' . $trainArray['late'] . ' minute late. ' . PHP_EOL;
        
        } else {
           $trainResponse .= 'It is ' . $trainArray['late'] . ' minutes late. ' . PHP_EOL;
        }
        // If you wanted to add a map
        // 'https://www.google.com/maps/place/' . $trainArray['lat'] . ' + ' . $trainArray['lon'] . '/';
    }
} else {
    // The user couldn't find their train, or they're being sassy
    $trainResponse = ':/ Found the line, but no data returned ';
}

        // Let's get a count of trains
        // Else
        // The user couldn't find their train, or they're being sassy

    }

return $trainResponse;
}


// Get input from user
if(isset($_POST['command'])){

$text = $_POST['text'];
$textExploded = explode(" ", $text, 2);
$commandName = $textExploded[0];
if(sizeof($textExploded) > 1){
    $remainingText = $textExploded[1];
}
else {
    $remainingText = '';
}

//$finalResponse = $commandName;

foreach ($commands as $name => $command) {
    if($commandName == $name){
        $response['command'] = $commandName;
        $finalResponse = call_user_func($command['function'], $remainingText);
    }
}

}
//var_dump($res);
//die();

// Send back json_encoded array
$response['text'] = $finalResponse;

// Flush the object
ignore_user_abort(true);
set_time_limit(0);
ob_start();
// do initial processing here
echo json_encode($response); // send the response
header('Connection: close');
header("Content-Type: application/json");
header('Content-Length: '.ob_get_length());
ob_end_flush();
ob_flush();
flush();
// Enjoy!