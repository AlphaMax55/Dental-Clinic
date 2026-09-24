<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
kontrol();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if(!$data) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz veri formatı']);
    exit;
}

if(!isset($data['csrf_token']) || !isset($<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
kontrol();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if(!$data) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz veri formatı']);
    exit;
}

if(!isset($data['csrf_token']) || !isset($_SESSION['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Güvenlik hatası!']);
    exit;
}

$ust_id = intval($data['ust_id'] ?? 0);
$ad_soyad = trim($data['ad_soyad'] ?? '');
$yorum = trim($data['yorum'] ?? '');
$email = trim($data['email'] ?? 'admin@adres gir');
$tedavi = $data['tedavi'] ?? 'Cevap';

if(!$ust_id || !$yorum || !$ad_soyad) {
    echo json_encode(['success' => false, 'message' => 'Eksik bilgi!']);
    exit;
}

// ADMIN CEVABI - DİREKT ONAYLI (onay = 1)
$stmt = $db->prepare("INSERT INTO yorumlar (ust_id, ad_soyad, email, yorum, tedavi, onay, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
$stmt->execute([$ust_id, $ad_soyad, $email, $yorum, $tedavi]);

echo json_encode(['success' => true, 'message' => 'Cevap başarıyla gönderildi!']);
?>