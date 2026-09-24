<?php

header('Content-Type: application/json; charset=utf-8');

// =====================================================
// GOOGLE PLACES API
// =====================================================

$placesApiKey = 'Api Gir';
$placeId = 'İd Gir';

// =====================================================
// GOOGLE CLOUD TRANSLATION API
// =====================================================

$translateApiKey = 'Api Gir';


// =====================================================
// GOOGLE PLACES API'DEN YORUMLARI AL
// =====================================================

$url = "https://maps.googleapis.com/maps/api/place/details/json?" .
       http_build_query([
           'place_id'    => $placeId,
           'fields'      => 'reviews,rating,user_ratings_total',
           'reviews_sort'=> 'newest',
           'language'    => 'tr',
           'key'         => $placesApiKey
       ]);

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);

if ($response === false) {

    $curlError = curl_error($ch);
    curl_close($ch);

    echo json_encode([
        'error' => 'Google Places bağlantı hatası.',
        'message' => $curlError
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);


// =====================================================
// PLACES API HATA KONTROLÜ
// =====================================================

if ($httpCode != 200 || empty($response)) {

    echo json_encode([
        'error' => 'Google Places API isteği başarısız.',
        'http_code' => $httpCode
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


$data = json_decode($response, true);

if (!$data) {

    echo json_encode([
        'error' => 'Google Places API geçersiz JSON döndürdü.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


// Google API kendi hata durumunu döndürmüşse
if (
    isset($data['status']) &&
    $data['status'] !== 'OK'
) {

    echo json_encode($data, JSON_UNESCAPED_UNICODE);

    exit;
}


// =====================================================
// TÜRKÇEYE ÇEVİRME FONKSİYONU
// =====================================================

function translateToTurkish($text, $apiKey)
{
    if (empty(trim($text))) {
        return $text;
    }

    $url = 'https://translation.googleapis.com/language/translate/v2';

    $postData = [
        'q'      => $text,
        'target' => 'tr',
        'format' => 'text',
        'key'    => $apiKey
    ];


    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);

    if ($response === false) {

        curl_close($ch);

        return $text;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);


    if ($httpCode != 200 || empty($response)) {
        return $text;
    }


    $result = json_decode($response, true);


    if (
        isset($result['data']['translations'][0]['translatedText'])
    ) {

        return html_entity_decode(
            $result['data']['translations'][0]['translatedText'],
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );
    }


    // Çeviri başarısız olursa orijinal yorumu göster
    return $text;
}


// =====================================================
// YORUMLARI TÜRKÇEYE ÇEVİR
// =====================================================

if (
    isset($data['result']['reviews']) &&
    is_array($data['result']['reviews'])
) {

    foreach ($data['result']['reviews'] as &$review) {

        if (
            isset($review['text']) &&
            !empty(trim($review['text']))
        ) {

            // Orijinal yorumu sakla
            $review['original_text'] = $review['text'];

            // Türkçeye çevir
            $review['text'] = translateToTurkish(
                $review['text'],
                $translateApiKey
            );
        }
    }

    unset($review);
}


// =====================================================
// SONUCU JAVASCRIPT'E GÖNDER
// =====================================================

echo json_encode(
    $data,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);

?>