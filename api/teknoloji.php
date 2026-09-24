<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once dirname(__DIR__) . '/admin/includes/config.php';

try {
    // 1. Teknoloji kartlarını çek
    $stmt = $db->query("SELECT * FROM teknolojiler WHERE aktif = 1 ORDER BY sira ASC");
    $kartlar = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. Sayfa ayarlarını çek
    $stmt = $db->query("SELECT anahtar, deger FROM teknoloji_ayarlar");
    $ayarlar = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $ayarlar[$row['anahtar']] = $row['deger'];
    }
    
    // 3. İkonlu özellikleri çek
    $stmt = $db->query("SELECT baslik, aciklama FROM teknoloji_ozellikler WHERE aktif = 1 ORDER BY sira ASC");
    $ozellikler = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'kartlar' => $kartlar,
        'ayarlar' => $ayarlar,
        'ozellikler' => $ozellikler
    ], JSON_UNESCAPED_UNICODE);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>