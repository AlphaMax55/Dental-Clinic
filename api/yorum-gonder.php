<?php
require_once '../inc/config.php';
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if(!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Güvenlik hatası!']);
    exit;
}

$ad_soyad = trim($data['ad_soyad'] ?? '');
$email = trim($data['email'] ?? '');
$tedavi = $data['tedavi'] ?? 'Genel';
$yorum = trim($data['yorum'] ?? '');
$ust_id = intval($data['ust_id'] ?? 0);

if(!$ad_soyad || !$email || !$yorum) {
    echo json_encode(['success' => false, 'message' => 'Tüm alanları doldurun!']);
    exit;
}

// KESİNLİKLE onay = 0 OLARAK KAYDET
$stmt = $db->prepare("INSERT INTO yorumlar (ad_soyad, email, yorum, tedavi, onay, ust_id, created_at) VALUES (?, ?, ?, ?, 0, ?, NOW())");
$stmt->execute([$ad_soyad, $email, $yorum, $tedavi, $ust_id]);

echo json_encode(['success' => true, 'message' => 'Yorumunuz gönderildi. Onaylandıktan sonra yayınlanacaktır.']);
?>