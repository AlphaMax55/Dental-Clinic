<?php
require_once '../inc/config.php';
header('Content-Type: application/json');

$id = intval($_GET['id'] ?? 0);
$tip = $_GET['tip'] ?? '';

if(!$id || !in_array($tip, ['begeni', 'begenmeme'])) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $db->prepare("UPDATE yorumlar SET $tip = $tip + 1 WHERE id = ?");
$stmt->execute([$id]);

echo json_encode(['success' => true]);
?>