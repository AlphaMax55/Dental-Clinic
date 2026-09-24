<?php
require_once dirname(__DIR__) . '/admin/includes/config.php';
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

$search = isset($_GET['q']) ? $_GET['q'] : '';

if (strlen($search) < 2) {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

try {
    // Tedavilerde ara
    $stmt = $db->prepare("SELECT id, baslik, slug, 'tedavi' as tip FROM tedaviler WHERE baslik LIKE :search LIMIT 10");
    $stmt->execute(['search' => "%$search%"]);
    $tedaviler = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $tedaviler]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'data' => []]);
}
?>