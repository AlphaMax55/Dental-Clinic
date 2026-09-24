<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once dirname(__DIR__) . '/inc/config.php';
session_start();
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
$email = trim($data['email'] ?? '');
$tedavi = $data['tedavi'] ?? 'Yorum';

// 🔥 KRİTİK: Eğer admin değilse (Prof. Dr. İbrahim Duran değilse) onay = 0
$onay = 0; // Varsayılan olarak onaysız

// Admin cevabı mı kontrol et
if($ad_soyad === 'Prof. Dr. İbrahim Duran' && $email === 'admin@dribrahimdurandentalclinic.com') {
    $onay = 1; // Admin cevabı direkt onaylı
} else {
    $onay = 0; // Kullanıcı cevabı onay bekler
}

// Eğer POST'tan onay geldiyse (admin panelinden geliyorsa) onu kullan
if(isset($data['onay'])) {
    $onay = intval($data['onay']);
}

if(!$ust_id || !$yorum || !$ad_soyad) {
    echo json_encode(['success' => false, 'message' => 'Eksik bilgi!']);
    exit;
}

try {
    $stmt = $db->prepare("INSERT INTO yorumlar (ust_id, ad_soyad, email, yorum, tedavi, onay, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$ust_id, $ad_soyad, $email, $yorum, $tedavi, $onay]);
    
    $mesaj = $onay == 1 ? 'Cevap başarıyla gönderildi.' : 'Cevabınız onay bekliyor. Onaylandıktan sonra yayınlanacaktır.';
    echo json_encode(['success' => true, 'message' => $mesaj, 'onay' => $onay]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>