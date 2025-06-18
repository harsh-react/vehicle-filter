<?php

$apiKey = '*********************';
$packageName = 'VehicleDetails';
$vrm = 'MR09RAJ';

$url = 'https://uk.api.vehicledataglobal.com/r2/lookup?' . http_build_query([
    'packageName' => $packageName,
    'apikey' => $apiKey,
    'vrm' => $vrm
]);

// Initiate cURL
$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json'
    ]
]);

// Execute the request
$response = curl_exec($ch);
$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Check for CURL errors
if (curl_errno($ch)) {
    echo 'Curl Error: ' . curl_error($ch);
    curl_close($ch);
    exit;
}

// Check the HTTP response status
if ($status_code !== 200) {
    echo "API responded with HTTP status code: {$status_code}\n";
    echo "Response: {$response}";
    curl_close($ch);
    exit;
}

// Close cURL
curl_close($ch);

// Decode and use the response
$data = json_decode($response, true);

echo "<script>console.log(" . json_encode($data, JSON_UNESCAPED_SLASHES) . ");</script>";
echo "<pre>";
print_r($data);
