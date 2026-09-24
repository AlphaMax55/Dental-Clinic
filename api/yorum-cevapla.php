<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
kontrol();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if(!isset($data['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $data['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Güvenlik hatası!']);
    exit;
}

$ust_id = intval($data['ust_id'] ?? 0);
$yorum = trim($data['yorum'] ?? '');
$ad_soyad = trim($data['ad_soyad'] ?? 'Prof. Dr. İbrahim Duran');
$email = trim($data['email'] ?? 'info@dribrahimdurandentalclinic.com');
$tedavi = $data['tedavi'] ?? 'Cevap';

if(!$ust_id || !$yorum) {
    echo json_encode(['success' => false, 'message' => 'Eksik bilgi!']);
    exit;
}

try {
    $stmt = $db->prepare("INSERT INTO yorumlar (ust_id, ad_soyad, email, yorum, tedavi, onay, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
    $stmt->execute([$ust_id, $ad_soyad, $email, $yorum, $tedavi]);
    
    echo json_encode(['success' => true, 'message' => 'Cevap gönderildi']);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>