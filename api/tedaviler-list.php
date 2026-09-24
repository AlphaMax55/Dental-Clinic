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
    $stmt = $db->prepare("
        SELECT id, slug, baslik, kategori, kisa_aciklama, image
        FROM tedaviler 
        WHERE aktif = 1 AND silindi = 0
        ORDER BY sira ASC, id ASC
    ");
    $stmt->execute();
    $tedaviler = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $tedaviler
    ], JSON_UNESCAPED_UNICODE);

} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
}
?>