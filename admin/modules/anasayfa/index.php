<?php
// admin/modules/anasayfa/index.php (TABLI - İKON, BAŞLIK, AÇIKLAMA INPUTLU)
require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

kontrol();
yetkiKontrol('anasayfa', 'goruntuleyebilir');

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// ========== AJAX İŞLEMLERİ ==========
if (isset($_GET['islem'])) {
    header('Content-Type: application/json');
    
    // ANA SAYFA İÇERİĞİNİ GETİR
    if ($_GET['islem'] === 'get') {
        $stmt = $db->prepare("SELECT * FROM anasayfa_icerik WHERE id = 1");
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }
    
    // ===== ANA SAYFA İÇERİĞİNİ KAYDET (SADECE GÖNDERİLEN ALANLAR) =====
    if ($_GET['islem'] === 'kaydet_icerik' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $data['csrf_token'])) {
            echo json_encode(['success' => false, 'message' => 'CSRF hatası']);
            exit;
        }
        
        try {
            // İzin verilen alanlar
            $allowedFields = [
                'tedaviler_baslik_tr', 'tedaviler_baslik_en', 
                'tedaviler_alt_baslik_tr', 'tedaviler_alt_baslik_en',
                'sol_sutun_baslik_tr', 'sol_sutun_baslik_en', 
                'sag_sutun_baslik_tr', 'sag_sutun_baslik_en',
                'akademik_vizyon_baslik_tr', 'akademik_vizyon_baslik_en',
                'randevu_baslik_tr', 'randevu_baslik_en', 
                'randevu_buton_yazi_tr', 'randevu_buton_yazi_en',
                'doktor_aciklama_tr', 'doktor_aciklama_en',
                'sss_baslik_tr', 'sss_baslik_en', 
                'sss_alt_baslik_tr', 'sss_alt_baslik_en',
                'tedaviler_metin_json',
                'seo_title_tr', 'seo_title_en', 
                'seo_description_tr', 'seo_description_en',
                'seo_keywords_tr', 'seo_keywords_en', 
                'seo_og_image', 'seo_canonical'
            ];
            
            $setParts = [];
            $params = [];
            
            // Sadece gelen verilerde olan alanları güncelle
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $setParts[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }
            
            if (empty($setParts)) {
                echo json_encode(['success' => false, 'message' => 'Güncellenecek alan yok']);
                exit;
            }
            
            // WHERE id = 1 için
            $params[] = 1;
            
            $sql = "UPDATE anasayfa_icerik SET " . implode(', ', $setParts) . " WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
			        require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/indexnow.php';
        $site_url = "https://www.dribrahimdurandentalclinic.com";
        indexNowTekliGonder($site_url);
        
			
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ===== SSS İŞLEMLERİ =====
    // SSS Listesi Getir
    if ($_GET['islem'] === 'sss_getir') {
        $stmt = $db->query("SELECT * FROM sss ORDER BY sira ASC");
        $sss_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $sss_list]);
        exit;
    }

// SSS Ekle (Otomatik sıra - en sona ekle)
if ($_GET['islem'] === 'sss_ekle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $data['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'CSRF hatası']);
        exit;
    }
    
    // 🔥 EN YÜKSEK SIRAYI BUL VE 1 EKLE (EN SONA EKLE)
    $stmt = $db->query("SELECT MAX(sira) as max_sira FROM sss");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $maxSira = $result['max_sira'] ?? 0;
    $yeniSira = $maxSira + 1;
    
    $stmt = $db->prepare("INSERT INTO sss (soru, cevap, kategori, sira, aktif) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['soru'], 
        $data['cevap'], 
        $data['kategori'], 
        $yeniSira,  // 🔥 En yüksek sıra + 1
        1
    ]);
    
    echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
    exit;
}

    // SSS Güncelle
    if ($_GET['islem'] === 'sss_guncelle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $data['csrf_token'])) {
            echo json_encode(['success' => false, 'message' => 'CSRF hatası']);
            exit;
        }
        $stmt = $db->prepare("UPDATE sss SET soru=?, cevap=?, kategori=?, sira=? WHERE id=?");
        $stmt->execute([$data['soru'], $data['cevap'], $data['kategori'], intval($data['sira']), intval($data['id'])]);
        echo json_encode(['success' => true]);
        exit;
    }
// SSS Toplu Sil
if ($_GET['islem'] === 'sss_toplu_sil' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $data['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'CSRF hatası']);
        exit;
    }
    
    if (empty($data['ids']) || !is_array($data['ids'])) {
        echo json_encode(['success' => false, 'message' => 'Silinecek kayıt seçilmedi']);
        exit;
    }
    
    try {
        // ID'leri güvenli hale getir
        $ids = array_map('intval', $data['ids']);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        // Sıraları güncellemek için önce silinecek kayıtların sıralarını al
        $stmt = $db->prepare("SELECT sira FROM sss WHERE id IN ($placeholders) ORDER BY sira ASC");
        $stmt->execute($ids);
        $silinenSiraliar = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Kayıtları sil
        $stmt = $db->prepare("DELETE FROM sss WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $silinenAdet = $stmt->rowCount();
        
        // Sıraları düzelt (silinen sıralardan sonra gelenleri 1 azalt)
        if (!empty($silinenSiraliar)) {
            $minSira = min($silinenSiraliar);
            $kaymaMiktari = count($silinenSiraliar);
            
            // Silinen sıralardan büyük olanları güncelle
            $stmt = $db->prepare("UPDATE sss SET sira = sira - ? WHERE sira > ?");
            $stmt->execute([$kaymaMiktari, $minSira]);
        }
        
        echo json_encode(['success' => true, 'silinen' => $silinenAdet]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
// SSS Sil (Sıralamayı da güncelle)
if ($_GET['islem'] === 'sss_sil' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Önce silinecek kaydın sırasını bul
    $stmt = $db->prepare("SELECT sira FROM sss WHERE id = ?");
    $stmt->execute([$id]);
    $silinenSira = $stmt->fetchColumn();
    
    // Kaydı sil
    $stmt = $db->prepare("DELETE FROM sss WHERE id = ?");
    $stmt->execute([$id]);
    
    // Silinen sıradan sonra gelenleri 1 azalt
    if ($silinenSira) {
        $stmt = $db->prepare("UPDATE sss SET sira = sira - 1 WHERE sira > ?");
        $stmt->execute([$silinenSira]);
    }
    
    echo json_encode(['success' => true]);
    exit;
}
// ===== SLIDER İŞLEMLERİ =====

// Slider Listesi Getir
if ($_GET['islem'] === 'slider_getir') {
    $stmt = $db->query("SELECT * FROM anasayfa_slider ORDER BY sira ASC");
    $slider_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $slider_list]);
    exit;
}

// Slider Ekle
if ($_GET['islem'] === 'slider_ekle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $data['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'CSRF hatası']);
        exit;
    }
    
    $stmt = $db->query("SELECT MAX(sira) as max_sira FROM anasayfa_slider");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $maxSira = $result['max_sira'] ?? 0;
    $yeniSira = $maxSira + 1;
    
    $stmt = $db->prepare("INSERT INTO anasayfa_slider 
        (title_tr, title_en, subtitle_tr, subtitle_en, badge_tr, badge_en, 
         image_url, features_tr, features_en, sira, aktif) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['title_tr'], $data['title_en'],
        $data['subtitle_tr'], $data['subtitle_en'],
        $data['badge_tr'], $data['badge_en'],
        $data['image_url'],
        $data['features_tr'], $data['features_en'],
        $yeniSira,
        $data['aktif'] ?? 1
    ]);
    
    echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
    exit;
}
// Slider Güncelle
if ($_GET['islem'] === 'slider_guncelle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $data['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'CSRF hatası']);
        exit;
    }
    
    // Eğer sira gönderilmediyse, mevcut sırayı koru
    $sira = isset($data['sira']) ? intval($data['sira']) : null;
    if ($sira === null || $sira === 0) {
        // Mevcut sırayı al
        $stmt = $db->prepare("SELECT sira FROM anasayfa_slider WHERE id = ?");
        $stmt->execute([intval($data['id'])]);
        $sira = $stmt->fetchColumn();
        if ($sira === false) $sira = 0;
    }
    
    $stmt = $db->prepare("UPDATE anasayfa_slider SET 
        title_tr=?, title_en=?, subtitle_tr=?, subtitle_en=?, 
        badge_tr=?, badge_en=?, image_url=?, 
        features_tr=?, features_en=?, sira=?, aktif=? 
        WHERE id=?");
    $stmt->execute([
        $data['title_tr'], $data['title_en'],
        $data['subtitle_tr'], $data['subtitle_en'],
        $data['badge_tr'], $data['badge_en'],
        $data['image_url'],
        $data['features_tr'], $data['features_en'],
        $sira,
        intval($data['aktif']),
        intval($data['id'])
    ]);
    
    echo json_encode(['success' => true]);
    exit;
}

// Slider Sil
if ($_GET['islem'] === 'slider_sil' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Önce silinecek kaydın sırasını bul
    $stmt = $db->prepare("SELECT sira FROM anasayfa_slider WHERE id = ?");
    $stmt->execute([$id]);
    $silinenSira = $stmt->fetchColumn();
    
    // Kaydı sil
    $stmt = $db->prepare("DELETE FROM anasayfa_slider WHERE id = ?");
    $stmt->execute([$id]);
    
    // Silinen sıradan sonra gelenleri 1 azalt
    if ($silinenSira) {
        $stmt = $db->prepare("UPDATE anasayfa_slider SET sira = sira - 1 WHERE sira > ?");
        $stmt->execute([$silinenSira]);
    }
    
    echo json_encode(['success' => true]);
    exit;
}
// Slider Resim Yükle (Kırpma + Döndürme + WebP)
if ($_GET['islem'] === 'slider_resim_yukle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'CSRF hatası']);
        exit;
    }
    
    if (!isset($_FILES['resim']) || $_FILES['resim']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Dosya yüklenemedi']);
        exit;
    }
    
    // Kullanıcıdan gelen parametreler
    $cropX = intval($_POST['cropX'] ?? 0);
    $cropY = intval($_POST['cropY'] ?? 0);
    $cropW = intval($_POST['cropW'] ?? 0);
    $cropH = intval($_POST['cropH'] ?? 0);
    $rotate = intval($_POST['rotate'] ?? 0);
    $quality = intval($_POST['quality'] ?? 85);
    
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/slider/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $sourcePath = $_FILES['resim']['tmp_name'];
    $imageInfo = getimagesize($sourcePath);
    $mimeType = $imageInfo['mime'];
    
    // Kaynak resmi yükle
    switch ($mimeType) {
        case 'image/jpeg':
            $source = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $source = imagecreatefrompng($sourcePath);
            imagepalettetotruecolor($source);
            break;
        case 'image/gif':
            $source = imagecreatefromgif($sourcePath);
            break;
        case 'image/webp':
            $source = imagecreatefromwebp($sourcePath);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Desteklenmeyen dosya formatı']);
            exit;
    }
    
    if (!$source) {
        echo json_encode(['success' => false, 'message' => 'Resim okunamadı']);
        exit;
    }
    
    $srcW = imagesx($source);
    $srcH = imagesy($source);
    
    // Kırpma parametreleri (geçerli mi kontrol et)
    if ($cropW > 0 && $cropH > 0 && $cropX + $cropW <= $srcW && $cropY + $cropH <= $srcH) {
        $cropped = imagecrop($source, ['x' => $cropX, 'y' => $cropY, 'width' => $cropW, 'height' => $cropH]);
        if ($cropped !== false) {
            imagedestroy($source);
            $source = $cropped;
            $srcW = $cropW;
            $srcH = $cropH;
        }
    }
    
    // Döndürme
    if ($rotate > 0) {
        $source = imagerotate($source, $rotate, 0);
        if ($source === false) {
            echo json_encode(['success' => false, 'message' => 'Döndürme hatası']);
            exit;
        }
        $srcW = imagesx($source);
        $srcH = imagesy($source);
    }
    
    // Boyutlandırma (maks 1920x1080)
    $maxW = 1920;
    $maxH = 1080;
    $ratio = min($maxW / $srcW, $maxH / $srcH, 1);
    $newW = intval($srcW * $ratio);
    $newH = intval($srcH * $ratio);
    
    $resized = imagecreatetruecolor($newW, $newH);
    imagecopyresampled($resized, $source, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);
    imagedestroy($source);
    $source = $resized;
    
    // WebP olarak kaydet
    $filename = 'slider_' . time() . '_' . bin2hex(random_bytes(4)) . '.webp';
    $targetPath = $uploadDir . $filename;
    
    // WebP kalitesi (0-100)
    $quality = max(1, min(100, $quality));
    $success = imagewebp($source, $targetPath, $quality);
    imagedestroy($source);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'url' => '/uploads/slider/' . $filename,
            'width' => $newW,
            'height' => $newH,
            'size' => filesize($targetPath)
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Resim kaydedilemedi']);
    }
    exit;
}

}

// ========== HTML GÖRÜNÜMÜ ==========
?>
<!-- SweetAlert2 -->


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<style>
/* ============================================================ */
/* ========== MODERN ANA SAYFA YÖNETİM CSS V3 ========== */
/* ============================================================ */

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.kurumsal-container {
    background: #f1f5f9;
    min-height: 100vh;
    padding: 24px;
}

.kurumsal-card {
    background: #ffffff;
    border-radius: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06), 0 1px 2px rgba(0, 0, 0, 0.03);
    overflow: hidden;
    border: 1px solid #eef2f6;
}

/* ============================================================ */
/* ===== HEADER ===== */
/* ============================================================ */
.kurumsal-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    padding: 20px 32px;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.kurumsal-header h1 {
    font-size: 1.3rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 12px;
    letter-spacing: -0.3px;
}

.kurumsal-header h1 i {
    color: #60a5fa;
    font-size: 1.4rem;
}

.kurumsal-header .btn-refresh {
    padding: 8px 18px;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 10px;
    color: #94a3b8;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 13px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
}

.kurumsal-header .btn-refresh:hover {
    background: rgba(255, 255, 255, 0.15);
    color: #ffffff;
}

/* ============================================================ */
/* ===== TABS ===== */
/* ============================================================ */
.kurumsal-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 2px;
    padding: 0 24px;
    background: #ffffff;
    border-bottom: 1px solid #eef2f6;
}

.kurumsal-tab {
    padding: 14px 20px;
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    background: transparent;
    border: none;
    cursor: pointer;
    border-bottom: 2.5px solid transparent;
    margin-bottom: -1px;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    letter-spacing: -0.2px;
}

.kurumsal-tab:hover {
    color: #3b82f6;
    background: #f8fafc;
}

.kurumsal-tab.active {
    color: #3b82f6;
    border-bottom-color: #3b82f6;
    background: #f8fafc;
}

.kurumsal-tab i {
    font-size: 14px;
}

/* ============================================================ */
/* ===== TAB İÇERİKLERİ ===== */
/* ============================================================ */
.tab-pane {
    display: none;
    padding: 28px;
    animation: fadeIn 0.3s ease;
}

.tab-pane.active {
    display: block;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ============================================================ */
/* ===== FORM CARDS ===== */
/* ============================================================ */
.form-card {
    background: #ffffff;
    border-radius: 14px;
    border: 1px solid #eef2f6;
    margin-bottom: 20px;
    overflow: hidden;
    transition: all 0.2s ease;
}

.form-card:hover {
    border-color: #d1d9e6;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.form-card-header {
    background: #fafbfc;
    padding: 12px 18px;
    border-bottom: 1px solid #eef2f6;
    font-weight: 600;
    font-size: 13px;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 10px;
    letter-spacing: -0.2px;
}

.form-card-header i {
    color: #3b82f6;
    font-size: 14px;
}

.form-card-body {
    padding: 16px 18px 18px;
}

/* ============================================================ */
/* ===== FORM ELEMENTS ===== */
/* ============================================================ */
.mb-4 {
    margin-bottom: 16px;
}

.form-label {
    display: block;
    font-weight: 600;
    font-size: 12px;
    color: #475569;
    margin-bottom: 4px;
    letter-spacing: -0.1px;
}

.form-control {
    width: 100%;
    padding: 10px 14px;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.2s ease;
    background: #ffffff;
    color: #1e293b;
}

.form-control:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.08);
}

.form-control::placeholder {
    color: #94a3b8;
    font-size: 13px;
}

textarea.form-control {
    resize: vertical;
    min-height: 80px;
    font-family: inherit;
}

/* ============================================================ */
/* ===== GRID ===== */
/* ============================================================ */
.grid-cols-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

/* ============================================================ */
/* ===== BUTTONS ===== */
/* ============================================================ */
.btn-add {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 8px 18px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    letter-spacing: -0.2px;
    box-shadow: 0 1px 2px rgba(59, 130, 246, 0.2);
}

.btn-add:hover {
    background: #2563eb;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-add.btn-danger {
    background: #dc2626;
    box-shadow: 0 1px 2px rgba(220, 38, 38, 0.2);
}

.btn-add.btn-danger:hover {
    background: #b91c1c;
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
}

.btn-add.btn-secondary {
    background: #64748b;
    box-shadow: 0 1px 2px rgba(100, 116, 139, 0.2);
}

.btn-add.btn-secondary:hover {
    background: #475569;
    box-shadow: 0 4px 12px rgba(100, 116, 139, 0.3);
}

.btn-save-modal {
    padding: 10px 28px;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    border: none;
    border-radius: 10px;
    color: white;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.25);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    letter-spacing: -0.2px;
}

.btn-save-modal:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.35);
}

.btn-cancel {
    padding: 10px 24px;
    background: #f1f5f9;
    border: none;
    border-radius: 10px;
    font-weight: 500;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    color: #475569;
}

.btn-cancel:hover {
    background: #e2e8f0;
}

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid #eef2f6;
    position: sticky;
    bottom: 0;
    background: white;
    z-index: 50;
    padding-bottom: 4px;
}

/* ============================================================ */
/* ===== SSS GRID ===== */
/* ============================================================ */
#sssListesiContainer {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 14px;
}

.sss-grid-item {
    background: #ffffff;
    border-radius: 14px;
    border: 1px solid #eef2f6;
    padding: 16px 18px;
    transition: all 0.25s ease;
    display: flex;
    flex-direction: column;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    cursor: default;
    position: relative;
}

.sss-grid-item:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 16px rgba(59, 130, 246, 0.1);
    transform: translateY(-2px);
}

.sss-grid-item .sss-checkbox {
    margin-top: 3px;
    width: 17px;
    height: 17px;
    cursor: pointer;
    accent-color: #3b82f6;
    flex-shrink: 0;
    border-radius: 4px;
}

/* ============================================================ */
/* ===== MODAL ===== */
/* ============================================================ */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(8px);
    z-index: 99999;
    align-items: center;
    justify-content: center;
    animation: modalFade 0.25s ease;
}

@keyframes modalFade {
    from {
        opacity: 0;
        transform: scale(0.96);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.modal-content {
    background: #ffffff;
    border-radius: 24px;
    width: 94%;
    max-width: 560px;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    display: flex;
    flex-direction: column;
}

.modal-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    padding: 16px 24px;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h4 {
    font-size: 1.05rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
}

.modal-header button {
    background: rgba(255, 255, 255, 0.1);
    border: none;
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.modal-header button:hover {
    background: rgba(255, 255, 255, 0.2);
}

.modal-body {
    padding: 24px;
    overflow-y: auto;
    flex: 1;
}

/* ============================================================ */
/* ===== RESPONSIVE ===== */
/* ============================================================ */
@media (max-width: 768px) {
    .kurumsal-header {
        flex-direction: column;
        gap: 12px;
        padding: 16px 20px;
        text-align: center;
    }

    .grid-cols-2 {
        grid-template-columns: 1fr;
        gap: 16px;
    }

    .kurumsal-tabs {
        overflow-x: auto;
        flex-wrap: nowrap;
        padding: 0 16px;
        gap: 0;
    }

    .kurumsal-tab {
        padding: 12px 16px;
        font-size: 12px;
        white-space: nowrap;
    }

    .tab-pane {
        padding: 16px;
    }

    .form-actions {
        flex-direction: column;
    }

    .form-actions button {
        width: 100%;
        justify-content: center;
    }

    #sssListesiContainer {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .kurumsal-container {
        padding: 12px;
    }

    .kurumsal-header h1 {
        font-size: 1.1rem;
    }

    .modal-content {
        width: 98%;
        max-width: 100%;
        border-radius: 16px;
    }

    .modal-body {
        padding: 16px;
    }
}
</style>
<!-- Cropper.js CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
<!-- Cropper.js JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
<div class="kurumsal-container">
    <div class="kurumsal-card">
        
        <!-- BAŞLIK -->
        <div class="kurumsal-header">
            <h1><i class="fas fa-home"></i> Anasayfa Yönetimi</h1>
            <div style="display:flex; gap:8px;">
                <button onclick="location.reload()" class="btn-refresh" style="padding:8px 18px; background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.05); border-radius:10px; color:#94a3b8; cursor:pointer;">Yenile</button>
            </div>
        </div>
        
        <!-- ÜST SEKMELER -->
        <div class="kurumsal-tabs">
            <button class="kurumsal-tab active" data-tab="tab-icerik">
                <i class="fas fa-heading text-blue-500"></i> Başlıklar & Metinler
            </button>
            <button class="kurumsal-tab" data-tab="tab-tedaviler">
                <i class="fas fa-list text-purple-500"></i> Tedavi Metinleri
            </button>
            <button class="kurumsal-tab" data-tab="tab-sss">
                <i class="fas fa-question-circle text-green-500"></i> SSS Yönetimi
            </button>
			<button class="kurumsal-tab" data-tab="tab-slider">
    <i class="fas fa-images text-purple-500"></i> Slider Yönetimi
</button>
            <button class="kurumsal-tab" data-tab="tab-seo">
                <i class="fas fa-search text-indigo-500"></i> SEO Ayarları
            </button>
        </div>
        
        <!-- ==================== TAB 1: BAŞLIKLAR & METİNLER ==================== -->
        <div class="tab-pane active" id="tab-icerik">
            <div class="grid grid-cols-2 gap-6">
                
                <!-- Tedaviler Bölümü -->
                <div>
                    <div class="form-card">
                        <div class="form-card-header"><i class="fas fa-heading text-blue-500"></i> Tedaviler Bölümü</div>
                        <div class="form-card-body">
                            <div class="mb-4">
                                <label class="form-label">Ana Başlık (TR)</label>
                                <input type="text" id="tedaviler_baslik_tr" class="form-control">
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Ana Başlık (EN)</label>
                                <input type="text" id="tedaviler_baslik_en" class="form-control">
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Alt Başlık (TR)</label>
                                <input type="text" id="tedaviler_alt_baslik_tr" class="form-control">
                            </div>
                            <div>
                                <label class="form-label">Alt Başlık (EN)</label>
                                <input type="text" id="tedaviler_alt_baslik_en" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sütun Başlıkları -->
                <div>
                    <div class="form-card">
                        <div class="form-card-header"><i class="fas fa-columns text-purple-500"></i> Sütun Başlıkları</div>
                        <div class="form-card-body">
                            <div class="mb-4">
                                <label class="form-label">Sol Sütun (TR)</label>
                                <input type="text" id="sol_sutun_baslik_tr" class="form-control">
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Sol Sütun (EN)</label>
                                <input type="text" id="sol_sutun_baslik_en" class="form-control">
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Sağ Sütun (TR)</label>
                                <input type="text" id="sag_sutun_baslik_tr" class="form-control">
                            </div>
                            <div>
                                <label class="form-label">Sağ Sütun (EN)</label>
                                <input type="text" id="sag_sutun_baslik_en" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Akademik Vizyon & Randevu -->
                <div>
                    <div class="form-card">
                        <div class="form-card-header"><i class="fas fa-trophy text-yellow-500"></i> Akademik Vizyon & Randevu</div>
                        <div class="form-card-body">
                            <div class="mb-4">
                                <label class="form-label">Vizyon Başlığı (TR)</label>
                                <input type="text" id="akademik_vizyon_baslik_tr" class="form-control">
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Vizyon Başlığı (EN)</label>
                                <input type="text" id="akademik_vizyon_baslik_en" class="form-control">
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Randevu Başlığı (TR)</label>
                                <input type="text" id="randevu_baslik_tr" class="form-control">
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Randevu Başlığı (EN)</label>
                                <input type="text" id="randevu_baslik_en" class="form-control">
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Randevu Buton Yazısı (TR)</label>
                                <input type="text" id="randevu_buton_yazi_tr" class="form-control" placeholder="Hemen Randevu Al">
                            </div>
                            <div>
                                <label class="form-label">Randevu Buton Yazısı (EN)</label>
                                <input type="text" id="randevu_buton_yazi_en" class="form-control" placeholder="Book Now">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Doktor & SSS Bölümü -->
                <div>
                    <div class="form-card">
                        <div class="form-card-header"><i class="fas fa-user-md text-green-500"></i> Doktor & SSS Bölümü</div>
                        <div class="form-card-body">
                            <div class="mb-4">
                                <label class="form-label">Doktor Açıklaması (TR)</label>
                                <textarea id="doktor_aciklama_tr" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Doktor Açıklaması (EN)</label>
                                <textarea id="doktor_aciklama_en" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">SSS Başlığı (TR)</label>
                                <input type="text" id="sss_baslik_tr" class="form-control">
                            </div>
                            <div class="mb-4">
                                <label class="form-label">SSS Başlığı (EN)</label>
                                <input type="text" id="sss_baslik_en" class="form-control">
                            </div>
                            <div class="mb-4">
                                <label class="form-label">SSS Alt Başlığı (TR)</label>
                                <input type="text" id="sss_alt_baslik_tr" class="form-control" placeholder="Tedavilerimiz hakkında merak edilenler">
                            </div>
                            <div>
                                <label class="form-label">SSS Alt Başlığı (EN)</label>
                                <input type="text" id="sss_alt_baslik_en" class="form-control" placeholder="What you wonder about our treatments">
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn-save-modal" onclick="saveIcerik()">
                    <i class="fas fa-save"></i> Bu Bölümü Kaydet
                </button>
            </div>
        </div>
        
        <!-- ==================== TAB 2: TEDAVİ METİNLERİ ==================== -->
        <div class="tab-pane" id="tab-tedaviler">
            
            <div class="grid grid-cols-2 gap-6">
                
                <!-- SOL TARAF -->
                <div>
                    <div class="form-card">
                        <div class="form-card-header" style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-tooth text-blue-500"></i>
                                <span>Sol Sütun Başlığı</span>
                            </div>
                            <input type="text" id="sol_sutun_baslik_tr2" class="form-control" style="width: auto; max-width: 300px; padding: 6px 12px; font-size: 13px;" placeholder="✨ Estetik & Gülüş Tasarımı">
                        </div>
                        <div class="form-card-body">
                            
                            <!-- 1. Diş Estetiği -->
                            <div class="mb-4 border-b border-gray-100 pb-4">
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <input type="text" id="tedavi_dis_estetigi_icon" class="form-control" style="width: 80px;" placeholder="🦷">
                                    <input type="text" id="tedavi_dis_estetigi_title" class="form-control" style="flex: 1;" placeholder="Diş Estetiği">
                                </div>
                                <textarea id="tedavi_dis_estetigi" class="form-control" style="margin-top:8px;" rows="3" placeholder="Açıklama..."></textarea>
                            </div>
                            
                            <!-- 2. Diş Ağrısı -->
                            <div class="mb-4 border-b border-gray-100 pb-4">
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <input type="text" id="tedavi_dis_agrisi_icon" class="form-control" style="width: 80px;" placeholder="🦷">
                                    <input type="text" id="tedavi_dis_agrisi_title" class="form-control" style="flex: 1;" placeholder="Diş Ağrısı">
                                </div>
                                <textarea id="tedavi_dis_agrisi" class="form-control" style="margin-top:8px;" rows="3" placeholder="Açıklama..."></textarea>
                            </div>
                            
                            <!-- 3. İmplant Tedavisi -->
                            <div class="mb-4 border-b border-gray-100 pb-4">
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <input type="text" id="tedavi_implant_icon" class="form-control" style="width: 80px;" placeholder="💉">
                                    <input type="text" id="tedavi_implant_title" class="form-control" style="flex: 1;" placeholder="İmplant Tedavisi">
                                </div>
                                <textarea id="tedavi_implant" class="form-control" style="margin-top:8px;" rows="3" placeholder="Açıklama..."></textarea>
                            </div>
                            
                            <!-- 4. Diş Eti Hastalıkları -->
                            <div class="mb-4 border-b border-gray-100 pb-4">
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <input type="text" id="tedavi_dis_eti_icon" class="form-control" style="width: 80px;" placeholder="🩸">
                                    <input type="text" id="tedavi_dis_eti_title" class="form-control" style="flex: 1;" placeholder="Diş Eti Hastalıkları">
                                </div>
                                <textarea id="tedavi_dis_eti" class="form-control" style="margin-top:8px;" rows="3" placeholder="Açıklama..."></textarea>
                            </div>
                            
                            <!-- 5. Kanal Tedavisi -->
                            <div>
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <input type="text" id="tedavi_kanal_icon" class="form-control" style="width: 80px;" placeholder="🔬">
                                    <input type="text" id="tedavi_kanal_title" class="form-control" style="flex: 1;" placeholder="Kanal Tedavisi">
                                </div>
                                <textarea id="tedavi_kanal" class="form-control" style="margin-top:8px;" rows="3" placeholder="Açıklama..."></textarea>
                            </div>

                        </div>
                    </div>
                </div>
                
                <!-- SAĞ TARAF -->
                <div>
                    <div class="form-card">
                        <div class="form-card-header" style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-smile text-teal-500"></i>
                                <span>Sağ Sütun Başlığı</span>
                            </div>
                            <input type="text" id="sag_sutun_baslik_tr2" class="form-control" style="width: auto; max-width: 300px; padding: 6px 12px; font-size: 13px;" placeholder="💉 Cerrahi & İmplantoloji">
                        </div>
                        <div class="form-card-body">
                            
                            <!-- 6. Ortodontik Tedavi -->
                            <div class="mb-4 border-b border-gray-100 pb-4">
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <input type="text" id="tedavi_ortodonti_icon" class="form-control" style="width: 80px;" placeholder="😬">
                                    <input type="text" id="tedavi_ortodonti_title" class="form-control" style="flex: 1;" placeholder="Ortodontik Tedavi">
                                </div>
                                <textarea id="tedavi_ortodonti" class="form-control" style="margin-top:8px;" rows="3" placeholder="Açıklama..."></textarea>
                            </div>
                            
                            <!-- 7. Çocuk Diş Tedavisi -->
                            <div class="mb-4 border-b border-gray-100 pb-4">
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <input type="text" id="tedavi_cocuk_icon" class="form-control" style="width: 80px;" placeholder="👶">
                                    <input type="text" id="tedavi_cocuk_title" class="form-control" style="flex: 1;" placeholder="Çocuk Diş Tedavisi">
                                </div>
                                <textarea id="tedavi_cocuk" class="form-control" style="margin-top:8px;" rows="3" placeholder="Açıklama..."></textarea>
                            </div>
                            
                            <!-- 8. Çene Eklemi Rahatsızlıkları -->
                            <div class="mb-4 border-b border-gray-100 pb-4">
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <input type="text" id="tedavi_cene_eklemi_icon" class="form-control" style="width: 80px;" placeholder="🦴">
                                    <input type="text" id="tedavi_cene_eklemi_title" class="form-control" style="flex: 1;" placeholder="Çene Eklemi Rahatsızlıkları">
                                </div>
                                <textarea id="tedavi_cene_eklemi" class="form-control" style="margin-top:8px;" rows="3" placeholder="Açıklama..."></textarea>
                            </div>
                            
                            <!-- 9. Diş Beyazlatma -->
                            <div class="mb-4 border-b border-gray-100 pb-4">
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <input type="text" id="tedavi_beyazlatma_icon" class="form-control" style="width: 80px;" placeholder="⭐">
                                    <input type="text" id="tedavi_beyazlatma_title" class="form-control" style="flex: 1;" placeholder="Diş Beyazlatma">
                                </div>
                                <textarea id="tedavi_beyazlatma" class="form-control" style="margin-top:8px;" rows="3" placeholder="Açıklama..."></textarea>
                            </div>
                            
                            <!-- 10. Samsun'da Diş Hekimi -->
                            <div>
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <input type="text" id="tedavi_samsun_icon" class="form-control" style="width: 80px;" placeholder="📍">
                                    <input type="text" id="tedavi_samsun_title" class="form-control" style="flex: 1;" placeholder="Samsun'da Diş Hekimi">
                                </div>
                                <textarea id="tedavi_samsun" class="form-control" style="margin-top:8px;" rows="3" placeholder="Açıklama..."></textarea>
                            </div>

                        </div>
                    </div>
                </div>
                
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn-save-modal" onclick="saveTedaviMetinleri()">
                    <i class="fas fa-save"></i> Bu Bölümü Kaydet
                </button>
            </div>
        </div>

<!-- ==================== TAB 3: SSS YÖNETİMİ ==================== -->
<div class="tab-pane" id="tab-sss">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
        <h3 style="font-size: 16px; font-weight: 700; color: #1e293b;">
            <i class="fas fa-list text-green-500"></i> SSS Soruları 
            <span id="sssSayac" style="font-size: 13px; font-weight: 400; color: #94a3b8; margin-left: 8px;"></span>
        </h3>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <!-- 🔥 TÜMÜNÜ SEÇ / KALDIR -->
            <button type="button" class="btn-add" style="background: #64748b; padding: 6px 14px; font-size: 12px;" onclick="tumunuSec()">
                <i class="fas fa-check-double"></i> Tümünü Seç
            </button>
            <button type="button" class="btn-add" style="background: #94a3b8; padding: 6px 14px; font-size: 12px;" onclick="tumunuKaldir()">
                <i class="fas fa-times"></i> Tümünü Kaldır
            </button>
            <!-- 🔥 TOPLU SİL BUTONU -->
            <button type="button" id="topluSilBtn" class="btn-add" style="background: #dc2626; display: none;" onclick="topluSilSSS()">
                <i class="fas fa-trash"></i> Seçilenleri Sil (<span id="seciliSayac">0</span>)
            </button>
            <button type="button" class="btn-add" onclick="openSSSModal(null)">
                <i class="fas fa-plus-circle"></i> Yeni Soru Ekle
            </button>
        </div>
    </div>
    
    <!-- SSS GRID LISTESI -->
    <div id="sssListesiContainer" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 12px;">
        <p style="color: #94a3b8; text-align: center; grid-column: 1 / -1; padding: 40px 0;">SSS'ler yükleniyor...</p>
    </div>
</div> 

<!-- ==================== TAB 3.5: SLIDER YÖNETİMİ ==================== -->
<div class="tab-pane" id="tab-slider">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
        <h3 style="font-size: 16px; font-weight: 700; color: #1e293b;">
            <i class="fas fa-images text-purple-500"></i> Slider Yönetimi 
            <span id="sliderSayac" style="font-size: 13px; font-weight: 400; color: #94a3b8; margin-left: 8px;"></span>
        </h3>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="btn-add" onclick="openSliderModal(null)">
                <i class="fas fa-plus-circle"></i> Yeni Slider Ekle
            </button>
        </div>
    </div>
    
    <!-- Slider Grid Listesi -->
    <div id="sliderListesiContainer" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 14px;">
        <p style="color: #94a3b8; text-align: center; grid-column: 1 / -1; padding: 40px 0;">Slider'lar yükleniyor...</p>
    </div>
</div>

<!-- ==================== SLIDER MODAL - BÜYÜK ==================== -->
<div id="sliderModal" class="modal">
    <div class="modal-content" style="max-width: 860px; max-height: 95vh; overflow-y: auto; border-radius: 20px;">
        <div class="modal-header" style="padding: 14px 24px; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 20px 20px 0 0; display: flex; justify-content: space-between; align-items: center;">
            <h4 id="sliderModalTitle" style="font-size: 18px; color: white; margin: 0; display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-plus-circle text-green-400"></i> Slider Ekle / Düzenle
            </h4>
            <button onclick="closeSliderModal()" style="background: rgba(255,255,255,0.1); border: none; color: white; width: 34px; height: 34px; border-radius: 50%; cursor: pointer; font-size: 18px; display: flex; align-items: center; justify-content: center; transition: all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'">✕</button>
        </div>
<div class="modal-body" style="padding: 20px 24px 24px;">
    <input type="hidden" id="edit_slider_id" value="">
    
    <!-- 2 KOLON: SOL = MEVCUT RESİM, SAĞ = YENİ RESİM YÜKLE -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 16px;">
        
        <!-- SOL KOLON: MEVCUT RESİM (ORİJİNAL) -->
        <div>
            <label style="font-size: 12px; font-weight: 600; color: #475569; display: block; margin-bottom: 4px;">📷 Mevcut Görsel</label>
            <div id="currentImageContainer" style="
                border: 2px solid #e2e8f0; 
                border-radius: 12px; 
                overflow: hidden; 
                background: #f8fafc;
                height: 220px;
                display: flex;
                align-items: center;
                justify-content: center;
                position: relative;
            ">
                <img id="currentSliderImage" src="" alt="Mevcut Görsel" style="
                    max-width: 100%;
                    max-height: 100%;
                    object-fit: contain;
                    display: none;
                ">
                <span id="noImageText" style="color: #94a3b8; font-size: 13px;">
                    <i class="fas fa-image" style="display: block; font-size: 32px; margin-bottom: 8px;"></i>
                    Görsel yüklenmemiş
                </span>
            </div>
            <div style="margin-top: 6px; font-size: 11px; color: #94a3b8; text-align: center;">
                <i class="fas fa-info-circle"></i> Bu görsel slider'da gösterilir
            </div>
        </div>
        
        <!-- SAĞ KOLON: RESİM SEÇME ALANI -->
        <div>
            <label style="font-size: 12px; font-weight: 600; color: #475569; display: block; margin-bottom: 4px;">🔄 Resmi Değiştir</label>
            
            <!-- DropZone (sadece sağda) -->
            <div id="sliderDropZone" style="
                border: 2px dashed #d1d5db;
                border-radius: 12px;
                padding: 20px;
                text-align: center;
                cursor: pointer;
                transition: all 0.2s ease;
                background: #fafbfc;
                height: 220px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            " onmouseover="this.style.borderColor='#3b82f6'; this.style.background='#f0f4ff';" 
               onmouseout="this.style.borderColor='#d1d5db'; this.style.background='#fafbfc';">
                <div id="sliderDropZoneContent">
                    <i class="fas fa-cloud-upload-alt" style="font-size: 32px; color: #94a3b8;"></i>
                    <p style="color: #64748b; font-size: 13px; margin-top: 6px;">Yeni görsel yüklemek için tıklayın</p>
                    <p style="color: #94a3b8; font-size: 11px;">1920x1080 px önerilir</p>
                </div>
                <img id="sliderPreview" src="" alt="Önizleme" style="
                    max-width: 100%;
                    max-height: 120px;
                    border-radius: 8px;
                    display: none;
                    margin-top: 8px;
                    object-fit: contain;
                ">
                <input type="file" id="sliderImageInput" accept="image/*" style="display:none;">
                <input type="hidden" id="slider_image_url" value="">
            </div>
            
            <!-- Yeni resim yüklendikten sonra gösterilecek bilgi -->
            <div id="newImageInfo" style="display: none; margin-top: 6px; font-size: 11px; color: #10b981; text-align: center;">
                <i class="fas fa-check-circle"></i> <span id="newImageSize">Yeni görsel yüklendi</span>
            </div>
        </div>
        
    </div>

    <!-- Cropper (açılır kapanır) -->
    <div id="cropperContainer" style="display: none; margin-bottom: 16px;">
        <div style="background: #0f172a; border-radius: 12px; padding: 12px; max-height: 350px; overflow: hidden; position: relative;">
            <img id="cropperImage" src="" alt="Kırpılacak Görsel" style="max-width: 100%; max-height: 320px; display: block; object-fit: contain;">
        </div>
        
        <!-- Cropper Kontrolleri -->
        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; align-items: center; justify-content: center; padding: 10px 14px; background: #f8fafc; border-radius: 10px; border: 1px solid #eef2f6;">
            <button type="button" onclick="rotateCropper(-90)" style="padding: 6px 14px; border-radius: 6px; background: #f1f5f9; border: 1px solid #e2e8f0; cursor: pointer; font-size: 12px; font-weight: 500; display: inline-flex; align-items: center; gap: 4px;">
                <i class="fas fa-undo"></i> Sola Döndür
            </button>
            <button type="button" onclick="rotateCropper(90)" style="padding: 6px 14px; border-radius: 6px; background: #f1f5f9; border: 1px solid #e2e8f0; cursor: pointer; font-size: 12px; font-weight: 500; display: inline-flex; align-items: center; gap: 4px;">
                <i class="fas fa-redo"></i> Sağa Döndür
            </button>
            <div style="width: 1px; height: 24px; background: #e2e8f0;"></div>
            <button type="button" onclick="resetCropper()" style="padding: 6px 14px; border-radius: 6px; background: #dbeafe; border: 1px solid #bfdbfe; cursor: pointer; font-size: 12px; font-weight: 500; display: inline-flex; align-items: center; gap: 4px; color: #2563eb;">
                <i class="fas fa-undo-alt"></i> Orijinal Hal
            </button>
            <div style="width: 1px; height: 24px; background: #e2e8f0;"></div>
            <button type="button" onclick="toggleCropAspect()" id="cropAspectBtn" style="padding: 6px 14px; border-radius: 6px; background: #fef3c7; border: 1px solid #fcd34d; cursor: pointer; font-size: 12px; font-weight: 500; display: inline-flex; align-items: center; gap: 4px; color: #b45309;">
                <i class="fas fa-expand"></i> <span id="cropAspectLabel">Serbest Kırp</span>
            </button>
            <div style="width: 1px; height: 24px; background: #e2e8f0;"></div>
            <button type="button" onclick="applyCrop()" style="padding: 8px 22px; border-radius: 6px; background: #10b981; border: none; cursor: pointer; font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; color: white; box-shadow: 0 2px 10px rgba(16,185,129,0.3);">
                <i class="fas fa-check-circle"></i> KIRP VE KAYDET
            </button>
            <div style="width: 1px; height: 24px; background: #e2e8f0;"></div>
            <label style="font-size: 12px; display: flex; align-items: center; gap: 6px; color: #64748b;">
                <i class="fas fa-file-image"></i> Kalite:
                <input type="range" id="qualityRange" min="50" max="100" value="85" oninput="document.getElementById('quality').value=this.value; document.getElementById('qualityLabel').textContent=this.value+'%'" style="width: 80px;">
                <span id="qualityLabel" style="font-size: 11px; font-weight: 600; color: #1e293b;">85%</span>
            </label>
            <input type="hidden" id="cropX" value="0">
            <input type="hidden" id="cropY" value="0">
            <input type="hidden" id="cropW" value="0">
            <input type="hidden" id="cropH" value="0">
            <input type="hidden" id="rotate" value="0">
            <input type="hidden" id="quality" value="85">
            <input type="hidden" id="cropAspectMode" value="free">
        </div>
        
        <!-- Önizleme -->
        <div id="previewContainer" style="margin-top: 10px; display: none;">
            <label style="font-size: 11px; font-weight: 600; color: #475569; display: block; margin-bottom: 4px;">Önizleme</label>
            <div style="border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; background: #f8fafc; padding: 8px;">
                <img id="sliderPreview" src="" alt="Önizleme" style="max-width: 100%; max-height: 100px; display: block; margin: 0 auto; object-fit: contain;">
            </div>
        </div>
    </div>

    <!-- Form Alanları - 2 KOLON -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
        <div>
            <label style="font-size: 11px; font-weight: 600; color: #475569; display: block; margin-bottom: 2px;">Başlık (TR)</label>
            <input type="text" id="slider_title_tr" class="form-control" style="padding: 6px 12px; font-size: 13px; border-radius: 8px; border: 1.5px solid #e2e8f0; width: 100%;" placeholder="Geleceğin Gülüşünü">
        </div>
        <div>
            <label style="font-size: 11px; font-weight: 600; color: #475569; display: block; margin-bottom: 2px;">Başlık (EN)</label>
            <input type="text" id="slider_title_en" class="form-control" style="padding: 6px 12px; font-size: 13px; border-radius: 8px; border: 1.5px solid #e2e8f0; width: 100%;" placeholder="The Future of Your Smile">
        </div>
        <div>
            <label style="font-size: 11px; font-weight: 600; color: #475569; display: block; margin-bottom: 2px;">Alt Başlık (TR)</label>
            <input type="text" id="slider_subtitle_tr" class="form-control" style="padding: 6px 12px; font-size: 13px; border-radius: 8px; border: 1.5px solid #e2e8f0; width: 100%;" placeholder="Bugünden Tasarlıyoruz">
        </div>
        <div>
            <label style="font-size: 11px; font-weight: 600; color: #475569; display: block; margin-bottom: 2px;">Alt Başlık (EN)</label>
            <input type="text" id="slider_subtitle_en" class="form-control" style="padding: 6px 12px; font-size: 13px; border-radius: 8px; border: 1.5px solid #e2e8f0; width: 100%;" placeholder="Designing Today">
        </div>
        <div>
            <label style="font-size: 11px; font-weight: 600; color: #475569; display: block; margin-bottom: 2px;">Rozet (TR)</label>
            <input type="text" id="slider_badge_tr" class="form-control" style="padding: 6px 12px; font-size: 13px; border-radius: 8px; border: 1.5px solid #e2e8f0; width: 100%;" placeholder="PROF. DR. İBRAHİM DURAN">
        </div>
        <div>
            <label style="font-size: 11px; font-weight: 600; color: #475569; display: block; margin-bottom: 2px;">Rozet (EN)</label>
            <input type="text" id="slider_badge_en" class="form-control" style="padding: 6px 12px; font-size: 13px; border-radius: 8px; border: 1.5px solid #e2e8f0; width: 100%;" placeholder="PROF. DR. IBRAHIM DURAN">
        </div>
        <div>
            <label style="font-size: 11px; font-weight: 600; color: #475569; display: block; margin-bottom: 2px;">Özellikler (TR) - Virgülle</label>
            <input type="text" id="slider_features_tr" class="form-control" style="padding: 6px 12px; font-size: 13px; border-radius: 8px; border: 1.5px solid #e2e8f0; width: 100%;" placeholder="Şeffaf Plak, Estetik Dolgu">
        </div>
        <div>
            <label style="font-size: 11px; font-weight: 600; color: #475569; display: block; margin-bottom: 2px;">Özellikler (EN) - Virgülle</label>
            <input type="text" id="slider_features_en" class="form-control" style="padding: 6px 12px; font-size: 13px; border-radius: 8px; border: 1.5px solid #e2e8f0; width: 100%;" placeholder="Clear Aligners, Aesthetic Filling">
        </div>
        <div>
            <label style="font-size: 11px; font-weight: 600; color: #475569; display: block; margin-bottom: 2px;">Durum</label>
            <select id="slider_aktif" class="form-control" style="padding: 6px 12px; font-size: 13px; border-radius: 8px; border: 1.5px solid #e2e8f0; width: 100%;">
                <option value="1">✅ Aktif</option>
                <option value="0">❌ Pasif</option>
            </select>
        </div>
    </div>
    
    <!-- Butonlar -->
    <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 18px; padding-top: 14px; border-top: 1px solid #eef2f6;">
        <button type="button" style="padding: 8px 20px; font-size: 13px; border-radius: 8px; border: none; background: #f1f5f9; color: #475569; cursor: pointer; font-weight: 500; transition: all 0.2s;" onclick="closeSliderModal()" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">İptal</button>
        <button type="button" id="sliderSaveBtn" style="padding: 8px 24px; font-size: 13px; border-radius: 8px; border: none; background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: all 0.2s; box-shadow: 0 2px 8px rgba(59,130,246,0.2);" onclick="saveSlider()" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(59,130,246,0.3)'" onmouseout="this.style.transform='none'; this.style.boxShadow='0 2px 8px rgba(59,130,246,0.2)'">
            <i class="fas fa-save"></i> Kaydet
        </button>
    </div>
</div>

  </div>
</div>

  <!-- ==================== TAB 4: SEO AYARLARI ==================== -->
        <div class="tab-pane" id="tab-seo">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <div class="form-card">
                        <div class="form-card-header"><i class="fas fa-search text-indigo-500"></i> Meta Başlıklar</div>
                        <div class="form-card-body">
                            <div class="mb-4">
                                <label class="form-label">SEO Başlık (TR)</label>
                                <input type="text" id="seo_title_tr" class="form-control">
                            </div>
                            <div>
                                <label class="form-label">SEO Başlık (EN)</label>
                                <input type="text" id="seo_title_en" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <div class="form-card">
                        <div class="form-card-header"><i class="fas fa-file-alt text-blue-500"></i> Meta Açıklamalar</div>
                        <div class="form-card-body">
                            <div class="mb-4">
                                <label class="form-label">Meta Açıklama (TR)</label>
                                <textarea id="seo_description_tr" class="form-control" rows="3"></textarea>
                            </div>
                            <div>
                                <label class="form-label">Meta Açıklama (EN)</label>
                                <textarea id="seo_description_en" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <div class="form-card">
                        <div class="form-card-header"><i class="fas fa-tags text-yellow-500"></i> Anahtar Kelimeler</div>
                        <div class="form-card-body">
                            <div class="mb-4">
                                <label class="form-label">Anahtar Kelimeler (TR)</label>
                                <input type="text" id="seo_keywords_tr" class="form-control">
                            </div>
                            <div>
                                <label class="form-label">Anahtar Kelimeler (EN)</label>
                                <input type="text" id="seo_keywords_en" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <div class="form-card">
                        <div class="form-card-header"><i class="fas fa-image text-purple-500"></i> OG Görsel & Canonical</div>
                        <div class="form-card-body">
                            <div class="mb-4">
                                <label class="form-label">OG Görsel URL</label>
                                <div style="display:flex; gap:8px;">
                                    <input type="text" id="seo_og_image" class="form-control" placeholder="/uploads/...">
                                    <button type="button" class="btn-save-modal" style="padding:6px 14px; font-size:12px;" onclick="uploadImage('seo_og_image')">
                                        <i class="fas fa-upload"></i> Yükle
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Canonical URL</label>
                                <input type="text" id="seo_canonical" class="form-control" placeholder="https://www.dribrahimdurandentalclinic.com/">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn-save-modal" onclick="saveSeo()">
                    <i class="fas fa-save"></i> Bu Bölümü Kaydet
                </button>
            </div>
        </div>
        
    </div>
</div>

<!-- SSS EKLE / DÜZENLE MODAL -->
<div id="sssModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h4 id="sssModalTitle"><i class="fas fa-plus-circle text-green-400"></i> Yeni SSS Ekle</h4>
            <button onclick="closeSSSModal()" class="w-8 h-8 bg-white/10 hover:bg-white/20 rounded-xl transition text-white"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="edit_sss_id" value="">
            <div class="form-card">
                <div class="form-card-body">
                    <div class="mb-4">
                        <label class="form-label">Kategori</label>
                        <input type="text" id="sss_kategori" class="form-control" placeholder="Örn: İmplant Tedavisi">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Soru</label>
                        <textarea id="sss_soru" class="form-control" rows="2" placeholder="Soruyu yazın..."></textarea>
                    </div>
                    <div>
                        <label class="form-label">Cevap</label>
                        <textarea id="sss_cevap" class="form-control" rows="4" placeholder="Cevabı yazın..."></textarea>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="closeSSSModal()">İptal</button>
                <button type="button" class="btn-save-modal" onclick="saveSSS()"><i class="fas fa-save"></i> Kaydet</button>
            </div>
        </div>
    </div>
</div>

<script>
const csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';
const ajaxUrl = '/admin/modules/anasayfa/index.php';

// ========== TAB GEÇİŞİ ==========
document.querySelectorAll('.kurumsal-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.kurumsal-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
        document.getElementById(this.getAttribute('data-tab')).classList.add('active');
        
        var tabId = this.getAttribute('data-tab');
        if(tabId === 'tab-sss') {
            setTimeout(loadSSSList, 100);
        }
        if(tabId === 'tab-slider') {
            setTimeout(loadSliderList, 100);
        }
    });
});
// ========== VERİLERİ YÜKLE ==========
function loadData() {
    fetch(ajaxUrl + '?islem=get')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const d = data.data;
                
                // Tedaviler Bölümü
                var el = document.getElementById('tedaviler_baslik_tr');
                if (el) el.value = d.tedaviler_baslik_tr || '';
                el = document.getElementById('tedaviler_baslik_en');
                if (el) el.value = d.tedaviler_baslik_en || '';
                el = document.getElementById('tedaviler_alt_baslik_tr');
                if (el) el.value = d.tedaviler_alt_baslik_tr || '';
                el = document.getElementById('tedaviler_alt_baslik_en');
                if (el) el.value = d.tedaviler_alt_baslik_en || '';
                
                // Sütun Başlıkları
                el = document.getElementById('sol_sutun_baslik_tr');
                if (el) el.value = d.sol_sutun_baslik_tr || '';
                el = document.getElementById('sol_sutun_baslik_en');
                if (el) el.value = d.sol_sutun_baslik_en || '';
                el = document.getElementById('sag_sutun_baslik_tr');
                if (el) el.value = d.sag_sutun_baslik_tr || '';
                el = document.getElementById('sag_sutun_baslik_en');
                if (el) el.value = d.sag_sutun_baslik_en || '';
                
                // TAB 2 Başlıkları
                el = document.getElementById('sol_sutun_baslik_tr2');
                if (el) el.value = d.sol_sutun_baslik_tr || '';
                el = document.getElementById('sag_sutun_baslik_tr2');
                if (el) el.value = d.sag_sutun_baslik_tr || '';
                
                // Akademik Vizyon & Randevu
                el = document.getElementById('akademik_vizyon_baslik_tr');
                if (el) el.value = d.akademik_vizyon_baslik_tr || '';
                el = document.getElementById('akademik_vizyon_baslik_en');
                if (el) el.value = d.akademik_vizyon_baslik_en || '';
                el = document.getElementById('randevu_baslik_tr');
                if (el) el.value = d.randevu_baslik_tr || '';
                el = document.getElementById('randevu_baslik_en');
                if (el) el.value = d.randevu_baslik_en || '';
                el = document.getElementById('randevu_buton_yazi_tr');
                if (el) el.value = d.randevu_buton_yazi_tr || '';
                el = document.getElementById('randevu_buton_yazi_en');
                if (el) el.value = d.randevu_buton_yazi_en || '';
                
                // Doktor & SSS
                el = document.getElementById('doktor_aciklama_tr');
                if (el) el.value = d.doktor_aciklama_tr || '';
                el = document.getElementById('doktor_aciklama_en');
                if (el) el.value = d.doktor_aciklama_en || '';
                el = document.getElementById('sss_baslik_tr');
                if (el) el.value = d.sss_baslik_tr || '';
                el = document.getElementById('sss_baslik_en');
                if (el) el.value = d.sss_baslik_en || '';
                el = document.getElementById('sss_alt_baslik_tr');
                if (el) el.value = d.sss_alt_baslik_tr || '';
                el = document.getElementById('sss_alt_baslik_en');
                if (el) el.value = d.sss_alt_baslik_en || '';
                
                // SEO
                el = document.getElementById('seo_title_tr');
                if (el) el.value = d.seo_title_tr || '';
                el = document.getElementById('seo_title_en');
                if (el) el.value = d.seo_title_en || '';
                el = document.getElementById('seo_description_tr');
                if (el) el.value = d.seo_description_tr || '';
                el = document.getElementById('seo_description_en');
                if (el) el.value = d.seo_description_en || '';
                el = document.getElementById('seo_keywords_tr');
                if (el) el.value = d.seo_keywords_tr || '';
                el = document.getElementById('seo_keywords_en');
                if (el) el.value = d.seo_keywords_en || '';
                el = document.getElementById('seo_og_image');
                if (el) el.value = d.seo_og_image || '';
                el = document.getElementById('seo_canonical');
                if (el) el.value = d.seo_canonical || '';
                
                // Tedavi Metinleri (JSON)
                if (d.tedaviler_metin_json) {
                    try {
                        const metinler = JSON.parse(d.tedaviler_metin_json);
                        const keys = ['dis_estetigi', 'dis_agrisi', 'implant', 'dis_eti', 'kanal', 'ortodonti', 'cocuk', 'cene_eklemi', 'beyazlatma', 'samsun'];
                        
                        keys.forEach(function(key) {
                            if (metinler[key]) {
                                var iconEl = document.getElementById('tedavi_' + key + '_icon');
                                var titleEl = document.getElementById('tedavi_' + key + '_title');
                                var descEl = document.getElementById('tedavi_' + key);
                                
                                if (iconEl) iconEl.value = metinler[key].icon || '';
                                if (titleEl) titleEl.value = metinler[key].title || '';
                                if (descEl) descEl.value = metinler[key].desc || '';
                            }
                        });
                    } catch (e) {
                        console.error('JSON hatası:', e);
                    }
                }
            }
        })
        .catch(err => console.error('Fetch hatası:', err));
}

// ========== ORTAK KAYDETME (MODERN BİLDİRİM) ==========
function savePayload(payload, tabId, successMessage) {
    const btn = document.querySelector('#' + tabId + ' .btn-save-modal');
    if (!btn) {
        Swal.fire({
            icon: 'error',
            title: 'Hata!',
            text: 'Kaydet butonu bulunamadı!',
            confirmButtonColor: '#3b82f6'
        });
        return;
    }
    
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Kaydediliyor...';
    btn.disabled = true;

    fetch(ajaxUrl + '?islem=kaydet_icerik', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: successMessage,
                timer: 1500,
                timerProgressBar: true,
                showConfirmButton: false,
                background: '#ffffff',
                borderRadius: '12px'
            });
            loadData();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Hata!',
                text: result.message || 'Kaydedilirken bir hata oluştu.',
                confirmButtonColor: '#3b82f6'
            });
        }
    })
    .catch(err => {
        Swal.fire({
            icon: 'error',
            title: 'Bağlantı Hatası!',
            text: 'Sunucuya bağlanılamadı.',
            confirmButtonColor: '#3b82f6'
        });
    })
    .finally(() => { 
        btn.innerHTML = originalText; 
        btn.disabled = false; 
    });
}

// ========== TAB 1: BAŞLIKLAR & METİNLER KAYDET ==========
function saveIcerik() {
    const payload = {
        tedaviler_baslik_tr: document.getElementById('tedaviler_baslik_tr') ? document.getElementById('tedaviler_baslik_tr').value : '',
        tedaviler_baslik_en: document.getElementById('tedaviler_baslik_en') ? document.getElementById('tedaviler_baslik_en').value : '',
        tedaviler_alt_baslik_tr: document.getElementById('tedaviler_alt_baslik_tr') ? document.getElementById('tedaviler_alt_baslik_tr').value : '',
        tedaviler_alt_baslik_en: document.getElementById('tedaviler_alt_baslik_en') ? document.getElementById('tedaviler_alt_baslik_en').value : '',
        sol_sutun_baslik_tr: document.getElementById('sol_sutun_baslik_tr') ? document.getElementById('sol_sutun_baslik_tr').value : '',
        sol_sutun_baslik_en: document.getElementById('sol_sutun_baslik_en') ? document.getElementById('sol_sutun_baslik_en').value : '',
        sag_sutun_baslik_tr: document.getElementById('sag_sutun_baslik_tr') ? document.getElementById('sag_sutun_baslik_tr').value : '',
        sag_sutun_baslik_en: document.getElementById('sag_sutun_baslik_en') ? document.getElementById('sag_sutun_baslik_en').value : '',
        akademik_vizyon_baslik_tr: document.getElementById('akademik_vizyon_baslik_tr') ? document.getElementById('akademik_vizyon_baslik_tr').value : '',
        akademik_vizyon_baslik_en: document.getElementById('akademik_vizyon_baslik_en') ? document.getElementById('akademik_vizyon_baslik_en').value : '',
        randevu_baslik_tr: document.getElementById('randevu_baslik_tr') ? document.getElementById('randevu_baslik_tr').value : '',
        randevu_baslik_en: document.getElementById('randevu_baslik_en') ? document.getElementById('randevu_baslik_en').value : '',
        randevu_buton_yazi_tr: document.getElementById('randevu_buton_yazi_tr') ? document.getElementById('randevu_buton_yazi_tr').value : '',
        randevu_buton_yazi_en: document.getElementById('randevu_buton_yazi_en') ? document.getElementById('randevu_buton_yazi_en').value : '',
        doktor_aciklama_tr: document.getElementById('doktor_aciklama_tr') ? document.getElementById('doktor_aciklama_tr').value : '',
        doktor_aciklama_en: document.getElementById('doktor_aciklama_en') ? document.getElementById('doktor_aciklama_en').value : '',
        sss_baslik_tr: document.getElementById('sss_baslik_tr') ? document.getElementById('sss_baslik_tr').value : '',
        sss_baslik_en: document.getElementById('sss_baslik_en') ? document.getElementById('sss_baslik_en').value : '',
        sss_alt_baslik_tr: document.getElementById('sss_alt_baslik_tr') ? document.getElementById('sss_alt_baslik_tr').value : '',
        sss_alt_baslik_en: document.getElementById('sss_alt_baslik_en') ? document.getElementById('sss_alt_baslik_en').value : '',
        csrf_token: csrfToken
    };
    savePayload(payload, 'tab-icerik', '✅ Başlıklar ve metinler kaydedildi!');
}

// ========== TAB 2: TEDAVİ METİNLERİ KAYDET ==========
function saveTedaviMetinleri() {
    const keys = ['dis_estetigi', 'dis_agrisi', 'implant', 'dis_eti', 'kanal', 'ortodonti', 'cocuk', 'cene_eklemi', 'beyazlatma', 'samsun'];
    const jsonData = {};
    
    keys.forEach(function(key) {
        jsonData[key] = {
            icon: document.getElementById('tedavi_' + key + '_icon') ? document.getElementById('tedavi_' + key + '_icon').value : '',
            title: document.getElementById('tedavi_' + key + '_title') ? document.getElementById('tedavi_' + key + '_title').value : '',
            desc: document.getElementById('tedavi_' + key) ? document.getElementById('tedavi_' + key).value : ''
        };
    });
    
    const payload = {
        tedaviler_metin_json: JSON.stringify(jsonData),
        sol_sutun_baslik_tr: document.getElementById('sol_sutun_baslik_tr2') ? document.getElementById('sol_sutun_baslik_tr2').value : '',
        sag_sutun_baslik_tr: document.getElementById('sag_sutun_baslik_tr2') ? document.getElementById('sag_sutun_baslik_tr2').value : '',
        csrf_token: csrfToken
    };
    savePayload(payload, 'tab-tedaviler', '✅ Tedavi metinleri kaydedildi!');
}

// ========== TAB 4: SEO KAYDET ==========
function saveSeo() {
    const payload = {
        seo_title_tr: document.getElementById('seo_title_tr') ? document.getElementById('seo_title_tr').value : '',
        seo_title_en: document.getElementById('seo_title_en') ? document.getElementById('seo_title_en').value : '',
        seo_description_tr: document.getElementById('seo_description_tr') ? document.getElementById('seo_description_tr').value : '',
        seo_description_en: document.getElementById('seo_description_en') ? document.getElementById('seo_description_en').value : '',
        seo_keywords_tr: document.getElementById('seo_keywords_tr') ? document.getElementById('seo_keywords_tr').value : '',
        seo_keywords_en: document.getElementById('seo_keywords_en') ? document.getElementById('seo_keywords_en').value : '',
        seo_og_image: document.getElementById('seo_og_image') ? document.getElementById('seo_og_image').value : '',
        seo_canonical: document.getElementById('seo_canonical') ? document.getElementById('seo_canonical').value : '',
        csrf_token: csrfToken
    };
    savePayload(payload, 'tab-seo', '✅ SEO ayarları kaydedildi!');
}

// ========== RESİM YÜKLE ==========
function uploadImage(targetId) {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (!file) return;
        const formData = new FormData();
        formData.append('resim', file);
        formData.append('csrf_token', csrfToken);
        fetch('/admin/modules/tedaviler/index.php?islem=resim_yukle', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById(targetId).value = data.url;
                    Swal.fire({
                        icon: 'success',
                        title: '✅ Resim yüklendi!',
                        timer: 1500,
                        timerProgressBar: true,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata!',
                        text: data.message || 'Resim yüklenirken hata oluştu.',
                        confirmButtonColor: '#3b82f6'
                    });
                }
            });
    };
    input.click();
}

// ========== SSS YÖNETİMİ ==========

// ========== TÜMÜNÜ SEÇ ==========
function tumunuSec() {
    const checkboxes = document.querySelectorAll('.sss-checkbox');
    checkboxes.forEach(function(cb) {
        cb.checked = true;
    });
    toggleTopluSil();
}

// ========== TÜMÜNÜ KALDIR ==========
function tumunuKaldir() {
    const checkboxes = document.querySelectorAll('.sss-checkbox');
    checkboxes.forEach(function(cb) {
        cb.checked = false;
    });
    toggleTopluSil();
}

// ========== SSS YÖNETİMİ ==========
function loadSSSList() {
    const container = document.getElementById('sssListesiContainer');
    const sayac = document.getElementById('sssSayac');
    const topluSilBtn = document.getElementById('topluSilBtn');
    const seciliSayac = document.getElementById('seciliSayac');
    
    container.innerHTML = '<p style="color: #94a3b8; text-align: center; grid-column: 1 / -1; padding: 40px 0;">Yükleniyor...</p>';
    if (topluSilBtn) topluSilBtn.style.display = 'none';
    
    fetch(ajaxUrl + '?islem=sss_getir')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                if (sayac) sayac.textContent = '(' + data.data.length + ' adet)';
                
                let html = '';
                data.data.forEach((item, index) => {
                    let kategoriRengi = '#3b82f6';
                    if (item.kategori && item.kategori.toLowerCase().includes('implant')) kategoriRengi = '#8b5cf6';
                    else if (item.kategori && item.kategori.toLowerCase().includes('estetik')) kategoriRengi = '#ec4899';
                    else if (item.kategori && item.kategori.toLowerCase().includes('çocuk')) kategoriRengi = '#14b8a6';
                    else if (item.kategori && item.kategori.toLowerCase().includes('ortodonti')) kategoriRengi = '#f59e0b';
                    
                    html += `
                        <div class="sss-grid-item" style="
                            background: #ffffff;
                            border-radius: 12px;
                            border: 1px solid #e2e8f0;
                            padding: 14px 16px;
                            transition: all 0.2s;
                            display: flex;
                            flex-direction: column;
                            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
                            cursor: default;
                            position: relative;
                        " onmouseover="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 4px 12px rgba(59,130,246,0.12)';" 
                           onmouseout="this.style.borderColor='#e2e8f0'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.04)';">
                            
                            <!-- CHECKBOX -->
                            <div style="display: flex; align-items: flex-start; gap: 10px;">
                                <input type="checkbox" class="sss-checkbox" data-id="${item.id}" onchange="toggleTopluSil()" style="
                                    margin-top: 3px;
                                    width: 16px;
                                    height: 16px;
                                    cursor: pointer;
                                    accent-color: #3b82f6;
                                    flex-shrink: 0;
                                ">
                                <div style="flex: 1;">
                                    <!-- Başlık ve Sıra -->
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 6px;">
                                        <span style="font-weight: 600; font-size: 13px; color: #1e293b; flex: 1; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.4;">${index + 1}. ${escapeHtml(item.soru)}</span>
                                        <span style="background: #f1f5f9; color: #64748b; font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 20px; white-space: nowrap; flex-shrink: 0;">#${item.sira || index + 1}</span>
                                    </div>
                                    
                                    <!-- Kategori -->
                                    <div style="margin-bottom: 10px;">
                                        <span style="background: ${kategoriRengi}15; color: ${kategoriRengi}; font-size: 10px; font-weight: 600; padding: 2px 10px; border-radius: 20px; display: inline-block;">${escapeHtml(item.kategori) || 'Genel'}</span>
                                    </div>
                                    
                                    <!-- Cevap -->
                                    <div style="font-size: 12px; color: #64748b; line-height: 1.5; flex: 1; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 10px; min-height: 32px;">${escapeHtml(item.cevap)}</div>
                                    
                                    <!-- Butonlar -->
                                    <div style="display: flex; gap: 6px; border-top: 1px solid #f1f5f9; padding-top: 10px; margin-top: auto;">
                                        <button class="btn-edit" onclick="openSSSModal(${item.id})" style="flex: 1; border: none; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 500; cursor: pointer; transition: all 0.2s; background: #dbeafe; color: #2563eb;" onmouseover="this.style.background='#bfdbfe'" onmouseout="this.style.background='#dbeafe'">
                                            <i class="fas fa-edit"></i> Düzenle
                                        </button>
                                        <button class="btn-del" onclick="deleteSSS(${item.id})" style="flex: 1; border: none; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 500; cursor: pointer; transition: all 0.2s; background: #fee2e2; color: #dc2626;" onmouseover="this.style.background='#fecaca'" onmouseout="this.style.background='#fee2e2'">
                                            <i class="fas fa-trash"></i> Sil
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;
                if (topluSilBtn) topluSilBtn.style.display = 'none';
            } else {
                if (sayac) sayac.textContent = '(0 adet)';
                if (topluSilBtn) topluSilBtn.style.display = 'none';
                container.innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: #f8fafc; border-radius: 16px; border: 2px dashed #e2e8f0;">
                        <i class="fas fa-question-circle" style="font-size: 48px; color: #cbd5e1; display: block; margin-bottom: 12px;"></i>
                        <p style="color: #94a3b8; font-size: 15px;">Henüz SSS eklenmemiş.</p>
                        <p style="color: #cbd5e1; font-size: 13px; margin-top: 4px;">"Yeni Soru Ekle" butonuna tıklayarak başlayın.</p>
                    </div>
                `;
            }
        })
        .catch(() => {
            container.innerHTML = '<p style="color: #ef4444; text-align: center; grid-column: 1 / -1; padding: 40px 0;">Yüklenirken hata oluştu.</p>';
        });
}
// ========== TOPLU SİL BUTONUNU GÖSTER/GİZLE ==========
function toggleTopluSil() {
    const checkboxes = document.querySelectorAll('.sss-checkbox:checked');
    const topluSilBtn = document.getElementById('topluSilBtn');
    const seciliSayac = document.getElementById('seciliSayac');
    
    if (checkboxes.length > 0) {
        topluSilBtn.style.display = 'flex';
        seciliSayac.textContent = checkboxes.length;
    } else {
        topluSilBtn.style.display = 'none';
    }
}

// ========== TÜMÜNÜ SEÇ ==========
function tumunuSec() {
    const checkboxes = document.querySelectorAll('.sss-checkbox');
    checkboxes.forEach(function(cb) {
        cb.checked = true;
    });
    toggleTopluSil();
}

// ========== TÜMÜNÜ KALDIR ==========
function tumunuKaldir() {
    const checkboxes = document.querySelectorAll('.sss-checkbox');
    checkboxes.forEach(function(cb) {
        cb.checked = false;
    });
    toggleTopluSil();
}

// ========== TOPLU SİL ==========
function topluSilSSS() {
    const checkboxes = document.querySelectorAll('.sss-checkbox:checked');
    const ids = [];
    checkboxes.forEach(function(cb) {
        ids.push(cb.getAttribute('data-id'));
    });
    
    if (ids.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Seçim Yok!',
            text: 'Lütfen silmek istediğiniz SSS\'leri seçin.',
            confirmButtonColor: '#3b82f6'
        });
        return;
    }
    
    Swal.fire({
        title: 'Seçilen SSS\'ler Silinecek!',
        html: `
            <p style="color: #64748b; font-size: 14px;">
                <strong style="color: #1e293b;">${ids.length}</strong> adet SSS kaydını silmek istediğinize emin misiniz?
            </p>
            <p style="color: #ef4444; font-size: 12px; margin-top: 8px;">
                <i class="fas fa-exclamation-triangle"></i> Bu işlem geri alınamaz!
            </p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: '<i class="fas fa-trash"></i> Evet, Tümünü Sil!',
        cancelButtonText: '<i class="fas fa-times"></i> İptal',
        reverseButtons: true,
        backdrop: 'rgba(15, 23, 42, 0.65)',
        background: '#ffffff',
        borderRadius: '16px',
        padding: '24px'
    }).then((result) => {
        if (result.isConfirmed) {
            // Tüm ID'leri gönder
            const data = {
                csrf_token: csrfToken,
                ids: ids
            };
            
            fetch(ajaxUrl + '?islem=sss_toplu_sil', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Silindi!',
                        text: result.silinen || ids.length + ' adet SSS başarıyla silindi.',
                        timer: 2000,
                        timerProgressBar: true,
                        showConfirmButton: false,
                        background: '#ffffff',
                        borderRadius: '12px'
                    });
                    loadSSSList();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata!',
                        text: result.message || 'Silinirken bir hata oluştu.',
                        confirmButtonColor: '#3b82f6'
                    });
                }
            })
            .catch(() => {
                Swal.fire({
                    icon: 'error',
                    title: 'Bağlantı Hatası!',
                    text: 'Sunucuya bağlanılamadı.',
                    confirmButtonColor: '#3b82f6'
                });
            });
        }
    });
}
function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

function openSSSModal(id = null) {
    document.getElementById('sssModal').style.display = 'flex';
    document.getElementById('edit_sss_id').value = id || '';
    if (id) {
        document.getElementById('sssModalTitle').innerHTML = '<i class="fas fa-edit text-blue-400"></i> SSS Düzenle';
        fetch(ajaxUrl + '?islem=sss_getir')
            .then(res => res.json())
            .then(data => {
                const item = data.data.find(x => x.id == id);
                if (item) {
                    document.getElementById('sss_kategori').value = item.kategori || '';
                    document.getElementById('sss_soru').value = item.soru || '';
                    document.getElementById('sss_cevap').value = item.cevap || '';
                }
            });
    } else {
        document.getElementById('sssModalTitle').innerHTML = '<i class="fas fa-plus-circle text-green-400"></i> Yeni SSS Ekle';
        document.getElementById('sss_kategori').value = '';
        document.getElementById('sss_soru').value = '';
        document.getElementById('sss_cevap').value = '';
    }
}

function closeSSSModal() {
    document.getElementById('sssModal').style.display = 'none';
}

// ========== SSS KAYDET (MODERN BİLDİRİM) ==========
function saveSSS() {
    const id = document.getElementById('edit_sss_id').value;
    const kategori = document.getElementById('sss_kategori').value.trim();
    const soru = document.getElementById('sss_soru').value.trim();
    const cevap = document.getElementById('sss_cevap').value.trim();

    if (!soru || !cevap) {
        Swal.fire({
            icon: 'warning',
            title: 'Eksik Bilgi!',
            text: 'Lütfen soru ve cevap alanlarını doldurun!',
            confirmButtonColor: '#3b82f6',
            timer: 3000,
            timerProgressBar: true
        });
        return;
    }

    const data = {
        csrf_token: csrfToken,
        id: id,
        kategori: kategori,
        soru: soru,
        cevap: cevap,
        sira: 0
    };

    const islem = id ? 'sss_guncelle' : 'sss_ekle';
    const btn = document.querySelector('#sssModal .btn-save-modal');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Kaydediliyor...';
    btn.disabled = true;

    fetch(ajaxUrl + '?islem=' + islem, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            closeSSSModal();
            loadSSSList();
            Swal.fire({
                icon: 'success',
                title: '✅ Kaydedildi!',
                text: id ? 'SSS başarıyla güncellendi.' : 'Yeni SSS başarıyla eklendi.',
                timer: 2000,
                timerProgressBar: true,
                showConfirmButton: false,
                background: '#ffffff',
                borderRadius: '12px'
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Hata!',
                text: result.message || 'Kaydedilirken bir hata oluştu.',
                confirmButtonColor: '#3b82f6'
            });
        }
    })
    .catch(err => {
        Swal.fire({
            icon: 'error',
            title: 'Bağlantı Hatası!',
            text: 'Sunucuya bağlanılamadı.',
            confirmButtonColor: '#3b82f6'
        });
    })
    .finally(() => { 
        btn.innerHTML = originalText; 
        btn.disabled = false; 
    });
}

// ========== SSS SİL (MODERN BİLDİRİM) ==========
function deleteSSS(id) {
    fetch(ajaxUrl + '?islem=sss_getir')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const item = data.data.find(x => x.id == id);
                if (!item) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata!',
                        text: 'SSS kaydı bulunamadı!',
                        confirmButtonColor: '#3b82f6'
                    });
                    return;
                }
                
                Swal.fire({
                    title: 'SSS\'yi Sil',
                    html: `
                        <p style="color: #64748b; font-size: 14px; margin-bottom: 4px;">
                            <strong style="color: #1e293b;">"${escapeHtml(item.soru)}"</strong>
                        </p>
                        <p style="color: #94a3b8; font-size: 13px;">Bu SSS kaydını silmek istediğinize emin misiniz?</p>
                        <p style="color: #ef4444; font-size: 12px; margin-top: 8px;">
                            <i class="fas fa-exclamation-triangle"></i> Bu işlem geri alınamaz!
                        </p>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#94a3b8',
                    confirmButtonText: '<i class="fas fa-trash"></i> Evet, Sil!',
                    cancelButtonText: '<i class="fas fa-times"></i> İptal',
                    reverseButtons: true,
                    backdrop: 'rgba(15, 23, 42, 0.65)',
                    background: '#ffffff',
                    borderRadius: '16px',
                    padding: '24px'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(ajaxUrl + '?islem=sss_sil&id=' + id)
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Silindi!',
                                        text: 'SSS başarıyla silindi.',
                                        timer: 2000,
                                        timerProgressBar: true,
                                        showConfirmButton: false,
                                        background: '#ffffff',
                                        borderRadius: '12px'
                                    });
                                    loadSSSList();
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Hata!',
                                        text: 'Silinirken bir hata oluştu.',
                                        confirmButtonColor: '#3b82f6'
                                    });
                                }
                            })
                            .catch(() => {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Bağlantı Hatası!',
                                    text: 'Sunucuya bağlanılamadı.',
                                    confirmButtonColor: '#3b82f6'
                                });
                            });
                    }
                });
            }
        })
        .catch(() => {
            Swal.fire({
                icon: 'error',
                title: 'Hata!',
                text: 'SSS bilgileri alınamadı.',
                confirmButtonColor: '#3b82f6'
            });
        });
}

// ========== SLIDER YÖNETİMİ ==========

// Slider Listesini Yükle
function loadSliderList() {
    const container = document.getElementById('sliderListesiContainer');
    const sayac = document.getElementById('sliderSayac');
    
    container.innerHTML = '<p style="color: #94a3b8; text-align: center; grid-column: 1 / -1; padding: 40px 0;">Yükleniyor...</p>';
    
    fetch(ajaxUrl + '?islem=slider_getir')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                if (sayac) sayac.textContent = '(' + data.data.length + ' adet)';
                
                let html = '';
                data.data.forEach((item, index) => {
                    const isActive = item.aktif == 1;
                    html += `
                        <div class="sss-grid-item" style="
                            background: #ffffff;
                            border-radius: 12px;
                            border: 1px solid #e2e8f0;
                            padding: 14px 16px;
                            transition: all 0.2s;
                            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
                            position: relative;
                        " onmouseover="this.style.borderColor='#8b5cf6'; this.style.boxShadow='0 4px 12px rgba(139,92,246,0.12)';" 
                           onmouseout="this.style.borderColor='#e2e8f0'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.04)';">
                            
                            <div style="display: flex; align-items: flex-start; gap: 12px;">
                                <!-- Resim -->
                                <div style="width: 60px; height: 60px; border-radius: 8px; overflow: hidden; flex-shrink: 0; background: #f1f5f9;">
                                    <img src="${item.image_url || '/assets/img/placeholder.jpg'}" 
                                         style="width:100%; height:100%; object-fit:cover;" 
                                         onerror="this.style.display='none'; this.parentElement.innerHTML='<i class=\\'fas fa-image\\' style=\\'font-size:24px; color:#94a3b8; display:flex; align-items:center; justify-content:center; height:100%;\\'></i>'">
                                </div>
                                
                                <div style="flex: 1;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                        <span style="font-weight: 600; font-size: 13px; color: #1e293b;">${escapeHtml(item.title_tr || item.title_en || 'İsimsiz')}</span>
                                        <span style="background: ${isActive ? '#dcfce7' : '#fee2e2'}; color: ${isActive ? '#16a34a' : '#dc2626'}; font-size: 9px; font-weight: 700; padding: 2px 8px; border-radius: 20px; white-space: nowrap; flex-shrink: 0;">
                                            ${isActive ? '✅ Aktif' : '❌ Pasif'}
                                        </span>
                                    </div>
                                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;">Sıra: #${item.sira || index + 1}</div>
                                    <div style="display: flex; gap: 6px; margin-top: 6px;">
                                        <button onclick="openSliderModal(${item.id})" style="border: none; padding: 2px 10px; border-radius: 4px; font-size: 10px; font-weight: 500; cursor: pointer; background: #dbeafe; color: #2563eb;">
                                            <i class="fas fa-edit"></i> Düzenle
                                        </button>
                                        <button onclick="deleteSlider(${item.id})" style="border: none; padding: 2px 10px; border-radius: 4px; font-size: 10px; font-weight: 500; cursor: pointer; background: #fee2e2; color: #dc2626;">
                                            <i class="fas fa-trash"></i> Sil
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;
            } else {
                if (sayac) sayac.textContent = '(0 adet)';
                container.innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: #f8fafc; border-radius: 16px; border: 2px dashed #e2e8f0;">
                        <i class="fas fa-images" style="font-size: 48px; color: #cbd5e1; display: block; margin-bottom: 12px;"></i>
                        <p style="color: #94a3b8; font-size: 15px;">Henüz slider eklenmemiş.</p>
                        <p style="color: #cbd5e1; font-size: 13px; margin-top: 4px;">"Yeni Slider Ekle" butonuna tıklayarak başlayın.</p>
                    </div>
                `;
            }
        })
        .catch(() => {
            container.innerHTML = '<p style="color: #ef4444; text-align: center; grid-column: 1 / -1; padding: 40px 0;">Yüklenirken hata oluştu.</p>';
        });
}

function openSliderModal(id = null) {
    var modal = document.getElementById('sliderModal');
    if (!modal) {
        console.error('Modal bulunamadı!');
        return;
    }
    modal.style.display = 'flex';
    document.getElementById('edit_slider_id').value = id || '';
    document.getElementById('slider_title_tr').value = '';
    document.getElementById('slider_title_en').value = '';
    document.getElementById('slider_subtitle_tr').value = '';
    document.getElementById('slider_subtitle_en').value = '';
    document.getElementById('slider_badge_tr').value = '';
    document.getElementById('slider_badge_en').value = '';
    document.getElementById('slider_features_tr').value = '';
    document.getElementById('slider_features_en').value = '';
    document.getElementById('slider_image_url').value = '';
    document.getElementById('cropperContainer').style.display = 'none';
    document.getElementById('previewContainer').style.display = 'none';
    
    // Mevcut resmi gizle
    document.getElementById('currentSliderImage').style.display = 'none';
    document.getElementById('noImageText').style.display = 'block';
    
    // DropZone'u sıfırla
    var dropContent = document.getElementById('sliderDropZoneContent');
    var dropZone = document.getElementById('sliderDropZone');
    dropContent.innerHTML = `
        <i class="fas fa-cloud-upload-alt" style="font-size: 32px; color: #94a3b8;"></i>
        <p style="color: #64748b; font-size: 13px; margin-top: 6px;">Yeni görsel yüklemek için tıklayın</p>
        <p style="color: #94a3b8; font-size: 11px;">1920x1080 px önerilir</p>
    `;
    dropContent.style.display = 'block';
    dropZone.style.borderColor = '#d1d5db';
    dropZone.style.background = '#fafbfc';
    document.getElementById('sliderPreview').style.display = 'none';
    document.getElementById('newImageInfo').style.display = 'none';
    
    if (cropper) { cropper.destroy(); cropper = null; }
    currentFile = null;
    
    if (id) {
        document.getElementById('sliderModalTitle').innerHTML = '<i class="fas fa-edit text-blue-400"></i> Slider Düzenle';
        fetch(ajaxUrl + '?islem=slider_getir')
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    var item = data.data.find(function(x) { return x.id == id; });
                    if (item) {
                        document.getElementById('slider_title_tr').value = item.title_tr || '';
                        document.getElementById('slider_title_en').value = item.title_en || '';
                        document.getElementById('slider_subtitle_tr').value = item.subtitle_tr || '';
                        document.getElementById('slider_subtitle_en').value = item.subtitle_en || '';
                        document.getElementById('slider_badge_tr').value = item.badge_tr || '';
                        document.getElementById('slider_badge_en').value = item.badge_en || '';
                        document.getElementById('slider_features_tr').value = item.features_tr || '';
                        document.getElementById('slider_features_en').value = item.features_en || '';
                        document.getElementById('slider_image_url').value = item.image_url || '';
                        document.getElementById('slider_aktif').value = item.aktif || 1;
                        
                        // Mevcut resmi göster
                        if (item.image_url) {
                            var img = document.getElementById('currentSliderImage');
                            img.src = item.image_url;
                            img.style.display = 'block';
                            document.getElementById('noImageText').style.display = 'none';
                        }
                    }
                }
            });
    } else {
        document.getElementById('sliderModalTitle').innerHTML = '<i class="fas fa-plus-circle text-green-400"></i> Yeni Slider Ekle';
    }
}
function closeSliderModal() {
    document.getElementById('sliderModal').style.display = 'none';
    if (cropper) { cropper.destroy(); cropper = null; }
    document.getElementById('cropperContainer').style.display = 'none';
}

// Slider Kaydet - DÜZELTİLDİ
function saveSlider() {
    var id = document.getElementById('edit_slider_id').value;
    var imageUrl = document.getElementById('slider_image_url').value;
    
    console.log('📸 saveSlider çağrıldı');
    console.log('📸 image_url değeri:', imageUrl);
    
    if (!imageUrl || imageUrl === '') {
        Swal.fire({
            icon: 'warning',
            title: 'Resim Gerekli!',
            text: 'Lütfen önce bir görsel yükleyip "Kırp ve Kaydet" butonuna tıklayın.',
            confirmButtonColor: '#3b82f6'
        });
        return;
    }
    
    var data = {
        csrf_token: csrfToken,
        id: id,
        title_tr: document.getElementById('slider_title_tr').value,
        title_en: document.getElementById('slider_title_en').value,
        subtitle_tr: document.getElementById('slider_subtitle_tr').value,
        subtitle_en: document.getElementById('slider_subtitle_en').value,
        badge_tr: document.getElementById('slider_badge_tr').value,
        badge_en: document.getElementById('slider_badge_en').value,
        features_tr: document.getElementById('slider_features_tr').value,
        features_en: document.getElementById('slider_features_en').value,
        image_url: imageUrl,
        aktif: document.getElementById('slider_aktif').value,
        sira: 0
    };
    
    console.log('📤 Gönderilecek veri:', data);
    
    var islem = id ? 'slider_guncelle' : 'slider_ekle';
    
    // 🔥 BUTONU BUL (farklı selector dene)
    var btn = document.querySelector('#sliderModal .btn-save-modal') || 
              document.querySelector('#sliderModal button[onclick="saveSlider()"]') ||
              document.querySelector('#sliderModal .btn-submit') ||
              document.querySelector('#sliderModal button:last-child');
    
    // Eğer buton bulunamazsa, modal'daki "Kaydet" yazısını ara
    if (!btn) {
        var allBtns = document.querySelectorAll('#sliderModal button');
        for (var i = 0; i < allBtns.length; i++) {
            if (allBtns[i].textContent.trim() === 'Kaydet' || allBtns[i].textContent.trim() === 'Save') {
                btn = allBtns[i];
                break;
            }
        }
    }
    
    var originalText = '';
    if (btn) {
        originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Kaydediliyor...';
        btn.disabled = true;
    } else {
        console.warn('⚠️ Kaydet butonu bulunamadı!');
    }
    
    fetch(ajaxUrl + '?islem=' + islem, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(function(res) { 
        console.log('📥 Response status:', res.status);
        return res.json(); 
    })
    .then(function(result) {
        console.log('📦 Response data:', result);
        if (result.success) {
            closeSliderModal();
            loadSliderList();
            Swal.fire({
                icon: 'success',
                title: '✅ Kaydedildi!',
                text: id ? 'Slider başarıyla güncellendi.' : 'Yeni slider başarıyla eklendi.',
                timer: 2000,
                timerProgressBar: true,
                showConfirmButton: false
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Hata!',
                text: result.message || 'Kaydedilirken bir hata oluştu.',
                confirmButtonColor: '#3b82f6'
            });
        }
    })
    .catch(function(err) {
        console.error('❌ Fetch hatası:', err);
        Swal.fire({
            icon: 'error',
            title: 'Bağlantı Hatası!',
            text: 'Sunucuya bağlanılamadı.',
            confirmButtonColor: '#3b82f6'
        });
    })
    .finally(function() {
        if (btn) {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });
}

// Slider Sil
function deleteSlider(id) {
    Swal.fire({
        title: 'Slider\'ı Sil',
        html: `
            <p style="color: #64748b; font-size: 14px;">Bu slider kaydını silmek istediğinize emin misiniz?</p>
            <p style="color: #ef4444; font-size: 12px; margin-top: 8px;">
                <i class="fas fa-exclamation-triangle"></i> Bu işlem geri alınamaz!
            </p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: '<i class="fas fa-trash"></i> Evet, Sil!',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(ajaxUrl + '?islem=slider_sil&id=' + id)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Silindi!',
                            timer: 1500,
                            timerProgressBar: true,
                            showConfirmButton: false
                        });
                        loadSliderList();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Hata!',
                            text: 'Silinirken bir hata oluştu.',
                            confirmButtonColor: '#3b82f6'
                        });
                    }
                });
        }
    });
}

// Slider Resim Yükle (Sürükle-Bırak)
document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('sliderDropZone');
    const fileInput = document.getElementById('sliderImageInput');
    
    if (!dropZone) return;
    
    dropZone.addEventListener('click', function() {
        fileInput.click();
    });
    
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            uploadSliderImage(file);
        }
    });
    
    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.style.borderColor = '#3b82f6';
        this.style.background = '#f0f4ff';
    });
    
    dropZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.style.borderColor = '#d1d5db';
        this.style.background = '#fafbfc';
    });
    
    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        this.style.borderColor = '#d1d5db';
        this.style.background = '#fafbfc';
        const file = e.dataTransfer.files[0];
        if (file && file.type.startsWith('image/')) {
            uploadSliderImage(file);
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Geçersiz Dosya!',
                text: 'Lütfen bir resim dosyası yükleyin.',
                confirmButtonColor: '#3b82f6'
            });
        }
    });
});

// ========== RESİM DÜZENLEME ==========

// ========== SLIDER CROPPER (YENİ) ==========
let cropper = null;
let currentFile = null;


function uploadSliderImage(file) {
    currentFile = file;
    
    // DropZone içeriğini gizle (yükleme mesajı göster)
    const dropContent = document.getElementById('sliderDropZoneContent');
    dropContent.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size: 24px; color: #3b82f6;"></i><p style="color: #64748b; margin-top: 4px; font-size: 12px;">Görsel işleniyor...</p>';
    
    const reader = new FileReader();
    reader.onload = function(e) { 
        // DropZone'u güncelle (önizleme yerine cropper başlat)
        initCropper(e.target.result); 
    };
    reader.readAsDataURL(file);
}
function rotateCropper(degrees) {
    if (cropper) cropper.rotate(degrees);
}

function resetCropper() {
    if (cropper) { cropper.reset(); cropper.clear(); cropper.crop(); }
}

function applyCrop() {
    if (!cropper) {
        Swal.fire({ 
            icon: 'warning', 
            title: 'Uyarı!', 
            text: 'Önce bir görsel yükleyin.', 
            confirmButtonColor: '#3b82f6' 
        });
        return;
    }
    
    const canvas = cropper.getCroppedCanvas({
        width: 1920,
        height: 1080,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high'
    });
    
    if (!canvas) {
        Swal.fire({ 
            icon: 'error', 
            title: 'Hata!', 
            text: 'Kırpma işlemi başarısız oldu.', 
            confirmButtonColor: '#3b82f6' 
        });
        return;
    }
    
    const quality = parseInt(document.getElementById('quality').value) / 100;
    canvas.toBlob(function(blob) {
        if (!blob) {
            Swal.fire({ 
                icon: 'error', 
                title: 'Hata!', 
                text: 'Resim dönüştürülemedi.', 
                confirmButtonColor: '#3b82f6' 
            });
            return;
        }
        
        const formData = new FormData();
        formData.append('resim', blob, 'slider_' + Date.now() + '.webp');
        formData.append('csrf_token', csrfToken);
        formData.append('quality', document.getElementById('quality').value);
        
        const dropContent = document.getElementById('sliderDropZoneContent');
        const preview = document.getElementById('sliderPreview');
        const imageUrlInput = document.getElementById('slider_image_url');
        const container = document.getElementById('cropperContainer');
        const dropZone = document.getElementById('sliderDropZone');
        
        dropContent.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size: 24px; color: #3b82f6;"></i><p style="color: #64748b; margin-top: 4px; font-size: 12px;">Yükleniyor...</p>';
        
        fetch(ajaxUrl + '?islem=slider_resim_yukle', {
            method: 'POST',
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                // ★★★ image_url'yi DOLDUR ★★★
                imageUrlInput.value = data.url;
                console.log('✅ image_url dolduruldu:', data.url);
                
                preview.src = data.url + '?t=' + Date.now();
                preview.style.display = 'block';
                container.style.display = 'none';
                
                dropZone.style.borderColor = '#10b981';
                dropZone.style.background = '#f0fdf4';
                dropContent.innerHTML = `
                    <i class="fas fa-check-circle" style="font-size: 24px; color: #10b981;"></i>
                    <p style="color: #10b981; margin-top: 4px; font-size: 12px; font-weight: 600;">✅ Resim yüklendi!</p>
                    <p style="color: #94a3b8; font-size: 10px;">${data.width}x${data.height} · ${(data.size / 1024).toFixed(1)} KB · WebP</p>
                `;
                dropContent.style.display = 'block';
                
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
                
                document.getElementById('previewContainer').style.display = 'block';
                document.getElementById('sliderPreview').src = data.url + '?t=' + Date.now();
                document.getElementById('sliderPreview').style.display = 'block';
                
                Swal.fire({
                    icon: 'success',
                    title: '✅ Resim yüklendi!',
                    html: `<p style="font-size: 13px; color: #64748b;">1920x1080 · ${(data.size / 1024).toFixed(1)} KB · WebP</p>`,
                    timer: 1500,
                    timerProgressBar: true,
                    showConfirmButton: false
                });
            } else {
                Swal.fire({ 
                    icon: 'error', 
                    title: 'Hata!', 
                    text: data.message || 'Resim yüklenirken hata oluştu.', 
                    confirmButtonColor: '#3b82f6' 
                });
                resetDropZone();
            }
        })
        .catch(function() {
            Swal.fire({ 
                icon: 'error', 
                title: 'Bağlantı Hatası!', 
                text: 'Sunucuya bağlanılamadı.', 
                confirmButtonColor: '#3b82f6' 
            });
            resetDropZone();
        });
    }, 'image/webp', quality);
}
function resetDropZone() {
    const dropContent = document.getElementById('sliderDropZoneContent');
    const dropZone = document.getElementById('sliderDropZone');
    dropContent.innerHTML = `
        <i class="fas fa-cloud-upload-alt" style="font-size: 32px; color: #94a3b8;"></i>
        <p style="color: #64748b; font-size: 13px; margin-top: 6px;">Görseli sürükleyin veya tıklayın</p>
        <p style="color: #94a3b8; font-size: 11px;">Önerilen boyut: 1920x1080px</p>
    `;
    dropZone.style.borderColor = '#d1d5db';
    dropZone.style.background = '#fafbfc';
}
// ========== CROPPER ÖZELLİKLERİ ==========

// Kırpma oranını değiştir (Serbest / 16:9)
function toggleCropAspect() {
    if (!cropper) return;
    const currentMode = document.getElementById('cropAspectMode').value;
    const btn = document.getElementById('cropAspectBtn');
    const label = document.getElementById('cropAspectLabel');
    
    if (currentMode === 'free') {
        cropper.setAspectRatio(16 / 9);
        document.getElementById('cropAspectMode').value = '16_9';
        label.textContent = '16:9 Kırp';
        btn.style.background = '#dbeafe';
        btn.style.borderColor = '#bfdbfe';
        btn.style.color = '#2563eb';
    } else {
        cropper.setAspectRatio(NaN);
        document.getElementById('cropAspectMode').value = 'free';
        label.textContent = 'Serbest Kırp';
        btn.style.background = '#fef3c7';
        btn.style.borderColor = '#fcd34d';
        btn.style.color = '#b45309';
    }
}

// Cropper'ı başlat (güncellendi)
function initCropper(imageUrl) {
    var image = document.getElementById('cropperImage');
    if (!image) {
        console.error('❌ cropperImage bulunamadı!');
        return;
    }
    image.src = imageUrl;
    
    var container = document.getElementById('cropperContainer');
    if (!container) {
        console.error('❌ cropperContainer bulunamadı!');
        return;
    }
    container.style.display = 'block';
    
    if (cropper) {
        cropper.destroy();
        cropper = null;
    }
    
    // Varsayılan serbest kırp
    var aspectBtn = document.getElementById('cropAspectBtn');
    var aspectLabel = document.getElementById('cropAspectLabel');
    if (aspectBtn) {
        aspectBtn.style.background = '#fef3c7';
        aspectBtn.style.borderColor = '#fcd34d';
        aspectBtn.style.color = '#b45309';
    }
    if (aspectLabel) {
        aspectLabel.textContent = 'Serbest Kırp';
    }
    document.getElementById('cropAspectMode').value = 'free';
    
    image.onload = function() {
        console.log('🖼️ Resim yüklendi, cropper başlatılıyor...');
        cropper = new Cropper(image, {
            aspectRatio: NaN,
            viewMode: 1,
            autoCropArea: 0.9,
            responsive: true,
            restore: false,
            guides: true,
            center: true,
            highlight: true,
            cropBoxMovable: true,
            cropBoxResizable: true,
            toggleDragModeOnDblclick: false,
            minCropBoxWidth: 50,
            minCropBoxHeight: 50,
            ready: function() {
                var containerData = cropper.getContainerData();
                cropper.setCropBoxData({
                    left: 0,
                    top: 0,
                    width: containerData.width,
                    height: containerData.height
                });
                console.log('✅ Cropper hazır');
            }
        });
        var previewContainer = document.getElementById('previewContainer');
        if (previewContainer) {
            previewContainer.style.display = 'block';
        }
    };
}

// Sayfa yüklendiğinde verileri çek
document.addEventListener('DOMContentLoaded', function() {
    loadData();
    loadSliderList(); // Slider listesini de yükle
});

</script>