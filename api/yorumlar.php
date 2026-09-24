<?php
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    http_response_code(200);
    exit();
}

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// config.php'den bağlantı al
require_once dirname(__DIR__) . '/admin/includes/config.php';

try {
    // Onaylı yorumları çek (TÜM SÜTUNLARI ÇEK)
    $stmt = $db->query("
        SELECT 
            id,
            ad_soyad,
            email,
            yorum,
            puan,
            tedavi,
            sehir,
            onay,
            DATE_FORMAT(created_at, '%d.%m.%Y') as tarih
        FROM yorumlar 
        WHERE onay = 1 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    
    $yorumlar = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $yorumlar
    ], JSON_UNESCAPED_UNICODE);

} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
}
?>