<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once dirname(__DIR__) . '/admin/includes/config.php';

$tip = $_GET['tip'] ?? '';
$kategori = $_GET['kategori'] ?? '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($tip === 'kategoriler') {
        $stmt = $db->query("SELECT * FROM galeri_kategoriler ORDER BY sira ASC");
        $kategoriler = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Her kategori için görsel sayısını ekle
        foreach ($kategoriler as &$kat) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM galeri_resimler WHERE kategori_id = ? AND durum = 1");
            $stmt->execute([$kat['id']]);
            $kat['count'] = $stmt->fetchColumn();
        }
        
        echo json_encode(['success' => true, 'data' => $kategoriler]);
    } 
    elseif ($tip === 'resimler') {
        $sql = "SELECT r.*, k.kategori_adi, k.kategori_slug 
                FROM galeri_resimler r 
                LEFT JOIN galeri_kategoriler k ON r.kategori_id = k.id 
                WHERE r.durum = 1";
        
        if ($kategori) {
            $sql .= " AND k.kategori_slug = '" . $db->real_escape_string($kategori) . "'";
        }
        
        $sql .= " ORDER BY r.sira ASC, r.created_at DESC";
        
        $stmt = $db->query($sql);
        $resimler = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $resimler]);
    }
    elseif ($tip === 'resim' && $id > 0) {
        $stmt = $db->prepare("SELECT * FROM galeri_resimler WHERE id = ? AND durum = 1");
        $stmt->execute([$id]);
        $resim = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resim) {
            // Görüntülenme sayısını artır
            $db->prepare("UPDATE galeri_resimler SET goruntulenme = goruntulenme + 1 WHERE id = ?")->execute([$id]);
        }
        
        echo json_encode(['success' => true, 'data' => $resim]);
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Geçersiz tip']);
    }
}
?>