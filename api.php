<?php
//Takes inn two get parameters date and zone
//Returns the data for the given date and zone
//If no date is given, it will return the data for today
//If no zone is given, it will return the data for zone N02
//If no date or zone is given, it will return the data for today and zone NO2
//If the date is not a valid date, it will return the data for today
//If the zone is not a valid zone, it will return the data for zone N02
//If the date is not a valid date and the zone is not a valid zone, it will return the data for today and zone 1

//Get the date and zone from the get parameters
$date = $_GET["date"];
$zone = $_GET["zone"];

//If no date is given, set the date to today
if($date == ""){
    $date = date("Y-m-d");
}
//Check if the date is valid, if not, set it to today
if(!checkdate(date("m", strtotime($date)), date("d", strtotime($date)), date("Y", strtotime($date)))){
    $date = date("Y-m-d");
}


$valid_zones = ["N01", "N02", "N03","N04","N05"];
//Check if zone is given, if not, set it to zone N02
if($zone == ""){
    $zone = "N02";
}  
//Check if the zone is valid, if not, set it to zone N02
if(!in_array($zone, $valid_zones)){
    $zone = "N02";
}




?>