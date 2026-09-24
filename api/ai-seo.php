<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once dirname(__DIR__, 2) . '/includes/config.php';

// Önce ayarlar tablosundan dene
$stmt = $db->prepare("SELECT deger FROM ayarlar WHERE anahtar = 'ai_seo'");
$stmt->execute();
$ai_seo = $stmt->fetchColumn();

// Eğer ayarlar tablosunda yoksa site_ayarlari tablosundan dene
if (!$ai_seo) {
    $stmt2 = $db->prepare("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'ai_seo'");
    $stmt2->execute();
    $ai_seo = $stmt2->fetchColumn();
}

// JSON formatını kontrol et ve düzelt
if ($ai_seo) {
    // JSON geçerli mi kontrol et
    $test_json = json_decode($ai_seo);
    if ($test_json === null) {
        // Geçersiz JSON, default değer gönder
        $default_json = [
            "@context" => "https://schema.org",
            "@type" => "Dentist",
            "name" => "Prof. Dr. İbrahim Duran",
            "alternateName" => "RivaDent Diş Kliniği",
            "url" => "adres gir",
            "telephone" => "+905052232343",
            "email" => "ibrahimdurandental@gmail.com"
        ];
        echo json_encode(['success' => true, 'data' => $default_json]);
        exit;
    }
}

echo json_encode(['success' => true, 'data' => $ai_seo]);
?>