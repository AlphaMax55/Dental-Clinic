<?php
require_once __DIR__ . '/../admin/includes/config.php';

header('Content-Type: application/json');

// ========== ID'leri AL ==========
$ids = isset($_POST['ids']) ? $_POST['ids'] : '';

if (empty($ids)) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    $ids = isset($data['ids']) ? $data['ids'] : '';
}

if (empty($ids)) {
    echo json_encode(['success' => false, 'error' => 'ID bulunamadı']);
    exit;
}

// ========== ID'leri DÜZGÜN TEMİZLE ==========
if (is_array($ids)) {
    // Array ise virgülle birleştir
    $ids = array_map('intval', $ids);
    $ids_string = implode(',', $ids);
} else {
    // String ise temizle
    $ids_string = preg_replace('/[^0-9,]/', '', $ids);
}

if (empty($ids_string)) {
    echo json_encode(['success' => false, 'error' => 'Geçersiz ID']);
    exit;
}

try {
    // ========== SİL ==========
    $stmt = $db->prepare("DELETE FROM site_istatistikler WHERE id IN ($ids_string)");
    $stmt->execute();
    $silinen = $stmt->rowCount();
    
    echo json_encode([
        'success' => true,
        'silinen' => $silinen,
        'ids' => $ids_string
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>