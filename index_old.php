<?php

//Sort out dates. The next day can be retrieved after 14:00
$date = new DateTime();
$interval = new DateInterval('P1D');

$yesterday = new $date;
$yesterday->sub($interval);
$yesterday = $yesterday->format("d.m.Y");
$tomorrow = new $date;
$tomorrow->add($interval);
$tomorrow = $tomorrow->format("d.m.Y");

$today = $date->format("d.m.Y");

//All the fetching and parsing is in a function so it can be called again
function fetch_and_parse_data_by_date_and_zone($date){
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

$today_data = fetch_and_parse_data_by_date_and_zone($today);
$tomorrow_data = fetch_and_parse_data_by_date_and_zone($tomorrow);




//Simple html part under this:
?>
<!DOCTYPE html>
<html>
<head>
    <title>Stromscrape</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.6.0/Chart.min.js"></script>
</head>
<body>
    <h1>Stromscrape</h1>
    <p>Today is <?php echo $today; ?>.</p>
    <p>Tomorrow is <?php echo $tomorrow; ?>.</p>
    <p>Yesterday was <?php echo $yesterday; ?>.</p>
    <p>Here is the data from tomorrow:</p>
    <!-- <p><?php //echo $done; ?></p> -->
    <!-- Make the two canvas next to eachother in a row -->
    <div style="display: flex;">
        <div style="flex: 50%; padding: 10px;">
            <canvas id="myChart"></canvas>
        </div>
        <div style="flex: 50%; padding: 10px;">
            <canvas id="myChart2"></canvas>
        </div>
    </div>
</body>
<script>
    //Get the data from php
    var jsonfile_day1 = {
        "jsonarray": <?php echo $today_data; ?>
    };
    //Make labels for the x and y axis
    var labels_day1 = jsonfile_day1.jsonarray.map(function(e) {
        return e.x;
    });
    var data_day1 = jsonfile_day1.jsonarray.map(function(e) {
        return e.y;
    });;

    //Make eqivalent for tomorrow
    var jsonfile_day2 = {
        "jsonarray": <?php echo $tomorrow_data; ?>
    };
    //Chekc if the data is empty
    if(jsonfile_day2.jsonarray == 0){
        var labels_day2 = [0];
        var data_day2 = [0];
    }
    else{
        var labels_day2 = jsonfile_day2.jsonarray.map(function(e) {
            return e.x;
        });
        var data_day2 = jsonfile_day2.jsonarray.map(function(e) {
            return e.y;
        });
    }

    //Get the canvas elements
    var ctx = document.getElementById("myChart").getContext('2d');
    var ctx2 = document.getElementById("myChart2").getContext('2d');

    var config = {
        type: 'line',
        data: {
            labels: labels_day1,
            datasets: [{
                label: 'Strømpris gjennom dagen: <?php echo $today; ?>',
                data: data_day1,
                backgroundColor: 'rgba(0, 119, 204, 0.3)'
            }]
        }
    };
    //make another config for the secound chart
    if(data_day2.length > 0){
        var config2 = {
            type: 'line',
            data: {
                labels: labels_day2,
                datasets: [{
                    label: 'Strømpris gjennom dagen: <?php echo $tomorrow; ?>',
                    data: data_day2,
                    backgroundColor: 'rgba(0, 119, 204, 0.3)'
                }]
            }
        };
    }
    else{
        //Write "Data is pending" if there is no data for tomorrow
        var config2 = {
            type: 'line',
            data: {
                labels: ["Data is pending"],
                datasets: [{
                    label: 'Strømpris gjennom dagen: <?php echo $tomorrow; ?>',
                    data: [0],
                    backgroundColor: 'rgba(0, 119, 204, 0.3)'
                }]
            }
        };
        ctx2.font = '48px serif';
        ctx2.fillText('Data is pending...', 50, 50);

    }

    // var config2 = {
    //     type: 'line',
    //     data: {
    //         labels: labels_day2,
    //         datasets: [{
    //             label: 'Strømpris gjennom dagen: <?php echo $tomorrow; ?>',
    //             data: data_day2,
    //             backgroundColor: 'rgba(50, 119, 100, 0.3)'
    //         }]
    //     }
    // };

    var chart = new Chart(ctx, config);
    var chart2 = new Chart(ctx2, config2);
</script>
