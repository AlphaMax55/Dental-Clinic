<?php
// www/site/inc/cevirmen.php

function t_cevir($metin) {
    global $db;
    
    // Eğer dil Türkçe ise veya metin boşsa direkt döndür
    if (!isset($_SESSION['dil']) || $_SESSION['dil'] !== 'en' || empty(trim($metin))) {
        return $metin;
    }
    
    // BELLEK (RAM) ÖNBELLEĞİ
    static $hafizadaki_ceviriler = null;
    
    // Sayfa ilk yüklendiğinde TÜM çevirileri TEK SEFERDE al
    if ($hafizadaki_ceviriler === null) {
        try {
            $sorgu = $db->query("SELECT metin_hash, ingilizce_metin FROM site_cevirileri");
            $hafizadaki_ceviriler = $sorgu->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
        } catch (Exception $e) {
            $hafizadaki_ceviriler = [];
        }
    }
    
    $hash = md5(trim($metin)); // Boşlukları temizle
    
    // 1. RAM'de var mı?
    if (isset($hafizadaki_ceviriler[$hash])) {
        return $hafizadaki_ceviriler[$hash];
    }
    
    // 2. Google Translate'e sor
    $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=tr&tl=en&dt=t&q=" . urlencode($metin);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 saniye timeout
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Bazı sunucular için
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200 || empty($response)) {
        return $metin; // Hata durumunda orijinal metni döndür
    }
    
    $sonuc = json_decode($response, true);
    $ingilizce_metin = "";
    
    if (isset($sonuc[0]) && is_array($sonuc[0])) {
        foreach ($sonuc[0] as $satir) {
            $ingilizce_metin .= $satir[0] ?? '';
        }
    }
    
    if (empty($ingilizce_metin)) {
        return $metin;
    }
    
    // 3. Veritabanına kaydet
    try {
        $ins = $db->prepare("INSERT INTO site_cevirileri (metin_hash, turkce_metin, ingilizce_metin) VALUES (?, ?, ?) 
                            ON DUPLICATE KEY UPDATE ingilizce_metin = VALUES(ingilizce_metin)");
        $ins->execute([$hash, $metin, $ingilizce_metin]);
        
        // RAM'e ekle
        $hafizadaki_ceviriler[$hash] = $ingilizce_metin;
    } catch (Exception $e) {
        // Sessizce geç, loglama isteğe bağlı
    }
    
    return $ingilizce_metin;
}
?>