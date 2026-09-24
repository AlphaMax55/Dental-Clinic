<?php
// ============================================================
// 📍 admin/modules/blog/ajax.php - TAM ÇÖZÜM
// ============================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
kontrol();

ob_clean();
header('Content-Type: application/json');

$ajax = isset($_GET['ajax']) ? $_GET['ajax'] : '';
$sub = isset($_GET['sub']) ? $_GET['sub'] : '';

// ============================================================
// 🔥 CSRF TOKEN GARANTİSİ
// ============================================================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ============================================================
// 🔥 CSRF KONTROL FONKSİYONU
// ============================================================
function csrfKontrol($gelen_token) {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return false;
    }
    if (empty($gelen_token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $gelen_token);
}

// ============================================================
// 🔥 YARDIMCI: İNDEXNOW
// ============================================================
function sendIndexNow($url) {
    $indexnow_file = dirname(__DIR__, 2) . '/inc/indexnow.php';
    if (file_exists($indexnow_file)) {
        require_once $indexnow_file;
        if (function_exists('indexNowTekliGonder')) {
            @indexNowTekliGonder($url);
            return true;
        }
    }
    return false;
}

// ============================================================
// 🔥 YARDIMCI: VERİ TEMİZLEME (JSON GÜVENLİ)
// ============================================================
function temizleJsonVeri($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $value = htmlspecialchars_decode($value, ENT_QUOTES);
                $value = str_replace(["\r\n", "\n", "\r"], ' ', $value);
                $value = preg_replace('/\s+/', ' ', $value);
                $value = trim($value);
                $data[$key] = $value;
            } elseif (is_array($value)) {
                $data[$key] = temizleJsonVeri($value);
            }
        }
    }
    return $data;
}

// ============================================================
// 🔥 YARDIMCI: SLUG OLUŞTUR (TÜRKÇE KARAKTER DÜZELTME)
// ============================================================
function createSlug($text) {
    if (empty($text)) return '';
    
    $donusum = [
        'ç'=>'c', 'Ç'=>'c',
        'ğ'=>'g', 'Ğ'=>'g',
        'ı'=>'i', 'İ'=>'i',
        'ö'=>'o', 'Ö'=>'o',
        'ş'=>'s', 'Ş'=>'s',
        'ü'=>'u', 'Ü'=>'u',
        ' '=>'-', '+'=>'-',
        '.'=>'', ','=>'', '?'=>'', '!'=>'',
        "'"=>'', '"'=>'', '&'=>'ve',
        '/'=>'-', '\\'=>'-',
        '('=>'', ')'=>'', ':'=>'', ';'=>''
    ];
    
    $slug = strtr($text, $donusum);
    $slug = preg_replace('/[^a-zA-Z0-9\-]/', '', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    $slug = strtolower($slug);
    
    if (empty($slug)) {
        $slug = 'yazi-' . time();
    }
    
    return $slug;
}

// ============================================================
// 🔥 YARDIMCI: KATEGORİ SLUG (TÜRKÇE KARAKTER DÜZELTME)
// ============================================================
function createCategorySlug($name) {
    $donusum = [
        'ğ'=>'g','Ğ'=>'g',
        'ü'=>'u','Ü'=>'u',
        'ş'=>'s','Ş'=>'s',
        'ı'=>'i','I'=>'i',
        'ö'=>'o','Ö'=>'o',
        'ç'=>'c','Ç'=>'c',
        'İ'=>'i',
        ' '=>'-','.'=>''
    ];
    $slug = strtolower(strtr($name, $donusum));
    $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

// ============================================================
// 🔥 YARDIMCI: BENZERSİZ SLUG KONTROLÜ
// ============================================================
function benzersizSlug($slug, $id = null) {
    global $db;
    
    $originalSlug = $slug;
    $counter = 1;
    
    while (true) {
        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM blog_yazilar WHERE slug = ? AND (id != ? OR ? IS NULL) AND (silindi = 0 OR silindi IS NULL)");
        $stmtCheck->execute([$slug, $id, $id]);
        $count = $stmtCheck->fetchColumn();
        
        if ($count == 0) {
            break;
        }
        
        $slug = $originalSlug . '-' . $counter;
        $counter++;
    }
    
    return $slug;
}

// ============================================================
// 1. RESİM YÜKLE (LİNK + DOSYA TEK SİSTEM)
// ============================================================
if ($ajax == 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!csrfKontrol($csrf)) {
        echo json_encode(['success' => false, 'message' => 'CSRF hatası - Token geçersiz']);
        exit;
    }
    
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/blog/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $resim_url = isset($_POST['resim_url']) ? trim($_POST['resim_url']) : '';
    $dosya = isset($_FILES['resim']) ? $_FILES['resim'] : null;
    
    $tempPath = null;
    $mime = null;
    
    // DURUM 1: Link
    if (!empty($resim_url)) {
        $ch = curl_init($resim_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        $resim_verisi = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200 || empty($resim_verisi)) {
            echo json_encode(['success' => false, 'message' => 'Resim indirilemedi: ' . $resim_url]);
            exit;
        }
        
        $tempPath = tempnam(sys_get_temp_dir(), 'blog_');
        file_put_contents($tempPath, $resim_verisi);
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tempPath);
        finfo_close($finfo);
        
        if (strpos($mime, 'image/') !== 0) {
            unlink($tempPath);
            echo json_encode(['success' => false, 'message' => 'Geçersiz resim dosyası: ' . $mime]);
            exit;
        }
        
    // DURUM 2: Dosya
    } elseif ($dosya && $dosya['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($dosya['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff'];
        
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Sadece resim dosyaları (jpg, png, gif, webp)']);
            exit;
        }
        
        $tempPath = $dosya['tmp_name'];
        $info = getimagesize($tempPath);
        if (!$info) {
            echo json_encode(['success' => false, 'message' => 'Geçersiz resim dosyası']);
            exit;
        }
        $mime = $info['mime'];
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Resim kaynağı bulunamadı (link veya dosya)']);
        exit;
    }
    
    // WEBP DÖNÜŞÜMÜ
    $filename = 'blog_' . time() . '_' . rand(1000, 9999) . '.webp';
    $filepath = $upload_dir . $filename;
    
    $image = null;
    
    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            $image = imagecreatefromjpeg($tempPath);
            break;
        case 'image/png':
            $image = imagecreatefrompng($tempPath);
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($tempPath);
            break;
        case 'image/webp':
            if (copy($tempPath, $filepath)) {
                if (strpos($tempPath, sys_get_temp_dir()) === 0) @unlink($tempPath);
                echo json_encode(['success' => true, 'url' => '/uploads/blog/' . $filename, 'filename' => $filename, 'message' => 'Resim başarıyla yüklendi (WebP)']);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Dosya kopyalanamadı']);
                exit;
            }
        case 'image/bmp':
            $image = imagecreatefrombmp($tempPath);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Desteklenmeyen format: ' . $mime]);
            exit;
    }
    
    if (!$image) {
        echo json_encode(['success' => false, 'message' => 'Resim işlenemedi']);
        exit;
    }
    
    $result = imagewebp($image, $filepath, 85);
    imagedestroy($image);
    
    if (strpos($tempPath, sys_get_temp_dir()) === 0) {
        @unlink($tempPath);
    }
    
    if ($result) {
        sendIndexNow('https://www.dribrahimdurandentalclinic.com/blog/');
        echo json_encode(['success' => true, 'url' => '/uploads/blog/' . $filename, 'filename' => $filename, 'message' => 'Resim başarıyla yüklendi ve WebP\'ye dönüştürüldü']);
    } else {
        echo json_encode(['success' => false, 'message' => 'WebP dönüşümü başarısız']);
    }
    exit;
}

// ============================================================
// 2. TOGGLE
// ============================================================
if ($ajax == 'toggle' && isset($_GET['id']) && isset($_GET['durum'])) {
    $id = intval($_GET['id']);
    $durum = intval($_GET['durum']);
    $stmt = $db->prepare("UPDATE blog_yazilar SET durum = ? WHERE id = ?");
    $stmt->execute([$durum, $id]);
    echo json_encode(['success' => true, 'durum' => $durum]);
    exit;
}

// ============================================================
// 3. SEO KAYDET
// ============================================================
if (isset($_GET['ajax']) && $_GET['ajax'] == 'seo_kaydet') {
    $data = json_decode(file_get_contents('php://input'), true);
    $csrf = $data['csrf_token'] ?? '';
    
    if (!csrfKontrol($csrf)) {
        echo json_encode(['success' => false, 'message' => 'CSRF hatası - Token geçersiz']);
        exit;
    }
    
    $fields = ['seo_title_tr', 'seo_title_en', 'seo_description_tr', 'seo_description_en', 'seo_keywords_tr', 'seo_keywords_en', 'seo_og_image', 'seo_canonical'];
    
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS blog_seo_ayarlar (
            id INT AUTO_INCREMENT PRIMARY KEY, 
            anahtar VARCHAR(100) NOT NULL UNIQUE, 
            deger TEXT, 
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        foreach ($fields as $key) {
            $value = isset($data[$key]) ? trim($data[$key]) : '';
            $stmt = $db->prepare("INSERT INTO blog_seo_ayarlar (anahtar, deger) VALUES (?, ?) ON DUPLICATE KEY UPDATE deger = ?");
            $stmt->execute([$key, $value, $value]);
        }
        
        sendIndexNow('https://www.dribrahimdurandentalclinic.com/blog/');
        echo json_encode(['success' => true, 'message' => 'SEO ayarları kaydedildi']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================================
// 4. SEO GET
// ============================================================
if (isset($_GET['ajax']) && $_GET['ajax'] == 'seo_get') {
    try {
        $stmt = $db->query("SELECT anahtar, deger FROM blog_seo_ayarlar");
        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[$row['anahtar']] = $row['deger'];
        }
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ============================================================
// 5. GET (YAZI DETAYI) - VERİ TEMİZLEMELİ
// ============================================================
if ($ajax == 'get' && isset($_GET['id'])) {
    $id = intval($_GET['id']);

    if ($sub == 'yazilar') {
        try {
            $stmt = $db->prepare("SELECT 
                id, baslik, slug, kategori, ozet, icerik, resim, 
                yazar, yazar_unvan, goruntulenme, begeni, yorum_sayisi, durum, 
                created_at, updated_at, silindi, silinme_tarihi,
                seo_title, seo_description, seo_keywords,
                seo_title_en, seo_description_en, seo_keywords_en
                FROM blog_yazilar WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                $data = temizleJsonVeri($data);
                echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success' => false, 'message' => 'Kayıt bulunamadı']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($sub == 'sss') {
        try {
            $stmt = $db->prepare("SELECT * FROM blog_sss WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($data) {
                $data = temizleJsonVeri($data);
                echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success' => false, 'message' => 'Kayıt bulunamadı']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($sub == 'kategoriler') {
        try {
            $stmt = $db->prepare("SELECT * FROM blog_kategoriler WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($data) {
                $data = temizleJsonVeri($data);
                echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success' => false, 'message' => 'Kayıt bulunamadı']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
        }
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Geçersiz sub: ' . $sub]);
    exit;
}

// ============================================================
// 6. SON SIRA SSS
// ============================================================
if ($ajax == 'son_sira_sss') {
    $maxSira = $db->query("SELECT IFNULL(MAX(sira), 0) + 1 as sira FROM blog_sss WHERE silindi = 0 OR silindi IS NULL")->fetch(PDO::FETCH_ASSOC)['sira'];
    echo json_encode(['success' => true, 'sira' => $maxSira]);
    exit;
}

// ============================================================
// 7. SON SIRA KATEGORİ
// ============================================================
if ($ajax == 'son_sira_kategori') {
    $maxSira = $db->query("SELECT IFNULL(MAX(sira), 0) + 1 as sira FROM blog_kategoriler WHERE silindi = 0 OR silindi IS NULL")->fetch(PDO::FETCH_ASSOC)['sira'];
    echo json_encode(['success' => true, 'sira' => $maxSira]);
    exit;
}

// ============================================================
// 8. SAVE (YAZI KAYDET) - 🔥 SLUG OTOMATİK + BENZERSİZLİK
// ============================================================
if ($ajax == 'save' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    
    if (!csrfKontrol($csrf)) {
        echo json_encode(['success' => false, 'message' => 'CSRF hatası - Token geçersiz']);
        exit;
    }
    
    // ============= YAZI KAYDET =============
    if ($sub == 'yazilar') {
        $id = isset($_POST['id']) && $_POST['id'] !== '' ? intval($_POST['id']) : null;
        $baslik = trim($_POST['baslik'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $kategori = trim($_POST['kategori'] ?? '');
        $ozet = trim($_POST['ozet'] ?? '');
        $icerik = trim($_POST['icerik'] ?? '');
        $resim = trim($_POST['resim'] ?? '');
        $yazar = trim($_POST['yazar'] ?? 'Prof. Dr. İbrahim Duran');
        $yazar_unvan = trim($_POST['yazar_unvan'] ?? '');
        $durum = isset($_POST['durum']) ? intval($_POST['durum']) : 1;
        
        $seo_title = trim($_POST['seo_title_tr'] ?? '');
        $seo_description = trim($_POST['seo_description_tr'] ?? '');
        $seo_keywords = trim($_POST['seo_keywords_tr'] ?? '');
        $seo_title_en = trim($_POST['seo_title_en'] ?? '');
        $seo_description_en = trim($_POST['seo_description_en'] ?? '');
        $seo_keywords_en = trim($_POST['seo_keywords_en'] ?? '');
        
        if (empty($baslik)) {
            echo json_encode(['success' => false, 'message' => 'Başlık zorunludur']);
            exit;
        }
        
        // 🔥 EĞER SLUG BOŞSA BAŞLIKTAN OTOMATİK OLUŞTUR
        if (empty($slug)) {
            $slug = createSlug($baslik);
        } else {
            // Slug'ı temizle
            $slug = createSlug($slug);
        }
        
        // 🔥 BENZERSİZLİK KONTROLÜ
        $slug = benzersizSlug($slug, $id);
        
        try {
            if ($id && $id > 0) {
                $stmt = $db->prepare("UPDATE blog_yazilar SET 
                    baslik=?, slug=?, kategori=?, ozet=?, icerik=?, 
                    resim=?, yazar=?, yazar_unvan=?, durum=?,
                    seo_title=?, seo_description=?, seo_keywords=?,
                    seo_title_en=?, seo_description_en=?, seo_keywords_en=?
                    WHERE id=?");
                $stmt->execute([
                    $baslik, $slug, $kategori, $ozet, $icerik, 
                    $resim, $yazar, $yazar_unvan, $durum,
                    $seo_title, $seo_description, $seo_keywords,
                    $seo_title_en, $seo_description_en, $seo_keywords_en,
                    $id
                ]);
                $message = 'Yazı güncellendi';
                sendIndexNow('https://www.dribrahimdurandentalclinic.com/blog/' . $slug);
            } else {
                $stmt = $db->prepare("INSERT INTO blog_yazilar 
                    (baslik, slug, kategori, ozet, icerik, resim, yazar, yazar_unvan, durum, created_at,
                     seo_title, seo_description, seo_keywords,
                     seo_title_en, seo_description_en, seo_keywords_en) 
                    VALUES (?,?,?,?,?,?,?,?,?, NOW(), ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $baslik, $slug, $kategori, $ozet, $icerik, 
                    $resim, $yazar, $yazar_unvan, $durum,
                    $seo_title, $seo_description, $seo_keywords,
                    $seo_title_en, $seo_description_en, $seo_keywords_en
                ]);
                $id = $db->lastInsertId();
                $message = 'Yazı eklendi';
                sendIndexNow('https://www.dribrahimdurandentalclinic.com/blog/' . $slug);
            }
            echo json_encode(['success' => true, 'message' => $message, 'id' => $id]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
        }
        exit;
    }
    
    // ============= SSS KAYDET =============
    if ($sub == 'sss') {
        $id = isset($_POST['id']) && $_POST['id'] !== '' ? intval($_POST['id']) : null;
        $soru = trim($_POST['soru'] ?? '');
        $cevap = trim($_POST['cevap'] ?? '');
        $yeniSira = intval($_POST['sira'] ?? 0);
        $durum = isset($_POST['durum']) ? intval($_POST['durum']) : 1;
        
        if (empty($soru) || empty($cevap)) {
            echo json_encode(['success' => false, 'message' => 'Soru ve cevap zorunludur']);
            exit;
        }
        
        try {
            if ($id && $id > 0) {
                $stmt = $db->prepare("SELECT sira FROM blog_sss WHERE id = ? AND (silindi = 0 OR silindi IS NULL)");
                $stmt->execute([$id]);
                $eskiSira = $stmt->fetchColumn();
                
                if ($yeniSira > 0 && $eskiSira !== false && $yeniSira != $eskiSira) {
                    if ($yeniSira > $eskiSira) {
                        $db->prepare("UPDATE blog_sss SET sira = sira - 1 WHERE sira > ? AND sira <= ? AND (silindi = 0 OR silindi IS NULL) AND id != ?")->execute([$eskiSira, $yeniSira, $id]);
                    } else {
                        $db->prepare("UPDATE blog_sss SET sira = sira + 1 WHERE sira >= ? AND sira < ? AND (silindi = 0 OR silindi IS NULL) AND id != ?")->execute([$yeniSira, $eskiSira, $id]);
                    }
                }
                
                $stmt = $db->prepare("UPDATE blog_sss SET soru=?, cevap=?, sira=?, durum=? WHERE id=?");
                $stmt->execute([$soru, $cevap, $yeniSira, $durum, $id]);
                $message = 'SSS güncellendi';
            } else {
                $maxSira = $db->query("SELECT IFNULL(MAX(sira), 0) + 1 as yeni_sira FROM blog_sss WHERE silindi = 0 OR silindi IS NULL")->fetch(PDO::FETCH_ASSOC)['yeni_sira'];
                $stmt = $db->prepare("INSERT INTO blog_sss (soru, cevap, sira, durum) VALUES (?,?,?,?)");
                $stmt->execute([$soru, $cevap, $maxSira, $durum]);
                $id = $db->lastInsertId();
                $message = 'SSS eklendi';
            }
            
            sendIndexNow('https://www.dribrahimdurandentalclinic.com/blog/');
            echo json_encode(['success' => true, 'message' => $message, 'id' => $id]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
        }
        exit;
    }
    
    // ============= KATEGORİ KAYDET =============
    if ($sub == 'kategoriler') {
        $id = isset($_POST['id']) && $_POST['id'] !== '' ? intval($_POST['id']) : null;
        $kategori_adi = trim($_POST['kategori_adi'] ?? '');
        $kategori_slug = trim($_POST['kategori_slug'] ?? '');
        $ikon = trim($_POST['ikon'] ?? 'FileText');
        $yeniSira = intval($_POST['sira'] ?? 0);
        
        if (empty($kategori_adi)) {
            echo json_encode(['success' => false, 'message' => 'Kategori adı zorunludur']);
            exit;
        }
        
        if (empty($kategori_slug)) {
            $kategori_slug = createCategorySlug($kategori_adi);
        }
        
        try {
            if ($id && $id > 0) {
                $stmt = $db->prepare("SELECT sira FROM blog_kategoriler WHERE id = ? AND (silindi = 0 OR silindi IS NULL)");
                $stmt->execute([$id]);
                $eskiSira = $stmt->fetchColumn();
                
                if ($yeniSira > 0 && $eskiSira !== false && $yeniSira != $eskiSira) {
                    if ($yeniSira > $eskiSira) {
                        $db->prepare("UPDATE blog_kategoriler SET sira = sira - 1 WHERE sira > ? AND sira <= ? AND (silindi = 0 OR silindi IS NULL) AND id != ?")->execute([$eskiSira, $yeniSira, $id]);
                    } else {
                        $db->prepare("UPDATE blog_kategoriler SET sira = sira + 1 WHERE sira >= ? AND sira < ? AND (silindi = 0 OR silindi IS NULL) AND id != ?")->execute([$yeniSira, $eskiSira, $id]);
                    }
                }
                
                $stmt = $db->prepare("UPDATE blog_kategoriler SET kategori_adi=?, kategori_slug=?, ikon=?, sira=? WHERE id=?");
                $stmt->execute([$kategori_adi, $kategori_slug, $ikon, $yeniSira, $id]);
                $message = 'Kategori güncellendi';
            } else {
                $maxSira = $db->query("SELECT IFNULL(MAX(sira), 0) + 1 as yeni_sira FROM blog_kategoriler WHERE silindi = 0 OR silindi IS NULL")->fetch(PDO::FETCH_ASSOC)['yeni_sira'];
                $stmt = $db->prepare("INSERT INTO blog_kategoriler (kategori_adi, kategori_slug, ikon, sira) VALUES (?,?,?,?)");
                $stmt->execute([$kategori_adi, $kategori_slug, $ikon, $maxSira]);
                $id = $db->lastInsertId();
                $message = 'Kategori eklendi';
            }
            
            sendIndexNow('https://www.dribrahimdurandentalclinic.com/blog/');
            echo json_encode(['success' => true, 'message' => $message, 'id' => $id]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
        }
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Geçersiz sub: ' . $sub]);
    exit;
}

// ============================================================
// 9. DELETE (DURUM = 0 EKLENDİ)
// ============================================================
if ($ajax == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $to_cop = isset($_GET['to_cop']) ? true : false;
    
    if ($sub == 'yazilar') {
        if ($to_cop) {
            $db->prepare("UPDATE blog_yazilar SET silindi = 1, durum = 0, silinme_tarihi = NOW() WHERE id = ?")->execute([$id]);
        } else {
            $db->prepare("DELETE FROM blog_yazilar WHERE id = ?")->execute([$id]);
        }
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($sub == 'sss') {
        if ($to_cop) {
            $db->prepare("UPDATE blog_sss SET silindi = 1, durum = 0, silinme_tarihi = NOW() WHERE id = ?")->execute([$id]);
        } else {
            $db->prepare("DELETE FROM blog_sss WHERE id = ?")->execute([$id]);
        }
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($sub == 'kategoriler') {
        if ($to_cop) {
            $db->prepare("UPDATE blog_kategoriler SET silindi = 1, silinme_tarihi = NOW() WHERE id = ?")->execute([$id]);
        } else {
            $db->prepare("DELETE FROM blog_kategoriler WHERE id = ?")->execute([$id]);
        }
        sendIndexNow('https://www.dribrahimdurandentalclinic.com/blog/');
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($sub == 'aboneler') {
        $db->prepare("DELETE FROM blog_aboneler WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Geçersiz sub']);
    exit;
}

// ============================================================
// 10. ÇÖP KUTUSU - YAZILAR
// ============================================================
if ($ajax == 'cop_listesi' && $sub == 'yazilar') {
    $stmt = $db->query("SELECT * FROM blog_yazilar WHERE silindi = 1 ORDER BY silinme_tarihi DESC");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

if ($ajax == 'restore' && $sub == 'yazilar' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $db->prepare("UPDATE blog_yazilar SET silindi = 0, durum = 1 WHERE id = ?")->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

if ($ajax == 'permanent_delete' && $sub == 'yazilar' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $db->prepare("DELETE FROM blog_yazilar WHERE id = ?")->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

if ($ajax == 'empty_cop' && $sub == 'yazilar') {
    $db->query("DELETE FROM blog_yazilar WHERE silindi = 1");
    sendIndexNow('https://www.dribrahimdurandentalclinic.com/blog/');
    echo json_encode(['success' => true]);
    exit;
}

// ============================================================
// 11. ÇÖP KUTUSU - SSS
// ============================================================
if ($ajax == 'sss_cop_listesi') {
    $stmt = $db->query("SELECT * FROM blog_sss WHERE silindi = 1 ORDER BY silinme_tarihi DESC");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

if ($ajax == 'sss_restore' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $maxSira = $db->query("SELECT IFNULL(MAX(sira), 0) FROM blog_sss WHERE silindi = 0 OR silindi IS NULL")->fetchColumn();
    $yeniSira = $maxSira + 1;
    $stmt = $db->prepare("UPDATE blog_sss SET silindi = 0, durum = 1, silinme_tarihi = NULL, sira = ? WHERE id = ?");
    $stmt->execute([$yeniSira, $id]);
    echo json_encode(['success' => true, 'yeni_sira' => $yeniSira]);
    exit;
}

if ($ajax == 'sss_permanent_delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $db->prepare("DELETE FROM blog_sss WHERE id = ?")->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

if ($ajax == 'sss_empty_cop') {
    $db->query("DELETE FROM blog_sss WHERE silindi = 1");
    sendIndexNow('https://www.dribrahimdurandentalclinic.com/blog/');
    echo json_encode(['success' => true]);
    exit;
}

// ============================================================
// 12. ÇÖP KUTUSU - KATEGORİLER
// ============================================================
if ($ajax == 'kategori_cop_listesi') {
    $stmt = $db->query("SELECT * FROM blog_kategoriler WHERE silindi = 1 ORDER BY silinme_tarihi DESC");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

if ($ajax == 'kategori_restore' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $db->prepare("UPDATE blog_kategoriler SET silindi = 0, silinme_tarihi = NULL WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

if ($ajax == 'kategori_permanent_delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $db->prepare("DELETE FROM blog_kategoriler WHERE id = ?")->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

if ($ajax == 'kategori_empty_cop') {
    $db->query("DELETE FROM blog_kategoriler WHERE silindi = 1");
    sendIndexNow('https://www.dribrahimdurandentalclinic.com/blog/');
    echo json_encode(['success' => true]);
    exit;
}

// ============================================================
// 13. GEÇERSİZ İŞLEM
// ============================================================
echo json_encode(['success' => false, 'message' => 'Geçersiz işlem: ' . $ajax]);
exit;
?>