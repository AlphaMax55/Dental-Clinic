<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once dirname(__DIR__) . '/admin/includes/config.php';

$input = json_decode(file_get_contents('php://input'), true);

$ad_soyad = isset($input['ad_soyad']) ? trim($input['ad_soyad']) : '';
$telefon = isset($input['telefon']) ? trim($input['telefon']) : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$konu = isset($input['konu']) ? trim($input['konu']) : '';
$mesaj = isset($input['mesaj']) ? trim($input['mesaj']) : '';

// Hata ayıklama için log
file_put_contents('randevu_log.txt', date('Y-m-d H:i:s') . ' - Gelen veri: ' . print_r($input, true) . "\n", FILE_APPEND);

if (empty($ad_soyad) || empty($telefon) || empty($mesaj)) {
    echo json_encode(['success' => false, 'message' => 'Lütfen ad, telefon ve mesaj alanlarını doldurun']);
    exit;
}

try {
    // Randevular tablosu yoksa oluştur
    $db->exec("CREATE TABLE IF NOT EXISTS `randevular` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `ad_soyad` varchar(100) NOT NULL,
        `telefon` varchar(20) NOT NULL,
        `email` varchar(100) DEFAULT NULL,
        `konu` varchar(100) DEFAULT NULL,
        `mesaj` text,
        `durum` enum('bekliyor','onaylandi','iptal') DEFAULT 'bekliyor',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    $stmt = $db->prepare("INSERT INTO randevular (ad_soyad, telefon, email, konu, mesaj, durum, created_at) VALUES (?, ?, ?, ?, ?, 'bekliyor', NOW())");
    $result = $stmt->execute([$ad_soyad, $telefon, $email, $konu, $mesaj]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Mesajınız başarıyla gönderildi! En kısa sürede dönüş yapılacaktır.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Veritabanına eklenirken hata oluştu']);
    }
} catch (PDOException $e) {
    file_put_contents('randevu_log.txt', date('Y-m-d H:i:s') . ' - Hata: ' . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Teknik bir sorun oluştu: ' . $e->getMessage()]);
}
?>