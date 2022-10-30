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

$url='https://transparency.entsoe.eu/transmission-domain/r2/dayAheadPrices/show?name=&defaultValue=false&viewType=GRAPH&areaType=BZN&atch=false&dateTime.dateTime=' . strval($tomorrow) . '+00:00|CET|DAY&biddingZone.values=CTY|10YNO-0--------C!BZN|10YNO-2--------T&resolution.values=PT15M&resolution.values=PT30M&resolution.values=PT60M&dateTime.timezone=CET_CEST&dateTime.timezone_input=CET+(UTC+1)+/+CEST+(UTC+2)';
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
//echo json_encode($test, JSON_PRETTY_PRINT);
//var_dump($test);
//Print the data
$arr = "[";
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
    $arr = $arr . $row;

    //echo strval($key) . " " . strval($value) . "\n";
}
$arr = substr($arr, 0, -1);
$arr = $arr . "]";
//echo $arr;
//echo json_encode($parsed4);
//echo json_encode($parsed4);
//convert the json sting into an array of times and their prices
//$prices = json_decode(json_encode($parsed4));
//var_dump($prices);

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
    <canvas id="canvas"></canvas>
</body>
<script>
    var jsonfile = {
        "jsonarray": <?php echo $arr; ?>
    };

    var labels = jsonfile.jsonarray.map(function(e) {
        return e.x;
    });
    var data = jsonfile.jsonarray.map(function(e) {
        return e.y;
    });;

    var ctx = canvas.getContext('2d');
    var config = {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Strømpris gjennom dagen: <?php echo $tomorrow; ?>',
            data: data,
            backgroundColor: 'rgba(0, 119, 204, 0.3)'
        }]
    }
    };

    var chart = new Chart(ctx, config);
</script>
