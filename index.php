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
//Check if the hour is after 13 and minutes are more then 05
if($date->format("H") >= 13 && $date->format("i") > 05){
    $first_day = fetch_and_parse_data_by_date($today);
    $second_day = fetch_and_parse_data_by_date($tomorrow);
    //Make corresponding dates
    $first_day_date = $today;
    $second_day_date = $tomorrow;
    $first_day_date_string = "Today";
    $second_day_date_string = "Tomorrow";

}
else{
    $first_day = fetch_and_parse_data_by_date($yesterday);
    $second_day = fetch_and_parse_data_by_date($today);
    //Make corresponding dates
    $first_day_date = $yesterday;
    $second_day_date = $today;
    $first_day_date_string = "Yesterday";
    $second_day_date_string = "Today";
}

# Function to get the average price for a given months so far.
function get_a_months_avererage_price($month){
    //Find todays day
    $date = new DateTime();

    $month = strval($month);
    $month = str_pad($month, 2, "0", STR_PAD_LEFT);
    // If the month is this month, get the average price for the month so far.
    if($month == $date->format("m")){
        $day = $date->format("d");
    }
    // If the month is not this month, find how many days there are in the month.
    if($month != $date->format("m")){
        $day = cal_days_in_month(CAL_GREGORIAN, (int)$month, $date->format("Y"));
    } 

    //Make an array of all the days in the month (or month so far)
    $days = array();
    for($i = 1; $i <= $day; $i++){
        $i = str_pad($i, 2, "0", STR_PAD_LEFT);
        $days[] = $i;
    }
    //Make an array of all the prices for month (or month so far)
    $prices = array();
    foreach($days as $day){
        $date_to_fetch = $day . "-" . $month . "-" . $date->format("Y");
        $price = fetch_and_parse_data_by_date($date_to_fetch);
        print($price);
        //$price = json_decode($price, true);
        // Add the price to the array
        //var_dump($price);
        //echo $prices[(int)$day];
    }
    //var_dump($prices);
    //echo "Hello";

    //Find the average price
    // $total = 0;
    // $count = 0;
    // foreach($prices as $price){
    //     print($price);
    //     /*if($price != 0){
    //         $total = $total + $price;
    //         $count = $count + 1;
    //     }*/
    // }
    $average = 1;
    //$average = $total / $count;
    return $average;
}
//$average = get_a_months_avererage_price("11");




//Simple html part under this:
?>
<!DOCTYPE html>
<html>
<head>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.6.0/Chart.min.js"></script>
    <style>
        body{
            background-color: #B2B2B2;
            width: 100%;
            text-align: center;
        }
        nav{
            background-color: #00ABB3;
            height: 50px;
        }
        div{
            text-align: center;
        }
        canvas {
            padding: 0;
            margin: auto;
            display: block;
            width: 80% !important;
            height: 50vh !important;
            background-color: #EAEAEA;
        }
    </style>
</head>
<body>
    <h1> Strømpriser </h1>
    <p>Yesterday: <?php echo $yesterday; ?>.</p>
    <p>Today: <?php echo $today; ?>.</p>
    <p>Tomorrow: <?php echo $tomorrow; ?>.</p>
    <div>
        <canvas id="myChart"></canvas>
    </div>
</body>
<script>
    //Get the data from php
    var jsonfile_day1 = {
        "jsonarray": <?php echo $first_day; ?>
    };
    var jsonfile_day2 = {
        "jsonarray": <?php echo $second_day; ?>
    };
    //combine the two arrays
    var jsonfile = jsonfile_day1.jsonarray.concat(jsonfile_day2.jsonarray);
    console.log(jsonfile);
    //Make labels for the x and y axis
    var labels = [];
    var data = [];
    //Loop through the array and add the data to the labels and data arrays
    for(var i = 0; i < jsonfile.length; i++){
        labels.push(jsonfile[i].x);
        data.push(jsonfile[i].y);
    }

    const verticalLinePlugin = {
        getLinePosition: function (chart, pointIndex) {
            const meta = chart.getDatasetMeta(0); // first dataset is used to discover X coordinate of a point
            const data = meta.data;
            return data[pointIndex]._model.x;
        },
        renderVerticalLine: function (chartInstance, pointIndex) {
            const lineLeftOffset = this.getLinePosition(chartInstance, pointIndex);
            const scale = chartInstance.scales['y-axis-0'];
            const context = chartInstance.chart.ctx;

            // render vertical line
            context.beginPath();
            context.strokeStyle = '#000000';
            context.moveTo(lineLeftOffset, scale.top);
            context.lineTo(lineLeftOffset, scale.bottom);
            context.stroke();

            // write label
            context.fillStyle = "#000000";
            context.textAlign = 'right';
            context.fillText('<?php echo $first_day_date ?>', lineLeftOffset-10, (scale.bottom - scale.top) / 10 + scale.top);
            context.fillText('<?php echo $first_day_date_string ?>', lineLeftOffset-10, (scale.bottom - scale.top) / 10 + scale.top-23);
            context.textAlign = 'left';
            context.fillText('<?php echo $second_day_date ?>', lineLeftOffset+10, (scale.bottom - scale.top) / 10 + scale.top);
            context.fillText('<?php echo $second_day_date_string ?>', lineLeftOffset+10, (scale.bottom - scale.top) / 10 + scale.top-23);

        },

        afterDatasetsDraw: function (chart, easing) {
            if (chart.config.lineAtIndex) {
                chart.config.lineAtIndex.forEach(pointIndex => this.renderVerticalLine(chart, pointIndex));
            }
        }
    };


    //Get the canvas elements
    var ctx = document.getElementById("myChart").getContext('2d');
    
    var config = {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Strømpris gjennom dagen: <?php echo $first_day_date . " and " . $second_day_date; ?> sone NO2',
                data: data,
                backgroundColor: 'rgba(0, 119, 204, 0.3)'
            }]
        },
        lineAtIndex: [24],
        plugins: [verticalLinePlugin],
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        // This more specific font property overrides the global property
                        font: {
                            size: 25
                        }
                    }
                }
            },
            scales: {
                yAxes: [{
                    ticks: {
                        fontSize: 20
                    }
                }],
                xAxes: [{
                    ticks: {
                        fontSize: 15
                    }
                }]
            }
        },
    };
    Chart.defaults.global.defaultFontSize = 20;
    var chart = new Chart(ctx, config);
    

</script>
