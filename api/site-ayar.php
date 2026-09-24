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

// GET isteği - ayarları oku
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $grup = isset($_GET['grup']) ? $_GET['grup'] : '';
        
        // AI SEO için ayarlar tablosundan da çek
        if ($grup === 'ai_seo') {
            // Önce ayarlar tablosundan dene (anahtar-değer tablosu)
            $stmt = $db->prepare("SELECT deger FROM ayarlar WHERE anahtar = 'ai_seo'");
            $stmt->execute();
            $ai_seo = $stmt->fetchColumn();
            
            if ($ai_seo) {
                echo json_encode(['success' => true, 'data' => ['ai_seo' => $ai_seo]]);
                exit;
            }
            
            // Yoksa site_ayarlari tablosundan dene
            $stmt2 = $db->prepare("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'ai_seo'");
            $stmt2->execute();
            $ai_seo2 = $stmt2->fetchColumn();
            
            echo json_encode(['success' => true, 'data' => ['ai_seo' => $ai_seo2]]);
            exit;
        }
        
        // Normal SEO ve diğer gruplar için site_ayarlari tablosu
        $sql = "SELECT ayar_key, ayar_value, ayar_tip FROM site_ayarlari";
        if (!empty($grup)) {
            $sql .= " WHERE grup = :grup";
        }
        $sql .= " ORDER BY sira ASC";
        
        $stmt = $db->prepare($sql);
        if (!empty($grup)) {
            $stmt->bindParam(':grup', $grup);
        }
        $stmt->execute();
        
        $ayarlar = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row['ayar_tip'] === 'json') {
                $ayarlar[$row['ayar_key']] = json_decode($row['ayar_value'], true);
            } else {
                $ayarlar[$row['ayar_key']] = $row['ayar_value'];
            }
        }
        
        echo json_encode(['success' => true, 'data' => $ayarlar]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// POST isteği - ayar güncelle
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['ayar_key']) || !isset($input['ayar_value'])) {
            echo json_encode(['success' => false, 'message' => 'Eksik parametreler']);
            exit;
        }
        
        $key = $input['ayar_key'];
        $value = $input['ayar_value'];
        $grup = isset($input['grup']) ? $input['grup'] : 'genel';
        $label = isset($input['label']) ? $input['label'] : $key;
        
        // AI SEO ise ayarlar tablosuna kaydet
        if ($key === 'ai_seo') {
            $stmt = $db->prepare("INSERT INTO ayarlar (anahtar, deger, aciklama) 
                                   VALUES ('ai_seo', :value, 'Yapay Zeka SEO JSON-LD Verisi')
                                   ON DUPLICATE KEY UPDATE deger = :value");
            $stmt->bindParam(':value', $value);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'AI SEO ayarı güncellendi']);
            exit;
        }
        
        // Normal ayarlar için site_ayarlari tablosu
        $sql = "INSERT INTO site_ayarlari (ayar_key, ayar_value, grup, label) 
                VALUES (:key, :value, :grup, :label)
                ON DUPLICATE KEY UPDATE 
                ayar_value = :value, 
                grup = :grup, 
                label = :label,
                updated_at = CURRENT_TIMESTAMP";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':key', $key);
        $stmt->bindParam(':value', $value);
        $stmt->bindParam(':grup', $grup);
        $stmt->bindParam(':label', $label);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Ayar güncellendi']);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>