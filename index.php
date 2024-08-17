<?php
// Include the fetch_data.php script to ensure data is fetched and files are created
include 'fetch_data.php';
// Load the .env file
loadEnv(__DIR__ . '/.env');
// Now you can access the API key using getenv()
$apiKey = getenv('API_KEY');
$prices = getPricesForTodayAndTomorrow($apiKey);

// Function to remove the "_id" key from the data array
function removeIdKey($data) {
    if (is_array($data)) {
        foreach ($data as &$item) {
            if (isset($item['_id'])) {
                unset($item['_id']);
            }
        }
    }
    return $data;
}

// Remove the "_id" key from the data arrays
$day1 = removeIdKey($prices['today']);
$day2 = isset($prices['tomorrow']) ? removeIdKey($prices['tomorrow']) : removeIdKey($prices['yesterday']);

// Since $day1 and $day2 are arrays with one element, flatten them
$day1 = $day1[0];
$day2 = $day2[0];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Electricity Prices</title>
    <!-- Add the plot_styling.css -->
    <link rel="stylesheet" href="plot_styling.css">
</head>
<body>
    <div class="container">
        <h1>Electricity Prices</h1>
        <div class="charts">
            <div class="chart-container" id="left-chart-container">
                <h2 id="left-day-label"></h2>
                <canvas id="leftDayChart"></canvas>
                <div id="left-summary" class="summary"></div>
            </div>
            <div class="chart-container" id="right-chart-container">
                <h2 id="right-day-label"></h2>
                <canvas id="rightDayChart"></canvas>
                <div id="right-summary" class="summary"></div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const day1 = <?php echo json_encode($day1); ?>;
        const day2 = <?php echo json_encode($day2); ?>;

        const metrics = [
            { name: 'dailyPriceAverage', nickname: 'Average' },
            { name: 'dailyPriceMax', nickname: 'Max' },
            { name: 'dailyPriceMin', nickname: 'Min' },
            // With support
            { name: 'dailyPriceAverageWithSupport', nickname: 'Average (with support)' },
            { name: 'dailyPriceMaxWithSupport', nickname: 'Max (with support)' },
            { name: 'dailyPriceMinWithSupport', nickname: 'Min (with support)' },
        ];

        const today = new Date().toISOString().split('T')[0];
        const firstDayDate = new Date(day1.date).toISOString().split('T')[0];
        const secondDayDate = new Date(day2.date).toISOString().split('T')[0];

        const isTodayFirstDay = firstDayDate === today;
        const isTomorrowFirstDay = new Date(firstDayDate).getTime() > new Date(today).getTime();

        let leftDay, rightDay;
        let leftDayLabel, rightDayLabel;

        if (isTomorrowFirstDay) {
            leftDay = day2;
            rightDay = day1;
            leftDayLabel = 'Yesterday\'s';
            rightDayLabel = 'Tomorrow\'s';
        } else {
            leftDay = isTodayFirstDay ? day2 : day1;
            rightDay = isTodayFirstDay ? day1 : day2;
            leftDayLabel = isTodayFirstDay ? 'Yesterday\'s' : 'Today\'s';
            rightDayLabel = isTodayFirstDay ? 'Today\'s' : 'Tomorrow\'s';
        }

        document.getElementById('left-day-label').textContent = leftDayLabel;
        document.getElementById('right-day-label').textContent = rightDayLabel;

        function createChart(ctx, dailyPrices, label) {
            return new Chart(ctx, {
                type: 'line',
                data: {
                    labels: Array.from({ length: dailyPrices.length }, (_, i) => i),
                    datasets: [{
                        label: label,
                        data: dailyPrices,
                        borderColor: '#007BFF',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false,
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                title: function() {
                                    return 'Hour or Price'; // Customize the tooltip title
                                },
                                label: function(context) {
                                    const hour = context.label.toString().padStart(2, '0'); // Ensure 2 digits for hour
                                    const price = context.raw;   // Price at that hour
                                    return `Hour: ${hour}:00, Price: ${price}`;
                                }
                            },
                            titleFont: {
                                size: 30
                            },
                            bodyFont: {
                                size: 30
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        intersect: false
                    },
                    scales: {
                        x: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Hour',
                                font: {
                                    size: 30
                                }
                            },
                            ticks: {
                                font: {
                                    size: 25
                                },
                                callback: function(value) {
                                    return `${value.toString().padStart(2, '0')}:00`; // Display hours in 4-digit format (00:00, 01:00, etc.)
                                }
                            }
                        },
                        y: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Price',
                                font: {
                                    size: 30
                                }
                            },
                            ticks: {
                                font: {
                                    size: 25
                                }
                            }
                        }
                    }
                }
            });
        }
        Chart.defaults.font.size = 30;

        // Create charts
        createChart(document.getElementById('leftDayChart').getContext('2d'), leftDay.dailyPriceArray, leftDayLabel);
        createChart(document.getElementById('rightDayChart').getContext('2d'), rightDay.dailyPriceArray, rightDayLabel);

        // Function to create a summary box
        function createSummaryBox(parentElement, idPrefix, label, value, extra_class = '') {
            const box = document.createElement('div');
            box.className = 'box';
            if(extra_class){
                box.className += ' ' + extra_class;
            }
            const title = document.createElement('p');
            title.id = `${idPrefix}-title`;
            title.className = idPrefix.split('-')[1];
            title.textContent = label;

            const data = document.createElement('p');
            data.id = idPrefix;
            data.textContent = value;

            box.appendChild(title);
            box.appendChild(data);

            parentElement.appendChild(box);
        }

        // Function to create and populate summary data
        function createSummaryData(parentElementId, dayData, dayLabel, side, extra_class = '') {
            const parentElement = document.getElementById(parentElementId);
            parentElement.innerHTML = ''; // Clear existing content
            metrics.forEach(metric => {
                const idPrefix = `${side}-${metric.name}`;
                const label = `${dayLabel} ${metric.nickname}`;
                const value = dayData[metric.name];
                createSummaryBox(parentElement, idPrefix, label, value, extra_class);
            });
        }

        // Create summary data for both days
        createSummaryData('left-summary', leftDay, leftDayLabel, 'left');
        createSummaryData('right-summary', rightDay, rightDayLabel, 'right', 'right-summary');
    </script>
</body>
</html>