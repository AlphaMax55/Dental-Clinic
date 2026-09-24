<?php
// /api/oturum-kapat.php - SQL ile otomatik hesaplama
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

require_once dirname(__DIR__) . '/inc/config.php';

$oturum_id = $_COOKIE['site_oturum'] ?? '';

if ($oturum_id) {
    // 🔥 Son kaydın tarihine göre süreyi otomatik hesapla
    $stmt = $db->prepare("UPDATE site_istatistikler 
        SET oturum_suresi = TIMESTAMPDIFF(SECOND, tarih, NOW())
        WHERE oturum_id = ? 
        AND oturum_suresi = 0 
        AND TIMESTAMPDIFF(SECOND, tarih, NOW()) BETWEEN 5 AND 3600
        ORDER BY id DESC LIMIT 1");
    $stmt->execute([$oturum_id]);
    
    // 🔥 Hemen çıkma tespiti
    $stmt = $db->prepare("UPDATE site_istatistikler 
        SET cikis_sayfasi = CONCAT(cikis_sayfasi, ' (Hemen Çıktı)')
        WHERE oturum_id = ? 
        AND oturum_suresi < 60 
        AND sayfa_sayisi <= 1
        ORDER BY id DESC LIMIT 1");
    $stmt->execute([$oturum_id]);
}

echo json_encode(['success' => true]);
?>