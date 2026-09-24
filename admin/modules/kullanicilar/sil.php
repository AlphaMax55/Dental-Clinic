<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
kontrol();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CSRF token oluştur (mesaj için)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Sadece POST isteklerini kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ?modul=kullanicilar');
    exit;
}

// CSRF token kontrolü
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['mesaj'] = 'Güvenlik hatası! Geçersiz token.';
    $_SESSION['mesaj_tip'] = 'error';
    header('Location: ?modul=kullanicilar');
    exit;
}

// Sadece superadmin silebilir
if (!yetkiKontrol('superadmin')) {
    $_SESSION['mesaj'] = 'Bu işlem için yetkiniz yok. Sadece Süper Admin kullanıcı silebilir.';
    $_SESSION['mesaj_tip'] = 'error';
    header('Location: ?modul=kullanicilar');
    exit;
}

$id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);

if (!$id) {
    $_SESSION['mesaj'] = 'Geçersiz kullanıcı ID!';
    $_SESSION['mesaj_tip'] = 'error';
    header('Location: ?modul=kullanicilar');
    exit;
}

// Kendini silemez
if ($id == $_SESSION['admin_id']) {
    $_SESSION['mesaj'] = 'Kendi hesabınızı silemezsiniz!';
    $_SESSION['mesaj_tip'] = 'error';
    header('Location: ?modul=kullanicilar');
    exit;
}

// Kullanıcıyı kontrol et
$kontrol = $db->prepare("SELECT rol FROM kullanicilar WHERE id = ?");
$kontrol->execute([$id]);
$kullanici = $kontrol->fetch();

if (!$kullanici) {
    $_SESSION['mesaj'] = 'Kullanıcı bulunamadı!';
    $_SESSION['mesaj_tip'] = 'error';
    header('Location: ?modul=kullanicilar');
    exit;
}

// Süper Admin silinemez
if ($kullanici['rol'] == 'superadmin') {
    $_SESSION['mesaj'] = 'Süper Admin hesabı silinemez!';
    $_SESSION['mesaj_tip'] = 'error';
    header('Location: ?modul=kullanicilar');
    exit;
}

try {
    $stmt = $db->prepare("DELETE FROM kullanicilar WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['mesaj'] = 'Kullanıcı başarıyla silindi.';
        $_SESSION['mesaj_tip'] = 'success';
    } else {
        $_SESSION['mesaj'] = 'Kullanıcı silinemedi!';
        $_SESSION['mesaj_tip'] = 'error';
    }
} catch (Exception $e) {
    $_SESSION['mesaj'] = 'Silme işlemi başarısız: ' . $e->getMessage();
    $_SESSION['mesaj_tip'] = 'error';
}

header('Location: ?modul=kullanicilar');
exit;
?>