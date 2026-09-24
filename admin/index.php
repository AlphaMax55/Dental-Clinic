<?php
ob_start();

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
kontrol();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$modul = $_GET['modul'] ?? 'dashboard';
$modul = preg_replace('/[^a-zA-Z0-9_-]/', '', $modul);
$sayfa = $_GET['sayfa'] ?? 'index';
$sayfa = preg_replace('/[^a-zA-Z0-9_-]/', '', $sayfa);

$modul_yolu = 'modules/' . $modul . '/' . $sayfa . '.php';

// İŞLEM DOSYALARI (sil, onayla, vs.) - LAYOUT YÜKLEME
$islem_dosyalari = ['sil', 'onayla', 'kaydet', 'guncelle'];
if (in_array($sayfa, $islem_dosyalari)) {
    if (file_exists($modul_yolu)) {
        include $modul_yolu;
    } else {
        echo "Dosya bulunamadı: " . htmlspecialchars($modul_yolu);
    }
    ob_end_flush();
    exit;
}

// NORMAL SAYFALAR İÇİN LAYOUT YÜKLE
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';
?>

<div class="flex-1 flex flex-col overflow-hidden bg-gray-100">
    <div class="flex-1 overflow-y-auto p-6">
        <?php
        if (file_exists($modul_yolu)) {
            include $modul_yolu;
        } else {
            echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg">';
            echo '<p class="font-bold">Sayfa bulunamadı!</p>';
            echo '<p>Modül: ' . htmlspecialchars($modul) . ', Sayfa: ' . htmlspecialchars($sayfa) . '</p>';
            echo '<p>Dosya: ' . htmlspecialchars($modul_yolu) . '</p>';
            echo '</div>';
        }
        ?>
    </div>
</div>

<?php 
require_once 'layouts/footer.php';
ob_end_flush();
?>