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


try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $db->query("SELECT * FROM kurumsal_ayarlari ORDER BY sira");
        $ayarlar = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row['ayar_tip'] === 'json') {
                $ayarlar[$row['ayar_key']] = json_decode($row['ayar_value'], true);
            } else {
                $ayarlar[$row['ayar_key']] = $row['ayar_value'];
            }
        }
        echo json_encode(['success' => true, 'data' => $ayarlar]);
    } 
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $key = $input['ayar_key'];
        $value = $input['ayar_value'];
        
        $stmt = $db->prepare("UPDATE kurumsal_ayarlari SET ayar_value = :value WHERE ayar_key = :key");
        $stmt->execute(['value' => $value, 'key' => $key]);
        
        echo json_encode(['success' => true]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>