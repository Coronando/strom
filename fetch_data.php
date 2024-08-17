<?php

function fetchPrices($url, $apiKey) {
    $ch = curl_init();
    
    // Set the curl options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'api-key: ' . $apiKey
    ]);

    // Execute the request
    $output = curl_exec($ch);
    curl_close($ch);

    return $output;
}

function savePricesToFile($data, $date) {
    $fileName = "electricity_prices_$date.json";
    file_put_contents($fileName, $data);
    return $fileName;
}

function getUpdatedPrices($apiKey) {
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('tomorrow'));

    $todayFileName = "electricity_prices_$today.json";
    $tomorrowFileName = "electricity_prices_$tomorrow.json";

    // If we have passed midnight, the tomorrow data is now valid for today.
    if (date('H') < 14) {
        // Check if yesterday's file exists
        $yesterday = date('Y-m-d', strtotime('yesterday'));
        $yesterdayFileName = "electricity_prices_$yesterday.json";

        if (!file_exists($yesterdayFileName)) {
            $yesterdayData = fetchPrices("https://api.strompriser.no/public/prices-today?country=Norway&region=2", $apiKey);
            savePricesToFile($yesterdayData, $yesterday);
        }

        // Ensure today's data is saved
        if (!file_exists($todayFileName)) {
            $todayData = fetchPrices("https://api.strompriser.no/public/prices-today?country=Norway&region=2", $apiKey);
            savePricesToFile($todayData, $today);
        }

        return [
            'yesterday' => json_decode(file_get_contents($yesterdayFileName), true),
            'today' => json_decode(file_get_contents($todayFileName), true)
        ];
    } else {
        // Save today's data if not already saved
        if (!file_exists($todayFileName)) {
            $todayData = fetchPrices("https://api.strompriser.no/public/prices-today?country=Norway&region=2", $apiKey);
            savePricesToFile($todayData, $today);
        }

        // Save tomorrow's data if not already saved
        if (!file_exists($tomorrowFileName)) {
            $tomorrowData = fetchPrices("https://api.strompriser.no/public/prices-tomorrow?country=Norway&region=2", $apiKey);
            savePricesToFile($tomorrowData, $tomorrow);
        }

        return [
            'today' => json_decode(file_get_contents($todayFileName), true),
            'tomorrow' => json_decode(file_get_contents($tomorrowFileName), true)
        ];
    }
}

// Usage example:

function loadEnv($path)
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Skip comments
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        putenv("$name=$value");
    }
}

// Load the .env file
loadEnv(__DIR__ . '/.env');

// Now you can access the API key using getenv()
$apiKey = getenv('API_KEY');
$prices = getUpdatedPrices($apiKey);

//print_r($prices);