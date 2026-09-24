<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
kontrol();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$islem = $_GET['islem'] ?? $_POST['islem'] ?? '';

// ========== SEO URL FONKSİYONU ==========
function seo_url($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

// ========== SAYFA LİSTESİNİ GETİR ==========
if ($islem === 'sayfa_listele') {
    $sayfalar = $db->query("SELECT * FROM dinamik_sayfalar ORDER BY sira ASC")->fetchAll();
    echo json_encode(['success' => true, 'data' => $sayfalar]);
    exit;
}

// ========== SAYFA KAYDET (YENİ/DÜZENLE) ==========
if ($islem === 'sayfa_kaydet') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!isset($data['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $data['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'CSRF hatası']);
        exit;
    }
    
    $id = isset($data['id']) ? intval($data['id']) : 0;
    $baslik = trim($data['baslik'] ?? '');
    $menu_adi = trim($data['menu_adi'] ?? '');
    $slug = seo_url($data['slug'] ?? $baslik);
    $aktif = isset($data['aktif']) ? 1 : 0;
    
    if (empty($baslik) || empty($menu_adi)) {
        echo json_encode(['success' => false, 'message' => 'Başlık ve menü adı zorunludur']);
        exit;
    }
    
    // Slug kontrolü
    $check = $db->prepare("SELECT id FROM dinamik_sayfalar WHERE slug = ? AND id != ?");
    $check->execute([$slug, $id]);
    if ($check->fetch()) {
        $slug = $slug . '-' . time();
    }
    
    try {
        if ($id > 0) {
            // Güncelle
            $stmt = $db->prepare("UPDATE dinamik_sayfalar SET baslik=?, menu_adi=?, slug=?, aktif=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$baslik, $menu_adi, $slug, $aktif, $id]);
            $message = 'Sayfa güncellendi';
            echo json_encode(['success' => true, 'message' => $message, 'id' => $id, 'slug' => $slug]);
            exit;
        } else {
            // ========== YENİ SAYFA EKLE ==========
            
            // Önce en büyük sırayı al
            $max_sira = $db->query("SELECT COALESCE(MAX(sira), 0) FROM dinamik_sayfalar")->fetchColumn();
            $yeni_sira = $max_sira + 1;
            
            // Veritabanına ekle
            $stmt = $db->prepare("INSERT INTO dinamik_sayfalar (baslik, menu_adi, slug, aktif, sira, created_at) VALUES (?,?,?,?,?, NOW())");
            $stmt->execute([$baslik, $menu_adi, $slug, $aktif, $yeni_sira]);
            $id = $db->lastInsertId();
            
            // ========== KLASÖR OLUŞTURMA (WINDOWS UYUMLU) ==========
            $sayfa_klasoru = $_SERVER['DOCUMENT_ROOT'] . '/site/' . $slug;
            
            // Klasörü oluştur - Windows'ta 0777 yerine 0755 dene
            if (!file_exists($sayfa_klasoru)) {
                $old_umask = umask(0);
                if(!mkdir($sayfa_klasoru, 0755, true)) {
                    umask($old_umask);
                    echo json_encode(['success' => false, 'message' => 'Klasör oluşturulamadı: ' . $sayfa_klasoru]);
                    exit;
                }
                umask($old_umask);
            }
            
            // Şablon dosyasını bul
            $template_path = __DIR__ . '/templates/sayfa_template.php';
            
            if (file_exists($template_path)) {
                $template = file_get_contents($template_path);
                $template = str_replace('{{PAGE_TITLE}}', addslashes($baslik), $template);
                $template = str_replace('{{PAGE_SLUG}}', $slug, $template);
            } else {
                // Basit şablon
                $template = '<?php
require_once dirname(__DIR__, 2) . "/inc/config.php";
$page_title = "' . addslashes($baslik) . '";
include dirname(__DIR__, 2) . "/inc/header.php";
?>
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6"><?php echo $page_title; ?></h1>
    <div class="prose max-w-none">
        <p>Sayfa içeriği buraya gelecek.</p>
    </div>
</div>
<?php include dirname(__DIR__, 2) . "/inc/footer.php"; ?>';
            }
            
            // index.php oluştur
            $dosya_yolu = $sayfa_klasoru . '/index.php';
            if (file_put_contents($dosya_yolu, $template) === false) {
                echo json_encode(['success' => false, 'message' => 'Dosya oluşturulamadı: ' . $dosya_yolu]);
                exit;
            }
            
            // ========== HEADER2 NAVLINK'E EKLE (BURASI ÖNEMLİ!) ==========
            $stmt_h = $db->prepare("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'header2_navlinks'");
            $stmt_h->execute();
            $mevcut_nav_json = $stmt_h->fetchColumn();
            $mevcut_nav = json_decode($mevcut_nav_json, true);
            if (!is_array($mevcut_nav)) {
                $mevcut_nav = [];
            }
            
            // Aynı isimde link var mı kontrol et
            $exists = false;
            foreach ($mevcut_nav as $nav) {
                if ($nav['name'] === $menu_adi || $nav['link'] === $slug) {
                    $exists = true;
                    break;
                }
            }
            
            // Yoksa ekle
            if (!$exists) {
                $mevcut_nav[] = ['name' => $menu_adi, 'link' => $slug];
                $stmt_up = $db->prepare("UPDATE site_ayarlari SET ayar_value = ? WHERE ayar_key = 'header2_navlinks'");
                $stmt_up->execute([json_encode($mevcut_nav, JSON_UNESCAPED_UNICODE)]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Sayfa oluşturuldu', 'id' => $id, 'slug' => $slug]);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// ========== SAYFA GETİR ==========
if ($islem === 'sayfa_getir' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $db->prepare("SELECT * FROM dinamik_sayfalar WHERE id = ?");
    $stmt->execute([$id]);
    $sayfa = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $sayfa]);
    exit;
}

// ========== SAYFA SİL ==========
if ($islem === 'sayfa_sil' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $stmt = $db->prepare("SELECT baslik, menu_adi, slug FROM dinamik_sayfalar WHERE id = ?");
    $stmt->execute([$id]);
    $sayfa = $stmt->fetch();
    
    if ($sayfa) {
        // ========== HEADER2'DEN KALDIR ==========
        $stmt2 = $db->prepare("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'header2_navlinks'");
        $stmt2->execute();
        $navlinks = json_decode($stmt2->fetchColumn(), true);
        if (is_array($navlinks)) {
            $yeni_navlinks = array_filter($navlinks, function($item) use ($sayfa) {
                return !($item['name'] == $sayfa['menu_adi'] || $item['link'] == $sayfa['slug']);
            });
            $stmt3 = $db->prepare("UPDATE site_ayarlari SET ayar_value = ? WHERE ayar_key = 'header2_navlinks'");
            $stmt3->execute([json_encode(array_values($yeni_navlinks), JSON_UNESCAPED_UNICODE)]);
        }
        
        // ========== FİZİKSEL DOSYALARI SİL ==========
        $klasor = $_SERVER['DOCUMENT_ROOT'] . '/site/' . $sayfa['slug'];
        if (file_exists($klasor . '/index.php')) {
            unlink($klasor . '/index.php');
        }
        if (is_dir($klasor)) {
            @rmdir($klasor);
        }
        
        // ========== VERİTABANINDAN SİL ==========
        $db->prepare("DELETE FROM dinamik_sayfalar WHERE id = ?")->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => 'Sayfa silindi']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Sayfa bulunamadı']);
    }
    exit;
}

// ========== BLOKLARI GETİR ==========
if ($islem === 'blok_listele' && isset($_GET['sayfa_id'])) {
    $sayfa_id = intval($_GET['sayfa_id']);
    $bloklar = $db->prepare("SELECT * FROM dinamik_sayfa_bloklari WHERE sayfa_id = ? ORDER BY sira ASC");
    $bloklar->execute([$sayfa_id]);
    echo json_encode(['success' => true, 'data' => $bloklar->fetchAll()]);
    exit;
}

// ========== BLOK KAYDET ==========
if ($islem === 'blok_kaydet') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    $id = isset($data['id']) ? intval($data['id']) : 0;
    $sayfa_id = intval($data['sayfa_id'] ?? 0);
    $blok_tip = $data['blok_tip'] ?? 'text';
    $baslik = $data['baslik'] ?? '';
    $aciklama = $data['aciklama'] ?? '';
    $resim = $data['resim'] ?? '';
    $icon = $data['icon'] ?? '';
    $buton_text = $data['buton_text'] ?? '';
    $buton_link = $data['buton_link'] ?? '';
    $icerik_json = isset($data['icerik_json']) ? json_encode($data['icerik_json']) : null;
    $aktif = isset($data['aktif']) ? 1 : 0;
    
    if ($id > 0) {
        $stmt = $db->prepare("UPDATE dinamik_sayfa_bloklari SET blok_tip=?, baslik=?, aciklama=?, resim=?, icon=?, buton_text=?, buton_link=?, icerik_json=?, aktif=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$blok_tip, $baslik, $aciklama, $resim, $icon, $buton_text, $buton_link, $icerik_json, $aktif, $id]);
    } else {
        $max_sira = $db->query("SELECT COALESCE(MAX(sira), 0) FROM dinamik_sayfa_bloklari WHERE sayfa_id = $sayfa_id")->fetchColumn();
        $yeni_sira = $max_sira + 1;
        $stmt = $db->prepare("INSERT INTO dinamik_sayfa_bloklari (sayfa_id, blok_tip, baslik, aciklama, resim, icon, buton_text, buton_link, icerik_json, sira, aktif) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$sayfa_id, $blok_tip, $baslik, $aciklama, $resim, $icon, $buton_text, $buton_link, $icerik_json, $yeni_sira, $aktif]);
        $id = $db->lastInsertId();
    }
    
    echo json_encode(['success' => true, 'id' => $id]);
    exit;
}

// ========== BLOK SİL ==========
if ($islem === 'blok_sil' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $db->prepare("DELETE FROM dinamik_sayfa_bloklari WHERE id = ?")->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

// ========== SIRALAMA GÜNCELLE ==========
if ($islem === 'siralama_guncelle') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    $siralamalar = $data['siralamalar'] ?? [];
    
    foreach ($siralamalar as $sira => $id) {
        $db->prepare("UPDATE dinamik_sayfalar SET sira = ? WHERE id = ?")->execute([$sira, $id]);
    }
    
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Geçersiz işlem: ' . $islem]);
?>