<?php
error_reporting(1);
ini_set('display_errors', 1);
header('Content-Type: application/json');
require_once dirname(__DIR__, 1) . '/includes/config.php';

$id = intval($_GET['id'] ?? 0);

if(!$id) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz ID']);
    exit;
}

try {
    // Önce bu ID'nin kendisini çek
    $stmt = $db->prepare("SELECT * FROM yorumlar WHERE id = ?");
    $stmt->execute([$id]);
    $kayit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$kayit) {
        echo json_encode(['success' => false, 'message' => 'Kayıt bulunamadı']);
        exit;
    }
    
    // Eğer bu bir CEVAP ise (ust_id != 0), ANA SORUYU bul
    if($kayit['ust_id'] != 0) {
        // Ana soruyu çek
        $stmt = $db->prepare("SELECT * FROM yorumlar WHERE id = ?");
        $stmt->execute([$kayit['ust_id']]);
        $soru = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$soru) {
            echo json_encode(['success' => false, 'message' => 'Ana soru bulunamadı']);
            exit;
        }
        
        // Ana sorunun cevaplarını çek (onay durumlarıyla birlikte)
        $stmt = $db->prepare("SELECT * FROM yorumlar WHERE ust_id = ? ORDER BY id ASC");
        $stmt->execute([$soru['id']]);
        $cevaplar = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } else {
        // Bu bir ANA SORU
        $soru = $kayit;
        
        // Cevaplarını çek
        $stmt = $db->prepare("SELECT * FROM yorumlar WHERE ust_id = ? ORDER BY id ASC");
        $stmt->execute([$soru['id']]);
        $cevaplar = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Tarih formatı
    $soru['tarih'] = date('d.m.Y H:i', strtotime($soru['created_at']));
    
    foreach($cevaplar as &$c) {
        $c['tarih'] = date('d.m.Y H:i', strtotime($c['created_at']));
    }
    
    echo json_encode([
        'success' => true, 
        'soru' => $soru, 
        'cevaplar' => $cevaplar
    ]);
    
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>