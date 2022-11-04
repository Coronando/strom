<?php
//Check if we are running local or on the server
if($_SERVER['SERVER_NAME'] == "localhost"){
    //If we are running local, we need to include the config file
    $DEBUG = true;
}
else{
    $DEBUG = false;
}

//Get the access token from the config.json file
$ACCESS_TOKEN = json_decode(file_get_contents("config.json"), true)['ACCESS_TOKEN'];
$date = "01.01.2022";
$zone = "N02";
if($DEBUG){
    $url = "http://localhost:3000/api.php?access_key=" . $ACCESS_TOKEN . "&date=" . $date . "&zone=" . $zone;
}
// else{
//     $url = "https://strom.tangane.no/api.php?access_key=$ACCESS_TOKEN&date=$date&zone=$zone";
// }
$data=file_get_contents($url);
$expected_data_string = "[{x: 0,y: 132.89} ,{x: 1,y: 129.30} ,{x: 2,y: 132.08} ,{x: 3,y: 111.44} ,{x: 4,y: 112.35} ,{x: 5,y: 113.90} ,{x: 6,y: 122.25} ,{x: 7,y: 118.58} ,{x: 8,y: 118.47} ,{x: 9,y: 117.86} ,{x: 10,y: 120.03} ,{x: 11,y: 117.07} ,{x: 12,y: 117.17} ,{x: 13,y: 120.08} ,{x: 14,y: 124.54} ,{x: 15,y: 124.89} ,{x: 16,y: 135.59} ,{x: 17,y: 149.97} ,{x: 18,y: 150.71} ,{x: 19,y: 143.81} ,{x: 20,y: 140.36} ,{x: 21,y: 139.85} ,{x: 22,y: 135.08} ,{x: 23,y: 123.42} ]";
if($data == $expected_data_string){
    echo "Success";
}
else{
    echo "Error";
}

?>