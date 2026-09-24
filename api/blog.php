<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once dirname(__DIR__) . '/admin/includes/config.php';

$tip = $_GET['tip'] ?? '';

// GET istekleri
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($tip === 'yazilar') {
        $stmt = $db->query("SELECT * FROM blog_yazilar WHERE durum = 1 ORDER BY created_at DESC");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } elseif ($tip === 'sss') {
        $stmt = $db->query("SELECT * FROM blog_sss WHERE durum = 1 ORDER BY sira ASC");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } elseif ($tip === 'kategoriler') {
        $stmt = $db->query("SELECT * FROM blog_kategoriler ORDER BY sira ASC");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Geçersiz tip']);
    }
}

// POST istekleri (abone ol)
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = trim($input['email'] ?? '');
    
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'E-posta adresi gerekli']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Geçerli bir e-posta adresi giriniz']);
        exit;
    }
    
    try {
        // Tablo yoksa oluştur
        $db->exec("CREATE TABLE IF NOT EXISTS `blog_aboneler` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `email` varchar(255) NOT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $stmt = $db->prepare("INSERT INTO blog_aboneler (email) VALUES (?)");
        $stmt->execute([$email]);
        echo json_encode(['success' => true, 'message' => '✅ Abone oldunuz!']);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo json_encode(['success' => false, 'message' => 'Bu e-posta zaten kayıtlı']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
}
?>