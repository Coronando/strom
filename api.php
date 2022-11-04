<?php
//Requires and includes
require("core_functions.php");

//Takes inn two get parameters date and zone
//Returns the data for the given date and zone
//If no date is given, it will return the data for today
//If no zone is given, it will return the data for zone N02
//If no date or zone is given, it will return the data for today and zone NO2
//If the date is not a valid date, it will return the data for today
//If the zone is not a valid zone, it will return the data for zone N02
//If the date is not a valid date and the zone is not a valid zone, it will return the data for today and zone 1


//If there is not given an access key, it will return an error message
if(!isset($_GET['access_key'])){
    echo "Error: No access key given";
    exit();
}
//Check if the key corresponds with ACCESS_TOKEN form the config.json file
$ACCESS_TOKEN = json_decode(file_get_contents("config.json"), true)['ACCESS_TOKEN'];
if($_GET['access_key'] != $ACCESS_TOKEN){
    echo "Error: Wrong access key";
    exit();
}

//Get the date and check it's validity
if(!isset($_GET["date"])){
    $date = new DateTime();
    $date = $date->format("d.m.Y");
}
else{
    //Check if the date is valid
    $date = DateTime::createFromFormat("d.m.Y", $_GET["date"]);
    if($date == false){
        $date = new DateTime();
        $date = $date->format("d.m.Y");
    }
    else{
        $date = $date->format("d.m.Y");
    }
}


// NB: Zones do not currently work as expected in the fetch_and_parse_data_by_date_and_zone function. This is because
// the link is not so easy to parse. This will be fixed in the future.
$valid_zones = ["N01", "N02", "N03","N04","N05"];
//Check and validate the zone
if(!isset($_GET["zone"])){
    $zone = "N02";
}
else{
    if(in_array($_GET["zone"], $valid_zones)){
        $zone = $_GET["zone"];
    }
    else{
        $zone = "N02";
    }
}

//Get the data for the given date and zone
$data = fetch_and_parse_data_by_date_and_zone($date, $zone);
print((string)$data)


?>