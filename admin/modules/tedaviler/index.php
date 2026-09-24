<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

kontrol();
yetkiKontrol('tedaviler', 'goruntuleyebilir');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ========== AJAX ISLEMLERI ==========
if (isset($_GET['islem']) && !empty($_GET['islem'])) {
    header('Content-Type: application/json');
    
    // ========== TEDAVILER GENEL SEO KAYDET ==========
    if ($_GET['islem'] === 'tedaviler_seo_kaydet' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $data['csrf_token'])) {
            echo json_encode(['success' => false, 'message' => 'CSRF hatasi']);
            exit;
        }

        $fields = [
            'seo_title_tr', 'seo_title_en',
            'seo_description_tr', 'seo_description_en',
            'seo_keywords_tr', 'seo_keywords_en',
            'seo_og_image', 'seo_canonical'
        ];

        try {
            $db->exec("CREATE TABLE IF NOT EXISTS tedaviler_seo_ayarlar (
                id INT AUTO_INCREMENT PRIMARY KEY,
                anahtar VARCHAR(100) NOT NULL UNIQUE,
                deger TEXT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");

            foreach ($fields as $key) {
                $val = trim($data[$key] ?? '');
                $stmt = $db->prepare("INSERT INTO tedaviler_seo_ayarlar (anahtar, deger) VALUES (?, ?) ON DUPLICATE KEY UPDATE deger = ?");
                $stmt->execute([$key, $val, $val]);
            }

            echo json_encode(['success' => true, 'message' => 'Tedaviler SEO ayarlari kaydedildi']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
        }
        exit;
    }
    
    // ========== RESIM DONDUR ==========
    if ($_GET['islem'] === 'resim_dondur' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $data['csrf_token'])) {
            echo json_encode(['success' => false, 'message' => 'CSRF hatasi']);
            exit;
        }
        
        $resim_url = trim($data['url'] ?? '');
        $yon = intval($data['yon'] ?? 90);
        
        if (empty($resim_url)) {
            echo json_encode(['success' => false, 'message' => 'Resim URL bos']);
            exit;
        }
        
        $dosya_yolu = $_SERVER['DOCUMENT_ROOT'] . $resim_url;
        if (!file_exists($dosya_yolu)) {
            echo json_encode(['success' => false, 'message' => 'Dosya bulunamadi']);
            exit;
        }
        
        $info = getimagesize($dosya_yolu);
        if (!$info) {
            echo json_encode(['success' => false, 'message' => 'Gecersiz resim']);
            exit;
        }
        
        $img = null;
        switch ($info['mime']) {
            case 'image/jpeg': $img = imagecreatefromjpeg($dosya_yolu); break;
            case 'image/png':  
                $img = imagecreatefrompng($dosya_yolu); 
                imagepalettetotruecolor($img);
                imagealphablending($img, true);
                imagesavealpha($img, true);
                break;
            case 'image/webp': $img = imagecreatefromwebp($dosya_yolu); break;
            case 'image/gif':  $img = imagecreatefromgif($dosya_yolu); break;
            default:
                echo json_encode(['success' => false, 'message' => 'Desteklenmeyen format']);
                exit;
        }
        
        if (!$img) {
            echo json_encode(['success' => false, 'message' => 'Resim islenemedi']);
            exit;
        }
        
        $dondurulmus = imagerotate($img, $yon, 0);
        imagedestroy($img);
        
        if (!$dondurulmus) {
            echo json_encode(['success' => false, 'message' => 'Dondurme basarisiz']);
            exit;
        }
        
        $pathinfo = pathinfo($dosya_yolu);
        $yeni_dosya = $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '_rotated_' . time() . '.webp';
        
        if (imagewebp($dondurulmus, $yeni_dosya, 85)) {
            imagedestroy($dondurulmus);
            $yeni_url = str_replace($_SERVER['DOCUMENT_ROOT'], '', $yeni_dosya);
            
            echo json_encode([
                'success' => true, 
                'url' => $yeni_url,
                'message' => 'Resim ' . $yon . '° donduruldu'
            ]);
        } else {
            imagedestroy($dondurulmus);
            echo json_encode(['success' => false, 'message' => 'Kaydetme basarisiz']);
        }
        exit;
    }   
        
    // ========== URL'DEN RESIM INDIR ==========
    if ($_GET['islem'] === 'url_ile_resim' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $data['csrf_token'])) {
            echo json_encode(['success' => false, 'message' => 'CSRF hatasi']);
            exit;
        }
        
        $url = trim($data['url'] ?? '');
        if (empty($url)) {
            echo json_encode(['success' => false, 'message' => 'URL bos olamaz']);
            exit;
        }
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            echo json_encode(['success' => false, 'message' => 'Gecersiz URL']);
            exit;
        }
        
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        $ext = explode('?', $ext)[0];
        
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Desteklenmeyen format: ' . $ext]);
            exit;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $image_data = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            echo json_encode(['success' => false, 'message' => 'cURL hatasi: ' . $curl_error]);
            exit;
        }
        
        if ($http_code !== 200) {
            echo json_encode(['success' => false, 'message' => 'HTTP ' . $http_code . ' hatasi']);
            exit;
        }
        
        if (empty($image_data)) {
            echo json_encode(['success' => false, 'message' => 'Indirilen veri bos']);
            exit;
        }
        
        $temp_file = sys_get_temp_dir() . '/url_image_' . time() . '.' . $ext;
        file_put_contents($temp_file, $image_data);

        $info = getimagesize($temp_file);
        if (!$info) {
            unlink($temp_file);
            echo json_encode(['success' => false, 'message' => 'Gecersiz resim dosyasi']);
            exit;
        }
        
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/tedaviler/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $filename = 'tedavi_' . time() . '_' . rand(1000, 9999) . '.webp';
        $destination = $upload_dir . $filename;
        
        $img = null;
        switch ($info['mime']) {
            case 'image/jpeg': $img = imagecreatefromjpeg($temp_file); break;
            case 'image/png':  
                $img = imagecreatefrompng($temp_file); 
                imagepalettetotruecolor($img);
                imagealphablending($img, true);
                imagesavealpha($img, true);
                break;
            case 'image/gif':  $img = imagecreatefromgif($temp_file); break;
            case 'image/webp': $img = imagecreatefromwebp($temp_file); break;
            default:
                unlink($temp_file);
                echo json_encode(['success' => false, 'message' => 'Desteklenmeyen format']);
                exit;
        }
        
        if (!$img) {
            unlink($temp_file);
            echo json_encode(['success' => false, 'message' => 'Resim islenemedi']);
            exit;
        }
        
        $width = $info[0];
        $height = $info[1];
        $max_width = 1200;
        
        if ($width > $max_width) {
            $new_width = $max_width;
            $new_height = ($height / $width) * $new_width;
            $new_img = imagecreatetruecolor($new_width, $new_height);
            imagecopyresampled($new_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            imagedestroy($img);
            $img = $new_img;
        }
        
        if (imagewebp($img, $destination, 85)) {
            imagedestroy($img);
            unlink($temp_file);
            echo json_encode([
                'success' => true, 
                'url' => '/uploads/tedaviler/' . $filename,
                'filename' => $filename
            ]);
        } else {
            imagedestroy($img);
            unlink($temp_file);
            echo json_encode(['success' => false, 'message' => 'WebP donusumu basarisiz']);
        }
        exit;
    }   
        
    // ========== RESIM YUKLE ==========
    if ($_GET['islem'] === 'resim_yukle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            echo json_encode(['success' => false, 'message' => 'CSRF hatasi']);
            exit;
        }
        
        if (!isset($_FILES['resim']) || $_FILES['resim']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Dosya yuklenemedi']);
            exit;
        }
        
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/tedaviler/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $file = $_FILES['resim'];
        $max_size = 20 * 1024 * 1024;
        
        if ($file['size'] > $max_size) {
            echo json_encode(['success' => false, 'message' => 'Max dosya boyutu 20MB']);
            exit;
        }
        
        $info = getimagesize($file['tmp_name']);
        if (!$info) {
            echo json_encode(['success' => false, 'message' => 'Gecersiz resim dosyasi']);
            exit;
        }
        
        $filename = 'tedavi_' . time() . '_' . rand(1000, 9999) . '.webp';
        $destination = $upload_dir . $filename;
        
        $img = null;
        switch ($info['mime']) {
            case 'image/jpeg': $img = imagecreatefromjpeg($file['tmp_name']); break;
            case 'image/png':  
                $img = imagecreatefrompng($file['tmp_name']); 
                imagepalettetotruecolor($img);
                imagealphablending($img, true);
                imagesavealpha($img, true);
                break;
            case 'image/gif':  $img = imagecreatefromgif($file['tmp_name']); break;
            case 'image/webp': $img = imagecreatefromwebp($file['tmp_name']); break;
            default:
                echo json_encode(['success' => false, 'message' => 'Desteklenmeyen format (JPEG, PNG, GIF, WEBP)']);
                exit;
        }
        
        $width = $info[0];
        $height = $info[1];
        $max_width = 1200;
        
        if ($width > $max_width) {
            $new_width = $max_width;
            $new_height = ($height / $width) * $new_width;
            $new_img = imagecreatetruecolor($new_width, $new_height);
            imagecopyresampled($new_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            imagedestroy($img);
            $img = $new_img;
        }
        
        if (imagewebp($img, $destination, 85)) {
            imagedestroy($img);
            echo json_encode([
                'success' => true, 
                'url' => '/uploads/tedaviler/' . $filename,
                'filename' => $filename
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'WebP donusumu basarisiz']);
        }
        exit;
    }   

    // ===== TEDAVI SLIDER ISLEMLERI =====
    if ($_GET['islem'] === 'slider_getir' && isset($_GET['tedavi_id'])) {
        $stmt = $db->prepare("SELECT * FROM tedavi_slider WHERE tedavi_id = ? ORDER BY sira ASC");
        $stmt->execute([$_GET['tedavi_id']]);
        $sliders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'sliders' => $sliders]);
        exit;
    }

    if ($_GET['islem'] === 'slider_sil' && isset($_GET['id'])) {
        $stmt = $db->prepare("DELETE FROM tedavi_slider WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($_GET['islem'] === 'get' && isset($_GET['id'])) {
        $stmt = $db->prepare("SELECT * FROM tedaviler WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            $data['avantajlar'] = json_decode($data['avantajlar_json'], true) ?: [];
            $data['sss'] = json_decode($data['sss_json'], true) ?: [];
            $data['adimlar'] = json_decode($data['adimlar_json'], true) ?: [];
            $stmt2 = $db->prepare("SELECT * FROM tedavi_slider WHERE tedavi_id = ? ORDER BY sira ASC");
            $stmt2->execute([$data['id']]);
            $data['sliders'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        }
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }
        
    // ========== TEDAVI KAYDET ==========
    if ($_GET['islem'] === 'kaydet' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $data['csrf_token'])) {
            echo json_encode(['success' => false, 'message' => 'CSRF hatasi']);
            exit;
        }
        
        $id = $data['id'] ?? null;
        $baslik = trim($data['baslik'] ?? '');
        $slug = trim($data['slug'] ?? '');
        $kategori = $data['kategori'] ?? '';
        $meta_description = trim($data['meta_description'] ?? '');
        $kisa_aciklama = trim($data['kisa_aciklama'] ?? '');
        $detayli_aciklama = $data['detayli_aciklama'] ?? '';
        $oncesi_resim = '';
        $sonrasi_resim = '';
        $sure = trim($data['sure'] ?? '');
        $video_url = trim($data['video_url'] ?? '');
        $sira = intval($data['sira'] ?? 0);
        $aktif = isset($data['aktif']) ? 1 : 0;

        
        $seo_title_tr = trim($data['seo_title_tr'] ?? '');
        $seo_title_en = trim($data['seo_title_en'] ?? '');
        $seo_description_tr = trim($data['seo_description_tr'] ?? '');
        $seo_description_en = trim($data['seo_description_en'] ?? '');
        $seo_keywords_tr = trim($data['seo_keywords_tr'] ?? '');
        $seo_keywords_en = trim($data['seo_keywords_en'] ?? '');
        $seo_og_image = trim($data['seo_og_image'] ?? '');
        $seo_canonical = trim($data['seo_canonical'] ?? '');
        $seo_robots = trim($data['seo_robots'] ?? 'index, follow');
        
        $avantajlar = json_encode($data['avantajlar'] ?? [], JSON_UNESCAPED_UNICODE);
        $sss = json_encode($data['sss'] ?? [], JSON_UNESCAPED_UNICODE);
        $adimlar = json_encode($data['adimlar'] ?? [], JSON_UNESCAPED_UNICODE);
        
        $sliders = $data['sliders'] ?? [];
        
        if (empty($baslik) || empty($kategori) || empty($kisa_aciklama)) {
            echo json_encode(['success' => false, 'message' => 'Baslik, Kategori ve Kisa Aciklama zorunludur']);
            exit;
        }
        
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9guısocGUISOC]+/u', '-', $baslik), '-'));
        }
        
        try {
            $db->beginTransaction();
            
            if ($id) {
                $stmt = $db->prepare("UPDATE tedaviler SET 
                    baslik=?, slug=?, kategori=?, meta_description=?, 
                    kisa_aciklama=?, detayli_aciklama=?, oncesi_resim=?, sonrasi_resim=?, sure=?, 
                    video_url=?, sira=?, aktif=?, 
                    avantajlar_json=?, sss_json=?, 
                    adimlar_json=?,
                    seo_title_tr=?, seo_title_en=?,
                    seo_description_tr=?, seo_description_en=?,
                    seo_keywords_tr=?, seo_keywords_en=?,
                    seo_og_image=?, seo_canonical=?, seo_robots=?
                    WHERE id=?");
                $stmt->execute([
                    $baslik, $slug, $kategori, $meta_description,
                    $kisa_aciklama, $detayli_aciklama, $oncesi_resim, $sonrasi_resim, $sure,
                    $video_url, $sira, $aktif, 
                     $avantajlar, $sss, $adimlar,
                    $seo_title_tr, $seo_title_en,
                    $seo_description_tr, $seo_description_en,
                    $seo_keywords_tr, $seo_keywords_en,
                    $seo_og_image, $seo_canonical, $seo_robots,
                    $id
                ]);
            } else {
                $stmt = $db->prepare("INSERT INTO tedaviler (
                    baslik, slug, kategori, meta_description, 
                    kisa_aciklama, detayli_aciklama, oncesi_resim, sonrasi_resim, sure, 
                    video_url, sira, aktif, 
                    avantajlar_json, sss_json, 
                    adimlar_json,
                    seo_title_tr, seo_title_en,
                    seo_description_tr, seo_description_en,
                    seo_keywords_tr, seo_keywords_en,
                    seo_og_image, seo_canonical, seo_robots
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([
                    $baslik, $slug, $kategori, $meta_description,
                    $kisa_aciklama, $detayli_aciklama, $oncesi_resim, $sonrasi_resim, $sure,
                    $video_url, $sira, $aktif, 
                     $avantajlar, $sss, $adimlar,
                    $seo_title_tr, $seo_title_en,
                    $seo_description_tr, $seo_description_en,
                    $seo_keywords_tr, $seo_keywords_en,
                    $seo_og_image, $seo_canonical, $seo_robots
                ]);
                $id = $db->lastInsertId();
            }
            
            // ===== SLIDER'LARI KAYDET (İŞLEM + ALT BAŞLIK DAHİL) =====
            if ($id) {
                $db->prepare("DELETE FROM tedavi_slider WHERE tedavi_id = ?")->execute([$id]);
                if (is_array($sliders) && count($sliders) > 0) {
$insertStmt = $db->prepare("INSERT INTO tedavi_slider (tedavi_id, before_resim, after_resim, islem, alt_baslik_json, islem_tarihi, goruntuleme_tarihi, sira) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$sira = 1;
foreach ($sliders as $slider) {
    $before = trim($slider['before'] ?? '');
    $after = trim($slider['after'] ?? '');
    $islem = trim($slider['islem'] ?? '');
    $islem_tarihi = trim($slider['islem_tarihi'] ?? '');
    $goruntuleme_tarihi = trim($slider['goruntuleme_tarihi'] ?? '');
    
    // 🔥 Alt başlık (sadece TR — EN, t_cevir() ile otomatik çevrilir)
    $alt_baslik_tr = trim($slider['alt_baslik_tr'] ?? '');
    $alt_baslik_json = null;
    if ($alt_baslik_tr !== '') {
        $alt_baslik_json = json_encode([
            'tr' => $alt_baslik_tr
        ], JSON_UNESCAPED_UNICODE);
    }
    
    if (!empty($before) || !empty($after)) {
        $insertStmt->execute([
            $id, 
            $before, 
            $after, 
            $islem,
            $alt_baslik_json,
            $islem_tarihi ?: null,
            $goruntuleme_tarihi ?: null,
            $sira++
        ]);
    }
}
                }
            }
            
            $db->commit();
            
            if ($id) {
                $indexnow_file = $_SERVER['DOCUMENT_ROOT'] . '/inc/indexnow.php';
                if (file_exists($indexnow_file)) {
                    require_once $indexnow_file;
                    $site_url = "https://www.dribrahimdurandentalclinic.com";  
                    $url = $site_url . '/tedaviler/' . $slug;
                    if (function_exists('indexNowTekliGonder')) {
                        @indexNowTekliGonder($url);
                    }
                }
            }
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ========== TEDAVI SIL ==========
    if ($_GET['islem'] === 'sil' && isset($_GET['id'])) {
        $stmt = $db->prepare("UPDATE tedaviler SET silindi = 1 WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        echo json_encode(['success' => true]);
        exit;
    }
        
    // ========== KALICI SIL ==========
    if ($_GET['islem'] === 'kalici_sil' && isset($_GET['id'])) {
        if ($_SESSION['admin_rol'] != 'superadmin') {
            echo json_encode(['success' => false, 'message' => 'Yetkiniz yok']);
            exit;
        }
        $stmt = $db->prepare("DELETE FROM tedaviler WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $db->prepare("DELETE FROM tedavi_slider WHERE tedavi_id = ?")->execute([$_GET['id']]);
        
        $indexnow_file = $_SERVER['DOCUMENT_ROOT'] . '/inc/indexnow.php';
        if (file_exists($indexnow_file)) {
            require_once $indexnow_file;
            $site_url = "https://www.dribrahimdurandentalclinic.com";
            $url = $site_url . '/tedaviler';
            if (function_exists('indexNowTekliGonder')) {
                @indexNowTekliGonder($url);
            }
        }
            
        echo json_encode(['success' => true]);
        exit;
    }
        
    // ========== GERI GETIR ==========
    if ($_GET['islem'] === 'geri_getir' && isset($_GET['id'])) {
        $stmt = $db->prepare("UPDATE tedaviler SET silindi = 0 WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        
        $indexnow_file = $_SERVER['DOCUMENT_ROOT'] . '/inc/indexnow.php';
        if (file_exists($indexnow_file)) {
            require_once $indexnow_file;
            $site_url = "https://www.dribrahimdurandentalclinic.com";
            $url = $site_url . '/tedaviler';
            if (function_exists('indexNowTekliGonder')) {
                @indexNowTekliGonder($url);
            }
        }
        
        echo json_encode(['success' => true]);
        exit;
    }
        
    // ========== DURUM DEGISTIR ==========
    if ($_GET['islem'] === 'durum' && isset($_GET['id'])) {
        $stmt = $db->prepare("UPDATE tedaviler SET aktif = IF(aktif=1,0,1) WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        echo json_encode(['success' => true]);
        exit;
    }
        
    echo json_encode(['success' => false, 'message' => 'Gecersiz islem']);
    exit;
}

// ========== NORMAL SAYFA ==========
$tedaviler = $db->query("SELECT * FROM tedaviler WHERE silindi = 0 ORDER BY sira ASC")->fetchAll();
$silinenler = $db->query("SELECT * FROM tedaviler WHERE silindi = 1 ORDER BY updated_at DESC")->fetchAll();
$kategoriler_raw = $db->query("SELECT DISTINCT kategori FROM tedaviler WHERE silindi = 0 ORDER BY kategori")->fetchAll(PDO::FETCH_COLUMN);

$sliderMap = [];
$sliderStmt = $db->query("SELECT tedavi_id, after_resim FROM tedavi_slider WHERE tedavi_id IN (SELECT id FROM tedaviler WHERE silindi = 0) ORDER BY tedavi_id, sira ASC");
while ($row = $sliderStmt->fetch()) {
    if (!isset($sliderMap[$row['tedavi_id']])) {
        $sliderMap[$row['tedavi_id']] = $row['after_resim'];
    }
}
$sliderMapSilinen = [];
$sliderStmtSilinen = $db->query("SELECT tedavi_id, after_resim FROM tedavi_slider WHERE tedavi_id IN (SELECT id FROM tedaviler WHERE silindi = 1) ORDER BY tedavi_id, sira ASC");
while ($row = $sliderStmtSilinen->fetch()) {
    if (!isset($sliderMapSilinen[$row['tedavi_id']])) {
        $sliderMapSilinen[$row['tedavi_id']] = $row['after_resim'];
    }
}

$kategoriler = [];
foreach ($kategoriler_raw as $kat) {
    if (strpos($kat, '{') === 0 || strpos($kat, '[') === 0) {
        $parsed = json_decode($kat, true);
        if (is_array($parsed)) {
            $kategoriler[] = $parsed['tr'] ?? $parsed['en'] ?? $kat;
        } else {
            $kategoriler[] = $kat;
        }
    } else {
        $kategoriler[] = $kat;
    }
}
$kategoriler = array_unique($kategoriler);

$toplam = count($tedaviler) + count($silinenler);
$aktif = $db->query("SELECT COUNT(*) FROM tedaviler WHERE aktif = 1 AND silindi = 0")->fetchColumn();
$pasif = $db->query("SELECT COUNT(*) FROM tedaviler WHERE aktif = 0 AND silindi = 0")->fetchColumn();
$silinen_sayisi = count($silinenler);
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'liste';

// ========== SEO AYARLARI ==========
$tedaviler_seo_title_tr = 'Tedaviler | Prof. Dr. İbrahim Duran | Diş Kliniği Samsun';
$tedaviler_seo_title_en = 'Treatments | Prof. Dr. İbrahim Duran | Dental Clinic Samsun';
$tedaviler_seo_description_tr = 'Prof. Dr. İbrahim Duran, Samsun Atakum\'da Diş Estetiği · Diş Ağrısı · İmplant Tedavisi · Diş Eti Hastalıkları · Kanal Tedavisi · Ortodontik Tedavi · Çocuk Diş Tedavisi · Çene Eklemi Rahatsızlıkları. Tedavi Hizmeti Vermektedir.';
$tedaviler_seo_description_en = 'Prof. Dr. İbrahim Duran offers dental aesthetic, implant treatment, gum diseases, root canal treatment, orthodontic treatment, pediatric dentistry, and jaw joint disorders in Samsun Atakum.';
$tedaviler_seo_keywords_tr = 'Prof. Dr. İbrahim Duran, Samsun diş hekimi, Atakum diş hekimi, Samsun diş kliniği, RivaDent Atakum, Samsun implant tedavisi, Samsun gülüş tasarımı, Samsun zirkonyum kaplama, Samsun lamine diş, Samsun diş beyazlatma, Samsun kanal tedavisi, Samsun ortodonti, Samsun çocuk diş hekimi, Samsun diş eti hastalıkları tedavisi, Samsun çene cerrahisi, Samsun 20 yaş dişi çekimi, Samsun kemik grefti, Samsun sinüs lifting';
$tedaviler_seo_keywords_en = 'Prof. Dr. İbrahim Duran, Samsun dentist, Atakum dentist, Samsun dental clinic, RivaDent Atakum, Samsun implant treatment, Samsun smile design, Samsun zirconium coating, Samsun laminate teeth, Samsun teeth whitening, Samsun root canal treatment, Samsun orthodontics, Samsun pediatric dentist, Samsun gum disease treatment';
$tedaviler_seo_og_image = '/uploads/tedaviler/tedaviler-og.webp';
$tedaviler_seo_canonical = 'https://www.dribrahimdurandentalclinic.com/tedaviler/';

try {
    $tables = $db->query("SHOW TABLES LIKE 'tedaviler_seo_ayarlar'")->fetchAll();
    if (count($tables) > 0) {
        $stmt = $db->query("SELECT anahtar, deger FROM tedaviler_seo_ayarlar");
        while ($row = $stmt->fetch()) {
            if ($row['anahtar'] == 'seo_title_tr') $tedaviler_seo_title_tr = $row['deger'];
            if ($row['anahtar'] == 'seo_title_en') $tedaviler_seo_title_en = $row['deger'];
            if ($row['anahtar'] == 'seo_description_tr') $tedaviler_seo_description_tr = $row['deger'];
            if ($row['anahtar'] == 'seo_description_en') $tedaviler_seo_description_en = $row['deger'];
            if ($row['anahtar'] == 'seo_keywords_tr') $tedaviler_seo_keywords_tr = $row['deger'];
            if ($row['anahtar'] == 'seo_keywords_en') $tedaviler_seo_keywords_en = $row['deger'];
            if ($row['anahtar'] == 'seo_og_image') $tedaviler_seo_og_image = $row['deger'];
            if ($row['anahtar'] == 'seo_canonical') $tedaviler_seo_canonical = $row['deger'];
        }
    }
} catch (Exception $e) {}
?>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
.kurumsal-container { background: #f1f5f9; min-height: 100vh; padding: 24px; }
.kurumsal-card { background: #ffffff; border-radius: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden; }
.kurumsal-header { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); padding: 24px 32px; color: white; display: flex; justify-content: space-between; align-items: center; }
.kurumsal-header h3 { margin: 0; font-size: 1.4rem; font-weight: 700; display: flex; align-items: center; gap: 12px; }
.kurumsal-header-actions { display: flex; gap: 10px; align-items: center; }
.kurumsal-header-actions button { padding: 8px 18px; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 8px; }
.btn-refresh { background: rgba(255,255,255,0.1); color: #94a3b8; border: 1px solid rgba(255,255,255,0.05); }
.btn-refresh:hover { background: rgba(255,255,255,0.2); color: white; }
.kurumsal-tabs { display: flex; flex-wrap: wrap; gap: 0; padding: 0 24px; background: #ffffff; border-bottom: 2px solid #f1f5f9; }
.kurumsal-tab { padding: 14px 24px; font-size: 13px; font-weight: 600; color: #64748b; background: transparent; border: none; cursor: pointer; border-bottom: 3px solid transparent; margin-bottom: -2px; transition: all 0.25s ease; position: relative; }
.kurumsal-tab:hover { color: #3b82f6; background: #f8fafc; }
.kurumsal-tab.active { color: #3b82f6; border-bottom-color: #3b82f6; }
.kurumsal-tab .tab-badge { background: #e2e8f0; color: #64748b; font-size: 10px; padding: 1px 8px; border-radius: 20px; margin-left: 6px; }
.kurumsal-tab.active .tab-badge { background: #dbeafe; color: #3b82f6; }

.filter-bar { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 24px; background: white; padding: 16px 20px; border-radius: 14px; border: 1px solid #e2e8f0; }
.filter-input { flex: 2; min-width: 200px; padding: 10px 16px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 13px; transition: all 0.2s; background: #fafbfc; }
.filter-input:focus { outline: none; border-color: #3b82f6; background: white; box-shadow: 0 0 0 4px rgba(59,130,246,0.08); }
.filter-select { padding: 10px 16px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 13px; background: #fafbfc; cursor: pointer; transition: all 0.2s; min-width: 150px; }
.filter-select:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59,130,246,0.08); }
.filter-btn { padding: 10px 22px; background: #f1f5f9; border: none; border-radius: 10px; font-size: 13px; font-weight: 500; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; }
.filter-btn:hover { background: #e2e8f0; }

.tedavi-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; }
.tedavi-card { background: white; border-radius: 20px; overflow: hidden; border: 1px solid #e2e8f0; transition: all 0.3s; }
.tedavi-card:hover { transform: translateY(-6px); box-shadow: 0 20px 30px -12px rgba(0,0,0,0.12); border-color: #cbd5e1; }
.tedavi-img { height: 180px; overflow: hidden; position: relative; background: #f1f5f9; }
.tedavi-img img { width: 100%; height: 100%; object-fit: scale-down; transition: transform 0.5s ease; }
.tedavi-card:hover .tedavi-img img { transform: scale(1.04); }
.tedavi-badge { position: absolute; top: 12px; left: 12px; background: rgba(0,0,0,0.75); backdrop-filter: blur(4px); padding: 4px 14px; border-radius: 20px; font-size: 10px; font-weight: 600; color: white; letter-spacing: 0.3px; }
.tedavi-status { position: absolute; top: 12px; right: 12px; padding: 4px 12px; border-radius: 20px; font-size: 10px; font-weight: 600; letter-spacing: 0.3px; }
.status-active { background: rgba(16, 185, 129, 0.9); color: white; }
.status-passive { background: rgba(239, 68, 68, 0.9); color: white; }
.tedavi-content { padding: 18px 20px 16px; }
.tedavi-category { font-size: 11px; color: #3b82f6; font-weight: 600; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
.tedavi-title { font-size: 17px; font-weight: 700; color: #0f172a; margin-bottom: 6px; line-height: 1.3; }
.tedavi-desc { font-size: 13px; color: #64748b; line-height: 1.5; margin-bottom: 14px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.tedavi-actions { display: flex; gap: 8px; padding-top: 14px; border-top: 1px solid #e2e8f0; }
.btn-icon { height: 38px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; border: none; font-size: 13px; font-weight: 500; }
.btn-edit { background: #3b82f6; color: white; width: 38px; }
.btn-edit:hover { background: #2563eb; transform: scale(1.06); }
.btn-toggle { background: #f59e0b; color: white; flex: 1; padding: 0 14px; }
.btn-toggle:hover { background: #d97706; }
.btn-delete { background: #ef4444; color: white; width: 38px; }
.btn-delete:hover { background: #dc2626; transform: scale(1.06); }

.modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); z-index: 99999; align-items: center; justify-content: center; animation: modalFadeIn 0.25s ease; }
@keyframes modalFadeIn { from { opacity: 0; transform: scale(0.96); } to { opacity: 1; transform: scale(1); } }
.modal-content { background: #ffffff; border-radius: 28px; width: 94%; max-width: 960px; max-height: 92vh; overflow: hidden; box-shadow: 0 30px 60px -20px rgba(0,0,0,0.4); display: flex; flex-direction: column; position: relative; }
.modal-header { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); padding: 20px 28px; color: white; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; }
.modal-header h4 { margin: 0; font-size: 1.2rem; font-weight: 700; display: flex; align-items: center; gap: 10px; }
.modal-header .modal-close { width: 38px; height: 38px; background: rgba(255,255,255,0.08); border: none; border-radius: 10px; color: #94a3b8; font-size: 18px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; }
.modal-header .modal-close:hover { background: rgba(239,68,68,0.2); color: #f87171; }
.modal-body { padding: 24px 28px 20px; overflow-y: auto; flex: 1; }
.modal-body::-webkit-scrollbar { width: 6px; }
.modal-body::-webkit-scrollbar-track { background: #f1f5f9; }
.modal-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 8px; }
.modal-body::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

.form-card { background: white; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 18px; overflow: hidden; transition: all 0.2s; }
.form-card:hover { border-color: #cbd5e1; box-shadow: 0 2px 10px rgba(0,0,0,0.04); }
.form-card-header { background: #f8fafc; padding: 12px 18px; border-bottom: 1px solid #e2e8f0; font-weight: 600; font-size: 13px; color: #1e293b; display: flex; align-items: center; gap: 10px; }
.form-card-body { padding: 16px 18px 18px; }
.form-label { display: block; font-weight: 600; font-size: 13px; color: #334155; margin-bottom: 5px; }
.form-label .required { color: #ef4444; }
.form-control { width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; transition: all 0.2s; background: #ffffff; color: #0f172a; }
.form-control:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59,130,246,0.08); }
.form-control::placeholder { color: #94a3b8; }
textarea.form-control { resize: vertical; min-height: 60px; font-family: inherit; }
select.form-control { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364758b' d='M6 8L1 3h10z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 14px center; padding-right: 36px; }

.grid { display: grid; }
.grid-cols-2 { grid-template-columns: 1fr 1fr; }
.grid-cols-3 { grid-template-columns: 1fr 1fr 1fr; }
.gap-4 { gap: 16px; }
.mt-2 { margin-top: 8px; }
.mt-4 { margin-top: 16px; }
.mb-4 { margin-bottom: 16px; }
.flex { display: flex; }
.items-center { align-items: center; }
.gap-2 { gap: 8px; }
.gap-3 { gap: 12px; }
.flex-1 { flex: 1; }
.space-y-2 > * + * { margin-top: 8px; }
.space-y-3 > * + * { margin-top: 12px; }

.btn-upload { padding: 10px 16px; background: #f1f5f9; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 13px; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; font-weight: 500; color: #475569; }
.btn-upload:hover { background: #e2e8f0; border-color: #cbd5e1; }
.btn-add { background: none; border: 1.5px dashed #3b82f6; color: #3b82f6; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; margin-top: 10px; padding: 8px 18px; border-radius: 10px; transition: all 0.2s; }
.btn-add:hover { background: #eff6ff; border-color: #2563eb; }
.btn-cancel { padding: 10px 28px; background: #f1f5f9; border: none; border-radius: 12px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; color: #475569; }
.btn-cancel:hover { background: #e2e8f0; }
.btn-save-modal { padding: 10px 32px; background: linear-gradient(135deg, #3b82f6, #2563eb); border: none; border-radius: 12px; color: white; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(59,130,246,0.3); }
.btn-save-modal:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(59,130,246,0.4); }
.btn-save-modal:active { transform: scale(0.98); }
.form-actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid #e2e8f0; }

.list-item { background: #f8fafc; border-radius: 10px; padding: 8px 12px; margin-bottom: 6px; display: flex; align-items: center; gap: 8px; border: 1px solid #e2e8f0; transition: all 0.2s; }
.list-item:hover { background: #f1f5f9; border-color: #cbd5e1; }
.list-item input { flex: 1; background: white; padding: 6px 10px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px; }
.list-item input:focus { outline: none; border-color: #3b82f6; }
.list-item .remove { color: #ef4444; cursor: pointer; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border-radius: 6px; transition: all 0.2s; }
.list-item .remove:hover { background: #fee2e2; }
.sss-item { background: #f8fafc; border-radius: 12px; padding: 14px; margin-bottom: 10px; border: 1px solid #e2e8f0; }
.sss-item input, .sss-item textarea { margin-bottom: 8px; }
.sss-item input:last-child { margin-bottom: 0; }

.seo-lang-tabs { display: flex; gap: 4px; margin-bottom: 16px; background: #f1f5f9; border-radius: 12px; padding: 4px; }
.seo-lang-btn { padding: 8px 20px; border: none; background: transparent; font-weight: 600; font-size: 13px; color: #94a3b8; border-radius: 8px; cursor: pointer; transition: all 0.2s; }
.seo-lang-btn.active { background: white; color: #3b82f6; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
.seo-lang-btn:hover:not(.active) { color: #475569; }
.seo-preview { background: #f8fafc; padding: 16px 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-top: 12px; }
.seo-preview-title { color: #1a0dab; font-size: 18px; cursor: pointer; text-decoration: none; font-weight: 400; margin: 0; }
.seo-preview-title:hover { text-decoration: underline; }
.seo-preview-url { color: #006621; font-size: 14px; margin-top: 2px; word-break: break-all; }
.seo-preview-desc { color: #545454; font-size: 14px; margin-top: 4px; line-height: 1.4; }

.drop-zone { border: 2px dashed #d1d5db; border-radius: 12px; padding: 24px 20px; text-align: center; transition: all 0.3s ease; background: #fafbfc; min-height: 140px; display: flex; align-items: center; justify-content: center; position: relative; }
.drop-zone:hover, .drop-zone.dragover { border-color: #3b82f6; background: #eff6ff; }
.drop-zone-preview img { width: 100%; max-height: 160px; object-fit: cover; border-radius: 8px; }

.modal-tabs { display: flex; gap: 0; background: #f8fafc; border-bottom: 2px solid #e2e8f0; padding: 0 24px; flex-shrink: 0; }
.modal-tab { display: flex; align-items: center; gap: 8px; padding: 14px 22px; border: none; background: transparent; font-size: 13px; font-weight: 600; color: #94a3b8; cursor: pointer; border-bottom: 3px solid transparent; margin-bottom: -2px; transition: all 0.25s ease; white-space: nowrap; }
.modal-tab:hover { color: #475569; background: rgba(0,0,0,0.02); }
.modal-tab.active { color: #3b82f6; border-bottom-color: #3b82f6; }
.tab-pane { display: none; animation: tabFadeIn 0.3s ease; }
.tab-pane.active { display: block; }
@keyframes tabFadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
.tab-nav { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-top: 16px; padding-top: 16px; border-top: 1px solid #e2e8f0; }
.tab-nav-prev, .tab-nav-next { display: inline-flex; align-items: center; gap: 8px; padding: 8px 20px; border: 1.5px solid #e2e8f0; border-radius: 10px; background: white; font-size: 13px; font-weight: 600; color: #475569; cursor: pointer; transition: all 0.2s ease; }
.tab-nav-prev:hover, .tab-nav-next:hover { border-color: #3b82f6; color: #3b82f6; background: #eff6ff; }
.tab-nav-prev:only-child { margin-left: auto; }

@media (max-width: 768px) { .modal-tabs { padding: 0 12px; overflow-x: auto; flex-wrap: nowrap; } .modal-tab { padding: 12px 14px; font-size: 12px; white-space: nowrap; } .modal-tab span { display: none; } .modal-tab i { font-size: 16px; } .tab-nav { flex-direction: column; gap: 8px; } .tab-nav-prev, .tab-nav-next { width: 100%; justify-content: center; } }
@media (max-width: 992px) { .stats-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 768px) { .kurumsal-container { padding: 12px; } .kurumsal-header { padding: 16px 20px; flex-direction: column; gap: 12px; } .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; } .filter-bar { flex-direction: column; align-items: stretch; } .filter-input { min-width: unset; } .tedavi-grid { grid-template-columns: 1fr; } .kurumsal-tabs { overflow-x: auto; flex-wrap: nowrap; padding: 0 12px; } .kurumsal-tab { padding: 10px 14px; font-size: 12px; white-space: nowrap; } .grid-cols-2, .grid-cols-3 { grid-template-columns: 1fr; gap: 12px; } .modal-content { width: 96%; max-height: 96vh; border-radius: 20px; } .modal-body { padding: 16px; } .form-card-body { padding: 12px 14px; } .form-actions { flex-direction: column; } .form-actions button { width: 100%; justify-content: center; } }
@media (max-width: 480px) { .stats-grid { grid-template-columns: 1fr 1fr; gap: 8px; } .stat-card { padding: 12px 14px; } .stat-card .stat-value { font-size: 22px; } .stat-card .stat-icon { font-size: 30px; } .modal-header { padding: 14px 16px; flex-wrap: wrap; gap: 8px; } .modal-header h4 { font-size: 1rem; } .modal-body { padding: 12px; } }
.slider-vaka-item { transition: all 0.2s; }
.slider-vaka-item:hover { border-color: #6366f1; }
</style>

<div class="kurumsal-container">
    <div class="kurumsal-card">
        
        <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 rounded-2xl p-6 shadow-2xl border-l-8 border-green-600 relative overflow-hidden group">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-green-500/10 rounded-full blur-3xl group-hover:bg-green-500/20 transition-all duration-700"></div>
            <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-teal-500/10 rounded-full blur-3xl group-hover:bg-teal-500/20 transition-all duration-700"></div>
            
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-teal-600 rounded-2xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-tooth text-white text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl font-black text-white tracking-tight">Tedaviler Yönetimi</h1>
                            <p class="text-slate-400 text-sm font-medium mt-1 flex items-center gap-2">
                                <i class="fas fa-stethoscope text-green-500 text-xs"></i>
                                Tüm tedavi işlemlerinizi buradan yönetin
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Sistem Durumu</p>
                        <div class="flex items-center gap-2 justify-end mt-1">
                            <span class="text-xs text-slate-300 font-bold">AKTİF</span>
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                            </span>
                        </div>
                    </div>
                    <button onclick="location.reload()" class="h-10 w-10 bg-slate-800 border border-slate-700 text-slate-300 rounded-xl hover:bg-green-600 hover:text-white hover:border-green-500 transition-all duration-300 shadow-lg active:scale-90 flex items-center justify-center group/btn">
                        <i class="fas fa-sync-alt group-hover/btn:rotate-180 transition-transform duration-500"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <div class="kurumsal-tabs">
            <button class="kurumsal-tab <?php echo $activeTab == 'liste' ? 'active' : ''; ?>" data-tab="liste">📋 Tedavi Listesi</button>
            <button class="kurumsal-tab <?php echo $activeTab == 'silinenler' ? 'active' : ''; ?>" data-tab="silinenler">🗑️ Çöp Kutusu (<?php echo $silinen_sayisi; ?>)</button>
            <button class="kurumsal-tab <?php echo $activeTab == 'seo' ? 'active' : ''; ?>" data-tab="seo">🔍 SEO</button>
        </div>
        
        <div class="tab-pane" id="tab-liste" style="padding: 24px; display: <?php echo $activeTab == 'liste' ? 'block' : 'none'; ?>">
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 shadow-lg hover:shadow-xl transition-all hover:-translate-y-1 group">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-xs font-bold uppercase tracking-wider">Toplam Tedavi</p>
                            <p class="text-3xl font-black text-white mt-1"><?php echo $toplam; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i class="fas fa-list text-white text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-4 shadow-lg hover:shadow-xl transition-all hover:-translate-y-1 group">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100 text-xs font-bold uppercase tracking-wider">Aktif Tedaviler</p>
                            <p class="text-3xl font-black text-white mt-1"><?php echo $aktif; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i class="fas fa-check-circle text-white text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-4 shadow-lg hover:shadow-xl transition-all hover:-translate-y-1 group">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-red-100 text-xs font-bold uppercase tracking-wider">Pasif Tedaviler</p>
                            <p class="text-3xl font-black text-white mt-1"><?php echo $pasif; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i class="fas fa-ban text-white text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl p-4 shadow-lg hover:shadow-xl transition-all hover:-translate-y-1 group cursor-pointer" onclick="location.href='?modul=tedaviler&tab=silinenler'">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-amber-100 text-xs font-bold uppercase tracking-wider">Çöp Kutusu</p>
                            <p class="text-3xl font-black text-white mt-1"><?php echo $silinen_sayisi; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i class="fas fa-trash-alt text-white text-xl"></i>
                        </div>
                    </div>
                    <p class="text-[10px] text-amber-200 mt-2">Silinen tedaviler</p>
                </div>
                
                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 shadow-lg hover:shadow-xl transition-all hover:-translate-y-1 group">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100 text-xs font-bold uppercase tracking-wider">Kategori</p>
                            <p class="text-3xl font-black text-white mt-1"><?php echo count($kategoriler); ?></p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i class="fas fa-tags text-white text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="filter-bar">
                <input type="text" id="searchInput" class="filter-input" placeholder="🔍 Tedavi ara...">
                <select id="kategoriFilter" class="filter-select">
                    <option value="">Tüm Kategoriler</option>
                    <?php foreach ($kategoriler as $kat): ?>
                        <option value="<?php echo htmlspecialchars($kat); ?>"><?php echo htmlspecialchars($kat); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="durumFilter" class="filter-select">
                    <option value="">Tüm Durumlar</option>
                    <option value="1">Aktif</option>
                    <option value="0">Pasif</option>
                </select>
                <button onclick="resetFilters()" class="filter-btn"><i class="fas fa-times"></i> Temizle</button>
                <button onclick="openModal()" class="filter-btn" style="background: #3b82f6; color: white;"><i class="fas fa-plus"></i> Yeni Tedavi Ekle</button>
            </div>
            
            <div class="tedavi-grid" id="tedaviListesi">
                <?php foreach ($tedaviler as $t):
                    $gosterimResim = $sliderMap[$t['id']] ?? $t['sonrasi_resim'] ?: 'https://placehold.co/400x300/667eea/white?text=Tedavi';
                    
                    $baslik = $t['baslik'];
                    if (strpos($baslik, '{') === 0 || strpos($baslik, '[') === 0) {
                        $parsed = json_decode($baslik, true);
                        if (is_array($parsed)) {
                            $baslik = $parsed['tr'] ?? $parsed['en'] ?? $parsed['tr'] ?? $t['baslik'];
                        }
                    }
                    
                    $kisa = $t['kisa_aciklama'];
                    if (strpos($kisa, '{') === 0 || strpos($kisa, '[') === 0) {
                        $parsed = json_decode($kisa, true);
                        if (is_array($parsed)) {
                            $kisa = $parsed['tr'] ?? $parsed['en'] ?? $parsed['tr'] ?? $t['kisa_aciklama'];
                        }
                    }
                    
                    $kategori = $t['kategori'];
                    if (strpos($kategori, '{') === 0 || strpos($kategori, '[') === 0) {
                        $parsed = json_decode($kategori, true);
                        if (is_array($parsed)) {
                            $kategori = $parsed['tr'] ?? $parsed['en'] ?? $parsed['tr'] ?? $t['kategori'];
                        }
                    }
                ?>
                <div class="tedavi-card" data-id="<?php echo $t['id']; ?>" data-baslik="<?php echo htmlspecialchars($baslik); ?>" data-kategori="<?php echo htmlspecialchars($kategori); ?>" data-aktif="<?php echo $t['aktif']; ?>">
                    <div class="tedavi-img">
                        <img src="<?php echo htmlspecialchars($gosterimResim); ?>" alt="<?php echo htmlspecialchars($baslik); ?>">
                        <span class="tedavi-badge"><?php echo htmlspecialchars($kategori); ?></span>
                        <span class="tedavi-status <?php echo $t['aktif'] ? 'status-active' : 'status-passive'; ?>">
                            <i class="fas <?php echo $t['aktif'] ? 'fa-check-circle' : 'fa-ban'; ?>"></i> <?php echo $t['aktif'] ? 'AKTİF' : 'PASİF'; ?>
                        </span>
                    </div>
                    <div class="tedavi-content">
                        <div class="tedavi-category"><?php echo htmlspecialchars($kategori); ?></div>
                        <h4 class="tedavi-title"><?php echo htmlspecialchars($baslik); ?></h4>
                        <p class="tedavi-desc"><?php echo htmlspecialchars(mb_substr($kisa, 0, 100)); ?></p>
                        <div class="tedavi-actions">
                            <button onclick="editTedavi(<?php echo $t['id']; ?>)" class="btn-icon btn-edit" title="Düzenle"><i class="fas fa-edit"></i></button>
                            <button onclick="toggleDurum(<?php echo $t['id']; ?>)" class="btn-icon btn-toggle" style="flex:1;" title="<?php echo $t['aktif'] ? 'Pasif Yap' : 'Aktif Yap'; ?>">
                                <i class="fas <?php echo $t['aktif'] ? 'fa-eye-slash' : 'fa-eye'; ?>"></i> <?php echo $t['aktif'] ? 'Pasif Yap' : 'Aktif Yap'; ?>
                            </button>
                            <button onclick="silTedavi(<?php echo $t['id']; ?>)" class="btn-icon btn-delete" title="Sil"><i class="fas fa-trash-alt"></i></button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="tab-pane" id="tab-silinenler" style="padding: 24px; display: <?php echo $activeTab == 'silinenler' ? 'block' : 'none'; ?>">
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
                <?php foreach ($silinenler as $t):
                    $gosterimResim = $sliderMapSilinen[$t['id']] ?? $t['sonrasi_resim'] ?: 'https://placehold.co/400x300/667eea/white?text=Tedavi';
                    
                    $baslik = $t['baslik'];
                    if (strpos($baslik, '{') === 0 || strpos($baslik, '[') === 0) {
                        $parsed = json_decode($baslik, true);
                        if (is_array($parsed)) {
                            $baslik = $parsed['tr'] ?? $parsed['en'] ?? $parsed['tr'] ?? $t['baslik'];
                        }
                    }
                ?>
                <div class="tedavi-card" style="opacity: 0.7;">
                    <div style="height: 160px; overflow: hidden; background: #f0f4f8;">
                        <img src="<?php echo htmlspecialchars($gosterimResim); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <div style="padding: 16px;">
                        <h4 style="margin: 0 0 4px;"><?php echo htmlspecialchars($baslik); ?></h4>
                        <p style="font-size: 11px; color: #94a3b8;">Silinme: <?php echo date('d.m.Y H:i', strtotime($t['updated_at'])); ?></p>
                        <div style="display: flex; gap: 8px; margin-top: 12px;">
                            <button onclick="geriGetir(<?php echo $t['id']; ?>)" class="btn-sm btn-success" style="flex:1;"><i class="fas fa-undo-alt"></i> Geri Getir</button>
                            <?php if($_SESSION['admin_rol'] == 'superadmin'): ?>
                            <button onclick="kaliciSil(<?php echo $t['id']; ?>)" class="btn-sm btn-danger"><i class="fas fa-trash-alt"></i> Kalıcı Sil</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="tab-pane" id="tab-seo" style="padding: 24px; display: <?php echo $activeTab == 'seo' ? 'block' : 'none'; ?>">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h3 style="font-size: 1.2rem; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-search" style="color: #667eea;"></i> Tedaviler Sayfası SEO Ayarları
                </h3>
                <span style="background: #8b5cf6; color: white; padding: 4px 14px; border-radius: 30px; font-size: 0.7rem; font-weight: 500;">
                    <i class="fas fa-globe"></i> /tedaviler/
                </span>
            </div>
            
            <div style="background: white; border-radius: 24px; border: 1px solid #e2e8f0; overflow: hidden;">
                <div style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); padding: 16px 24px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-cog" style="color: white;"></i>
                        <span style="color: white; font-weight: 600;">SEO Meta Ayarları</span>
                        <span style="background: rgba(255,255,255,0.2); padding: 2px 10px; border-radius: 20px; font-size: 0.6rem; color: white;">Tedaviler Ana Sayfası</span>
                    </div>
                </div>
                
                <div style="padding: 24px;">
                    <form id="tedavilerSeoForm" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="tedaviler_seo_kaydet" value="1">
                        
                        <div style="display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">
                            <button type="button" class="tedaviler-seo-lang-btn active" data-lang="tr" style="padding: 8px 20px; border: none; background: none; font-weight: 600; color: #3b82f6; border-bottom: 2px solid #3b82f6; cursor: pointer;">🇹🇷 Türkçe</button>
                            <button type="button" class="tedaviler-seo-lang-btn" data-lang="en" style="padding: 8px 20px; border: none; background: none; font-weight: 600; color: #94a3b8; border-bottom: 2px solid transparent; cursor: pointer;">🇬🇧 English</button>
                        </div>
                        
                        <div class="tedaviler-seo-lang-content" data-lang="tr">
                            <div style="background: #f8fafc; border-radius: 16px; padding: 20px; margin-bottom: 20px;">
                                <div style="margin-bottom: 16px;">
                                    <label class="form-label">📌 SEO Başlık (Title) - TR</label>
                                    <input type="text" name="seo_title_tr" id="tedaviler_seo_title_tr" class="form-control" value="<?php echo htmlspecialchars($tedaviler_seo_title_tr); ?>">
                                </div>
                                <div style="margin-bottom: 16px;">
                                    <label class="form-label">📝 Meta Açıklama (Description) - TR</label>
                                    <textarea name="seo_description_tr" id="tedaviler_seo_description_tr" class="form-control" rows="4"><?php echo htmlspecialchars($tedaviler_seo_description_tr); ?></textarea>
                                </div>
                                <div>
                                    <label class="form-label">🏷️ Anahtar Kelimeler (Keywords) - TR</label>
                                    <input type="text" name="seo_keywords_tr" id="tedaviler_seo_keywords_tr" class="form-control" value="<?php echo htmlspecialchars($tedaviler_seo_keywords_tr); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="tedaviler-seo-lang-content" data-lang="en" style="display: none;">
                            <div style="background: #f8fafc; border-radius: 16px; padding: 20px; margin-bottom: 20px;">
                                <div style="margin-bottom: 16px;">
                                    <label class="form-label">📌 SEO Title - EN</label>
                                    <input type="text" name="seo_title_en" id="tedaviler_seo_title_en" class="form-control" value="<?php echo htmlspecialchars($tedaviler_seo_title_en); ?>">
                                </div>
                                <div style="margin-bottom: 16px;">
                                    <label class="form-label">📝 Meta Description - EN</label>
                                    <textarea name="seo_description_en" id="tedaviler_seo_description_en" class="form-control" rows="4"><?php echo htmlspecialchars($tedaviler_seo_description_en); ?></textarea>
                                </div>
                                <div>
                                    <label class="form-label">🏷️ Keywords - EN</label>
                                    <input type="text" name="seo_keywords_en" id="tedaviler_seo_keywords_en" class="form-control" value="<?php echo htmlspecialchars($tedaviler_seo_keywords_en); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div style="background: #f8fafc; border-radius: 16px; padding: 20px; margin-bottom: 20px;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div>
                                    <label class="form-label">🖼️ OG Görsel (Sosyal Medya)</label>
                                    <div style="display: flex; gap: 8px;">
                                        <input type="text" name="seo_og_image" id="tedaviler_seo_og_image" class="form-control" value="<?php echo htmlspecialchars($tedaviler_seo_og_image); ?>">
                                        <button type="button" class="btn-upload" onclick="tedavilerSeoResimYukle()"><i class="fas fa-upload"></i></button>
                                    </div>
                                    <div style="font-size: 10px; color: #94a3b8; margin-top: 4px;">1200x630 px önerilir</div>
                                </div>
                                <div>
                                    <label class="form-label">🔗 Canonical URL</label>
                                    <input type="text" name="seo_canonical" id="tedaviler_seo_canonical" class="form-control" value="<?php echo htmlspecialchars($tedaviler_seo_canonical); ?>">
                                    <div style="font-size: 10px; color: #94a3b8; margin-top: 4px;">Boş bırakırsanız otomatik oluşur</div>
                                </div>
                            </div>
                        </div>
                        
                        <div style="background: #f1f5f9; border-radius: 16px; padding: 20px; margin-bottom: 20px;">
                            <div style="font-size: 11px; font-weight: 600; color: #94a3b8; margin-bottom: 8px;"><i class="fas fa-eye"></i> Google Arama Sonucu Önizlemesi</div>
                            <div style="background: white; padding: 12px 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                                <div id="tedaviler_seo_preview_title" style="color: #1a0dab; font-size: 18px;"><?php echo htmlspecialchars($tedaviler_seo_title_tr); ?></div>
                                <div id="tedaviler_seo_preview_url" style="color: #006621; font-size: 14px; margin-top: 2px;"><?php echo htmlspecialchars($tedaviler_seo_canonical); ?></div>
                                <div id="tedaviler_seo_preview_desc" style="color: #545454; font-size: 14px; margin-top: 4px;"><?php echo htmlspecialchars($tedaviler_seo_description_tr); ?></div>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 12px; justify-content: flex-end;">
                            <button type="button" class="btn-cancel" onclick="resetTedavilerSeo()">Sıfırla</button>
                            <button type="submit" class="btn-save-modal"><i class="fas fa-save"></i> SEO Ayarlarını Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ========== MODAL ========== -->
<div id="tedaviModal" class="modal">
    <div class="modal-content">
        
        <div class="modal-header">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-tooth text-white text-xl"></i>
                </div>
                <div>
                    <h4 id="modalTitle" class="text-lg font-bold">Yeni Tedavi Ekle</h4>
                    <p class="text-xs text-white/70 mt-0.5">Tedavi bilgilerini eksiksiz doldurun</p>
                </div>
            </div>
            <button type="button" onclick="closeModal()" class="w-8 h-8 bg-white/10 hover:bg-white/20 rounded-xl transition-all text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-tabs">
            <button type="button" class="modal-tab active" data-tab="tab1">
                <i class="fas fa-info-circle"></i>
                <span>Temel & Görsel</span>
            </button>
            <button type="button" class="modal-tab" data-tab="tab2">
                <i class="fas fa-file-alt"></i>
                <span>İçerik & Detay</span>
            </button>
            <button type="button" class="modal-tab" data-tab="tab3">
                <i class="fas fa-search"></i>
                <span>SEO & Meta</span>
            </button>
            <button type="button" class="modal-tab" data-tab="tab4">
                <i class="fas fa-list-ol"></i>
                <span>Tedavi Adımları</span>
            </button>
            <button type="button" class="modal-tab" data-tab="tab5">
                <i class="fas fa-star"></i>
                <span>SSS & Avantaj</span>
            </button>
        </div>
        
        <div class="modal-body">
		
		<div id="deleteConfirmOverlay" style="display:none; position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; border-radius:28px; overflow:hidden; backdrop-filter:blur(2px);">
    <div style="background:white; padding:30px; border-radius:16px; max-width:400px; margin:20px; box-shadow:0 20px 60px rgba(0,0,0,0.3); width:90%;">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
            <div style="width:40px; height:40px; background:#fee2e2; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                <i class="fas fa-exclamation-triangle text-red-500 text-xl"></i>
            </div>
            <h3 style="margin:0; color:#1e293b; font-size:18px; font-weight:700;">Seti Sil</h3>
        </div>
        <p style="color:#475569; margin-bottom:20px; font-size:14px; line-height:1.6;">
            Bu seti <strong>kalıcı olarak</strong> silmek istediğinize emin misiniz?<br>
            <span style="color:#94a3b8; font-size:13px;">Bu işlem geri alınamaz.</span>
        </p>
        <div style="display:flex; gap:12px; justify-content:flex-end;">
            <button onclick="cancelDelete()" style="padding:10px 28px; background:#f1f5f9; border:none; border-radius:10px; cursor:pointer; font-weight:600; color:#475569; transition:all 0.2s;">
                İptal
            </button>
            <button onclick="confirmDelete()" style="padding:10px 28px; background:#ef4444; border:none; border-radius:10px; cursor:pointer; font-weight:600; color:white; transition:all 0.2s; box-shadow:0 4px 12px rgba(239,68,68,0.3);">
                <i class="fas fa-trash-alt"></i> Kalıcı Sil
            </button>
        </div>
    </div>
</div>
            <form id="tedaviForm">
                <input type="hidden" name="id" id="tedaviId">
                
                <div class="tab-pane active" id="tab1">
                    <div class="form-card">
                        <div class="form-card-header">
                            <i class="fas fa-info-circle text-blue-500"></i>
                            <span>Temel Bilgiler</span>
                        </div>
                        <div class="form-card-body">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="form-label">Başlık <span class="text-red-500">*</span></label>
                                    <input type="text" name="baslik" id="baslik" class="form-control" placeholder="Örn: İmplant Tedavisi">
                                </div>
                                <div>
                                    <label class="form-label">Slug (URL)</label>
                                    <input type="text" name="slug" id="slug" class="form-control" placeholder="Otomatik oluşur">
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-4 mt-4">
                                <div>
                                    <label class="form-label">Kategori <span class="text-red-500">*</span></label>
                                    <select name="kategori" id="kategori" class="form-control">
                                        <option value="">Seçiniz</option>
                                        <option value="Estetik Diş Hekimliği">✨ Estetik Diş Hekimliği</option>
                                        <option value="Cerrahi & İmplantoloji">🔬 Cerrahi & İmplantoloji</option>
                                        <option value="Protetik Diş Tedavisi">🦷 Protetik Diş Tedavisi</option>
                                        <option value="Ortodonti & Çene">📐 Ortodonti & Çene</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Sıra</label>
                                    <input type="number" name="sira" id="sira" class="form-control" value="0">
                                </div>
                                <div>
                                    <label class="form-label">Durum</label>
                                    <label class="flex items-center gap-2 mt-2 cursor-pointer">
                                        <input type="checkbox" name="aktif" id="aktif" value="1" checked>
                                        <span>Aktif</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-card">
                        <div class="form-card-header">
                            <i class="fas fa-image text-purple-500"></i>
                            <span>📸 Öncesi / Sonrası Görselleri ve Slider Setleri</span>
                            <span style="font-size:10px; color:#94a3b8; margin-left:auto;">Ana vaka ve çoklu setler</span>
                        </div>
                        <div class="form-card-body">
                            <div id="sliderVakalarContainer" class="space-y-4"></div>

                            <button type="button" class="btn-add mt-2 w-full justify-center py-3 border-2 border-dashed border-indigo-400 bg-indigo-50/50 text-indigo-700 hover:bg-indigo-100/50 font-semibold rounded-xl transition" onclick="yeniVakaSetiEkle()">
                                <i class="fas fa-plus-circle"></i> + Yeni Vaka Seti Ekle (Slider İçin Çoklu Resim)
                            </button>
                        </div>
                    </div>

                    <div style="display:none;">
                        <img id="onizlemeImg" src="">
                        <input type="hidden" name="image" id="image" value="">
                        <input type="text" id="resimUrlInput" value="">
                        <div id="resimOnizleme"></div>
                        <div id="resimYukleAlan"></div>
                        <div id="cropContainer"></div>
                        <div id="orijinalButon"></div>
                        <input type="file" id="resimInput">
                    </div>

                    <div class="tab-nav">
                        <button type="button" class="tab-nav-next" onclick="switchTab('tab2')">
                            İçerik & Detay <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
                
                <div class="tab-pane" id="tab2">
                    <div class="form-card">
                        <div class="form-card-header">
                            <i class="fas fa-file-alt text-green-500"></i>
                            <span>İçerik</span>
                        </div>
                        <div class="form-card-body">
                            <div>
                                <label class="form-label">Kısa Açıklama <span class="text-red-500">*</span></label>
                                <textarea name="kisa_aciklama" id="kisa_aciklama" class="form-control" rows="2" placeholder="Listelerde gözükecek kısa açıklama..."></textarea>
                            </div>
                            <div class="mt-4">
                                <label class="form-label">Detaylı Açıklama (HTML)</label>
                                <textarea name="detayli_aciklama" id="detayli_aciklama" class="form-control" rows="4" placeholder="HTML etiketleri kullanabilirsiniz"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-card">
                        <div class="form-card-header">
                            <i class="fas fa-chart-line text-amber-500"></i>
                            <span>Tedavi Bilgileri</span>
                        </div>
                        <div class="form-card-body">
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="form-label">Tedavi Süresi</label>
                                    <input type="text" name="sure" id="sure" class="form-control" placeholder="3-4 seans">
                                </div>

                            </div>
                        </div>
                    </div>
                    
                    <div class="tab-nav">
                        <button type="button" class="tab-nav-prev" onclick="switchTab('tab1')">
                            <i class="fas fa-arrow-left"></i> Temel & Görsel
                        </button>
                        <button type="button" class="tab-nav-next" onclick="switchTab('tab3')">
                            SEO & Meta <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
                
                <div class="tab-pane" id="tab3">
                    <div class="form-card">
                        <div class="form-card-header">
                            <i class="fas fa-search text-indigo-500"></i>
                            <span>🔍 SEO & Meta Bilgileri</span>
                            <span style="font-size:10px; color:#94a3b8; margin-left:auto;">Google'da nasıl görüneceğini belirler</span>
                        </div>
                        <div class="form-card-body">
                            <div class="seo-lang-tabs">
                                <button type="button" class="seo-lang-btn active" data-lang="tr">🇹🇷 Türkçe</button>
                                <button type="button" class="seo-lang-btn" data-lang="en">🇬🇧 English</button>
                            </div>
                            
                            <div class="seo-lang-content" data-lang="tr">
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                                    <div>
                                        <label class="form-label">📌 SEO Başlık (Title) - TR</label>
                                        <input type="text" name="seo_title_tr" id="seo_title_tr" class="form-control" 
                                               placeholder="Samsun İmplant Tedavisi | Prof. Dr. İbrahim Duran"
                                               oninput="updateCharCount(this, 'seo_title_tr_count')">
                                        <div style="font-size:11px; color:#94a3b8; text-align:right; margin-top:4px;">
                                            <span id="seo_title_tr_count">0</span>/70 karakter
                                        </div>
                                    </div>
                                    <div>
                                        <label class="form-label">🖼️ OG Görsel (Sosyal Medya)</label>
                                        <div style="display:flex; gap:8px;">
                                            <input type="text" name="seo_og_image" id="seo_og_image" class="form-control flex-1" 
                                                   placeholder="/uploads/tedaviler/implant-detay.webp">
                                            <button type="button" class="btn-upload" onclick="uploadImage('seo_og_image')">
                                                <i class="fas fa-upload"></i>
                                            </button>
                                        </div>
                                        <div style="font-size:10px; color:#94a3b8; margin-top:4px;">
                                            <i class="fas fa-info-circle"></i> 1200x630 px önerilir
                                        </div>
                                    </div>
                                </div>
                                <div style="margin-top:12px;">
                                    <label class="form-label">📝 Meta Açıklama (Description) - TR</label>
                                    <textarea name="seo_description_tr" id="seo_description_tr" class="form-control" rows="2"
                                              placeholder="Samsun Atakum'da Prof. Dr. İbrahim Duran ile güvenli implant tedavisi."
                                              oninput="updateCharCount(this, 'seo_description_tr_count')"></textarea>
                                    <div style="font-size:11px; color:#94a3b8; text-align:right; margin-top:4px;">
                                        <span id="seo_description_tr_count">0</span>/160 karakter
                                    </div>
                                </div>
                                <div style="margin-top:12px;">
                                    <label class="form-label">🏷️ Anahtar Kelimeler (Keywords) - TR</label>
                                    <input type="text" name="seo_keywords_tr" id="seo_keywords_tr" class="form-control" 
                                           placeholder="Samsun implant, Atakum implant tedavisi, diş implantı">
                                </div>
                            </div>
                            
                            <div class="seo-lang-content" data-lang="en" style="display:none;">
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                                    <div>
                                        <label class="form-label">📌 SEO Title - EN</label>
                                        <input type="text" name="seo_title_en" id="seo_title_en" class="form-control" 
                                               placeholder="Samsun Implant Treatment | Prof. Dr. İbrahim Duran"
                                               oninput="updateCharCount(this, 'seo_title_en_count')">
                                        <div style="font-size:11px; color:#94a3b8; text-align:right; margin-top:4px;">
                                            <span id="seo_title_en_count">0</span>/70 characters
                                        </div>
                                    </div>
                                    <div>
                                        <label class="form-label">🖼️ OG Image</label>
                                        <input type="text" name="seo_og_image_en" id="seo_og_image_en" class="form-control" 
                                               placeholder="/uploads/tedaviler/implant-detay.webp" readonly>
                                        <div style="font-size:10px; color:#94a3b8; margin-top:4px;">
                                            <i class="fas fa-info-circle"></i> Same as Turkish version
                                        </div>
                                    </div>
                                </div>
                                <div style="margin-top:12px;">
                                    <label class="form-label">📝 Meta Description - EN</label>
                                    <textarea name="seo_description_en" id="seo_description_en" class="form-control" rows="2"
                                              placeholder="Safe implant treatment with Prof. Dr. İbrahim Duran in Samsun."
                                              oninput="updateCharCount(this, 'seo_description_en_count')"></textarea>
                                    <div style="font-size:11px; color:#94a3b8; text-align:right; margin-top:4px;">
                                        <span id="seo_description_en_count">0</span>/160 characters
                                    </div>
                                </div>
                                <div style="margin-top:12px;">
                                    <label class="form-label">🏷️ Keywords - EN</label>
                                    <input type="text" name="seo_keywords_en" id="seo_keywords_en" class="form-control" 
                                           placeholder="Samsun implant, dental implant, implant treatment">
                                </div>
                            </div>
                            
                            <div style="margin-top:16px; padding-top:16px; border-top:1px solid #e2e8f0;">
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                                    <div>
                                        <label class="form-label">🔗 Canonical URL</label>
                                        <input type="text" name="seo_canonical" id="seo_canonical" class="form-control" 
                                               placeholder="https://www.dribrahimdurandentalclinic.com/tedavi-detay.php?slug=implant">
                                        <div style="font-size:10px; color:#94a3b8; margin-top:4px;">
                                            <i class="fas fa-info-circle"></i> Boş bırakırsanız otomatik oluşur.
                                        </div>
                                    </div>
                                    <div>
                                        <label class="form-label">🤖 Robots Meta</label>
                                        <select name="seo_robots" id="seo_robots" class="form-control">
                                            <option value="index, follow">index, follow (Varsayılan)</option>
                                            <option value="noindex, follow">noindex, follow</option>
                                            <option value="index, nofollow">index, nofollow</option>
                                            <option value="noindex, nofollow">noindex, nofollow</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="seo-preview">
                                <div id="seo_preview_title" class="seo-preview-title">Başlık Giriniz</div>
                                <div id="seo_preview_url" class="seo-preview-url">www.dribrahimdurandentalclinic.com/tedaviler/</div>
                                <div id="seo_preview_desc" class="seo-preview-desc">Açıklama Giriniz</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="tab-nav">
                        <button type="button" class="tab-nav-prev" onclick="switchTab('tab2')">
                            <i class="fas fa-arrow-left"></i> İçerik & Detay
                        </button>
                        <button type="button" class="tab-nav-next" onclick="switchTab('tab4')">
                            Tedavi Adımları <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
                
                <div class="tab-pane" id="tab4">
                    <div class="form-card">
                        <div class="form-card-header">
                            <i class="fas fa-list-ol text-indigo-500"></i>
                            <span>📋 Tedavi Adımları</span>
                            <span style="font-size:10px; color:#94a3b8; margin-left:auto;">İstediğiniz kadar adım ekleyin</span>
                        </div>
                        <div class="form-card-body">
                            <div id="adimlar_listesi" class="space-y-3"></div>
                            <button type="button" class="btn-add" onclick="addAdimItem()">
                                <i class="fas fa-plus-circle"></i> Adım Ekle
                            </button>
                        </div>
                    </div>
                    
                    <div class="tab-nav">
                        <button type="button" class="tab-nav-prev" onclick="switchTab('tab3')">
                            <i class="fas fa-arrow-left"></i> SEO & Meta
                        </button>
                        <button type="button" class="tab-nav-next" onclick="switchTab('tab5')">
                            SSS & Avantaj <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
                
                <div class="tab-pane" id="tab5">
                    <div class="form-card">
                        <div class="form-card-header">
                            <i class="fas fa-star text-yellow-500"></i>
                            <span>Avantajlar</span>
                        </div>
                        <div class="form-card-body">
                            <div id="avantajlar_listesi" class="space-y-2"></div>
                            <button type="button" class="btn-add" onclick="addListItem('avantajlar_listesi', 'avantajlar')">
                                <i class="fas fa-plus-circle"></i> Avantaj Ekle
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-card">
                        <div class="form-card-header">
                            <i class="fas fa-question-circle text-red-500"></i>
                            <span>Sık Sorulan Sorular (SSS)</span>
                        </div>
                        <div class="form-card-body">
                            <div id="sss_listesi" class="space-y-3"></div>
                            <button type="button" class="btn-add" onclick="addSSSItem()">
                                <i class="fas fa-plus-circle"></i> Soru Ekle
                            </button>
                        </div>
                    </div>
                    
                    <div class="tab-nav">
                        <button type="button" class="tab-nav-prev" onclick="switchTab('tab4')">
                            <i class="fas fa-arrow-left"></i> Tedavi Adımları
                        </button>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">
                        <i class="fas fa-times"></i> İptal
                    </button>
                    <button type="button" class="btn-save-modal" onclick="saveTedavi()">
                        <i class="fas fa-save"></i> Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ========== YENİ VAKA SETİ (İŞLEM BİLGİLERİ + ALT BAŞLIK DAHİL) ==========
function yeniVakaSetiEkle(beforeVal = '', afterVal = '', siraVal = 0, islemVal = '', islemTarihiVal = '', goruntulemeTarihiVal = '', altBaslikTrVal = '') {
    const container = document.getElementById('sliderVakalarContainer');
    const index = container.children.length + 1;
    const uid = 'set_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
    
    const div = document.createElement('div');
    div.className = 'slider-vaka-item border-2 border-dashed border-indigo-300 rounded-2xl p-4 bg-slate-50 mb-6 shadow-sm relative';
    
    div.innerHTML = `
        <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-200">
            <span class="font-bold text-sm text-indigo-950 flex items-center gap-2">
                <i class="fas fa-layer-group text-indigo-600"></i> Vaka Seti #${index} (Slider)
            </span>
            <div class="flex items-center gap-2">
                <label class="text-xs text-gray-500 font-medium">Sıra:</label>
                <input type="number" name="slider_sira[]" value="${siraVal || index}" class="form-control text-xs" style="width: 60px; padding: 4px 8px;">
                <button type="button" onclick="showDeleteConfirmation(this)" class="bg-red-500 text-white px-3 py-1 rounded-lg text-xs hover:bg-red-600 transition flex items-center gap-1 font-medium">
                    <i class="fas fa-trash-alt"></i> Seti Sil
                </button>
            </div>
        </div>

        <!-- 🔥 İŞLEM BİLGİLERİ (YASAL UYUM) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4 p-3 bg-amber-50 border border-amber-200 rounded-xl">
            <div>
                <label class="text-xs font-bold text-amber-800 mb-1 block">
                    <i class="fas fa-tooth"></i> İşlem Adı
                </label>
                <input type="text" name="slider_islem[]" class="form-control text-xs" 
                       style="padding:6px 10px;" 
                       placeholder="Örn: Dikişsiz İmplant" 
                       value="${islemVal}">
            </div>
            <div>
                <label class="text-xs font-bold text-amber-800 mb-1 block">
                    <i class="fas fa-calendar-check"></i> İşlem Tarihi
                </label>
                <input type="date" name="slider_islem_tarihi[]" class="form-control text-xs" 
                       style="padding:6px 10px;" 
                       value="${islemTarihiVal}">
            </div>
            <div>
                <label class="text-xs font-bold text-amber-800 mb-1 block">
                    <i class="fas fa-eye"></i> Görüntüleme Tarihi
                </label>
                <input type="date" name="slider_goruntuleme_tarihi[]" class="form-control text-xs" 
                       style="padding:6px 10px;" 
                       value="${goruntulemeTarihiVal}">
            </div>
        </div>

        <!-- 🔥 ALT BAŞLIK (SLIDER İLE DÖNEN BAŞLIK) -->
        <div class="mb-4 p-3 bg-purple-50 border border-purple-200 rounded-xl">
            <label class="text-xs font-bold text-purple-800 mb-1 block">
                ✦ Alt Başlık <span class="text-purple-500 font-normal">(İngilizce otomatik çevrilir)</span>
            </label>
            <input type="text" name="slider_alt_baslik_tr[]" class="form-control text-xs" 
                   style="padding:6px 10px;" 
                   placeholder="Örn: Lityum Disilikat Seramikler ile Minimal İnvaziv Gülüş Dönüşümü" 
                   value="${altBaslikTrVal}">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="border border-gray-200 rounded-xl p-3 bg-gray-50">
                <div class="flex items-center justify-between mb-2">
                    <label class="text-sm font-bold text-red-600">🔴 ÖNCESİ</label>
                    <span class="text-[10px] text-gray-400">Slider Öncesi</span>
                </div>
                
                <div class="border-2 border-dashed border-gray-300 rounded-xl p-3 text-center bg-white relative">
                    <div id="${uid}_oncesiOnizleme" class="relative w-full ${beforeVal ? '' : 'hidden'}">
                        <img id="${uid}_oncesiImg" src="${beforeVal || ''}" class="w-full rounded-lg shadow border border-gray-200 max-h-[180px] object-contain bg-white mx-auto cursor-pointer">
                    </div>
                    
                    <div id="${uid}_oncesiYukleAlan" class="w-full ${beforeVal ? 'hidden' : ''}">
                        <svg class="w-10 h-10 text-gray-400 mx-auto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="2" width="20" height="20" rx="2"/><circle cx="8.5" cy="8.5" r="2.5"/><path d="M21 15l-5-5-6 6-3-3-4 4"/>
                        </svg>
                        <p class="text-sm text-gray-600 mt-1">Sürükle veya <span class="text-red-600 font-semibold cursor-pointer" onclick="document.getElementById('${uid}_oncesiInput').click()">tıkla</span></p>
                        <p class="text-xs text-gray-400">PNG, JPG, WEBP (Max 20MB)</p>
                    </div>
                    <input type="file" id="${uid}_oncesiInput" accept="image/*" class="hidden" onchange="klonResimYukle(this.files[0], '${uid}', 'oncesi')">
                    
                    <div class="w-full mt-2">
                        <div class="flex gap-1 justify-center flex-wrap">
                            <button type="button" onclick="klonResimKaldir('${uid}', 'oncesi')" class="px-3 py-1.5 bg-red-500 text-white rounded-lg text-xs hover:bg-red-600 transition font-medium">❌ Kaldır</button>
                            <button type="button" onclick="document.getElementById('${uid}_oncesiInput').click()" class="px-3 py-1.5 bg-gray-500 text-white rounded-lg text-xs hover:bg-gray-600 transition font-medium">📤 Değiştir</button>
                            <button type="button" onclick="klonResimDondur('${uid}', 'oncesi', 90)" class="px-3 py-1.5 bg-orange-500 text-white rounded-lg text-xs hover:bg-orange-600 transition font-medium">↺ -90°</button>
                            <button type="button" onclick="klonResimDondur('${uid}', 'oncesi', -90)" class="px-3 py-1.5 bg-orange-500 text-white rounded-lg text-xs hover:bg-orange-600 transition font-medium">↻ +90°</button>
                        </div>
                        
                        <div class="mt-2 p-2 bg-blue-50 rounded-lg border border-blue-200">
                            <div class="flex gap-2">
                                <input type="text" id="${uid}_oncesiUrlInput" class="form-control text-xs flex-1" placeholder="https://..." style="padding:4px 8px;" value="${beforeVal || ''}">
                                <button type="button" onclick="klonResimUrl('${uid}', 'oncesi')" class="px-3 py-1 bg-blue-600 text-white rounded-lg text-xs hover:bg-blue-700 transition whitespace-nowrap">
                                    <i class="fas fa-download"></i> Ekle
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="slider_before[]" id="${uid}_oncesi_resim" value="${beforeVal}">
            </div>
            
            <div class="border border-gray-200 rounded-xl p-3 bg-gray-50">
                <div class="flex items-center justify-between mb-2">
                    <label class="text-sm font-bold text-green-600">🟢 SONRASI</label>
                    <span class="text-[10px] text-gray-400">Slider Sonrası</span>
                </div>
                
                <div class="border-2 border-dashed border-gray-300 rounded-xl p-3 text-center bg-white relative">
                    <div id="${uid}_sonrasiOnizleme" class="relative w-full ${afterVal ? '' : 'hidden'}">
                        <img id="${uid}_sonrasiImg" src="${afterVal || ''}" class="w-full rounded-lg shadow border border-gray-200 max-h-[180px] object-contain bg-white cursor-pointer">
                    </div>
                    
                    <div id="${uid}_sonrasiYukleAlan" class="w-full ${afterVal ? 'hidden' : ''}">
                        <svg class="w-10 h-10 text-gray-400 mx-auto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="2" width="20" height="20" rx="2"/><circle cx="8.5" cy="8.5" r="2.5"/><path d="M21 15l-5-5-6 6-3-3-4 4"/>
                        </svg>
                        <p class="text-sm text-gray-600 mt-1">Sürükle veya <span class="text-green-600 font-semibold cursor-pointer" onclick="document.getElementById('${uid}_sonrasiInput').click()">tıkla</span></p>
                        <p class="text-xs text-gray-400">PNG, JPG, WEBP (Max 20MB)</p>
                    </div>
                    <input type="file" id="${uid}_sonrasiInput" accept="image/*" class="hidden" onchange="klonResimYukle(this.files[0], '${uid}', 'sonrasi')">
                    
                    <div class="w-full mt-2">
                        <div class="flex gap-1 justify-center flex-wrap">
                            <button type="button" onclick="klonResimKaldir('${uid}', 'sonrasi')" class="px-3 py-1.5 bg-red-500 text-white rounded-lg text-xs hover:bg-red-600 transition font-medium">❌ Kaldır</button>
                            <button type="button" onclick="document.getElementById('${uid}_sonrasiInput').click()" class="px-3 py-1.5 bg-gray-500 text-white rounded-lg text-xs hover:bg-gray-600 transition font-medium">📤 Değiştir</button>
                            <button type="button" onclick="klonResimDondur('${uid}', 'sonrasi', 90)" class="px-3 py-1.5 bg-orange-500 text-white rounded-lg text-xs hover:bg-orange-600 transition font-medium">↺ -90°</button>
                            <button type="button" onclick="klonResimDondur('${uid}', 'sonrasi', -90)" class="px-3 py-1.5 bg-orange-500 text-white rounded-lg text-xs hover:bg-orange-600 transition font-medium">↻ +90°</button>
                        </div>
                        
                        <div class="mt-2 p-2 bg-blue-50 rounded-lg border border-blue-200">
                            <div class="flex gap-2">
                                <input type="text" id="${uid}_sonrasiUrlInput" class="form-control text-xs flex-1" placeholder="https://..." style="padding:4px 8px;" value="${afterVal || ''}">
                                <button type="button" onclick="klonResimUrl('${uid}', 'sonrasi')" class="px-3 py-1 bg-blue-600 text-white rounded-lg text-xs hover:bg-blue-700 transition whitespace-nowrap">
                                    <i class="fas fa-download"></i> Ekle
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="slider_after[]" id="${uid}_sonrasi_resim" value="${afterVal}">
            </div>
        </div>
    `;
    container.appendChild(div);
}
let deleteTarget = null;

function showDeleteConfirmation(btn) {
    deleteTarget = btn.closest('.slider-vaka-item');
    if (deleteTarget) {
        document.getElementById('deleteConfirmOverlay').style.display = 'flex';
    }
}

function confirmDelete() {
    if (deleteTarget) {
        deleteTarget.remove();
        setSetNumaralariniGuncelle();
        cancelDelete();
        showToast('🗑️ Set kalıcı olarak silindi!', 'warning');
    }
}

function cancelDelete() {
    document.getElementById('deleteConfirmOverlay').style.display = 'none';
    deleteTarget = null;
}

// Klonlanan setlerin fonksiyonları
function klonResimYukle(file, uid, tip) {
    if (!file) return;
    if (file.size > 20 * 1024 * 1024) {
        showToast('❌ Dosya çok büyük! Max 20MB.', 'error');
        return;
    }
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById(uid + '_' + tip + 'Img').src = e.target.result;
        document.getElementById(uid + '_' + tip + 'Onizleme').classList.remove('hidden');
        document.getElementById(uid + '_' + tip + 'YukleAlan').classList.add('hidden');
    };
    reader.readAsDataURL(file);
    
    const fd = new FormData();
    fd.append('resim', file);
    fd.append('csrf_token', csrfToken);
    fetch(ajaxUrl + '?islem=resim_yukle', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            document.getElementById(uid + '_' + tip + '_resim').value = d.url;
            document.getElementById(uid + '_' + tip + 'Img').src = d.url + '?v=' + Date.now();
            document.getElementById(uid + '_' + tip + 'UrlInput').value = d.url;
            showToast('✅ Görsel yüklendi!', 'success');
        } else {
            showToast('❌ Hata: ' + d.message, 'error');
            klonResimKaldir(uid, tip);
        }
    })
    .catch(() => {
        showToast('❌ Bağlantı hatası!', 'error');
        klonResimKaldir(uid, tip);
    });
}

function klonResimKaldir(uid, tip) {
    document.getElementById(uid + '_' + tip + '_resim').value = '';
    document.getElementById(uid + '_' + tip + 'Img').src = '';
    document.getElementById(uid + '_' + tip + 'Onizleme').classList.add('hidden');
    document.getElementById(uid + '_' + tip + 'YukleAlan').classList.remove('hidden');
    document.getElementById(uid + '_' + tip + 'UrlInput').value = '';
    showToast('🗑️ Kaldırıldı', 'warning');
}

function klonResimUrl(uid, tip) {
    var url = document.getElementById(uid + '_' + tip + 'UrlInput').value.trim();
    if (!url) { showToast('⚠️ URL girin!', 'warning'); return; }
    try { new URL(url); } catch(e) { showToast('❌ Geçersiz URL!', 'error'); return; }
    showToast('⏳ İndiriliyor...', 'info');
    fetch(ajaxUrl + '?islem=url_ile_resim', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ url: url, csrf_token: csrfToken })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            document.getElementById(uid + '_' + tip + '_resim').value = d.url;
            document.getElementById(uid + '_' + tip + 'Img').src = d.url + '?v=' + Date.now();
            document.getElementById(uid + '_' + tip + 'Onizleme').classList.remove('hidden');
            document.getElementById(uid + '_' + tip + 'YukleAlan').classList.add('hidden');
            document.getElementById(uid + '_' + tip + 'UrlInput').value = d.url;
            showToast('✅ İndirildi!', 'success');
        } else {
            showToast('❌ Hata: ' + d.message, 'error');
        }
    })
    .catch(() => showToast('❌ Bağlantı hatası!', 'error'));
}

function klonResimDondur(uid, tip, yon) {
    var url = document.getElementById(uid + '_' + tip + '_resim').value;
    if (!url) { showToast('⚠️ Önce resim yükleyin!', 'warning'); return; }
    fetch(ajaxUrl + '?islem=resim_dondur', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ url: url, yon: yon, csrf_token: csrfToken })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            document.getElementById(uid + '_' + tip + '_resim').value = d.url;
            document.getElementById(uid + '_' + tip + 'Img').src = d.url + '?v=' + Date.now();
            document.getElementById(uid + '_' + tip + 'UrlInput').value = d.url;
            showToast('✅ Döndürüldü!', 'success');
        } else {
            showToast('❌ Hata: ' + d.message, 'error');
        }
    })
    .catch(() => showToast('❌ Bağlantı hatası!', 'error'));
}

function setSetNumaralariniGuncelle() {
    const items = document.querySelectorAll('.slider-vaka-item');
    items.forEach((item, idx) => {
        const titleSpan = item.querySelector('.text-indigo-950');
        if (titleSpan) {
            titleSpan.innerHTML = `<i class="fas fa-layer-group text-indigo-600"></i> Vaka Seti #${idx + 1} (Slider)`;
        }
        const siraInput = item.querySelector('input[name="slider_sira[]"]');
        if (siraInput) siraInput.value = idx + 1;
    });
}

// ===== TAB GEÇİŞİ =====
function switchTab(tabId) {
    document.querySelectorAll('.tab-pane').forEach(function(pane) {
        pane.classList.remove('active');
    });
    var target = document.getElementById(tabId);
    if (target) target.classList.add('active');
    document.querySelectorAll('.modal-tab').forEach(function(btn) {
        btn.classList.remove('active');
        if (btn.getAttribute('data-tab') === tabId) {
            btn.classList.add('active');
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.modal-tab').forEach(function(btn) {
        btn.addEventListener('click', function() {
            switchTab(this.getAttribute('data-tab'));
        });
    });
});

const csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';
const ajaxUrl = '/admin/modules/tedaviler/index.php';

let orijinalResim = '';
let guncelResim = '';
let currentFile = null;
let cropper = null;
let cropperModal = null;
let currentTargetId = 'image';

let orijinalOncesiResim = '';
let guncelOncesiResim = '';
let orijinalSonrasiResim = '';
let guncelSonrasiResim = '';

function openModal() {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle"></i> Yeni Tedavi Ekle';
    document.getElementById('tedaviForm').reset();
    document.getElementById('tedaviId').value = '';
    document.getElementById('avantajlar_listesi').innerHTML = '';
    document.getElementById('sss_listesi').innerHTML = '';
    document.getElementById('adimlar_listesi').innerHTML = '';
    document.getElementById('aktif').checked = true;
    document.getElementById('seo_preview_title').textContent = 'Başlık Giriniz';
    document.getElementById('seo_preview_desc').textContent = 'Açıklama Giriniz';
    document.getElementById('seo_preview_url').textContent = 'www.dribrahimdurandentalclinic.com/tedavi-detay.php?slug=';

    orijinalResim = '';
    guncelResim = '';
    document.getElementById('onizlemeImg').src = '';
    document.getElementById('image').value = '';
    document.getElementById('resimUrlInput').value = '';
    document.getElementById('resimOnizleme').classList.add('hidden');
    document.getElementById('resimYukleAlan').classList.remove('hidden');
    document.getElementById('cropContainer').classList.add('hidden');
    document.getElementById('orijinalButon').classList.add('hidden');
    document.getElementById('resimInput').value = '';

    document.getElementById('seo_og_image').value = '';
    
    document.getElementById('sliderVakalarContainer').innerHTML = '';
    yeniVakaSetiEkle('', '', 1);

    document.getElementById('tedaviModal').style.display = 'flex';
}
function closeModal() {
    document.getElementById('tedaviModal').style.display = 'none';
    if (cropper) { cropper.destroy(); cropper = null; }
    if (cropperModal) { cropperModal.remove(); cropperModal = null; }
}

function uploadImage(targetId) {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (!file) return;
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;
        const formData = new FormData();
        formData.append('resim', file);
        formData.append('csrf_token', csrfToken);
        fetch(ajaxUrl + '?islem=resim_yukle', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById(targetId).value = data.url;
                if (targetId === 'image') {
                    document.getElementById('seo_og_image').value = data.url;
                    guncelResim = data.url;
                    document.getElementById('onizlemeImg').src = data.url + '?v=' + Date.now();
                    document.getElementById('resimOnizleme').classList.remove('hidden');
                    document.getElementById('resimYukleAlan').classList.add('hidden');
                    document.getElementById('cropContainer').classList.remove('hidden');
                    document.getElementById('resimUrlInput').value = data.url;
                    if (!orijinalResim) {
                        orijinalResim = data.url;
                    }
                    document.getElementById('orijinalButon').classList.remove('hidden');
                }
                showToast('✅ Resim yüklendi!', 'success');
            } else {
                alert('❌ Hata: ' + data.message);
            }
        })
        .catch(err => alert('Bağlantı hatası: ' + err.message))
        .finally(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    };
    input.click();
}

function addListItem(containerId, fieldName) {
    const container = document.getElementById(containerId);
    const div = document.createElement('div');
    div.className = 'list-item';
    div.innerHTML = `
        <input type="text" name="${fieldName}[]" class="form-control" style="flex:1;" placeholder="Örn: Uzun ömürlü">
        <span onclick="this.parentElement.remove()" class="remove"><i class="fas fa-times"></i></span>
    `;
    container.appendChild(div);
}

function addSSSItem() {
    const container = document.getElementById('sss_listesi');
    const div = document.createElement('div');
    div.className = 'sss-item';
    div.innerHTML = `
        <div style="margin-bottom:8px;">
            <input type="text" name="sss_soru[]" class="form-control" placeholder="Soru">
        </div>
        <div style="margin-bottom:8px;">
            <textarea name="sss_cevap[]" class="form-control" rows="2" placeholder="Cevap"></textarea>
        </div>
        <div style="text-align:right;">
            <span onclick="this.parentElement.parentElement.remove()" style="color:#ef4444; cursor:pointer; font-size:12px;">&times; Sil</span>
        </div>
    `;
    container.appendChild(div);
}

function addAdimItem() {
    const container = document.getElementById('adimlar_listesi');
    const index = container.children.length + 1;
    const div = document.createElement('div');
    div.className = 'adim-item';
    div.style.cssText = 'background: #f8fafc; border-radius: 12px; padding: 14px; margin-bottom: 10px; border: 1px solid #e2e8f0;';
    div.innerHTML = `
        <div style="display:flex; gap:8px; align-items:center; margin-bottom:8px;">
            <span style="font-weight:700; color:#6366f1; min-width:30px; font-size:14px;">#${index}</span>
            <input type="text" name="adim_baslik[]" class="form-control" style="flex:2; padding:6px 10px; border:1px solid #e2e8f0; border-radius:6px;" placeholder="Adım başlığı (Örn: Muayene ve Planlama)">
        </div>
        <div style="display:flex; gap:8px; align-items:start;">
            <textarea name="adim_aciklama[]" class="form-control" rows="2" style="flex:1; padding:6px 10px; border:1px solid #e2e8f0; border-radius:6px;" placeholder="Adım açıklaması..."></textarea>
            <span onclick="this.parentElement.parentElement.remove()" style="color:#ef4444; cursor:pointer; font-size:18px; padding:4px 8px; border-radius:6px; transition:all 0.2s; margin-top:4px;">✖</span>
        </div>
        <input type="hidden" name="adim_no[]" value="${index}">
    `;
    container.appendChild(div);
}

function editTedavi(id) {
    fetch(ajaxUrl + '?islem=get&id=' + id)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const t = data.data;
                
                let baslik = t.baslik || '';
                try {
                    if (baslik.startsWith('{') || baslik.startsWith('[')) {
                        const parsed = JSON.parse(baslik);
                        baslik = parsed.tr || parsed.en || parsed['tr'] || baslik;
                    }
                } catch(e) {}
                document.getElementById('baslik').value = baslik;
                
                let kisa = t.kisa_aciklama || '';
                try {
                    if (kisa.startsWith('{') || kisa.startsWith('[')) {
                        const parsed = JSON.parse(kisa);
                        kisa = parsed.tr || parsed.en || parsed['tr'] || kisa;
                    }
                } catch(e) {}
                document.getElementById('kisa_aciklama').value = kisa;
                
                // ===== SLIDER VAKA SETLERİNİ DOLDUR (İŞLEM + ALT BAŞLIK DAHİL) =====
                const sliderContainer = document.getElementById('sliderVakalarContainer');
                sliderContainer.innerHTML = '';
                const sliders = t.sliders || [];
                if (Array.isArray(sliders) && sliders.length > 0) {
sliders.forEach(sv => {
    // 🔥 Alt başlık JSON parse (sadece TR)
    let altTr = '';
    try {
        const parsed = typeof sv.alt_baslik_json === 'string' 
            ? JSON.parse(sv.alt_baslik_json) 
            : sv.alt_baslik_json;
        if (parsed && typeof parsed === 'object') {
            altTr = parsed.tr || '';
        }
    } catch(e) {
        // JSON değilse direkt string olarak al
        altTr = typeof sv.alt_baslik_json === 'string' ? sv.alt_baslik_json : '';
    }
    
    yeniVakaSetiEkle(
        sv.before_resim, 
        sv.after_resim, 
        sv.sira,
        sv.islem || '',
        sv.islem_tarihi || '',
        sv.goruntuleme_tarihi || '',
        altTr
    );
});
                } else {
                    yeniVakaSetiEkle('', '', 1);
                }
                
                if (sliders.length > 0 && sliders[0].after_resim) {
                    document.getElementById('seo_og_image').value = sliders[0].after_resim;
                } else {
                    document.getElementById('seo_og_image').value = t.seo_og_image || '';
                }
                
                document.getElementById('tedaviId').value = t.id;
                document.getElementById('slug').value = t.slug || '';
                document.getElementById('kategori').value = t.kategori || '';
                document.getElementById('sira').value = t.sira || 0;
                document.getElementById('aktif').checked = t.aktif == 1;
                document.getElementById('detayli_aciklama').value = t.detayli_aciklama || '';
                document.getElementById('sure').value = t.sure || '';

                
                let seoTitle = t.seo_title_tr || '';
                document.getElementById('seo_title_tr').value = seoTitle;
                let seoDesc = t.seo_description_tr || '';
                document.getElementById('seo_description_tr').value = seoDesc;
                let seoKeywords = t.seo_keywords_tr || '';
                document.getElementById('seo_keywords_tr').value = seoKeywords;
                
                document.getElementById('seo_title_en').value = t.seo_title_en || '';
                document.getElementById('seo_description_en').value = t.seo_description_en || '';
                document.getElementById('seo_keywords_en').value = t.seo_keywords_en || '';
                document.getElementById('seo_canonical').value = t.seo_canonical || '';
                document.getElementById('seo_robots').value = t.seo_robots || 'index, follow';
                
                document.getElementById('seo_preview_title').textContent = seoTitle || baslik || 'Başlık Giriniz';
                document.getElementById('seo_preview_desc').textContent = seoDesc || kisa || 'Açıklama Giriniz';
                var canonical = t.seo_canonical || '';
                if (canonical) {
                    document.getElementById('seo_preview_url').textContent = canonical.replace(/^https?:\/\//, '');
                } else {
                    document.getElementById('seo_preview_url').textContent = 'www.dribrahimdurandentalclinic.com/tedaviler/' + (t.slug || 'slug');
                }
                
                let avantajlar = t.avantajlar || [];
                if (typeof avantajlar === 'string') {
                    try { avantajlar = JSON.parse(avantajlar); } catch(e) { avantajlar = []; }
                }
                const avantajContainer = document.getElementById('avantajlar_listesi');
                avantajContainer.innerHTML = '';
                if (Array.isArray(avantajlar) && avantajlar.length > 0) {
                    avantajlar.forEach(av => {
                        const div = document.createElement('div');
                        div.className = 'list-item';
                        div.innerHTML = `
                            <input type="text" name="avantajlar[]" class="form-control" style="flex:1;" value="${escapeHtml(av)}">
                            <span onclick="this.parentElement.remove()" class="remove"><i class="fas fa-times"></i></span>
                        `;
                        avantajContainer.appendChild(div);
                    });
                }
                
                let sss = t.sss || [];
                if (typeof sss === 'string') {
                    try { sss = JSON.parse(sss); } catch(e) { sss = []; }
                }
                const sssContainer = document.getElementById('sss_listesi');
                sssContainer.innerHTML = '';
                if (Array.isArray(sss) && sss.length > 0) {
                    sss.forEach(ss => {
                        const div = document.createElement('div');
                        div.className = 'sss-item';
                        div.innerHTML = `
                            <div style="margin-bottom:8px;">
                                <input type="text" name="sss_soru[]" class="form-control" value="${escapeHtml(ss.soru || '')}" placeholder="Soru">
                            </div>
                            <div style="margin-bottom:8px;">
                                <textarea name="sss_cevap[]" class="form-control" rows="2" placeholder="Cevap">${escapeHtml(ss.cevap || '')}</textarea>
                            </div>
                            <div style="text-align:right;">
                                <span onclick="this.parentElement.parentElement.remove()" style="color:#ef4444; cursor:pointer; font-size:12px;">&times; Sil</span>
                            </div>
                        `;
                        sssContainer.appendChild(div);
                    });
                }
                
                let adimlar = t.adimlar || [];
                if (typeof adimlar === 'string') {
                    try { adimlar = JSON.parse(adimlar); } catch(e) { adimlar = []; }
                }
                const adimContainer = document.getElementById('adimlar_listesi');
                adimContainer.innerHTML = '';
                if (Array.isArray(adimlar) && adimlar.length > 0) {
                    adimlar.forEach((adim, idx) => {
                        const div = document.createElement('div');
                        div.className = 'adim-item';
                        div.style.cssText = 'background: #f8fafc; border-radius: 12px; padding: 14px; margin-bottom: 10px; border: 1px solid #e2e8f0;';
                        div.innerHTML = `
                            <div style="display:flex; gap:8px; align-items:center; margin-bottom:8px;">
                                <span style="font-weight:700; color:#6366f1; min-width:30px; font-size:14px;">#${idx + 1}</span>
                                <input type="text" name="adim_baslik[]" class="form-control" style="flex:2; padding:6px 10px; border:1px solid #e2e8f0; border-radius:6px;" placeholder="Adım başlığı" value="${escapeHtml(adim.baslik || '')}">
                            </div>
                            <div style="display:flex; gap:8px; align-items:start;">
                                <textarea name="adim_aciklama[]" class="form-control" rows="2" style="flex:1; padding:6px 10px; border:1px solid #e2e8f0; border-radius:6px;" placeholder="Adım açıklaması...">${escapeHtml(adim.aciklama || '')}</textarea>
                                <span onclick="this.parentElement.parentElement.remove()" style="color:#ef4444; cursor:pointer; font-size:18px; padding:4px 8px; border-radius:6px; transition:all 0.2s; margin-top:4px;">✖</span>
                            </div>
                            <input type="hidden" name="adim_no[]" value="${idx + 1}">
                        `;
                        adimContainer.appendChild(div);
                    });
                }
                
                document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Tedavi Düzenle';
                document.getElementById('tedaviModal').style.display = 'flex';
            }
        });
}
function saveTedavi() {
    const formData = new FormData(document.getElementById('tedaviForm'));
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        if (key === 'avantajlar[]') {
            if (!data.avantajlar) data.avantajlar = [];
            if (value.trim()) data.avantajlar.push(value.trim());
        } else if (key === 'sss_soru[]') {
            if (!data.sss) data.sss = [];
            const index = data.sss.length;
            data.sss[index] = { ...data.sss[index], soru: value };
        } else if (key === 'sss_cevap[]') {
            const index = data.sss.length - 1;
            if (data.sss[index]) data.sss[index].cevap = value;
        } else {
            data[key] = value;
        }
    }
    
    data.adimlar = [];
    const adimItems = document.querySelectorAll('.adim-item');
    adimItems.forEach(function(item) {
        const baslik = item.querySelector('input[name="adim_baslik[]"]');
        const aciklama = item.querySelector('textarea[name="adim_aciklama[]"]');
        const no = item.querySelector('input[name="adim_no[]"]');
        if (baslik && baslik.value.trim()) {
            data.adimlar.push({
                no: no ? parseInt(no.value) : data.adimlar.length + 1,
                baslik: baslik.value.trim(),
                aciklama: aciklama ? aciklama.value.trim() : ''
            });
        }
    });

    // ===== SLIDER VERİLERİ (İŞLEM + ALT BAŞLIK DAHİL) =====
    data.sliders = [];
    const vakaItems = document.querySelectorAll('.slider-vaka-item');
    vakaItems.forEach(item => {
        const before = item.querySelector('input[name="slider_before[]"]')?.value || '';
        const after = item.querySelector('input[name="slider_after[]"]')?.value || '';
        const sira = item.querySelector('input[name="slider_sira[]"]')?.value || 0;
        const islem = item.querySelector('input[name="slider_islem[]"]')?.value || '';
        const islemTarihi = item.querySelector('input[name="slider_islem_tarihi[]"]')?.value || '';
        const goruntulemeTarihi = item.querySelector('input[name="slider_goruntuleme_tarihi[]"]')?.value || '';
        
    // 🔥 Alt başlık (sadece TR)
    const altBaslikTr = item.querySelector('input[name="slider_alt_baslik_tr[]"]')?.value?.trim() || '';
    
    if (before || after) {
        data.sliders.push({ 
            before: before, 
            after: after, 
            sira: sira,
            islem: islem,
            alt_baslik_tr: altBaslikTr,
            islem_tarihi: islemTarihi,
            goruntuleme_tarihi: goruntulemeTarihi
        });
    }
    });
    
    if (data.sliders.length > 0 && data.sliders[0].after) {
        data.seo_og_image = data.sliders[0].after;
    }
    
    data.csrf_token = csrfToken;
    data.aktif = document.getElementById('aktif').checked ? 1 : 0;
    data.id = document.getElementById('tedaviId').value;
    
    const btn = document.querySelector('.btn-save-modal');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Kaydediliyor...';
    btn.disabled = true;
    
    fetch(ajaxUrl + '?islem=kaydet', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showToast('✅ Tedavi kaydedildi! Sayfa yenileniyor...', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            alert('❌ Hata: ' + (result.message || 'Bilinmeyen hata'));
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(err => {
        alert('❌ Bağlantı hatası: ' + err.message);
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

function silTedavi(id) {
    if (confirm('Bu tedaviyi çöp kutusuna taşımak istediğinize emin misiniz?')) {
        fetch(ajaxUrl + '?islem=sil&id=' + id)
            .then(res => res.json())
            .then(() => location.reload());
    }
}

function toggleDurum(id) {
    fetch(ajaxUrl + '?islem=durum&id=' + id)
        .then(res => res.json())
        .then(() => location.reload());
}

function geriGetir(id) {
    if (confirm('Bu tedaviyi geri getirmek istediğinize emin misiniz?')) {
        fetch(ajaxUrl + '?islem=geri_getir&id=' + id)
            .then(res => res.json())
            .then(() => location.reload());
    }
}

function kaliciSil(id) {
    if (confirm('⚠️ Bu tedaviyi KALICI OLARAK silmek istediğinize emin misiniz? Bu işlem geri alınamaz!')) {
        fetch(ajaxUrl + '?islem=kalici_sil&id=' + id)
            .then(res => res.json())
            .then(() => location.reload());
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        if (m === '"') return '&quot;';
        return m;
    });
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    const colors = {
        success: '#10b981',
        error: '#ef4444',
        info: '#3b82f6',
        warning: '#f59e0b'
    };
    toast.style.cssText = `
        position: fixed; bottom: 20px; right: 20px;
        background: ${colors[type] || colors.success};
        color: white; padding: 12px 24px; border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 999999; font-weight: 600; 
        animation: slideIn 0.3s ease; font-size: 14px;
        max-width: 400px;
    `;
    toast.innerHTML = message;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function filterTedaviler() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const kategori = document.getElementById('kategoriFilter').value;
    const durum = document.getElementById('durumFilter').value;
    document.querySelectorAll('.tedavi-card').forEach(card => {
        const baslik = card.getAttribute('data-baslik').toLowerCase();
        const cardKategori = card.getAttribute('data-kategori');
        const cardDurum = card.getAttribute('data-aktif');
        let show = true;
        if (search && !baslik.includes(search)) show = false;
        if (kategori && cardKategori !== kategori) show = false;
        if (durum !== '' && cardDurum !== durum) show = false;
        card.style.display = show ? 'block' : 'none';
    });
}

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('kategoriFilter').value = '';
    document.getElementById('durumFilter').value = '';
    filterTedaviler();
}

document.getElementById('searchInput')?.addEventListener('keyup', filterTedaviler);
document.getElementById('kategoriFilter')?.addEventListener('change', filterTedaviler);
document.getElementById('durumFilter')?.addEventListener('change', filterTedaviler);

document.querySelectorAll('.seo-lang-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.seo-lang-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const lang = this.dataset.lang;
        document.querySelectorAll('.seo-lang-content').forEach(el => {
            el.style.display = el.dataset.lang === lang ? 'block' : 'none';
        });
    });
});

function updateCharCount(el, counterId) {
    const counter = document.getElementById(counterId);
    if (counter) {
        const max = el.maxLength || 160;
        counter.textContent = el.value.length;
        counter.style.color = el.value.length > max ? '#ef4444' : '#94a3b8';
    }
}

document.getElementById('seo_title_tr')?.addEventListener('input', function() {
    document.getElementById('seo_preview_title').textContent = this.value || 'Başlık Giriniz';
});
document.getElementById('seo_description_tr')?.addEventListener('input', function() {
    document.getElementById('seo_preview_desc').textContent = this.value || 'Açıklama Giriniz';
});
document.getElementById('slug')?.addEventListener('input', function() {
    var canonicalInput = document.querySelector('input[name="seo_canonical"]');
    if (canonicalInput && canonicalInput.value) {
        var url = canonicalInput.value.replace(/^https?:\/\//, '');
        document.getElementById('seo_preview_url').textContent = url;
    } else {
        document.getElementById('seo_preview_url').textContent = 'www.dribrahimdurandentalclinic.com/tedaviler/' + (this.value || 'slug');
    }
});

// ========== TEDAVİLER SAYFASI SEO (GENEL) ==========
document.querySelectorAll('.tedaviler-seo-lang-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.tedaviler-seo-lang-btn').forEach(b => {
            b.classList.remove('active');
            b.style.color = '#94a3b8';
            b.style.borderBottom = '2px solid transparent';
        });
        this.classList.add('active');
        this.style.color = '#3b82f6';
        this.style.borderBottom = '2px solid #3b82f6';
        const lang = this.dataset.lang;
        document.querySelectorAll('.tedaviler-seo-lang-content').forEach(el => {
            el.style.display = el.dataset.lang === lang ? 'block' : 'none';
        });
    });
});

function tedavilerSeoResimYukle() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (!file) return;
        const formData = new FormData();
        formData.append('resim', file);
        formData.append('csrf_token', csrfToken);
        fetch(ajaxUrl + '?islem=resim_yukle', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('tedaviler_seo_og_image').value = data.url;
                    showToast('✅ Resim yüklendi: ' + data.url, 'success');
                } else {
                    showToast('❌ Hata: ' + data.message, 'error');
                }
            })
            .catch(() => showToast('❌ Bağlantı hatası', 'error'));
    };
    input.click();
}

document.getElementById('tedavilerSeoForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const data = {};
    formData.forEach((value, key) => { data[key] = value; });
    const btn = this.querySelector('.btn-save-modal');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Kaydediliyor...';
    btn.disabled = true;
    fetch(ajaxUrl + '?islem=tedaviler_seo_kaydet', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            showToast('✅ SEO ayarları kaydedildi!', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showToast('❌ Hata: ' + (result.message || 'Bilinmeyen hata'), 'error');
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(() => {
        showToast('❌ Bağlantı hatası', 'error');
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
});

function resetTedavilerSeo() {
    if (!confirm('SEO ayarlarını varsayılan değerlere sıfırlamak istediğinize emin misiniz?')) return;
    document.getElementById('tedaviler_seo_title_tr').value = 'Tedaviler | Prof. Dr. İbrahim Duran | Diş Kliniği Samsun';
    document.getElementById('tedaviler_seo_description_tr').value = 'Prof. Dr. İbrahim Duran, Samsun Atakum\'da Diş Estetiği · Diş Ağrısı · İmplant Tedavisi · Diş Eti Hastalıkları · Kanal Tedavisi · Ortodontik Tedavi · Çocuk Diş Tedavisi · Çene Eklemi Rahatsızlıkları. Tedavi Hizmeti Vermektedir.';
    document.getElementById('tedaviler_seo_keywords_tr').value = 'Prof. Dr. İbrahim Duran, Samsun diş hekimi, Atakum diş hekimi, Samsun diş kliniği, RivaDent Atakum, Samsun implant tedavisi, Samsun gülüş tasarımı, Samsun zirkonyum kaplama';
    showToast('🔄 Değerler varsayılana döndü', 'info');
}

document.querySelectorAll('.kurumsal-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
        var tabName = this.getAttribute('data-tab');
        document.querySelectorAll('.kurumsal-tab').forEach(function(t) {
            t.classList.remove('active');
        });
        this.classList.add('active');
        document.querySelectorAll('.tab-pane').forEach(function(pane) {
            pane.style.display = 'none';
        });
        var target = document.getElementById('tab-' + tabName);
        if (target) {
            target.style.display = 'block';
        }
        var url = new URL(window.location.href);
        url.searchParams.set('tab', tabName);
        window.history.pushState({}, '', url);
    });
});

const toastStyle = document.createElement('style');
toastStyle.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
`;
document.head.appendChild(toastStyle);
</script>

<?php
if (isset($_GET['islem'])) exit;
?>