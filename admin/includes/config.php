<?php
// ========== SESSION AYARLARI - INFINITYFREE FİX ==========
// InfinityFree'de session hatasını çözmek için
$session_save_path = dirname(__DIR__, 2) . '/tmp/sessions';

if (!file_exists($session_save_path)) {
    mkdir($session_save_path, 0777, true);
}

session_save_path($session_save_path);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sunucu adını algıla
$hostname = $_SERVER['HTTP_HOST'] ?? '';
$is_local = ($hostname === 'localhost' || $hostname === '127.0.0.1' || strpos($hostname, '.test') !== false);
// Veritabanı bilgileri
if ($is_local) {
    // LOKAL
    define('DB_HOST', 'Host');
    define('DB_USER', 'Kullanici');
    define('DB_PASS', 'Sifre');
    define('DB_NAME', 'DB_adi');
    define('SITE_URL', 'http://localhost');
    define('UPLOAD_PATH', 'C:/AppServ/www/site/uploads/');
} else {
// CANLI SİTE 
    define('DB_HOST', 'Host');
    define('DB_USER', 'Kullanici');
    define('DB_PASS', 'Sifre');
    define('DB_NAME', 'DB_adi');
    define('SITE_URL', 'Site_adi');
    define('UPLOAD_PATH', dirname(__DIR__, 2) . '/uploads/');
}

define('ADMIN_URL', SITE_URL . '/admin');
define('UPLOAD_URL', SITE_URL . '/uploads/');
define('API_URL', SITE_URL . '/api');

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
} catch(PDOException $e) {
    die("Veritabanıaa bağlantı hatası: " . $e->getMessage());
}

// Site ayarlarını çek
$ayarlar = [];
try {
    $sorgu = $db->query("SELECT anahtar, deger FROM ayarlar");
    while($row = $sorgu->fetch()) {
        $ayarlar[$row['anahtar']] = $row['deger'];
    }
} catch(PDOException $e) {
    // Tablo yoksa hata verme
}
?>