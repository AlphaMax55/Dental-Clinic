<?php
ob_start();

require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
kontrol();

// Sadece POST isteklerini kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ?modul=yorumlar');
    ob_end_flush();
    exit;
}

// CSRF token kontrolü
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['mesaj'] = 'Güvenlik hatası!';
    $_SESSION['mesaj_tip'] = 'error';
    header('Location: ?modul=yorumlar');
    ob_end_flush();
    exit;
}

// YETKİ KONTROLÜ
if (!yetkiVar('yorumlar', 'silebilir')) {
    $_SESSION['mesaj'] = 'Bu işlem için yetkiniz yok.';
    $_SESSION['mesaj_tip'] = 'error';
    header('Location: ?modul=yorumlar');
    ob_end_flush();
    exit;
}

$id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);

if (!$id) {
    $_SESSION['mesaj'] = 'Geçersiz yorum ID!';
    $_SESSION['mesaj_tip'] = 'error';
    header('Location: ?modul=yorumlar');
    ob_end_flush();
    exit;
}

try {
    $stmt = $db->prepare("DELETE FROM yorumlar WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['mesaj'] = 'Yorum başarıyla silindi.';
        $_SESSION['mesaj_tip'] = 'success';
    } else {
        $_SESSION['mesaj'] = 'Yorum bulunamadı!';
        $_SESSION['mesaj_tip'] = 'warning';
    }
} catch (Exception $e) {
    $_SESSION['mesaj'] = 'Silme işlemi başarısız!';
    $_SESSION['mesaj_tip'] = 'error';
}

header('Location: ?modul=yorumlar');
ob_end_flush();
exit;
?>