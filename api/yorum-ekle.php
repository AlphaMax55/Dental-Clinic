<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

require_once '../admin/includes/config.php';

// Session başlat (token için)
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Gelen veriyi al
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// CSRF TOKEN KONTROLÜ
$posted_token = $data['csrf_token'] ?? ($_POST['csrf_token'] ?? '');
$session_token = $_SESSION['csrf_token'] ?? '';

if(empty($session_token) || $posted_token !== $session_token) {
    echo json_encode([
        'success' => false, 
        'message' => 'Güvenlik hatası! Lütfen sayfayı yenileyip tekrar deneyin.',
        'debug' => ['session_token' => substr($session_token, 0, 20) . '...', 'posted_token' => substr($posted_token, 0, 20) . '...']
    ]);
    exit;
}

$ad_soyad = isset($data['ad_soyad']) ? trim($data['ad_soyad']) : (isset($_POST['ad_soyad']) ? trim($_POST['ad_soyad']) : '');
$email = isset($data['email']) ? trim($data['email']) : (isset($_POST['email']) ? trim($_POST['email']) : '');
$yorum = isset($data['yorum']) ? trim($data['yorum']) : (isset($_POST['yorum']) ? trim($_POST['yorum']) : '');
$tedavi = isset($data['tedavi']) ? trim($data['tedavi']) : (isset($_POST['tedavi']) ? trim($_POST['tedavi']) : '');
$puan = isset($data['puan']) ? intval($data['puan']) : (isset($_POST['puan']) ? intval($_POST['puan']) : 5);

// Validasyon
if(empty($ad_soyad) || empty($yorum) || empty($tedavi)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Lütfen ad, yorum ve tedavi alanlarını doldurun'
    ]);
    exit;
}

if($puan < 1 || $puan > 5) {
    $puan = 5;
}

try {
    $stmt = $db->prepare("INSERT INTO yorumlar (ad_soyad, email, yorum, tedavi, puan, onay, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())");
    $result = $stmt->execute([$ad_soyad, $email, $yorum, $tedavi, $puan]);
    
    if($result) {
        // Token yenile (tekrar gönderimi engelle)
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
        echo json_encode([
            'success' => true, 
            'message' => 'Yorumunuz için teşekkürler! Onaylandıktan sonra yayınlanacaktır.'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Veritabanına eklenirken hata oluştu'
        ]);
    }
} catch(PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
}
?>