<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
kontrol();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ?modul=tedaviler');
    exit;
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Güvenlik hatası!');
}

$yorum_id = filter_var($_POST['yorum_id'] ?? 0, FILTER_VALIDATE_INT);
$tedavi_id = filter_var($_POST['tedavi_id'] ?? 0, FILTER_VALIDATE_INT);

if (!$yorum_id || !$tedavi_id) {
    $_SESSION['mesaj'] = 'Geçersiz yorum ID!';
    $_SESSION['mesaj_tip'] = 'error';
    header('Location: ?modul=tedaviler&sayfa=duzenle&id=' . $tedavi_id);
    exit;
}

$stmt = $db->prepare("UPDATE tedavi_yorumlar SET onay = 1 WHERE id = ?");
$stmt->execute([$yorum_id]);

$_SESSION['mesaj'] = 'Yorum onaylandı!';
$_SESSION['mesaj_tip'] = 'success';
header('Location: ?modul=tedaviler&sayfa=duzenle&id=' . $tedavi_id);
exit;
?>