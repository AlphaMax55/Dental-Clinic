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

$stmt = $db->prepare("UPDATE yorumlar SET onay = 1 WHERE id = ?");
$stmt->execute([$id]);

echo json_encode(['success' => true, 'message' => 'Onaylandı']);
?>