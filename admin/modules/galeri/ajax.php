<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';
if (!function_exists('galeri_slug_uret')) {
    function galeri_slug_uret($metin) {
        $tr = ['ç','ğ','ı','İ','ö','ş','ü','Ç','Ğ','Ö','Ş','Ü','â','î','û'];
        $en = ['c','g','i','i','o','s','u','c','g','o','s','u','a','i','u'];
        $metin = str_replace($tr, $en, $metin);
        $metin = mb_strtolower($metin, 'UTF-8');
        $metin = preg_replace('/[^a-z0-9]+/', '-', $metin);
        $metin = preg_replace('/-+/', '-', $metin);
        $metin = trim($metin, '-');
        return mb_substr($metin, 0, 180);
    }
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
//panel galeri ajax
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

$islem = $_GET['islem'] ?? '';

// Resim Yükle
if ($islem === 'resim_yukle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'CSRF hatası']);
        exit;
    }
    
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/galeri/';
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
    
    $file = $_FILES['resim'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp'];
    
    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz dosya tipi']);
        exit;
    }
    
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Max 5MB']);
        exit;
    }
    
    $filename = 'galeri_' . time() . '_' . rand(1000,9999) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
        echo json_encode(['success' => true, 'url' => '/uploads/galeri/' . $filename]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Upload başarısız']);
    }
    exit;
}

// Görsel Getir
if ($islem === 'get_gorsel' && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM galeri_resimler WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($data) {
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Görsel bulunamadı']);
    }
    exit;
}

// ========== SEO KAYDET ==========
if ($islem === 'seo_kaydet' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_input = file_get_contents('php://input');
    $data = json_decode($raw_input, true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz veri']);
        exit;
    }
    
    if (!isset($data['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $data['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'CSRF hatası']);
        exit;
    }
    
    unset($data['csrf_token']);
    
    $seoFields = ['seo_title_tr', 'seo_title_en', 'seo_description_tr', 'seo_description_en', 'seo_keywords_tr', 'seo_keywords_en', 'seo_og_image', 'seo_canonical'];
    
    try {
        $db->query("CREATE TABLE IF NOT EXISTS galeri_ayarlar (
            id INT AUTO_INCREMENT PRIMARY KEY,
            anahtar VARCHAR(100) NOT NULL UNIQUE,
            deger TEXT
        )");
    } catch(Exception $e) {}
    
    foreach ($data as $key => $value) {
        if (in_array($key, $seoFields)) {
            $stmt = $db->prepare("INSERT INTO galeri_ayarlar (anahtar, deger) VALUES (?, ?) ON DUPLICATE KEY UPDATE deger = ?");
            $stmt->execute([$key, $value, $value]);
        }
    }
    
    require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/indexnow.php';
    $site_url = "https://www.dribrahimdurandentalclinic.com";
    $url = $site_url . '/galeri';
    indexNowTekliGonder($url);

    echo json_encode(['success' => true]);
    exit;
}

// Görsel/Video Kaydet
if ($islem === 'kaydet_gorsel' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'CSRF hatası']);
        exit;
    }
    
    $id = $_POST['id'] ?? null;
    $baslik = trim($_POST['baslik'] ?? '');
	$slug = trim($_POST['slug'] ?? '');
if (empty($slug) && !empty($baslik)) {
    $slug = galeri_slug_uret($baslik);
}
    $aciklama = trim($_POST['aciklama'] ?? '');
    $kategori_id = intval($_POST['kategori_id'] ?? 0);
    $resim_url = trim($_POST['resim_url'] ?? '');
    $thumbnail_url = trim($_POST['thumbnail_url'] ?? '');
    $tarih = trim($_POST['tarih'] ?? date('Y'));
    $sira = intval($_POST['sira'] ?? 0);
    $durum = isset($_POST['durum']) ? 1 : 0;
    
    $medya_tipi = ($_POST['medya_tipi'] ?? 'resim') === 'video' ? 'video' : 'resim';
    $video_url = trim($_POST['video_url'] ?? '');
    $video_sure = trim($_POST['video_sure'] ?? '');
    
    $seo_title = trim($_POST['seo_title'] ?? '');
    $seo_description = trim($_POST['seo_description'] ?? '');
    $seo_keywords = trim($_POST['seo_keywords'] ?? '');
    $seo_og_image = trim($_POST['seo_og_image'] ?? '');
    
    $seo_kolon = $db->query("SHOW COLUMNS FROM galeri_resimler LIKE 'seo_title'")->fetch();
    $seo_var = (bool)$seo_kolon;
    
    if ($medya_tipi === 'video' && empty($thumbnail_url) && !empty($video_url)) {
        if (preg_match('~(?:youtube(?:-nocookie)?\.com/(?:embed/|watch\?(?:.*&)?v=|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $video_url, $m)) {
            $thumbnail_url = 'https://img.youtube.com/vi/' . $m[1] . '/maxresdefault.jpg';
        }
    }
    
    if (empty($baslik)) {
        echo json_encode(['success' => false, 'message' => 'Başlık gerekli']);
        exit;
    }
    
    if ($medya_tipi === 'video' && empty($video_url)) {
        echo json_encode(['success' => false, 'message' => 'Video URL gerekli']);
        exit;
    }
    
    if ($medya_tipi === 'resim' && empty($resim_url)) {
        echo json_encode(['success' => false, 'message' => 'Görsel gerekli']);
        exit;
    }
    
    if (!empty($video_sure) && !preg_match('/^PT(\d+H)?(\d+M)?(\d+S)?$/', $video_sure)) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz süre formatı! Örnek: PT1M30S, PT45S, PT2M']);
        exit;
    }
    
    try {
        $kolon = $db->query("SHOW COLUMNS FROM galeri_resimler LIKE 'medya_tipi'")->fetch();
        if (!$kolon) {
            $db->exec("ALTER TABLE galeri_resimler ADD COLUMN medya_tipi ENUM('resim','video') DEFAULT 'resim' AFTER kategori_id");
            $db->exec("ALTER TABLE galeri_resimler ADD COLUMN video_url VARCHAR(500) DEFAULT NULL AFTER resim_url");
        }
        
        $kolon_sure = $db->query("SHOW COLUMNS FROM galeri_resimler LIKE 'video_sure'")->fetch();
        if (!$kolon_sure) {
            try {
                $db->exec("ALTER TABLE galeri_resimler ADD COLUMN video_sure VARCHAR(20) DEFAULT NULL AFTER video_url");
            } catch(Exception $e) {}
        }
        
        if (!$seo_var) {
            try {
                $db->exec("ALTER TABLE galeri_resimler ADD COLUMN seo_title VARCHAR(255) DEFAULT NULL");
                $db->exec("ALTER TABLE galeri_resimler ADD COLUMN seo_description TEXT DEFAULT NULL");
                $db->exec("ALTER TABLE galeri_resimler ADD COLUMN seo_keywords VARCHAR(255) DEFAULT NULL");
                $db->exec("ALTER TABLE galeri_resimler ADD COLUMN seo_og_image VARCHAR(500) DEFAULT NULL");
                $seo_var = true;
            } catch(Exception $e) {}
        }
        
        if ($id && $id !== '') {
            if ($seo_var) {
                $stmt = $db->prepare("UPDATE galeri_resimler SET baslik=?, slug=?, aciklama=?, kategori_id=?, medya_tipi=?, resim_url=?, video_url=?, video_sure=?, thumbnail_url=?, tarih=?, sira=?, durum=?, seo_title=?, seo_description=?, seo_keywords=?, seo_og_image=? WHERE id=?");
                $stmt->execute([$baslik, $slug, $aciklama, $kategori_id, $medya_tipi, $resim_url, $video_url, $video_sure, $thumbnail_url, $tarih, $sira, $durum, $seo_title, $seo_description, $seo_keywords, $seo_og_image, $id]);
            } else {
                $stmt = $db->prepare("UPDATE galeri_resimler SET baslik=?, slug=?, aciklama=?, kategori_id=?, medya_tipi=?, resim_url=?, video_url=?, video_sure=?, thumbnail_url=?, tarih=?, sira=?, durum=? WHERE id=?");
                $stmt->execute([$baslik, $slug, $aciklama, $kategori_id, $medya_tipi, $resim_url, $video_url, $video_sure, $thumbnail_url, $tarih, $sira, $durum, $id]);
            }
        } else {
            $maxSira = $db->query("SELECT IFNULL(MAX(sira), 0) + 1 as yeni_sira FROM galeri_resimler WHERE silindi = 0")->fetch(PDO::FETCH_ASSOC)['yeni_sira'];
            
            if ($seo_var) {
                $stmt = $db->prepare("INSERT INTO galeri_resimler (baslik, slug, aciklama, kategori_id, medya_tipi, resim_url, video_url, video_sure, thumbnail_url, tarih, sira, durum, seo_title, seo_description, seo_keywords, seo_og_image, silindi) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,0)");
                $stmt->execute([$baslik, $slug, $aciklama, $kategori_id, $medya_tipi, $resim_url, $video_url, $video_sure, $thumbnail_url, $tarih, $maxSira, $durum, $seo_title, $seo_description, $seo_keywords, $seo_og_image]);
            } else {
                $stmt = $db->prepare("INSERT INTO galeri_resimler (baslik, slug, aciklama, kategori_id, medya_tipi, resim_url, video_url, video_sure, thumbnail_url, tarih, sira, durum, silindi) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,0)");
                $stmt->execute([$baslik, $slug, $aciklama, $kategori_id, $medya_tipi, $resim_url, $video_url, $video_sure, $thumbnail_url, $tarih, $maxSira, $durum]);
            }
        }
        
        require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/indexnow.php';
        $site_url = "https://www.dribrahimdurandentalclinic.com";
        $url = $site_url . '/galeri';
        indexNowTekliGonder($url);
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Görsel Durum Değiştir
if ($islem === 'toggle_durum' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $current = $db->query("SELECT durum FROM galeri_resimler WHERE id = $id")->fetchColumn();
    $new = ($current == 1) ? 0 : 1;
    $db->query("UPDATE galeri_resimler SET durum = $new WHERE id = $id");
    echo json_encode(['success' => true, 'durum' => $new]);
    exit;
}

// Görsel Sil (Çöp kutusuna taşı)
if ($islem === 'sil_gorsel' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $silinenSira = $db->query("SELECT sira FROM galeri_resimler WHERE id = $id")->fetchColumn();
    $db->query("UPDATE galeri_resimler SET silindi = 1 WHERE id = $id");
    $db->query("UPDATE galeri_resimler SET sira = sira - 1 WHERE sira > $silinenSira AND silindi = 0");
    
    require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/indexnow.php';
    $site_url = "https://www.dribrahimdurandentalclinic.com";
    $url = $site_url . '/galeri';
    indexNowTekliGonder($url);
    
    echo json_encode(['success' => true]);
    exit;
}

// Çöp Kutusunu Tamamen Temizle
if ($islem === 'cöp_temizle') {
    $stmt = $db->query("SELECT resim_url, thumbnail_url FROM galeri_resimler WHERE silindi = 1");
    $silinecekler = $stmt->fetchAll();
    
    foreach ($silinecekler as $s) {
        $resim_yolu = $_SERVER['DOCUMENT_ROOT'] . $s['resim_url'];
        $thumb_yolu = $_SERVER['DOCUMENT_ROOT'] . $s['thumbnail_url'];
        if (file_exists($resim_yolu)) unlink($resim_yolu);
        if (file_exists($thumb_yolu)) unlink($thumb_yolu);
    }
    
    $db->query("DELETE FROM galeri_resimler WHERE silindi = 1");
    echo json_encode(['success' => true]);
    exit;
}

// Son sıra
if ($islem === 'son_sira') {
    $maxSira = $db->query("SELECT IFNULL(MAX(sira), 0) + 1 as sira FROM galeri_resimler WHERE silindi = 0")->fetch(PDO::FETCH_ASSOC)['sira'];
    echo json_encode(['success' => true, 'sira' => $maxSira]);
    exit;
}

// Kategori Getir
if ($islem === 'get_kategori' && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM galeri_kategoriler WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($data) {
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Kategori bulunamadı']);
    }
    exit;
}

// ========== KATEGORİ KAYDET (TEK/ÇİFT ID SİSTEMİ) ==========
if ($islem === 'kaydet_kategori' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'CSRF hatası']);
        exit;
    }
    
    $id = $_POST['id'] ?? null;
    $kategori_adi = trim($_POST['kategori_adi'] ?? '');
    $kategori_slug = trim($_POST['kategori_slug'] ?? '');
    $ikon = trim($_POST['ikon'] ?? 'Camera');
    $sira = intval($_POST['sira'] ?? 0);
    $medya_tipi = in_array($_POST['medya_tipi'] ?? 'hepsi', ['hepsi','resim','video'], true) ? $_POST['medya_tipi'] : 'hepsi';
    
    // medya_tipi kolonu yoksa ekle
    try {
        $kolon_medya = $db->query("SHOW COLUMNS FROM galeri_kategoriler LIKE 'medya_tipi'")->fetch();
        if (!$kolon_medya) {
            $db->exec("ALTER TABLE galeri_kategoriler ADD COLUMN medya_tipi ENUM('hepsi','resim','video') DEFAULT 'hepsi' AFTER ikon");
        }
    } catch(Exception $e) {}
    
    try {
        if ($id && $id !== '') {
            // GÜNCELLEME — ID değişmez
            $db->prepare("UPDATE galeri_kategoriler SET kategori_adi=?, kategori_slug=?, ikon=?, medya_tipi=?, sira=? WHERE id=?")
                ->execute([$kategori_adi, $kategori_slug, $ikon, $medya_tipi, $sira, $id]);
        } else {
            // YENİ EKLEME — 🔥 TEK/ÇİFT ID SİSTEMİ
            $maxSira = $db->query("SELECT IFNULL(MAX(sira), 0) + 1 as yeni_sira FROM galeri_kategoriler")->fetch(PDO::FETCH_ASSOC)['yeni_sira'];
            
            if ($medya_tipi === 'video') {
                // 🎥 VİDEO → ÇİFT ID (2, 4, 6, 8, 10...)
                $maxCift = (int)$db->query("SELECT IFNULL(MAX(id), 0) FROM galeri_kategoriler WHERE id % 2 = 0")->fetchColumn();
                $yeni_id = $maxCift > 0 ? $maxCift + 2 : 2;
            } else {
                // 📷 RESİM / HEPİ → TEK ID (1, 3, 5, 7, 9, 11...)
                $maxTek = (int)$db->query("SELECT IFNULL(MAX(id), 0) FROM galeri_kategoriler WHERE id % 2 = 1")->fetchColumn();
                $yeni_id = $maxTek > 0 ? $maxTek + 2 : 1;
            }
            
            // Çakışma kontrolü (güvenlik)
            $kontrol = $db->prepare("SELECT COUNT(*) FROM galeri_kategoriler WHERE id = ?");
            $kontrol->execute([$yeni_id]);
            while ($kontrol->fetchColumn() > 0) {
                $yeni_id += 2; // Çakışırsa bir sonraki aynı tip id'ye geç
                $kontrol->execute([$yeni_id]);
            }
            
            $db->prepare("INSERT INTO galeri_kategoriler (id, kategori_adi, kategori_slug, ikon, medya_tipi, sira) VALUES (?,?,?,?,?,?)")
                ->execute([$yeni_id, $kategori_adi, $kategori_slug, $ikon, $medya_tipi, $maxSira]);
        }
        
        require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/indexnow.php';
        $site_url = "https://www.dribrahimdurandentalclinic.com";
        $url = $site_url . '/galeri';
        indexNowTekliGonder($url);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Son kategori sıra
if ($islem === 'son_kategori_sira') {
    $maxSira = $db->query("SELECT IFNULL(MAX(sira), 0) + 1 as sira FROM galeri_kategoriler")->fetch(PDO::FETCH_ASSOC)['sira'];
    echo json_encode(['success' => true, 'sira' => $maxSira]);
    exit;
}

// Kategori Sil
if ($islem === 'sil_kategori' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $db->prepare("DELETE FROM galeri_resimler WHERE kategori_id = ?")->execute([$id]);
    $db->prepare("DELETE FROM galeri_kategoriler WHERE id = ?")->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

// Tüm kategorileri getir
if ($islem === 'tum_kategoriler') {
    $kategoriler = $db->query("SELECT id, kategori_adi FROM galeri_kategoriler ORDER BY sira ASC")->fetchAll();
    echo json_encode(['success' => true, 'data' => $kategoriler]);
    exit;
}

// Çöp kutusu listesi
if ($islem === 'cop_listesi') {
    $stmt = $db->query("SELECT r.*, k.kategori_adi FROM galeri_resimler r LEFT JOIN galeri_kategoriler k ON r.kategori_id = k.id WHERE r.silindi = 1 ORDER BY r.sira ASC");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

// Kalıcı sil (tek görsel)
if ($islem === 'kalici_sil' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $stmt = $db->prepare("SELECT resim_url, thumbnail_url FROM galeri_resimler WHERE id = ?");
    $stmt->execute([$id]);
    $resim = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resim) {
        $resim_yolu = $_SERVER['DOCUMENT_ROOT'] . $resim['resim_url'];
        $thumb_yolu = $_SERVER['DOCUMENT_ROOT'] . $resim['thumbnail_url'];
        if (file_exists($resim_yolu)) unlink($resim_yolu);
        if (file_exists($thumb_yolu)) unlink($thumb_yolu);
    }
    
    $db->prepare("DELETE FROM galeri_resimler WHERE id = ?")->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

// Çöpten Geri Al
if ($islem === 'geri_al' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $stmt = $db->query("SELECT MAX(sira) as max_sira FROM galeri_resimler WHERE silindi = 0");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $maxSira = $row['max_sira'] ? $row['max_sira'] : 0;
    $yeniSira = $maxSira + 1;
    
    $update = $db->prepare("UPDATE galeri_resimler SET silindi = 0, sira = ? WHERE id = ?");
    $update->execute([$yeniSira, $id]);
    
    echo json_encode(['success' => true, 'yeni_sira' => $yeniSira, 'max_sira' => $maxSira]);
    exit;
}

// ========== KIRPILAN RESMİ YÜKLE ==========
if ($islem === 'kirp_yukle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'CSRF hatası']);
        exit;
    }
    
    if (!isset($_FILES['kirp_resim']) || $_FILES['kirp_resim']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Dosya yüklenemedi']);
        exit;
    }
    
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/galeri/';
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
    
    $filename = 'kirpilan_' . time() . '_' . rand(1000, 9999) . '.png';
    $target = $upload_dir . $filename;
    
    if (move_uploaded_file($_FILES['kirp_resim']['tmp_name'], $target)) {
        echo json_encode(['success' => true, 'url' => '/uploads/galeri/' . $filename]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Kaydetme başarısız']);
    }
    exit;
}
echo json_encode(['success' => false, 'message' => 'Geçersiz işlem: ' . $islem]);
?>