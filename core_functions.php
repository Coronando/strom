<?php

//All the fetching and parsing is in a function so it can be called again
function fetch_and_parse_data_by_date($date){
    //Link to data with from the N02 price zone.
    $url='https://transparency.entsoe.eu/transmission-domain/r2/dayAheadPrices/show?name=&defaultValue=false&viewType=GRAPH&areaType=BZN&atch=false&dateTime.dateTime=' . strval($date) . '+00:00|CET|DAY&biddingZone.values=CTY|10YNO-0--------C!BZN|10YNO-2--------T&resolution.values=PT15M&resolution.values=PT30M&resolution.values=PT60M&dateTime.timezone=CET_CEST&dateTime.timezone_input=CET+(UTC+1)+/+CEST+(UTC+2)';
    
    //file_get_contents() reads remote webpage content
    $lines_string=file_get_contents($url);
    $parsed = htmlspecialchars($lines_string);
    $key = "&quot;,&quot;showSecondaryYAxis&quot;:false,&quot;showSecondaryYAxisLabels&quot;:true,&quot;showWholeGraph&quot;:true,&quot;showCursor&quot;:true,&quot;cursorTextColor&quot;:&quot;#000&quot;,&quot;cursorColor&quot;:&quot;#FDC400&quot;,&quot;columnWidth&quot;:0.8,&quot;columnSpacing&quot;:5,&quot;showGraphTitles&quot;:true,&quot;customBalloonValues&quot;:false,&quot;axisAbsoluteValuesLabels&quot;:false,&quot;autoMargins&quot;:true,&quot;marginTop&quot;:20,&quot;marginBottom&quot;:20,&quot;marginLeft&quot;:20,&quot;marginRight&quot;:20,&quot;showNoDataLabel&quot;:false},&quot;chartData&quot;:";
    
    //Delete everything before $key
    $parsed2 = substr($parsed, strpos($parsed, $key)+strlen($key));
    $key2 = "T&resolution.values=PT15M&resolution.values=PT30M&resolution.values=PT60M&dateTime.timezone=CET_CEST&dateTime.timezone_input=CET+(UTC+1)+/+CEST+(UTC+2)";
    
    //Delete before after $key2
    $parsed3 = substr($parsed2, strpos($parsed2, $key2));
    $parsed3 = str_replace("&quot;","\"",$parsed3);
    $parsed3 = preg_replace("/&#?[a-z0-9]+;/i",'',$parsed3);
    $key3 = "}]}";
    
    //Delete everything after $key3 and $key3
    $parsed4 = substr($parsed3, 0,strpos($parsed3, $key3)+2);
    
    //replace "cat" with "Time" in $parsed 4
    $parsed4 = str_replace("cat", "Time", $parsed4);
    
    //replace "val1" with "Price" in $parsed 4
    $done = str_replace("val1", "Price", $parsed4);
    $test = json_decode($done, true);

    
    //Create a new array with only the time and price
    $parsed_data = "[";
    //Check if the data is empty
    if($test == 0){
        $parsed_data = 0;
    }
    else{
        foreach ($test as $key => $value) {
            $row = "";
            foreach ($value as $key2 => $value2) {
                if($key2 == "Time"){
                    $row = $row . "{x: " . $key . ",";
                }
                if($key2 == "Price"){
                    $row = $row . "y: " . $value2 . "} ";
                }
            }
            $row = $row . ",";
            $parsed_data = $parsed_data . $row;
        }
        $parsed_data = substr($parsed_data, 0, -1);
        $parsed_data = $parsed_data . "]";
    }

    return $parsed_data;
}

?>