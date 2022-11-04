<?php
//Includes and requires
require("core_functions.php");

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


//Check if the hour is after 13 and minutes are more then 05
if($date->format("H") >= 13 && $date->format("i") > 05){
    $first_day = fetch_and_parse_data_by_date_and_zone($today);
    $second_day = fetch_and_parse_data_by_date_and_zone($tomorrow);
    //Make corresponding dates
    $first_day_date = $today;
    $second_day_date = $tomorrow;
    $first_day_date_string = "Today";
    $second_day_date_string = "Tomorrow";

}
else{
    $first_day = fetch_and_parse_data_by_date_and_zone($yesterday);
    $second_day = fetch_and_parse_data_by_date_and_zone($today);
    //Make corresponding dates
    $first_day_date = $yesterday;
    $second_day_date = $today;
    $first_day_date_string = "Yesterday";
    $second_day_date_string = "Today";
}

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
