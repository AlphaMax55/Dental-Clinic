<?php
// DEBUG - HATAYI GÖRMEK İÇİN
ini_set('display_errors', 1);
error_reporting(E_ALL);

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

// config.php yolu kontrol et
$config_path = dirname(__DIR__) . '/admin/includes/config.php';
if (!file_exists($config_path)) {
    echo json_encode(['success' => false, 'error' => 'config.php bulunamadı: ' . $config_path]);
    exit;
}

require_once $config_path;

try {
    $slug = $_GET['slug'] ?? '';
    
    if (empty($slug)) {
        echo json_encode(['success' => false, 'message' => 'Slug gerekli']);
        exit;
    }

    // Veritabanında slug var mı kontrol et
    $stmt = $db->prepare("SELECT COUNT(*) FROM tedaviler WHERE slug = ?");
    $stmt->execute([$slug]);
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        echo json_encode(['success' => false, 'message' => 'Slug bulunamadı: ' . $slug]);
        exit;
    }

    $stmt = $db->prepare("
        SELECT * FROM tedaviler 
        WHERE slug = ? AND aktif = 1 AND silindi = 0
    ");
    $stmt->execute([$slug]);
    $tedavi = $stmt->fetch();

    if (!$tedavi) {
        echo json_encode(['success' => false, 'message' => 'Tedavi bulunamadı (aktif/silindi kontrolü)']);
        exit;
    }

    // JSON alanlarını diziye çevir
    $tedavi['avantajlar'] = json_decode($tedavi['avantajlar_json'], true) ?? [];
    $tedavi['galeri'] = json_decode($tedavi['galeri_json'], true) ?? [];
    $tedavi['sss'] = json_decode($tedavi['sss_json'], true) ?? [];
    $tedavi['adimlar'] = json_decode($tedavi['adimlar_json'], true) ?? [];

    // Gereksiz alanları kaldır
    unset($tedavi['avantajlar_json']);
    unset($tedavi['galeri_json']);
    unset($tedavi['sss_json']);
    unset($tedavi['adimlar_json']);

    echo json_encode([
        'success' => true,
        'data' => $tedavi
    ], JSON_UNESCAPED_UNICODE);

} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
}
?>