<?php
ob_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
kontrol();

$modul = $_GET['modul'] ?? '';
$islem = $_GET['islem'] ?? '';

if (empty($modul) || empty($islem)) {
    header('Location: index.php');
    exit;
}

$islem_yolu = 'modules/' . $modul . '/' . $islem . '.php';

if (file_exists($islem_yolu)) {
    include $islem_yolu;
} else {
    die('İşlem dosyası bulunamadı!');
}

ob_end_flush();
?>