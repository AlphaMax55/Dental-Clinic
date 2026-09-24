<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
header('Content-Type: application/json');
kontrol();

$id = intval($_GET['id'] ?? 0);

if(!$id) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz ID']);
    exit;
}

// Soruyu ve cevaplarını sil
$stmt = $db->prepare("DELETE FROM yorumlar WHERE id = ? OR ust_id = ?");
$stmt->execute([$id, $id]);

echo json_encode(['success' => true, 'message' => 'Reddedildi']);
?>