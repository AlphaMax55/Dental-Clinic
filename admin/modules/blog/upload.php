<?php

//modul  - upload.php
require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
kontrol();

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

$csrf = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
    echo json_encode(['success' => false, 'message' => 'CSRF hatası']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['resim'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit;
}

$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/blog/';
if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

$file = $_FILES['resim'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if (!in_array($ext, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Sadece resim dosyaları (jpg, png, gif, webp)']);
    exit;
}

if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'Max 5MB']);
    exit;
}

// ========== WEBP DÖNÜŞÜMÜ ==========
function convertToWebp($sourcePath, $destPath, $quality = 80) {
    $info = getimagesize($sourcePath);
    if (!$info) return false;
    
    $mime = $info['mime'];
    
    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $image = imagecreatefrompng($sourcePath);
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
            break;
        case 'image/webp':
            // Zaten webp ise kopyala
            copy($sourcePath, $destPath);
            return true;
        default:
            return false;
    }
    
    if (!$image) return false;
    
    imagewebp($image, $destPath, $quality);
    imagedestroy($image);
    return true;
}

// Geçici dosya yolunu al
$tempPath = $file['tmp_name'];
$filename = 'blog_' . time() . '_' . rand(1000, 9999) . '.webp';
$filepath = $upload_dir . $filename;

// Webp'ye dönüştür ve kaydet
if (convertToWebp($tempPath, $filepath, 80)) {
    echo json_encode([
        'success' => true, 
        'url' => '/uploads/blog/' . $filename,
        'filename' => $filename
    ]);
} else {
    // Dönüşüm başarısız olursa orijinali kaydet
    if (move_uploaded_file($tempPath, $filepath)) {
        echo json_encode([
            'success' => true, 
            'url' => '/uploads/blog/' . $filename,
            'filename' => $filename
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Dosya kaydedilemedi']);
    }
}
?>