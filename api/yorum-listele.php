<?php
// www/site/api/yorum-listele.php
require_once '../inc/config.php';
header('Content-Type: application/json');

try {
    // 1. Sorguyu daha güvenli hale getiriyoruz
    $stmt = $db->query("SELECT * FROM yorumlar WHERE onay = 1 AND silinme_tarihi IS NULL ORDER BY id ASC");
    $yorumlar = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Verileri işlerken dilden bağımsız hale getiriyoruz (t_cevir kancası ile)
    foreach($yorumlar as &$y) {
        $y['tarih'] = date('d.m.Y H:i', strtotime($y['created_at']));
        
        // Veritabanından gelen metinleri sunucu tarafında çevirip öyle JSON'a gönderiyoruz
        $y['tedavi']    = t_cevir($y['tedavi'] ?? 'Genel');
        $y['ad_soyad']  = htmlspecialchars($y['ad_soyad'] ?? 'Anonim');
        $y['yorum']     = htmlspecialchars($y['yorum'] ?? '');
        $y['begeni']    = (int)($y['begeni'] ?? 0);
        $y['begenmeme'] = (int)($y['begenmeme'] ?? 0);
    }

    echo json_encode(['success' => true, 'data' => $yorumlar]);

} catch (PDOException $e) {
    // Hata durumunda boş veri döndürme, hata mesajını yakala
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>