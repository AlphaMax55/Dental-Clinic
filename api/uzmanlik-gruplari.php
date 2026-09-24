<?php

header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// OPTIONS isteği için (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
header('Content-Type: application/json');
require_once __DIR__ . '/../admin/includes/config.php';
$method = $_SERVER['REQUEST_METHOD'];

// GET - Listele
if ($method === 'GET') {
    $stmt = $db->query("SELECT * FROM uzmanlik_gruplari ORDER BY grup_sira ASC");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($data as &$row) {
        $row['maddeler'] = json_decode($row['maddeler'], true);
    }
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

// POST - Yeni Ekle
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $max = $db->query("SELECT MAX(grup_sira) as m FROM uzmanlik_gruplari")->fetch(PDO::FETCH_ASSOC)['m'] ?? 0;
    $stmt = $db->prepare("INSERT INTO uzmanlik_gruplari (grup_sira, baslik, maddeler) VALUES (?, ?, ?)");
    $stmt->execute([$max + 1, $input['baslik'], json_encode($input['maddeler'], JSON_UNESCAPED_UNICODE)]);
    echo json_encode(['success' => true]);
    exit;
}

// PUT - Toplu Güncelle
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    foreach ($input['gruplar'] as $index => $grup) {
        $stmt = $db->prepare("UPDATE uzmanlik_gruplari SET baslik = ?, maddeler = ?, grup_sira = ? WHERE id = ?");
        $stmt->execute([$grup['baslik'], json_encode($grup['maddeler'], JSON_UNESCAPED_UNICODE), $index + 1, $grup['id']]);
    }
    echo json_encode(['success' => true]);
    exit;
}

// DELETE - Sil
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? 0;
    $stmt = $db->prepare("DELETE FROM uzmanlik_gruplari WHERE id = ?");
    if ($stmt->execute([$id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Silme hatası']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
?>