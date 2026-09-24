<?php
header('Content-Type: application/json');

$access_token = 'ya29.a0Aa7MYioS5hFadC2okYcx8gL5knFXobWfeFJy1AW8rZqnsRW9usRAmGcdVrsWSzpdjS7bccNezkDAsXilWaPFPZZbs1JsUkSce5XgEGC5CDY-y7uAKD8GR3_V1FJ9odkR0nJN9Kw5_nEu3X2upotuYIjELRtDQBt6SbpkgQA3_qwxNvjMLzTGbwtYZoeFvsbUFXUDv0caCgYKARMSARASFQHGX2MiwKQZBn7VXa373kO5cXPXA0206';

$siteUrl = 'Adres Gir';
$startDate = '2025-01-01';
$endDate = '2025-03-31';

$request_body = json_encode([
    'startDate' => $startDate,
    'endDate' => $endDate,
    'dimensions' => ['query', 'page', 'device'],
    'rowLimit' => 10
]);

$ch = curl_init("https://www.googleapis.com/webmasters/v3/sites/" . urlencode($siteUrl) . "/searchAnalytics/query");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $access_token,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $request_body);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: " . $http_code . "\n\n";
echo $response;
?>