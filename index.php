<?php
// Include the fetch_data.php script to ensure data is fetched and files are created
include 'fetch_data.php';
// Load the .env file
loadEnv(__DIR__ . '/.env');
// Now you can access the API key using getenv()
$apiKey = getenv('API_KEY');
$prices = getUpdatedPrices($apiKey);

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

// Function to apply 25% tax to all price-related fields
function applyTax($data) {
    if (is_array($data)) {
        foreach ($data as &$item) {
            // Apply 25% tax to each price in the dailyPriceArray
            if (isset($item['dailyPriceArray'])) {
                $item['dailyPriceArray'] = array_map(fn($price) => $price * 1.25, $item['dailyPriceArray']);
            }
            // Apply 25% tax to the relevant metrics
            $item['dailyPriceAverage'] = $item['dailyPriceAverage'] * 1.25;
            $item['dailyPriceMax'] = $item['dailyPriceMax'] * 1.25;
            $item['dailyPriceMin'] = $item['dailyPriceMin'] * 1.25;
            if (isset($item['dailyPriceAverageWithSupport'])) {
                $item['dailyPriceAverageWithSupport'] = $item['dailyPriceAverageWithSupport'] * 1.25;
            }
            if (isset($item['dailyPriceMaxWithSupport'])) {
                $item['dailyPriceMaxWithSupport'] = $item['dailyPriceMaxWithSupport'] * 1.25;
            }
            if (isset($item['dailyPriceMinWithSupport'])) {
                $item['dailyPriceMinWithSupport'] = $item['dailyPriceMinWithSupport'] * 1.25;
            }
        }
    }
    return $data;
}

// Remove the "_id" key from the data arrays
$day1 = removeIdKey($prices['today']);
$day2 = isset($prices['tomorrow']) ? removeIdKey($prices['tomorrow']) : removeIdKey($prices['yesterday']);

// Apply 25% tax to the data arrays
$day1 = applyTax($day1);
$day2 = applyTax($day2);

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

        // Get today's date in 'YYYY-MM-DD' format
        const today = new Date().toISOString().split('T')[0];

        // Get the dates for day1 and day2 in 'YYYY-MM-DD' format
        const firstDayDate = new Date(day1.date).toISOString().split('T')[0];
        const secondDayDate = new Date(day2.date).toISOString().split('T')[0];

        // Check if today is the first day
        const isTodayFirstDay = firstDayDate === today;

        // Check if the first day is in the future (i.e., tomorrow or later)
        const isFirstDayInFuture = new Date(firstDayDate).getTime() > new Date(today).getTime();

        // Initialize variables for left and right days and their labels
        let leftDay, rightDay;
        let leftDayLabel, rightDayLabel;

        if (isFirstDayInFuture) {
            leftDay = day2;
            rightDay = day1;
            leftDayLabel = "Yesterday's";
            rightDayLabel = "Tomorrow's";
        } else {
            if (!isTodayFirstDay) {
                leftDay = day2;
                rightDay = day1;
                leftDayLabel = "Yesterday's";
                rightDayLabel = "Today's";
            } else {
                rightDay = day2;
                leftDay = day1;
                leftDayLabel = "Today's";
                rightDayLabel = "Tomorrow's";
            }
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


        console.log(leftDay);
        console.log(rightDay);
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