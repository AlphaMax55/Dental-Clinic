<?php
// ============================================================
// indexnow.php - IndexNow Bildirim Fonksiyonu (SADECE TEK)
// ============================================================

function indexNowBildir($guncellenen_url) {
    $host = "www.dribrahimdurandentalclinic.com";
    $key = "d7f5e8b3a2c1f4e5d6c7b8a9f0e1d2c3";
    $keyLocation = "https://www.dribrahimdurandentalclinic.com/" . $key . ".txt";

    // Tek URL'yi diziye çevir
    if (!is_array($guncellenen_url)) {
        $guncellenen_url = [$guncellenen_url];
    }

    $data = [
        "host" => $host,
        "key" => $key,
        "keyLocation" => $keyLocation,
        "urlList" => $guncellenen_url
    ];

    $json = json_encode($data);

    $ch = curl_init("https://api.indexnow.org/indexnow");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json; charset=utf-8'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    // Log tut
    $log_dir = __DIR__ . '/../logs/';
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log = date('Y-m-d H:i:s') . " - " . implode(', ', $guncellenen_url) . " - HTTP: $http_code\n";
    if ($http_code === 200) {
        file_put_contents($log_dir . 'indexnow_success.log', $log, FILE_APPEND);
    } else {
        file_put_contents($log_dir . 'indexnow_errors.log', $log . " - HATA: $error\n", FILE_APPEND);
    }

    return ['success' => ($http_code === 200), 'http_code' => $http_code];
}

// ============================================================
// TEK BİR URL GÖNDER (SADECE BU KULLANILACAK)
// ============================================================
function indexNowTekliGonder($url) {
    return indexNowBildir([$url]);
}
?>