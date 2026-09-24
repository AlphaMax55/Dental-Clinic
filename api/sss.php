<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
kontrol();
header('Content-Type: application/json');

$islem = $_GET['islem'] ?? '';

if($islem === 'listele') {
    $stmt = $db->query("SELECT * FROM sss WHERE aktif = 1 ORDER BY sira ASC");
    $sss = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $sss]);
    exit;
}

if($islem === 'sil' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $db->prepare("DELETE FROM sss WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}