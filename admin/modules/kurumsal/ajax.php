<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
error_reporting(0);

$islem = $_GET['islem'] ?? '';

// ========== 1. RESİM YÜKLE ==========
if ($islem === 'resim_yukle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'CSRF hatası']);
        exit;
    }
    
    if (!isset($_FILES['resim']) || $_FILES['resim']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Dosya yükleme hatası']);
        exit;
    }
    
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/kurumsal/';
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
    
    $file = $_FILES['resim'];
    $info = getimagesize($file['tmp_name']);
    
    if (!$info) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz resim dosyası']);
        exit;
    }
    
    $filename = 'kurumsal_' . time() . '_' . rand(1000, 9999) . '.webp';
    $destination = $upload_dir . $filename;
    
    $width = $info[0];
    $height = $info[1];
    $max_width = 800;
    
    if ($width > $max_width) {
        $new_width = $max_width;
        $new_height = ($height / $width) * $new_width;
    } else {
        $new_width = $width;
        $new_height = $height;
    }
    
    $new_img = imagecreatetruecolor($new_width, $new_height);
    imagealphablending($new_img, false);
    imagesavealpha($new_img, true);
    
    $img = null;
    switch ($info['mime']) {
        case 'image/jpeg': $img = imagecreatefromjpeg($file['tmp_name']); break;
        case 'image/png':  $img = imagecreatefrompng($file['tmp_name']); break;
        case 'image/gif':  $img = imagecreatefromgif($file['tmp_name']); break;
        case 'image/webp': $img = imagecreatefromwebp($file['tmp_name']); break;
    }
    
    imagecopyresampled($new_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    if ($img && imagewebp($new_img, $destination, 80)) {
        imagedestroy($img);
        imagedestroy($new_img);
        echo json_encode(['success' => true, 'url' => '/uploads/kurumsal/' . $filename]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Resim işlenemedi']);
    }
    exit;
}

// ========== 2. KAYDET (TEK SORGULU - KESİN ÇÖZÜM) ==========
if ($islem === 'kaydet' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_input = file_get_contents('php://input');
    $data = json_decode($raw_input, true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz veri']);
        exit;
    }
    
    if (!isset($data['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $data['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'CSRF hatası']);
        exit;
    }
    
    unset($data['csrf_token']);
    
    $success = true;
    $error_messages = [];
    
    foreach ($data as $key => $value) {
        // JSON alanları
        if (is_array($value)) {
            $value = json_encode(array_values($value), JSON_UNESCAPED_UNICODE);
        }
        
        try {
            // 🔥 TEK SORGUDO HALLET: INSERT OR UPDATE
            $stmt = $db->prepare("
                INSERT INTO kurumsal_ayarlari (ayar_key, ayar_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE ayar_value = ?
            ");
            $stmt->execute([$key, $value, $value]);
        } catch (Exception $e) {
            $success = false;
            $error_messages[] = $key . ': ' . $e->getMessage();
        }
    }
    
    if ($success) {
		
		        require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/indexnow.php';
        $site_url = "https://www.dribrahimdurandentalclinic.com";
        $url = $site_url . '/kurumsal';
        indexNowTekliGonder($url);
		
        echo json_encode(['success' => true, 'message' => 'Kaydedildi!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Hata: ' . implode(', ', $error_messages)]);
    }
    exit;
}

// ========== 3. DİĞER İŞLEMLER ==========
if ($islem === 'yontemler_kaydet' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $data['csrf_token'])) { 
        echo json_encode(['success' => false]); 
        exit; 
    }
    $yontemler = json_encode($data['yontemler'], JSON_UNESCAPED_UNICODE);
    $stmt = $db->prepare("UPDATE kurumsal_ayarlari SET ayar_value = ? WHERE ayar_key = 'yontemler_liste'");
    $stmt->execute([$yontemler]);
	
	        require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/indexnow.php';
        $site_url = "https://www.dribrahimdurandentalclinic.com";
        $url = $site_url . '/kurumsal';
        indexNowTekliGonder($url);
		
    echo json_encode(['success' => true]);
    exit;
}

if ($islem === 'set_tab' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (isset($data['tab'])) {
        $_SESSION['aktif_tab'] = $data['tab'];
        echo json_encode(['success' => true]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);