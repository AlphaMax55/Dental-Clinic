<?php
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    http_response_code(200);
    exit();
}

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

require_once '../admin/includes/config.php';

$kategori = $_GET['kategori'] ?? '';
$slug = $_GET['slug'] ?? '';

if (empty($kategori)) {
    echo json_encode(['success' => false, 'message' => 'Kategori gerekli']);
    exit;
}

$stmt = $db->prepare("
    SELECT id, slug, baslik, kategori, kisa_aciklama, image
    FROM tedaviler 
    WHERE kategori = ? AND aktif = 1 AND silindi = 0 AND slug != ?
    ORDER BY sira ASC, id ASC
");
$stmt->execute([$kategori, $slug]);
$tedaviler = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'data' => $tedaviler
], JSON_UNESCAPED_UNICODE);
?>