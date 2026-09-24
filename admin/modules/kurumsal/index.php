<?php
// ============================================================
// HATA RAPORLAMAYI KAPAT
// ============================================================
error_reporting(0);
ini_set('display_errors', 0);
ini_set('max_input_vars', 10000);
ini_set('post_max_size', '100M');
ini_set('upload_max_filesize', '100M');

// ============================================================
// AJAX İSTEKLERİ
// ============================================================
if (isset($_GET['islem'])) {
    require_once dirname(__DIR__, 2) . '/includes/config.php';
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    header('Content-Type: application/json');
    
    // ========== RESİM YÜKLE ==========
    if ($_GET['islem'] === 'resim_yukle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            echo json_encode(['success' => false, 'message' => 'CSRF hatası']);
            exit;
        }
        
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/kurumsal/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $file = $_FILES['resim'];
        $info = getimagesize($file['tmp_name']);
        
        if (!$info) {
            echo json_encode(['success' => false, 'message' => 'Geçersiz resim dosyası']);
            exit;
        }
        
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($info['mime'], $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Geçersiz dosya tipi']);
            exit;
        }
        
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Max 5MB']);
            exit;
        }
        
        $filename = 'kurumsal_' . time() . '_' . rand(1000,9999) . '.webp';
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
        }
        
        if ($img && imagewebp($img, $destination, 80)) {
            imagedestroy($img);
            echo json_encode(['success' => true, 'url' => '/uploads/kurumsal/' . $filename]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Dönüştürme başarısız']);
        }
        exit;
    }
    
    // ========== KAYDET ==========
    if ($_GET['islem'] === 'kaydet' && $_SERVER['REQUEST_METHOD'] === 'POST') {
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
        
        unset($data['csrf_token'], $data['tab']);
        
        // JSON alanları (kurumsal_ayarlari tablosu için)
        $jsonFields = ['vizyon_kutu', 'misyon_kutu', 'uzmanlik_grup1_liste', 'uzmanlik_grup2_liste', 'uzmanlik_grup3_liste', 'yontemler_liste', 'degerler_liste', 'tedavi_kartlari'];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $jsonFields)) {
                if (is_array($value)) {
                    $value = json_encode(array_values($value), JSON_UNESCAPED_UNICODE);
                } elseif (empty($value)) {
                    $value = '[]';
                }
            }
            
            try {
                $stmt = $db->prepare("UPDATE kurumsal_ayarlari SET ayar_value = ? WHERE ayar_key = ?");
                $stmt->execute([$value, $key]);
            } catch (Exception $e) {
                // Hata olursa devam et
            }
        }
        
        // ========== TEDAVİ METİNLERİNİ KAYDET (anasayfa_icerik tablosu) ==========
        if (isset($data['tedaviler_metin']) && is_array($data['tedaviler_metin'])) {
            $tedaviler_metin_json = json_encode($data['tedaviler_metin'], JSON_UNESCAPED_UNICODE);
            
            try {
                // Önce kayıt var mı kontrol et
                $kontrol = $db->query("SELECT id FROM anasayfa_icerik WHERE id = 1")->fetch();
                if ($kontrol) {
                    $stmt = $db->prepare("UPDATE anasayfa_icerik SET tedaviler_metin_json = ? WHERE id = 1");
                } else {
                    $stmt = $db->prepare("INSERT INTO anasayfa_icerik (id, tedaviler_metin_json) VALUES (1, ?)");
                }
                $stmt->execute([$tedaviler_metin_json]);
            } catch (Exception $e) {
                // Hata olursa devam et
            }
        }
        
        // Başlık ve alt başlık
        if (isset($data['tedaviler_baslik'])) {
            try {
                $stmt = $db->prepare("UPDATE anasayfa_icerik SET tedaviler_baslik_tr = ? WHERE id = 1");
                $stmt->execute([$data['tedaviler_baslik']]);
            } catch (Exception $e) {}
        }
        
        if (isset($data['tedaviler_alt'])) {
            try {
                $stmt = $db->prepare("UPDATE anasayfa_icerik SET tedaviler_alt_baslik_tr = ? WHERE id = 1");
                $stmt->execute([$data['tedaviler_alt']]);
            } catch (Exception $e) {}
        }
        
        echo json_encode(['success' => true]);
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
    exit;
}

// ============================================================
// NORMAL SAYFA ÇALIŞMASI
// ============================================================
require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

kontrol();
yetkiKontrol('kurumsal', 'goruntuleyebilir');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ============================================================
// FONKSİYONLAR
// ============================================================
function jsonVal($value, $default = '') {
    if (empty($value)) return $default;
    $decoded = json_decode($value, true);
    return is_array($decoded) ? $decoded : $default;
}

// ============================================================
// KURUMSAL AYARLARI ÇEK
// ============================================================
$stmt = $db->query("SELECT * FROM kurumsal_ayarlari");
$ayarlar = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $ayarlar[$row['ayar_key']] = $row['ayar_value'];
}

// JSON alanlarını çözümlenmiş haliyle değişkenlere ata
$uzmanlik_grup1_liste = jsonVal($ayarlar['uzmanlik_grup1_liste'] ?? '', []);
$uzmanlik_grup2_liste = jsonVal($ayarlar['uzmanlik_grup2_liste'] ?? '', []);
$uzmanlik_grup3_liste = jsonVal($ayarlar['uzmanlik_grup3_liste'] ?? '', []);
$yontemler_liste = jsonVal($ayarlar['yontemler_liste'] ?? '', []);
$degerler_liste = jsonVal($ayarlar['degerler_liste'] ?? '', []);
$tedavi_kartlari = jsonVal($ayarlar['tedavi_kartlari'] ?? '', []);
$vizyon_kutu = jsonVal($ayarlar['vizyon_kutu'] ?? '', []);

// ============================================================
// TEDAVİ METİNLERİNİ ÇEK (anasayfa_icerik tablosu)
// ============================================================
$tedaviler_metin = [];
$tedaviler_baslik = 'Tedavilerimiz';
$tedaviler_alt = 'Prof. Dr. İbrahim Duran kliniğinden bir kesit.';
$tedavi_icon = [];
$tedavi_title = [];

try {
    $anasayfa = $db->query("SELECT * FROM anasayfa_icerik WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
    if ($anasayfa) {
        // Başlık ve alt başlık
        if (!empty($anasayfa['tedaviler_baslik_tr'])) {
            $tedaviler_baslik = $anasayfa['tedaviler_baslik_tr'];
        }
        if (!empty($anasayfa['tedaviler_alt_baslik_tr'])) {
            $tedaviler_alt = $anasayfa['tedaviler_alt_baslik_tr'];
        }
        
        // ========== TEDAVİ METİNLERİ ==========
        $raw_metin = [];
        
        // 1. Önce tedaviler_metin_json'dan dene
        if (!empty($anasayfa['tedaviler_metin_json'])) {
            $raw_metin = json_decode($anasayfa['tedaviler_metin_json'], true);
        }
        
        // 2. Boşsa eski tedaviler_metin'den dene
        if (empty($raw_metin) && !empty($anasayfa['tedaviler_metin'])) {
            $raw_metin = json_decode($anasayfa['tedaviler_metin'], true);
        }
        
        // 3. Eğer raw_metin array ise işle
        if (is_array($raw_metin) && !empty($raw_metin)) {
            foreach ($raw_metin as $key => $value) {
                if (is_array($value)) {
                    // Nesne ise desc'yi al, yoksa boş string
                    $tedaviler_metin[$key] = $value['desc'] ?? '';
                    $tedavi_icon[$key] = $value['icon'] ?? '';
                    $tedavi_title[$key] = $value['title'] ?? '';
                } elseif (is_string($value)) {
                    // Zaten string ise direkt al
                    $tedaviler_metin[$key] = $value;
                    $tedavi_icon[$key] = '';
                    $tedavi_title[$key] = '';
                } else {
                    $tedaviler_metin[$key] = '';
                    $tedavi_icon[$key] = '';
                    $tedavi_title[$key] = '';
                }
            }
        }
    }
} catch (Exception $e) {
    // Tablo yoksa sessizce devam et
}

// ============================================================
// VARSYILAN DEĞERLER (BOŞSA)
// ============================================================
$tedaviKeys = ['dis_estetigi', 'dis_agrisi', 'implant', 'dis_eti', 'kanal', 'ortodonti', 'cocuk', 'cene_eklemi', 'beyazlatma', 'samsun'];
foreach ($tedaviKeys as $key) {
    if (!isset($tedaviler_metin[$key])) {
        $tedaviler_metin[$key] = '';
    }
    if (!isset($tedavi_icon[$key])) {
        $tedavi_icon[$key] = '';
    }
    if (!isset($tedavi_title[$key])) {
        $tedavi_title[$key] = '';
    }
}
// ============================================================
// TAB YÖNETİMİ
// ============================================================
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : (isset($_SESSION['aktif_tab']) ? $_SESSION['aktif_tab'] : 'hakkimizda');

if ($activeTab && $activeTab !== 'hakkimizda') {
    $_SESSION['aktif_tab'] = $activeTab;
} elseif (!isset($_SESSION['aktif_tab'])) {
    $_SESSION['aktif_tab'] = 'hakkimizda';
}

// ============================================================
// DEĞİŞKENLERİ ATA (HTML'DE KULLANILMAK ÜZERE)
// ============================================================
$tedaviler_baslik = $tedaviler_baslik ?? 'Tedavilerimiz';
$tedaviler_alt = $tedaviler_alt ?? 'Prof. Dr. İbrahim Duran kliniğinden bir kesit.';
$tedaviler_metin = $tedaviler_metin ?? [];

// Eğer tedaviler_metin boşsa varsayılan değerleri ata
if (empty($tedaviler_metin)) {
    $tedaviler_metin = [
        'dis_estetigi' => ['icon' => '🦷', 'title' => 'Diş Estetiği', 'desc' => ''],
        'dis_agrisi' => ['icon' => '🦷', 'title' => 'Diş Ağrısı', 'desc' => ''],
        'implant' => ['icon' => '💉', 'title' => 'İmplant Tedavisi', 'desc' => ''],
        'dis_eti' => ['icon' => '🩸', 'title' => 'Diş Eti Hastalıkları', 'desc' => ''],
        'kanal' => ['icon' => '🔬', 'title' => 'Kanal Tedavisi', 'desc' => ''],
        'ortodonti' => ['icon' => '😬', 'title' => 'Ortodontik Tedavi', 'desc' => ''],
        'cocuk' => ['icon' => '👶', 'title' => 'Çocuk Diş Tedavisi', 'desc' => ''],
        'cene_eklemi' => ['icon' => '🦴', 'title' => 'Çene Eklemi Rahatsızlıkları', 'desc' => ''],
        'beyazlatma' => ['icon' => '⭐', 'title' => 'Diş Beyazlatma', 'desc' => ''],
        'samsun' => ['icon' => '📍', 'title' => 'Samsun\'da Diş Hekimi', 'desc' => '']
    ];
}
?>

<!-- REST OF YOUR HTML CODE CONTINUES... -->

<!-- REST OF YOUR HTML CODE CONTINUES... -->

<style>
    * { box-sizing: border-box; }
    .kurumsal-container { background: #f0f4f8; min-height: 100vh; padding: 20px; }
    .kurumsal-card { background: white; border-radius: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); overflow: hidden; }
    .kurumsal-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px 28px; color: white; }
    .kurumsal-header h3 { margin: 0; font-size: 1.4rem; font-weight: 700; display: flex; align-items: center; gap: 12px; }
    .kurumsal-tabs { display: flex; flex-wrap: wrap; gap: 4px; padding: 0 20px; background: white; border-bottom: 1px solid #e2e8f0; }
    .kurumsal-tab { padding: 14px 22px; font-size: 13px; font-weight: 600; color: #64748b; background: transparent; border: none; cursor: pointer; border-bottom: 3px solid transparent; margin-bottom: -1px; transition: all 0.2s; }
    .kurumsal-tab:hover { color: #667eea; background: #f8fafc; }
    .kurumsal-tab.active { color: #667eea; border-bottom-color: #667eea; }
    
    .two-columns { flex-wrap: wrap; }
    .form-col { flex: 0 0 60%; padding: 24px; border-right: 1px solid #e2e8f0; }
    .preview-col { flex: 0 0 40%; background: #f8fafc; padding: 24px; position: sticky; top: 20px; height: calc(100vh - 120px); overflow: auto; }
    
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-weight: 600; font-size: 13px; color: #1e293b; margin-bottom: 6px; }
    .form-group label small { color: #94a3b8; font-weight: normal; font-size: 10px; }
    .form-control { width: 100%; padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 13px; transition: all 0.2s; }
    .form-control:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
    textarea.form-control { resize: vertical; }
    
    .upload-btn { background: #f1f5f9; padding: 6px 12px; border-radius: 8px; cursor: pointer; font-size: 11px; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; gap: 4px; margin-left: 8px; }
    .upload-btn:hover { background: #e2e8f0; }
    
    .btn-save { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px 28px; border-radius: 40px; font-weight: 600; font-size: 13px; cursor: pointer; margin-top: 20px; }
    .btn-save:hover { transform: scale(1.02); box-shadow: 0 4px 12px rgba(102,126,234,0.4); }
    
    .preview-card { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 20px; }
    .preview-header { background: #1e293b; color: white; padding: 12px 16px; font-size: 12px; font-weight: 600; }
    .preview-content { padding: 20px; max-height: 500px; overflow: auto; }
    
    .list-item { background: #f8fafc; padding: 8px 12px; border-radius: 8px; margin-bottom: 6px; display: flex; justify-content: space-between; align-items: center; }
    .list-item .remove { color: #ef4444; cursor: pointer; font-size: 12px; margin-left: 10px; }
    .add-item { margin-top: 8px; font-size: 11px; color: #667eea; cursor: pointer; }
    
    @media (max-width: 768px) {
        .two-columns { flex-direction: column; }
        .form-col, .preview-col { flex: auto; border-right: none; }
        .preview-col { position: relative; height: auto; }
        .kurumsal-tabs { overflow-x: auto; flex-wrap: nowrap; }
    }
</style>

<!-- Quill Editor -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<div class="kurumsal-container">
    <div class="kurumsal-card">
        <div class="kurumsal-header">
            <h3><i class="fas fa-building"></i> Kurumsal Sayfa Yönetimi</h3>
        </div>
        
        <div class="kurumsal-tabs">
            <button class="kurumsal-tab <?php echo $activeTab == 'hakkimizda' ? 'active' : ''; ?>" data-tab="hakkimizda">📖 Hakkımızda</button>
            <button class="kurumsal-tab <?php echo $activeTab == 'vizyon' ? 'active' : ''; ?>" data-tab="vizyon">👁️ Vizyon & Misyon</button>
            <button class="kurumsal-tab <?php echo $activeTab == 'uzmanlik' ? 'active' : ''; ?>" data-tab="uzmanlik">🔬 Uzmanlık Alanları</button>
            <button class="kurumsal-tab <?php echo $activeTab == 'tedaviler' ? 'active' : ''; ?>" data-tab="tedaviler">🦷 Tedaviler</button>
			<button class="kurumsal-tab <?php echo $activeTab == 'degerler' ? 'active' : ''; ?>" data-tab="degerler">💎 Değerlerimiz</button>
            <button class="kurumsal-tab <?php echo $activeTab == 'hekim' ? 'active' : ''; ?>" data-tab="hekim">👨‍⚕️ Hekim Profili</button>
            <button class="kurumsal-tab <?php echo $activeTab == 'slayt' ? 'active' : ''; ?>" data-tab="slayt">🖼️ Tedavi Kartları</button>
        <button class="kurumsal-tab <?php echo $activeTab == 'seo' ? 'active' : ''; ?>" data-tab="seo">🔍 SEO Ayarları</button>
		</div>
        
        <form id="kurumsalForm">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="tab" id="activeTab" value="<?php echo $activeTab; ?>">
            
            <div class="two-columns">
                <div class="form-col">
<!-- Modern Hakkımızda Tasarımı -->
<div id="tab-hakkimizda" class="tab-pane" style="display: <?php echo $activeTab == 'hakkimizda' ? 'block' : 'none'; ?>">
    
<!-- Hero Alanı -->
<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 16px; padding: 16px 20px; margin-bottom: 24px; color: white;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
        <div>
            <div style="background: rgba(255,255,255,0.2); display: inline-block; padding: 2px 10px; border-radius: 50px; font-size: 10px; margin-bottom: 6px;">
                <i class="fas fa-star"></i> KURUMSAL
            </div>
            <h3 style="margin: 0; font-size: 20px; font-weight: 700;">Hakkımızda</h3>
            <p style="margin: 4px 0 0 0; opacity: 0.9; font-size: 12px;">Klinik tanıtım metinlerinizi düzenleyin</p>
        </div>
        <div style="width: 45px; height: 45px; background: rgba(255,255,255,0.2); border-radius: 14px; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-building" style="font-size: 22px;"></i>
        </div>
    </div>
</div>
    
    <!-- Grid Düzen -->
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin-bottom: 30px;">
        
        <!-- Sol Kolon -->
        <div>
            <div style="background: white; border-radius: 20px; border: 1px solid #e2e8f0; overflow: hidden;">
                <div style="background: #f8fafc; padding: 16px 20px; border-bottom: 1px solid #e2e8f0;">
                    <i class="fas fa-tag" style="color: #3b82f6;"></i> <strong>Sayfa Bilgileri</strong>
                </div>
                <div style="padding: 20px;">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; font-size: 12px; color: #64748b; margin-bottom: 6px;">🏷️ BADGE</label>
                        <input type="text" name="hakkimizda_badge" class="form-control" value="<?php echo htmlspecialchars($ayarlar['hakkimizda_badge'] ?? 'Hakkımızda'); ?>" style="border: 2px solid #e2e8f0; border-radius: 12px; padding: 12px; font-size: 14px;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; font-size: 12px; color: #64748b; margin-bottom: 6px;">📌 BAŞLIK</label>
                        <input type="text" name="hakkimizda_baslik" class="form-control" value="<?php echo htmlspecialchars($ayarlar['hakkimizda_baslik'] ?? 'Bilimsel Temelli Estetik Mükemmellik'); ?>" style="border: 2px solid #e2e8f0; border-radius: 12px; padding: 12px; font-size: 14px;">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sağ Kolon -->
        <div>
            <div style="background: white; border-radius: 20px; border: 1px solid #e2e8f0; overflow: hidden;">
                <div style="background: #f8fafc; padding: 16px 20px; border-bottom: 1px solid #e2e8f0;">
                    <i class="fas fa-quote-left" style="color: #3b82f6;"></i> <strong>Alıntılar</strong>
                </div>
                <div style="padding: 20px;">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; font-size: 12px; color: #64748b; margin-bottom: 6px;">💬 QUOTE / ALINTI</label>
                        <textarea name="hakkimizda_quote" class="form-control" rows="3" style="border: 2px solid #e2e8f0; border-radius: 12px; padding: 12px; font-size: 14px;"><?php echo htmlspecialchars($ayarlar['hakkimizda_quote'] ?? ''); ?></textarea>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; font-size: 12px; color: #64748b; margin-bottom: 6px;">📄 ALT YAZI</label>
                        <textarea name="hakkimizda_alt" class="form-control" rows="3" style="border: 2px solid #e2e8f0; border-radius: 12px; padding: 12px; font-size: 14px;"><?php echo htmlspecialchars($ayarlar['hakkimizda_alt'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Açıklama Yazısı - Tam Genişlik -->
    <div style="background: white; border-radius: 20px; border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 30px;">
        <div style="background: #f8fafc; padding: 16px 20px; border-bottom: 1px solid #e2e8f0;">
            <i class="fas fa-align-left" style="color: #3b82f6;"></i> <strong>Açıklama Yazısı</strong>
            <span style="font-size: 11px; color: #94a3b8; margin-left: 10px;">(HTML destekli - Zengin metin düzenleyici)</span>
        </div>
<div style="padding: 20px;">
    <label style="display:block; font-weight:600; font-size:13px; color:#334155; margin-bottom:8px;">
        <i class="fas fa-code"></i> İçerik (HTML)
    </label>
    <textarea name="hakkimizda_yazi" id="hakkimizda_yazi" 
              style="width: 100%; height: 400px; padding: 16px; font-family: 'Courier New', Consolas, monospace; font-size: 13px; line-height: 1.6; border: 2px solid #e2e8f0; border-radius: 12px; background: #0f172a; color: #7dd3fc; resize: vertical; outline: none;"
              placeholder="HTML kodunuzu buraya yapıştırın..."><?php echo htmlspecialchars($ayarlar['hakkimizda_yazi'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
    <div style="font-size: 11px; color: #94a3b8; margin-top: 6px;">
        <i class="fas fa-info-circle"></i> HTML etiketleri desteklenir. Sitede otomatik render edilir.
    </div>
</div>
  </div>
    
    <!-- Kaydet Butonu -->
    <div style="text-align: right;">
        <button type="button" class="btn-save" onclick="saveTab('hakkimizda')" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 14px 32px; border-radius: 50px; font-weight: 600;">
            <i class="fas fa-save"></i> Değişiklikleri Kaydet
        </button>
    </div>
    
</div>
<!-- Modern Vizyon & Misyon Tasarımı -->
<div id="tab-vizyon" class="tab-pane" style="display: <?php echo $activeTab == 'vizyon' ? 'block' : 'none'; ?>">
    
<!-- Hero Alanı -->
<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 16px; padding: 16px 20px; margin-bottom: 24px; color: white;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <div style="background: rgba(255,255,255,0.2); display: inline-block; padding: 2px 10px; border-radius: 50px; font-size: 10px; margin-bottom: 6px;">
                <i class="fas fa-compass"></i> STRATEJİ
            </div>
            <h3 style="margin: 0; font-size: 20px; font-weight: 700;">Vizyon & Misyon</h3>
            <p style="margin: 4px 0 0 0; font-size: 12px; opacity: 0.9;">Kurum hedeflerinizi ve değerlerinizi düzenleyin</p>
        </div>
        <div style="width: 45px; height: 45px; background: rgba(255,255,255,0.2); border-radius: 14px; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-eye" style="font-size: 22px;"></i>
        </div>
    </div>
</div>
    
    <!-- 2 Kartlı Düzen -->
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px;">
        
        <!-- VİZYON KARTI -->
        <div style="background: white; border-radius: 24px; border: 1px solid #e2e8f0; overflow: hidden; transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 20px 35px -10px rgba(0,0,0,0.15)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 12px 16px; color: white; border-bottom: none;">
    <div style="display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-eye" style="font-size: 20px;"></i>
        <div>
            <h4 style="margin: 0; font-size: 16px; font-weight: 600;">Vizyon</h4>
            <p style="margin: 2px 0 0 0; opacity: 0.8; font-size: 10px;">Gelecek hedeflerimiz</p>
        </div>
    </div>
</div>
            <div style="padding: 24px;">
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: 600; font-size: 11px; color: #64748b; text-transform: uppercase;">Badge</label>
                    <input type="text" name="vizyon_badge" class="form-control" value="<?php echo htmlspecialchars($ayarlar['vizyon_badge'] ?? 'Vizyonumuz'); ?>" style="margin-top: 4px;">
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: 600; font-size: 11px; color: #64748b; text-transform: uppercase;">Başlık</label>
                    <input type="text" name="vizyon_baslik" class="form-control" value="<?php echo htmlspecialchars($ayarlar['vizyon_baslik'] ?? 'Geleceğe Bakış'); ?>" style="margin-top: 4px;">
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: 600; font-size: 11px; color: #64748b; text-transform: uppercase;">Yazı</label>
                    <textarea name="vizyon_yazi" class="form-control" rows="4" style="margin-top: 4px;"><?php echo htmlspecialchars($ayarlar['vizyon_yazi'] ?? ''); ?></textarea>
                </div>
                <div>
                    <label style="font-weight: 600; font-size: 11px; color: #64748b; text-transform: uppercase;">Quote</label>
                    <textarea name="vizyon_quote" class="form-control" rows="2" style="margin-top: 4px;"><?php echo htmlspecialchars($ayarlar['vizyon_quote'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
        
        <!-- MİSYON KARTI -->
        <div style="background: white; border-radius: 24px; border: 1px solid #e2e8f0; overflow: hidden; transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 20px 35px -10px rgba(0,0,0,0.15)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
<div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 12px 16px; color: white; border-bottom: none;">
    <div style="display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-bullseye" style="font-size: 20px;"></i>
        <div>
            <h4 style="margin: 0; font-size: 16px; font-weight: 600;">Misyon</h4>
            <p style="margin: 2px 0 0 0; opacity: 0.8; font-size: 10px;">Varoluş amacımız</p>
        </div>
    </div>
</div>
            <div style="padding: 24px;">
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: 600; font-size: 11px; color: #64748b; text-transform: uppercase;">Badge</label>
                    <input type="text" name="misyon_badge" class="form-control" value="<?php echo htmlspecialchars($ayarlar['misyon_badge'] ?? 'Misyonumuz'); ?>" style="margin-top: 4px;">
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: 600; font-size: 11px; color: #64748b; text-transform: uppercase;">Başlık</label>
                    <input type="text" name="misyon_baslik" class="form-control" value="<?php echo htmlspecialchars($ayarlar['misyon_baslik'] ?? 'Hedefimiz'); ?>" style="margin-top: 4px;">
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: 600; font-size: 11px; color: #64748b; text-transform: uppercase;">Yazı</label>
                    <textarea name="misyon_yazi" class="form-control" rows="4" style="margin-top: 4px;"><?php echo htmlspecialchars($ayarlar['misyon_yazi'] ?? ''); ?></textarea>
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: 600; font-size: 11px; color: #64748b; text-transform: uppercase;">Quote</label>
                    <textarea name="misyon_quote" class="form-control" rows="2" style="margin-top: 4px;"><?php echo htmlspecialchars($ayarlar['misyon_quote'] ?? ''); ?></textarea>
                </div>
                <div>
                    <label style="font-weight: 600; font-size: 11px; color: #64748b; text-transform: uppercase;">Alt Yazı</label>
                    <textarea name="misyon_alt" class="form-control" rows="2" style="margin-top: 4px;"><?php echo htmlspecialchars($ayarlar['misyon_alt'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Kaydet Butonu -->
    <div style="margin-top: 30px; text-align: right;">
        <button type="button" class="btn-save" onclick="saveTab('vizyon')" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 14px 32px; border-radius: 50px;">
            <i class="fas fa-save"></i> Vizyon & Misyon'u Kaydet
        </button>
    </div>
</div>                 
<!-- ========== UZMANLIK ALANLARI ========== -->
<div id="tab-uzmanlik" class="tab-pane" style="display: <?php echo $activeTab == 'uzmanlik' ? 'block' : 'none'; ?>">
    
    <!-- Başlık -->
    <div style="margin-bottom: 24px; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px;">
        <h3 style="margin: 0; font-size: 18px; font-weight: 600; color: #1e293b;">
            <i class="fas fa-microscope" style="color: #3b82f6; margin-right: 8px;"></i> 
            Uzmanlık Alanları
        </h3>
        <p style="margin: 5px 0 0 28px; font-size: 12px; color: #64748b;">Klinik uzmanlık alanlarınızı ve detaylarını düzenleyin</p>
    </div>
    
    <!-- Başlık ve Alt Başlık -->
    <div style="display: flex; gap: 20px; margin-bottom: 24px; flex-wrap: wrap;">
        <div style="flex: 1;">
            <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px;">
                <i class="fas fa-heading" style="color: #3b82f6;"></i> Ana Başlık
            </label>
            <input type="text" name="uzmanlik_baslik" class="form-control" value="<?php echo htmlspecialchars($ayarlar['uzmanlik_baslik'] ?? 'Uzmanlık Alanlarımız'); ?>" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px;">
        </div>
        <div style="flex: 1;">
            <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px;">
                <i class="fas fa-info-circle" style="color: #3b82f6;"></i> Alt Başlık
            </label>
            <input type="text" name="uzmanlik_alt" class="form-control" value="<?php echo htmlspecialchars($ayarlar['uzmanlik_alt'] ?? ''); ?>" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px;">
        </div>
    </div>
    
    <!-- Yeni Bölüm Ekle Butonu -->
    <div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
        <button type="button" onclick="openUzmanlikModal()" style="background: #10b981; color: white; border: none; padding: 8px 20px; border-radius: 8px; cursor: pointer; font-size: 13px;">
            <i class="fas fa-plus-circle"></i> + Yeni Uzmanlık Alanı Ekle
        </button>
    </div>
    
    <!-- MODAL -->
    <div id="uzmanlikModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 20px; width: 90%; max-width: 500px; overflow: hidden;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 16px 20px; display: flex; justify-content: space-between;">
                <h4 style="margin: 0; color: white;"><i class="fas fa-plus-circle"></i> Yeni Uzmanlık Alanı Ekle</h4>
                <button type="button" onclick="closeUzmanlikModal()" style="background: rgba(255,255,255,0.2); border: none; color: white; font-size: 20px;">✖</button>
            </div>
            <div style="padding: 20px;">
                <div style="margin-bottom: 16px;">
                    <label>Bölüm Başlığı</label>
                    <input type="text" id="yeni_grup_baslik" class="form-control" placeholder="Örn: İmplant & Protez Uygulamalar">
                </div>
                <div style="margin-bottom: 16px;">
                    <label>Maddeler (Her satıra bir madde)</label>
                    <textarea id="yeni_grup_maddeler" rows="6" class="form-control" placeholder="Diş implantı&#10;Ameliyatsız implant"></textarea>
                </div>
            </div>
            <div style="padding: 16px; background: #f8fafc; text-align: right;">
                <button type="button" onclick="closeUzmanlikModal()" style="background: #e2e8f0; border: none; padding: 6px 16px; border-radius: 6px;">İptal</button>
                <button onclick="addNewUzmanlikGroupFromModal()" style="background: #3b82f6; border: none; padding: 6px 16px; border-radius: 6px; color: white;">Ekle</button>
            </div>
        </div>
    </div>
    
    <!-- TÜM GRUPLAR - DİNAMİK (YENİ TABLODAN) -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-bottom: 20px;" id="uzmanlikGruplariContainer">
        
        <?php 
        // YENİ TABLODAN grupları çek
        $gruplar = $db->query("SELECT * FROM uzmanlik_gruplari ORDER BY grup_sira ASC")->fetchAll();
        foreach ($gruplar as $grup): 
            $grupId = $grup['id'];
            $grupBaslik = $grup['baslik'];
            $grupListe = json_decode($grup['maddeler'], true);
        ?>
        <!-- GRUP KARTI -->
        <div class="uzmanlik-kart" data-grup-id="<?php echo $grupId; ?>" style="background: white; border-radius: 20px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
            
            <!-- Kart Başlığı -->
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 14px 16px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 36px; height: 36px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-layer-group" style="color: white; font-size: 18px;"></i>
                    </div>
                    <div style="flex: 1;">
                        <input type="text" class="grup-baslik-input" 
                               value="<?php echo htmlspecialchars($grupBaslik); ?>" 
                               placeholder="Grup Başlığı"
                               style="width: 100%; background: transparent; border: none; color: white; font-weight: 600; font-size: 14px; outline: none;">
                    </div>
                    <button type="button" onclick="removeUzmanlikGroup(this, <?php echo $grupId; ?>)" style="background: rgba(255,255,255,0.2); border: none; color: white; padding: 4px 8px; border-radius: 6px; cursor: pointer;">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
            
            <!-- Kart İçeriği -->
            <div style="padding: 16px; background: #fafbff; min-height: 280px;">
                <div style="margin-bottom: 12px;">
                    <span style="font-size: 11px; color: #64748b; font-weight: 500;">
                        <i class="fas fa-list"></i> Uzmanlık Maddeleri
                    </span>
                </div>
                <div class="uzmanlik-madde-listesi" style="max-height: 250px; overflow-y: auto;">
                    <?php foreach ($grupListe as $item): ?>
                        <div class="madde-item" style="background: white; border-radius: 10px; padding: 8px 12px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #e2e8f0;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-check-circle" style="color: #10b981; font-size: 14px;"></i>
                                <span class="madde-text"><?php echo htmlspecialchars($item); ?></span>
                            </div>
                            <span class="remove-madde" onclick="removeMadde(this, <?php echo $grupId; ?>)" style="color: #ef4444; cursor: pointer;">✖</span>
                            <input type="hidden" class="madde-value" value="<?php echo htmlspecialchars($item); ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Yeni Madde Ekleme -->
                <div style="margin-top: 16px; display: flex; gap: 8px;">
                    <input type="text" class="yeni-madde-input" 
                           placeholder="Yeni uzmanlık alanı..." 
                           style="flex: 1; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 12px;">
                    <button type="button" class="madde-ekle-btn" onclick="addMadde(this, <?php echo $grupId; ?>)" 
                            style="background: #3b82f6; border: none; padding: 8px 16px; border-radius: 10px; color: white; cursor: pointer;">
                        <i class="fas fa-plus"></i> Ekle
                    </button>
                </div>
            </div>
            
            <!-- Alt Bilgi -->
            <div style="background: #f8fafc; padding: 8px 16px; border-top: 1px solid #e2e8f0; font-size: 11px; color: #94a3b8;">
                <i class="fas fa-chalkboard-user"></i> <span class="madde-sayisi"><?php echo count($grupListe); ?></span> uzmanlık alanı
            </div>
        </div>
        <?php endforeach; ?>
        
    </div>
    
    <!-- Kaydet Butonu -->
    <div style="margin-top: 24px; text-align: right;">
<button type="button" class="btn-save" onclick="kaydetTumGruplar()">💾 Uzmanlık Alanlarını Kaydet</button>

    </div>
    
</div>


<!-- ==================== TAB 2: TEDAVİ METİNLERİ (KURUMSAL) ==================== -->
<div class="tab-pane" id="tab-tedaviler">
    
    <!-- Başlık ve Alt Başlık -->
    <div style="display: flex; gap: 20px; margin-bottom: 24px; flex-wrap: wrap; background: #f8fafc; padding: 16px 20px; border-radius: 12px;">
        <div style="flex: 1;">
            <label style="display: block; font-weight: 600; font-size: 12px; color: #475569; margin-bottom: 4px;">Bölüm Başlığı</label>
            <input type="text" id="tedaviler_baslik" class="form-control" value="<?php echo htmlspecialchars($ayarlar['tedaviler_baslik'] ?? 'Tedavilerimiz'); ?>" style="font-size: 13px;">
        </div>
        <div style="flex: 1;">
            <label style="display: block; font-weight: 600; font-size: 12px; color: #475569; margin-bottom: 4px;">Alt Başlık</label>
            <input type="text" id="tedaviler_alt" class="form-control" value="<?php echo htmlspecialchars($ayarlar['tedaviler_alt'] ?? 'Prof. Dr. İbrahim Duran kliniğinden bir kesit.'); ?>" style="font-size: 13px;">
        </div>
    </div>
    
    <div class="grid grid-cols-2 gap-6">
        
        <!-- SOL TARAF - Estetik & Gülüş Tasarımı -->
        <div>
            <div class="form-card">
                <div class="form-card-header" style="display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; border-radius: 12px 12px 0 0; padding: 12px 16px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-star" style="color: #fcd34d;"></i>
                        <span style="font-weight: 700;">✨ Estetik & Gülüş Tasarımı</span>
                    </div>
                    <span style="font-size: 10px; background: rgba(255,255,255,0.2); padding: 2px 10px; border-radius: 20px;">5 tedavi</span>
                </div>
                <div class="form-card-body" style="padding: 16px;">
                    
                    <!-- 1. Diş Estetiği -->
                    <div class="mb-4 border-b border-gray-100 pb-4">
                        <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 6px;">
                            <input type="text" id="tedavi_dis_estetigi_icon" class="form-control" style="width: 60px; text-align: center; font-size: 18px; padding: 6px;" value="🦷">
                            <input type="text" id="tedavi_dis_estetigi_title" class="form-control" style="flex: 1; font-weight: 600; padding: 6px 12px;" value="Diş Estetiği" placeholder="Başlık">
                        </div>
                        <textarea id="tedavi_dis_estetigi" class="form-control" rows="2" style="font-size: 13px; padding: 8px 12px;" placeholder="Açıklama..."><?php echo isset($tedaviler_metin['dis_estetigi']) ? htmlspecialchars($tedaviler_metin['dis_estetigi']) : ''; ?></textarea>
                    </div>
                    
                    <!-- 2. Diş Ağrısı -->
                    <div class="mb-4 border-b border-gray-100 pb-4">
                        <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 6px;">
                            <input type="text" id="tedavi_dis_agrisi_icon" class="form-control" style="width: 60px; text-align: center; font-size: 18px; padding: 6px;" value="🦷">
                            <input type="text" id="tedavi_dis_agrisi_title" class="form-control" style="flex: 1; font-weight: 600; padding: 6px 12px;" value="Diş Ağrısı" placeholder="Başlık">
                        </div>
                        <textarea id="tedavi_dis_agrisi" class="form-control" rows="2" style="font-size: 13px; padding: 8px 12px;" placeholder="Açıklama..."><?php echo isset($tedaviler_metin['dis_agrisi']) ? htmlspecialchars($tedaviler_metin['dis_agrisi']) : ''; ?></textarea>
                    </div>
                    
                    <!-- 3. İmplant Tedavisi -->
                    <div class="mb-4 border-b border-gray-100 pb-4">
                        <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 6px;">
                            <input type="text" id="tedavi_implant_icon" class="form-control" style="width: 60px; text-align: center; font-size: 18px; padding: 6px;" value="💉">
                            <input type="text" id="tedavi_implant_title" class="form-control" style="flex: 1; font-weight: 600; padding: 6px 12px;" value="İmplant Tedavisi" placeholder="Başlık">
                        </div>
                        <textarea id="tedavi_implant" class="form-control" rows="2" style="font-size: 13px; padding: 8px 12px;" placeholder="Açıklama..."><?php echo isset($tedaviler_metin['implant']) ? htmlspecialchars($tedaviler_metin['implant']) : ''; ?></textarea>
                    </div>
                    
                    <!-- 4. Diş Eti Hastalıkları -->
                    <div class="mb-4 border-b border-gray-100 pb-4">
                        <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 6px;">
                            <input type="text" id="tedavi_dis_eti_icon" class="form-control" style="width: 60px; text-align: center; font-size: 18px; padding: 6px;" value="🩸">
                            <input type="text" id="tedavi_dis_eti_title" class="form-control" style="flex: 1; font-weight: 600; padding: 6px 12px;" value="Diş Eti Hastalıkları" placeholder="Başlık">
                        </div>
                        <textarea id="tedavi_dis_eti" class="form-control" rows="2" style="font-size: 13px; padding: 8px 12px;" placeholder="Açıklama..."><?php echo isset($tedaviler_metin['dis_eti']) ? htmlspecialchars($tedaviler_metin['dis_eti']) : ''; ?></textarea>
                    </div>
                    
                    <!-- 5. Kanal Tedavisi -->
                    <div>
                        <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 6px;">
                            <input type="text" id="tedavi_kanal_icon" class="form-control" style="width: 60px; text-align: center; font-size: 18px; padding: 6px;" value="🔬">
                            <input type="text" id="tedavi_kanal_title" class="form-control" style="flex: 1; font-weight: 600; padding: 6px 12px;" value="Kanal Tedavisi" placeholder="Başlık">
                        </div>
                        <textarea id="tedavi_kanal" class="form-control" rows="2" style="font-size: 13px; padding: 8px 12px;" placeholder="Açıklama..."><?php echo isset($tedaviler_metin['kanal']) ? htmlspecialchars($tedaviler_metin['kanal']) : ''; ?></textarea>
                    </div>

                </div>
            </div>
        </div>
        
        <!-- SAĞ TARAF - Cerrahi & İmplantoloji -->
        <div>
            <div class="form-card">
                <div class="form-card-header" style="display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%); color: white; border-radius: 12px 12px 0 0; padding: 12px 16px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-syringe" style="color: #a7f3d0;"></i>
                        <span style="font-weight: 700;">💉 Cerrahi & İmplantoloji</span>
                    </div>
                    <span style="font-size: 10px; background: rgba(255,255,255,0.2); padding: 2px 10px; border-radius: 20px;">5 tedavi</span>
                </div>
                <div class="form-card-body" style="padding: 16px;">
                    
                    <!-- 6. Ortodontik Tedavi -->
                    <div class="mb-4 border-b border-gray-100 pb-4">
                        <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 6px;">
                            <input type="text" id="tedavi_ortodonti_icon" class="form-control" style="width: 60px; text-align: center; font-size: 18px; padding: 6px;" value="😬">
                            <input type="text" id="tedavi_ortodonti_title" class="form-control" style="flex: 1; font-weight: 600; padding: 6px 12px;" value="Ortodontik Tedavi" placeholder="Başlık">
                        </div>
                        <textarea id="tedavi_ortodonti" class="form-control" rows="2" style="font-size: 13px; padding: 8px 12px;" placeholder="Açıklama..."><?php echo isset($tedaviler_metin['ortodonti']) ? htmlspecialchars($tedaviler_metin['ortodonti']) : ''; ?></textarea>
                    </div>
                    
                    <!-- 7. Çocuk Diş Tedavisi -->
                    <div class="mb-4 border-b border-gray-100 pb-4">
                        <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 6px;">
                            <input type="text" id="tedavi_cocuk_icon" class="form-control" style="width: 60px; text-align: center; font-size: 18px; padding: 6px;" value="👶">
                            <input type="text" id="tedavi_cocuk_title" class="form-control" style="flex: 1; font-weight: 600; padding: 6px 12px;" value="Çocuk Diş Tedavisi" placeholder="Başlık">
                        </div>
                        <textarea id="tedavi_cocuk" class="form-control" rows="2" style="font-size: 13px; padding: 8px 12px;" placeholder="Açıklama..."><?php echo isset($tedaviler_metin['cocuk']) ? htmlspecialchars($tedaviler_metin['cocuk']) : ''; ?></textarea>
                    </div>
                    
                    <!-- 8. Çene Eklemi Rahatsızlıkları -->
                    <div class="mb-4 border-b border-gray-100 pb-4">
                        <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 6px;">
                            <input type="text" id="tedavi_cene_eklemi_icon" class="form-control" style="width: 60px; text-align: center; font-size: 18px; padding: 6px;" value="🦴">
                            <input type="text" id="tedavi_cene_eklemi_title" class="form-control" style="flex: 1; font-weight: 600; padding: 6px 12px;" value="Çene Eklemi Rahatsızlıkları" placeholder="Başlık">
                        </div>
                        <textarea id="tedavi_cene_eklemi" class="form-control" rows="2" style="font-size: 13px; padding: 8px 12px;" placeholder="Açıklama..."><?php echo isset($tedaviler_metin['cene_eklemi']) ? htmlspecialchars($tedaviler_metin['cene_eklemi']) : ''; ?></textarea>
                    </div>
                    
                    <!-- 9. Diş Beyazlatma -->
                    <div class="mb-4 border-b border-gray-100 pb-4">
                        <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 6px;">
                            <input type="text" id="tedavi_beyazlatma_icon" class="form-control" style="width: 60px; text-align: center; font-size: 18px; padding: 6px;" value="⭐">
                            <input type="text" id="tedavi_beyazlatma_title" class="form-control" style="flex: 1; font-weight: 600; padding: 6px 12px;" value="Diş Beyazlatma" placeholder="Başlık">
                        </div>
                        <textarea id="tedavi_beyazlatma" class="form-control" rows="2" style="font-size: 13px; padding: 8px 12px;" placeholder="Açıklama..."><?php echo isset($tedaviler_metin['beyazlatma']) ? htmlspecialchars($tedaviler_metin['beyazlatma']) : ''; ?></textarea>
                    </div>
                    
                    <!-- 10. Samsun'da Diş Hekimi -->
                    <div>
                        <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 6px;">
                            <input type="text" id="tedavi_samsun_icon" class="form-control" style="width: 60px; text-align: center; font-size: 18px; padding: 6px;" value="📍">
                            <input type="text" id="tedavi_samsun_title" class="form-control" style="flex: 1; font-weight: 600; padding: 6px 12px;" value="Samsun'da Diş Hekimi" placeholder="Başlık">
                        </div>
                        <textarea id="tedavi_samsun" class="form-control" rows="2" style="font-size: 13px; padding: 8px 12px;" placeholder="Açıklama..."><?php echo isset($tedaviler_metin['samsun']) ? htmlspecialchars($tedaviler_metin['samsun']) : ''; ?></textarea>
                    </div>

                </div>
            </div>
        </div>
        
    </div>
    
    <!-- Kaydet Butonu -->
    <div class="form-actions" style="margin-top: 24px; padding-top: 16px; border-top: 2px solid #e2e8f0; display: flex; gap: 12px; justify-content: flex-end;">
        <button type="button" onclick="tedavileriSifirla()" style="padding: 10px 24px; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 40px; cursor: pointer; font-weight: 600; font-size: 13px;">
            <i class="fas fa-undo"></i> Sıfırla
        </button>
        <button type="button" id="tedaviKaydetBtn" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); padding: 10px 28px; border-radius: 40px; color: white; border: none; cursor: pointer; font-weight: 600; font-size: 13px;">
            <i class="fas fa-save"></i> 💾 Tedavileri Kaydet
        </button>
    </div>
    
</div>
<!-- ========== DEĞERLERİMİZ ========== -->
<div id="tab-degerler" class="tab-pane" style="display: <?php echo $activeTab == 'degerler' ? 'block' : 'none'; ?>">
    
    <div style="margin-bottom: 24px; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px;">
        <h3 style="margin: 0; font-size: 18px; font-weight: 600;">
            <i class="fas fa-heart" style="color: #3b82f6;"></i> Değerlerimiz
        </h3>
        <p style="margin: 5px 0 0 28px; font-size: 12px; color: #64748b;">Kurum değerlerinizi düzenleyin</p>
    </div>
    
    <div style="margin-bottom: 24px;">
        <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px;">Başlık</label>
        <input type="text" name="degerler_baslik" class="form-control" value="<?php echo htmlspecialchars($ayarlar['degerler_baslik'] ?? 'Vazgeçilmez Değerlerimiz'); ?>">
    </div>
    
    <!-- Yeni Değer Ekle Butonu -->
    <div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
        <button type="button" onclick="openDegerModal()" style="background: #10b981; color: white; border: none; padding: 8px 20px; border-radius: 8px; cursor: pointer;">
            <i class="fas fa-plus-circle"></i> + Yeni Değer Ekle
        </button>
    </div>
    
    <!-- Değerler Grid Listesi -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-bottom: 20px;" id="degerlerGrid">
        <?php foreach ($degerler_liste as $index => $item): ?>
        <div class="deger-kart" data-index="<?php echo $index; ?>" style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; transition: all 0.3s;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 16px; text-align: center;">
                <?php
                $iconMap = [
                    'HeartPulse' => '❤️',
                    'ShieldCheck' => '🛡️',
                    'Award' => '🏆',
                    'Microscope' => '🔬',
                    'Sparkles' => '✨',
                    'BadgeCheck' => '✅',
                    'Zap' => '⚡',
                    'Stethoscope' => '🩺'
                ];
                $iconSymbol = $iconMap[$item['icon'] ?? 'HeartPulse'] ?? '❤️';
                ?>
                <span style="font-size: 40px;"><?php echo $iconSymbol; ?></span>
                <h4 style="margin: 8px 0 0 0; color: white; font-size: 18px;"><?php echo htmlspecialchars($item['title'] ?? ''); ?></h4>
            </div>
            <div style="padding: 16px;">
                <p style="margin: 0 0 12px 0; font-size: 13px; color: #475569; line-height: 1.5;"><?php echo htmlspecialchars($item['desc'] ?? ''); ?></p>
                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" onclick="editDeger(<?php echo $index; ?>)" style="background: #3b82f6; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer;">✏️ Düzenle</button>
                    <button type="button" onclick="deleteDeger(<?php echo $index; ?>)" style="background: #ef4444; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer;">🗑️ Sil</button>
                </div>
            </div>
            <input type="hidden" name="degerler_liste[<?php echo $index; ?>][title]" value="<?php echo htmlspecialchars($item['title'] ?? ''); ?>">
            <input type="hidden" name="degerler_liste[<?php echo $index; ?>][icon]" value="<?php echo htmlspecialchars($item['icon'] ?? 'HeartPulse'); ?>">
            <input type="hidden" name="degerler_liste[<?php echo $index; ?>][desc]" value="<?php echo htmlspecialchars($item['desc'] ?? ''); ?>">
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Değer Modal -->
    <div id="degerModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 20px; width: 90%; max-width: 450px;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 16px 20px; border-radius: 20px 20px 0 0; color: white;">
                <h4 style="margin: 0;"><i class="fas fa-plus-circle"></i> Değer Ekle/Düzenle</h4>
            </div>
            <div style="padding: 20px;">
                <div style="margin-bottom: 15px;">
                    <label>Başlık *</label>
                    <input type="text" id="degerBaslik" class="form-control" style="width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 8px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label>İkon</label>
                    <select id="degerIkon" class="form-control" style="width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 8px;">
                        <option value="HeartPulse">❤️ HeartPulse</option>
                        <option value="ShieldCheck">🛡️ ShieldCheck</option>
                        <option value="Award">🏆 Award</option>
                        <option value="Microscope">🔬 Microscope</option>
                        <option value="Sparkles">✨ Sparkles</option>
                        <option value="BadgeCheck">✅ BadgeCheck</option>
                        <option value="Zap">⚡ Zap</option>
                        <option value="Stethoscope">🩺 Stethoscope</option>
                    </select>
                </div>
                <div style="margin-bottom: 15px;">
                    <label>Açıklama</label>
                    <textarea id="degerDesc" rows="4" class="form-control" placeholder="Değer açıklaması..." style="width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 8px;"></textarea>
                </div>
            </div>
            <div style="padding: 16px; background: #f8fafc; border-top: 1px solid #e2e8f0; text-align: right;">
                <button type="button" onclick="closeDegerModal()" style="background: #e2e8f0; border: none; padding: 6px 20px; border-radius: 6px;">İptal</button>
                <button type="button" onclick="saveDeger()" style="background: #3b82f6; border: none; padding: 6px 20px; border-radius: 6px; color: white;">Kaydet</button>
            </div>
        </div>
    </div>
    
    <!-- Alt Yazı ve Kaydet Butonu -->
    <div>
        <div class="form-group" style="margin-bottom: 20px;">
            <label>Alt Yazı</label>
            <textarea name="degerler_alt" class="form-control" rows="3"><?php echo htmlspecialchars($ayarlar['degerler_alt'] ?? ''); ?></textarea>
        </div>
        <div style="text-align: right;">
            <button type="button" class="btn-save" onclick="saveDegerler()">💾 Değerleri Kaydet</button>
        </div>
    </div>
    
</div>
                    
<!-- ========== HEKİM PROFİLİ ========== -->
<div id="tab-hekim" class="tab-pane" style="display: <?php echo $activeTab == 'hekim' ? 'block' : 'none'; ?>">
    
    <div style="margin-bottom: 24px; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px;">
        <h3 style="margin: 0; font-size: 18px; font-weight: 600;">
            <i class="fas fa-user-md" style="color: #3b82f6;"></i> Hekim Profili
        </h3>
        <p style="margin: 5px 0 0 28px; font-size: 12px; color: #64748b;">Hekim bilgilerinizi düzenleyin</p>
    </div>
    
    <!-- İki Sütunlu Düzen -->
    <div style="display: flex; gap: 24px; flex-wrap: wrap;">
        
        <!-- SOL SÜTUN: Fotoğraf ve Badge'ler -->
        <div style="flex: 1; min-width: 250px;">
            
            <!-- Fotoğraf Alanı -->
            <div style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 20px; margin-bottom: 20px; text-align: center;">
                <div style="width: 200px; height: 200px; margin: 0 auto 16px auto; border-radius: 50%; overflow: hidden; background: #f1f5f9; border: 4px solid #e2e8f0;">
                    <img id="hekimFotoPreview" src="<?php echo !empty($ayarlar['hekim_foto']) ? htmlspecialchars($ayarlar['hekim_foto']) : '/placeholder-hekim.jpg'; ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='/placeholder-hekim.jpg'">
                </div>
                <div style="display: flex; gap: 8px; justify-content: center;">
                    <input type="text" id="hekim_foto" name="hekim_foto" class="form-control" value="<?php echo htmlspecialchars($ayarlar['hekim_foto'] ?? ''); ?>" style="flex: 1;">
                    <button type="button" class="upload-btn" onclick="uploadImage('hekim_foto')" style="margin:0;"><i class="fas fa-upload"></i> Yükle</button>
                </div>
                <div style="font-size: 11px; color: #94a3b8; margin-top: 8px;">Önerilen boyut: 400x400px</div>
            </div>
            

        </div>
        
        <!-- SAĞ SÜTUN: Ünvan, İsim ve Özgeçmiş -->
        <div style="flex: 2; min-width: 300px;">
            
            <!-- Ünvan ve İsim -->
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 16px; padding: 20px; margin-bottom: 20px; color: white;">
                <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                    <div style="flex: 1;">
                        <label style="display: block; font-weight: 600; font-size: 12px; opacity: 0.8; margin-bottom: 4px;">Ünvan</label>
                        <input type="text" name="hekim_unvan" class="form-control" value="<?php echo htmlspecialchars($ayarlar['hekim_unvan'] ?? 'Prof. Dr.'); ?>" style="background: rgba(255,255,255,0.2); border: none; color: white; font-size: 18px; font-weight: 600;">
                    </div>
                    <div style="flex: 2;">
                        <label style="display: block; font-weight: 600; font-size: 12px; opacity: 0.8; margin-bottom: 4px;">İsim</label>
                        <input type="text" name="hekim_isim" class="form-control" value="<?php echo htmlspecialchars($ayarlar['hekim_isim'] ?? 'İbrahim DURAN'); ?>" style="background: rgba(255,255,255,0.2); border: none; color: white; font-size: 18px; font-weight: 600;">
                    </div>
                </div>
            </div>
                        <!-- Badge'ler -->
            <div style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 20px;">
                <h4 style="margin: 0 0 16px 0; font-size: 14px; font-weight: 600;">
                    <i class="fas fa-certificate" style="color: #3b82f6;"></i> Rozetler / Badge'ler
                </h4>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                    <?php for($i=1; $i<=4; $i++): ?>
                    <div>
                        <label style="display: block; font-weight: 600; font-size: 11px; color: #1e293b; margin-bottom: 4px;">Badge <?php echo $i; ?></label>
                        <input type="text" name="hekim_badge_<?php echo $i; ?>" class="form-control" value="<?php echo htmlspecialchars($ayarlar['hekim_badge_'.$i] ?? ''); ?>" placeholder="Örn: PROF" style="padding: 6px 10px; font-size: 12px; text-align: center;">
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
        
    </div>
    
    <!-- Kaydet Butonu -->
    <div style="margin-top: 24px; text-align: right;">
        <button type="button" class="btn-save" onclick="saveTab('hekim')" style="background: #3b82f6; color: white; border: none; padding: 12px 28px; border-radius: 12px; font-weight: 500; font-size: 13px; cursor: pointer;">
            <i class="fas fa-save"></i> Hekim Profilini Kaydet
        </button>
    </div>
    
</div>
                    
<!-- ========== TEDAVİ KARTLARI (SLAYT) ========== -->
<div id="tab-slayt" class="tab-pane" style="display: <?php echo $activeTab == 'slayt' ? 'block' : 'none'; ?>">
    
    <div style="margin-bottom: 24px; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px;">
        <h3 style="margin: 0; font-size: 18px; font-weight: 600;">
            <i class="fas fa-sliders-h" style="color: #3b82f6;"></i> Tedavi Kartları (Slayt)
        </h3>
        <p style="margin: 5px 0 0 28px; font-size: 12px; color: #64748b;">Ana sayfada gösterilecek tedavi kartlarını düzenleyin</p>
    </div>
    
    <div style="margin-bottom: 24px;">
        <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px;">Bölüm Başlığı</label>
        <input type="text" name="slayt_baslik" class="form-control" value="<?php echo htmlspecialchars($ayarlar['slayt_baslik'] ?? 'Tedavi Kartları'); ?>" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px;">
    </div>
    
    <!-- Yeni Kart Ekle Butonu -->
    <div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
        <button type="button" onclick="openTedaviKartModal()" style="background: #10b981; color: white; border: none; padding: 8px 20px; border-radius: 8px; cursor: pointer;">
            <i class="fas fa-plus-circle"></i> + Yeni Kart Ekle
        </button>
    </div>
    
    <!-- Kartlar Grid Listesi -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-bottom: 20px;" id="tedaviKartlariGrid">
        <?php foreach ($tedavi_kartlari as $index => $item): ?>
        <div class="tedavi-kart" data-index="<?php echo $index; ?>" style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; transition: all 0.3s; cursor: move;">
            <!-- Resim Alanı -->
            <div style="height: 180px; overflow: hidden; position: relative; background: #f0f4f8;">
                <?php if (!empty($item['image'])): ?>
                <img src="<?php echo htmlspecialchars($item['image']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-image" style="color: white; font-size: 48px; opacity: 0.5;"></i>
                </div>
                <?php endif; ?>
                <div style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.6); border-radius: 6px; padding: 4px 8px;">
                    <i class="fas fa-grip-vertical" style="color: white; font-size: 12px;"></i>
                </div>
            </div>
            <!-- İçerik -->
            <div style="padding: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <h4 style="margin: 0 0 8px 0; font-size: 16px; font-weight: 700;"><?php echo htmlspecialchars($item['title'] ?? ''); ?></h4>
                    <div style="display: flex; gap: 6px;">
                        <button type="button" onclick="editTedaviKart(<?php echo $index; ?>)" style="background: #3b82f6; color: white; border: none; padding: 4px 10px; border-radius: 6px; cursor: pointer;">✏️</button>
                        <button type="button" onclick="deleteTedaviKart(<?php echo $index; ?>)" style="background: #ef4444; color: white; border: none; padding: 4px 10px; border-radius: 6px; cursor: pointer;">🗑️</button>
                    </div>
                </div>
                <p style="margin: 8px 0 0 0; font-size: 12px; color: #64748b; line-height: 1.4;"><?php echo htmlspecialchars(mb_substr($item['desc'] ?? '', 0, 100)); ?></p>
            </div>
            <!-- Hidden Inputs -->
            <input type="hidden" name="tedavi_kartlari[<?php echo $index; ?>][title]" value="<?php echo htmlspecialchars($item['title'] ?? ''); ?>">
            <input type="hidden" name="tedavi_kartlari[<?php echo $index; ?>][desc]" value="<?php echo htmlspecialchars($item['desc'] ?? ''); ?>">
            <input type="hidden" name="tedavi_kartlari[<?php echo $index; ?>][image]" value="<?php echo htmlspecialchars($item['image'] ?? ''); ?>">
            <input type="hidden" name="tedavi_kartlari[<?php echo $index; ?>][id]" value="<?php echo htmlspecialchars($item['id'] ?? ($index+1)); ?>">
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- TEDAVİ KARTI MODAL -->
    <div id="tedaviKartModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 20px; width: 90%; max-width: 550px; max-height: 90%; overflow: auto;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 16px 20px; border-radius: 20px 20px 0 0; color: white;">
                <h4 style="margin: 0;"><i class="fas fa-plus-circle"></i> Tedavi Kartı Ekle/Düzenle</h4>
            </div>
            <div style="padding: 20px;">
                <div style="margin-bottom: 15px;">
                    <label>Başlık *</label>
                    <input type="text" id="tedaviKartBaslik" class="form-control" placeholder="Örn: İmplant Tedavisi" style="width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 8px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label>Açıklama</label>
                    <textarea id="tedaviKartDesc" rows="3" class="form-control" placeholder="Detaylı açıklama..." style="width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 8px;"></textarea>
                </div>
                <div style="margin-bottom: 15px;">
                    <label>Resim URL</label>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" id="tedaviKartResim" class="form-control" placeholder="https://..." style="flex: 1;">
                        <button type="button" class="upload-btn" onclick="uploadTedaviKartImage()"><i class="fas fa-upload"></i> Seç</button>
                    </div>
                </div>
                <div id="tedaviKartResimPreview" style="margin-top: 10px; display: none; text-align: center;">
                    <img id="tedaviKartResimPreviewImg" src="" style="max-width: 100%; height: 100px; border-radius: 8px; object-fit: cover;">
                </div>
                <div style="margin-top: 10px; font-size: 11px; color: #94a3b8;">
                    <i class="fas fa-info-circle"></i> Önerilen boyut: 800x600px
                </div>
            </div>
            <div style="padding: 16px; background: #f8fafc; border-top: 1px solid #e2e8f0; text-align: right;">
                <button type="button" onclick="closeTedaviKartModal()" style="background: #e2e8f0; border: none; padding: 6px 20px; border-radius: 6px;">İptal</button>
                <button type="button" onclick="saveTedaviKart()" style="background: #3b82f6; border: none; padding: 6px 20px; border-radius: 6px; color: white;">Kaydet</button>
            </div>
        </div>
    </div>
    
	
    <!-- Kaydet Butonu -->
    <div style="margin-top: 24px; text-align: right;">
        <button type="button" class="btn-save" onclick="saveTedaviKartlari()">💾 Tüm Kartları Kaydet</button>
    </div>
    
</div>
             
<!-- ========== SEO AYARLARI TAB ========== -->
<div id="tab-seo" class="tab-pane" style="display: <?php echo $activeTab == 'seo' ? 'block' : 'none'; ?>">
    
    <div style="margin-bottom: 24px; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px;">
        <h3 style="margin: 0; font-size: 18px; font-weight: 600;">
            <i class="fas fa-search" style="color: #3b82f6;"></i> SEO Ayarları
        </h3>
        <p style="margin: 5px 0 0 28px; font-size: 12px; color: #64748b;">Kurumsal sayfasının Google'da nasıl görüneceğini belirleyin</p>
    </div>
    
    <!-- Dil Tabs -->
    <div style="display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">
        <button type="button" class="seo-lang-btn active" data-lang="tr" style="padding: 6px 16px; border: none; background: none; font-weight: 600; color: #3b82f6; border-bottom: 2px solid #3b82f6; cursor: pointer;">🇹🇷 Türkçe</button>
        <button type="button" class="seo-lang-btn" data-lang="en" style="padding: 6px 16px; border: none; background: none; font-weight: 600; color: #94a3b8; border-bottom: 2px solid transparent; cursor: pointer;">🇬🇧 English</button>
    </div>
    
    <!-- TÜRKÇE SEO -->
    <div class="seo-lang-content" data-lang="tr">
        <div style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 20px; margin-bottom: 20px;">
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px;">
                    📌 SEO Başlık (Title) - TR
                    <small style="color: #94a3b8; font-weight: normal; font-size: 10px;">(70 karakter ideal)</small>
                </label>
                <input type="text" name="seo_title_tr" class="form-control" value="<?php echo htmlspecialchars($ayarlar['seo_title_tr'] ?? 'Kurumsal | Prof. Dr. İbrahim Duran | Diş Kliniği Samsun'); ?>" oninput="updateCharCount(this, 'seo_title_tr_count')">
                <div style="font-size: 11px; color: #94a3b8; text-align: right; margin-top: 4px;">
                    <span id="seo_title_tr_count">0</span>/70 karakter
                </div>
            </div>
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px;">
                    📝 Meta Açıklama (Description) - TR
                    <small style="color: #94a3b8; font-weight: normal; font-size: 10px;">(160 karakter ideal)</small>
                </label>
                <textarea name="seo_description_tr" class="form-control" rows="3" oninput="updateCharCount(this, 'seo_description_tr_count')"><?php echo htmlspecialchars($ayarlar['seo_description_tr'] ?? 'Prof. Dr. İbrahim Duran Diş Kliniği kurumsal bilgileri. Vizyonumuz, misyonumuz, değerlerimiz ve Samsun Atakum\'daki tedavi yaklaşımımız.'); ?></textarea>
                <div style="font-size: 11px; color: #94a3b8; text-align: right; margin-top: 4px;">
                    <span id="seo_description_tr_count">0</span>/160 karakter
                </div>
            </div>
            
            <div>
                <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px;">
                    🏷️ Anahtar Kelimeler (Keywords) - TR
                </label>
                <input type="text" name="seo_keywords_tr" class="form-control" value="<?php echo htmlspecialchars($ayarlar['seo_keywords_tr'] ?? 'kurumsal, diş kliniği Samsun, Prof. Dr. İbrahim Duran, diş hekimi kurumsal, Samsun diş hekimi'); ?>" placeholder="Virgül ile ayırın">
            </div>
        </div>
    </div>
    
    <!-- İNGİLİZCE SEO -->
    <div class="seo-lang-content" data-lang="en" style="display: none;">
        <div style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 20px; margin-bottom: 20px;">
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px;">
                    📌 SEO Title - EN
                    <small style="color: #94a3b8; font-weight: normal; font-size: 10px;">(70 characters ideal)</small>
                </label>
                <input type="text" name="seo_title_en" class="form-control" value="<?php echo htmlspecialchars($ayarlar['seo_title_en'] ?? 'Corporate | Prof. Dr. İbrahim Duran | Dental Clinic Samsun'); ?>" oninput="updateCharCount(this, 'seo_title_en_count')">
                <div style="font-size: 11px; color: #94a3b8; text-align: right; margin-top: 4px;">
                    <span id="seo_title_en_count">0</span>/70 characters
                </div>
            </div>
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px;">
                    📝 Meta Description - EN
                    <small style="color: #94a3b8; font-weight: normal; font-size: 10px;">(160 characters ideal)</small>
                </label>
                <textarea name="seo_description_en" class="form-control" rows="3" oninput="updateCharCount(this, 'seo_description_en_count')"><?php echo htmlspecialchars($ayarlar['seo_description_en'] ?? 'Corporate information about Prof. Dr. İbrahim Duran Dental Clinic. Our vision, mission, values and treatment approach in Samsun Atakum.'); ?></textarea>
                <div style="font-size: 11px; color: #94a3b8; text-align: right; margin-top: 4px;">
                    <span id="seo_description_en_count">0</span>/160 characters
                </div>
            </div>
            
            <div>
                <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px;">
                    🏷️ Keywords - EN
                </label>
                <input type="text" name="seo_keywords_en" class="form-control" value="<?php echo htmlspecialchars($ayarlar['seo_keywords_en'] ?? 'corporate, dental clinic Samsun, Prof. Dr. İbrahim Duran, dental corporate, Samsun dentist'); ?>" placeholder="Comma separated">
            </div>
        </div>
    </div>
    
    <!-- ORTAK ALANLAR -->
    <div style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 20px; margin-bottom: 20px;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div>
                <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px;">
                    🖼️ OG Görsel (Sosyal Medya)
                </label>
                <div style="display: flex; gap: 8px;">
                    <input type="text" name="seo_og_image" id="seo_og_image" class="form-control flex-1" value="<?php echo htmlspecialchars($ayarlar['seo_og_image'] ?? '/uploads/kurumsal-og.webp'); ?>" placeholder="/uploads/kurumsal-og.webp">
                    <button type="button" class="upload-btn" onclick="uploadImage('seo_og_image')"><i class="fas fa-upload"></i> Yükle</button>
                </div>
                <div style="font-size: 10px; color: #94a3b8; margin-top: 4px;">
                    <i class="fas fa-info-circle"></i> 1200x630 px önerilir
                </div>
            </div>
            <div>
                <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px;">
                    🔗 Canonical URL
                </label>
                <input type="text" name="seo_canonical" class="form-control" value="<?php echo htmlspecialchars($ayarlar['seo_canonical'] ?? 'https://www.dribrahimdurandentalclinic.com/kurumsal/'); ?>" placeholder="https://...">
                <div style="font-size: 10px; color: #94a3b8; margin-top: 4px;">
                    <i class="fas fa-info-circle"></i> Boş bırakırsanız otomatik oluşur
                </div>
            </div>
        </div>
    </div>
    
    <!-- Google Önizleme -->
    <div style="background: #f8fafc; border-radius: 16px; border: 1px solid #e2e8f0; padding: 20px; margin-bottom: 20px;">
        <div style="font-size: 11px; font-weight: 600; color: #94a3b8; margin-bottom: 8px;">
            <i class="fas fa-eye"></i> Google Arama Sonucu Önizlemesi
        </div>
        <div style="background: white; padding: 12px 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
            <div id="seo_preview_title" style="color: #1a0dab; font-size: 18px; cursor: pointer;">
                <?php echo htmlspecialchars($ayarlar['seo_title_tr'] ?? 'Kurumsal | Prof. Dr. İbrahim Duran | Diş Kliniği Samsun'); ?>
            </div>
            <div id="seo_preview_url" style="color: #006621; font-size: 14px; margin-top: 2px;">
                www.dribrahimdurandentalclinic.com/kurumsal/
            </div>
            <div id="seo_preview_desc" style="color: #545454; font-size: 14px; margin-top: 4px;">
                <?php echo htmlspecialchars($ayarlar['seo_description_tr'] ?? 'Prof. Dr. İbrahim Duran Diş Kliniği kurumsal bilgileri. Vizyonumuz, misyonumuz, değerlerimiz ve Samsun Atakum\'daki tedavi yaklaşımımız.'); ?>
            </div>
        </div>
    </div>
    
    <!-- Kaydet Butonu -->
    <div style="text-align: right;">
        <button type="button" class="btn-save" onclick="saveTab('seo')" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 14px 32px; border-radius: 50px; font-weight: 600;">
            <i class="fas fa-save"></i> SEO Ayarlarını Kaydet
        </button>
    </div>
    
</div>
			 
                </div>
                

            </div>
        </form>
    </div>
</div>
<script>
// ========== SAYFA İLK YÜKLENİŞTE TAB'I KORU ==========
(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const tabFromUrl = urlParams.get('tab');
    
    if (tabFromUrl) {
        // URL parametresini kullan
        document.querySelectorAll('.kurumsal-tab').forEach(t => {
            if (t.getAttribute('data-tab') === tabFromUrl) {
                t.classList.add('active');
            } else {
                t.classList.remove('active');
            }
        });
        document.querySelectorAll('.tab-pane').forEach(pane => {
            if (pane.id === 'tab-' + tabFromUrl) {
                pane.style.display = 'block';
            } else {
                pane.style.display = 'none';
            }
        });
        const activeTabInput = document.getElementById('activeTab');
        if (activeTabInput) activeTabInput.value = tabFromUrl;
    }
})();
const csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';
const ajaxUrl = '/admin/modules/kurumsal/ajax.php';

// ============================================
// 1. RESİM YÜKLEME FONKSİYONLARI
// ============================================

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
        
        fetch(ajaxUrl + '?islem=resim_yukle', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            console.log("SUNUCU CEVABI:", text);
            return JSON.parse(text);
        })
        .then(data => {
if (data.success) {
    document.getElementById(targetId).value = data.url;
    
    // Hekim fotoğrafı ise önizlemeyi güncelle
    if (targetId === 'hekim_foto') {
        const preview = document.getElementById('hekimFotoPreview');
        if (preview) preview.src = data.url;
    }
    
    alert('✅ Resim yüklendi: ' + data.url);
    updatePreview();
} else {
    alert('❌ Hata: ' + data.message);
}
        })
        .catch(err => alert('Bağlantı hatası: ' + err));
    };
    input.click();
}

function uploadImageToInput(btn) {
    const inputField = btn.previousElementSibling;
    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = 'image/*';
    fileInput.onchange = function(e) {
        const file = e.target.files[0];
        if (!file) return;
        const formData = new FormData();
        formData.append('resim', file);
        formData.append('csrf_token', csrfToken);
        
        fetch(ajaxUrl + '?islem=resim_yukle', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            console.log("SUNUCU CEVABI:", text);
            return JSON.parse(text);
        })
        .then(data => {
            if (data.success) {
                inputField.value = data.url;
                alert('✅ Resim yüklendi');
                updatePreview();
            } else {
                alert('❌ Hata: ' + data.message);
            }
        })
        .catch(err => alert('Bağlantı hatası: ' + err));
    };
    fileInput.click();
}

function uploadItemImage(btn, fieldName, index) {
    const inputField = btn.parentElement.parentElement.querySelector(`input[name="${fieldName}[${index}][image]"]`);
    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = 'image/*';
    fileInput.onchange = function(e) {
        const file = e.target.files[0];
        if (!file) return;
        const formData = new FormData();
        formData.append('resim', file);
        formData.append('csrf_token', csrfToken);
        
        fetch(ajaxUrl + '?islem=resim_yukle', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            console.log("SUNUCU CEVABI:", text);
            return JSON.parse(text);
        })
        .then(data => {
            if (data.success) {
                inputField.value = data.url;
                alert('✅ Resim yüklendi');
                updatePreview();
            } else {
                alert('❌ Hata: ' + data.message);
            }
        })
        .catch(err => alert('Bağlantı hatası: ' + err));
    };
    fileInput.click();
}

// ============================================
// 2. GENEL LİSTE İŞLEMLERİ (Vizyon, Misyon, Değerler, Yöntemler)
// ============================================

function addListItem(containerId, fieldName) {
    const text = prompt('Yeni madde girin:');
    if (!text) return;
    const container = document.getElementById(containerId);
    const div = document.createElement('div');
    div.className = 'list-item';
    div.innerHTML = `<span>${escapeHtml(text)}</span><span class="remove" onclick="removeListItem(this)">✖</span><input type="hidden" name="${fieldName}[]" value="${escapeHtml(text)}">`;
    container.appendChild(div);
    updatePreview();
}

function removeListItem(el) {
    if (confirm('Silmek istediğinize emin misiniz?')) {
        el.parentElement.remove();
        updatePreview();
    }
}

function addToList(containerId, fieldName, inputId) {
    const input = document.getElementById(inputId);
    const text = input.value.trim();
    
    if (text === '') {
        alert('Lütfen bir değer girin');
        return;
    }
    
    const container = document.getElementById(containerId);
    const div = document.createElement('div');
    div.className = 'list-item';
    div.style.cssText = 'background:#f8fafc; padding:6px 10px; border-radius:6px; margin-bottom:5px; display:flex; justify-content:space-between; align-items:center;';
    div.innerHTML = `
        <span>${escapeHtml(text)}</span>
        <span class="remove" onclick="removeListItem(this)" style="color:red; cursor:pointer;">✖</span>
        <input type="hidden" name="${fieldName}[]" value="${escapeHtml(text)}">
    `;
    container.appendChild(div);
    
    input.value = '';
    input.focus();
}

// ============================================
// 3. TEDAVİ YÖNTEMLERİ İŞLEMLERİ
// ============================================

function addYontemItem() {
    const index = Date.now();
    const container = document.getElementById('yontemler_liste_container');
    const div = document.createElement('div');
    div.className = 'list-item';
    div.style.flexWrap = 'wrap';
    div.style.gap = '8px';
    div.innerHTML = `
        <div style="flex:1;">
            <input type="text" name="yontemler_liste[${index}][title]" class="form-control" style="width:100%; margin-bottom:5px;" placeholder="Başlık">
            <textarea name="yontemler_liste[${index}][desc]" class="form-control" style="width:100%;" rows="2" placeholder="Açıklama"></textarea>
        </div>
        <div style="display:flex; gap:5px;">
            <button type="button" class="upload-btn" onclick="uploadItemImage(this, 'yontemler_liste', ${index})"><i class="fas fa-image"></i> Resim</button>
            <span class="remove" onclick="removeListItem(this)">✖</span>
        </div>
        <input type="hidden" name="yontemler_liste[${index}][icon]" value="Microscope">
        <input type="hidden" name="yontemler_liste[${index}][image]" value="">
    `;
    container.appendChild(div);
    updatePreview();
}

// ========== DEĞERLERİMİZ ==========

let editingDegerIndex = null;

function openDegerModal() {
    editingDegerIndex = null;
    document.getElementById('degerBaslik').value = '';
    document.getElementById('degerIkon').value = 'HeartPulse';
    document.getElementById('degerDesc').value = '';
    document.getElementById('degerModal').style.display = 'flex';
}

function closeDegerModal() {
    document.getElementById('degerModal').style.display = 'none';
}

function editDeger(index) {
    editingDegerIndex = index;
    const kart = document.querySelector(`.deger-kart[data-index="${index}"]`);
    const baslik = kart.querySelector('input[name*="[title]"]').value;
    const ikon = kart.querySelector('input[name*="[icon]"]').value;
    const desc = kart.querySelector('input[name*="[desc]"]').value;
    
    document.getElementById('degerBaslik').value = baslik;
    document.getElementById('degerIkon').value = ikon;
    document.getElementById('degerDesc').value = desc;
    document.getElementById('degerModal').style.display = 'flex';
}

function deleteDeger(index) {
    if (confirm('Bu değeri silmek istediğinize emin misiniz?')) {
        document.querySelector(`.deger-kart[data-index="${index}"]`).remove();
    }
}

function saveDeger() {
    const baslik = document.getElementById('degerBaslik').value.trim();
    if (!baslik) {
        alert('Başlık giriniz!');
        return;
    }
    
    const yeniDeger = {
        title: baslik,
        icon: document.getElementById('degerIkon').value,
        desc: document.getElementById('degerDesc').value
    };
    
    const mevcutDegerler = [];
    document.querySelectorAll('.deger-kart').forEach(kart => {
        mevcutDegerler.push({
            title: kart.querySelector('input[name*="[title]"]').value,
            icon: kart.querySelector('input[name*="[icon]"]').value,
            desc: kart.querySelector('input[name*="[desc]"]').value
        });
    });
    
    if (editingDegerIndex !== null) {
        mevcutDegerler[editingDegerIndex] = yeniDeger;
    } else {
        mevcutDegerler.push(yeniDeger);
    }
    
    // Sayfayı yenilemeden önce verileri kaydet
    const baslikInput = document.querySelector('input[name="degerler_baslik"]')?.value || '';
    const altInput = document.querySelector('textarea[name="degerler_alt"]')?.value || '';
    
    const saveData = {
        degerler_baslik: baslikInput,
        degerler_alt: altInput,
        degerler_liste: mevcutDegerler,
        csrf_token: csrfToken
    };
    
    fetch(ajaxUrl + '?islem=kaydet', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(saveData)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('✅ Değer kaydedildi! Sayfa yenileniyor...');
            location.reload();
        } else {
            alert('❌ Kayıt hatası: ' + (result.message || 'Bilinmeyen hata'));
        }
    })
    .catch(err => console.error('Hata:', err));
    
    closeDegerModal();
}

function saveDegerler() {
    const degerler = [];
    document.querySelectorAll('.deger-kart').forEach(kart => {
        degerler.push({
            title: kart.querySelector('input[name*="[title]"]').value,
            icon: kart.querySelector('input[name*="[icon]"]').value,
            desc: kart.querySelector('input[name*="[desc]"]').value
        });
    });
    
    const data = {
        degerler_baslik: document.querySelector('input[name="degerler_baslik"]')?.value || '',
        degerler_alt: document.querySelector('textarea[name="degerler_alt"]')?.value || '',
        degerler_liste: degerler,
        csrf_token: csrfToken
    };
    
    const btn = event.target;
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
            alert('✅ Değerler kaydedildi! Sayfa yenileniyor...');
            location.reload();
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

// ========== TEDAVİ KARTLARI (SLAYT) ==========

let editingTedaviKartIndex = null;

function openTedaviKartModal() {
    editingTedaviKartIndex = null;
    document.getElementById('tedaviKartBaslik').value = '';
    document.getElementById('tedaviKartDesc').value = '';
    document.getElementById('tedaviKartResim').value = '';
    document.getElementById('tedaviKartResimPreview').style.display = 'none';
    document.getElementById('tedaviKartModal').style.display = 'flex';
}

function closeTedaviKartModal() {
    const modal = document.getElementById('tedaviKartModal');
    if (modal) {
        modal.style.display = 'none';
    }
    // KESİNLİKLE SAYFA YENİLEME VEYA YÖNLENDİRME YOK
    return false;
}

function editTedaviKart(index) {
    editingTedaviKartIndex = index;
    const kart = document.querySelector(`.tedavi-kart[data-index="${index}"]`);
    const baslik = kart.querySelector('input[name*="[title]"]').value;
    const desc = kart.querySelector('input[name*="[desc]"]').value;
    const resim = kart.querySelector('input[name*="[image]"]').value;
    
    document.getElementById('tedaviKartBaslik').value = baslik;
    document.getElementById('tedaviKartDesc').value = desc;
    document.getElementById('tedaviKartResim').value = resim;
    
    if (resim) {
        document.getElementById('tedaviKartResimPreviewImg').src = resim;
        document.getElementById('tedaviKartResimPreview').style.display = 'block';
    }
    document.getElementById('tedaviKartModal').style.display = 'flex';
}

function deleteTedaviKart(index) {
    if (confirm('Bu kartı silmek istediğinize emin misiniz?')) {
        document.querySelector(`.tedavi-kart[data-index="${index}"]`).remove();
    }
}

function uploadTedaviKartImage() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (!file) return;
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
                document.getElementById('tedaviKartResim').value = data.url;
                document.getElementById('tedaviKartResimPreviewImg').src = data.url;
                document.getElementById('tedaviKartResimPreview').style.display = 'block';
                alert('✅ Resim yüklendi');
            } else {
                alert('Hata: ' + data.message);
            }
        });
    };
    input.click();
}

function saveTedaviKart() {
    const baslik = document.getElementById('tedaviKartBaslik').value.trim();
    if (!baslik) {
        alert('Başlık giriniz!');
        return;
    }
    
    const yeniKart = {
        id: Date.now().toString(),
        title: baslik,
        desc: document.getElementById('tedaviKartDesc').value,
        image: document.getElementById('tedaviKartResim').value || ''
    };
    
    const mevcutKartlar = [];
    document.querySelectorAll('.tedavi-kart').forEach(kart => {
        mevcutKartlar.push({
            id: kart.querySelector('input[name*="[id]"]').value,
            title: kart.querySelector('input[name*="[title]"]').value,
            desc: kart.querySelector('input[name*="[desc]"]').value,
            image: kart.querySelector('input[name*="[image]"]').value
        });
    });
    
    if (editingTedaviKartIndex !== null) {
        mevcutKartlar[editingTedaviKartIndex] = yeniKart;
    } else {
        mevcutKartlar.push(yeniKart);
    }
    
    // DOĞRUDAN VERİTABANINA KAYDET
    const saveData = {
        slayt_baslik: document.querySelector('input[name="slayt_baslik"]')?.value || '',
        tedavi_kartlari: mevcutKartlar,
        csrf_token: csrfToken
    };
    
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Kaydediliyor...';
    btn.disabled = true;
    
    fetch(ajaxUrl + '?islem=kaydet', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(saveData)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('✅ Kaydedildi! Sayfa yenileniyor...');
            location.reload();
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
    
    closeTedaviKartModal();
}

function saveTedaviKartlari() {
    const kartlar = [];
    document.querySelectorAll('.tedavi-kart').forEach(kart => {
        kartlar.push({
            id: kart.querySelector('input[name*="[id]"]').value,
            title: kart.querySelector('input[name*="[title]"]').value,
            desc: kart.querySelector('input[name*="[desc]"]').value,
            image: kart.querySelector('input[name*="[image]"]').value
        });
    });
    
    const data = {
        slayt_baslik: document.querySelector('input[name="slayt_baslik"]')?.value || '',
        tedavi_kartlari: kartlar,
        csrf_token: csrfToken
    };
    
    const btn = event.target;
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
            alert('✅ Tedavi kartları kaydedildi! Sayfa yenileniyor...');
            location.reload();
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

// Sürükle-sıralama için Sortable
document.addEventListener('DOMContentLoaded', function() {
    const grid = document.getElementById('tedaviKartlariGrid');
    if (grid && typeof Sortable !== 'undefined') {
        new Sortable(grid, {
            handle: '.tedavi-kart',
            animation: 300,
            onEnd: function() {
                // Sıralama değişince input name'leri güncelle
                document.querySelectorAll('.tedavi-kart').forEach((kart, newIndex) => {
                    kart.setAttribute('data-index', newIndex);
                    const oldIndex = kart.querySelector('input[name*="[id]"]').value;
                    kart.querySelector('input[name*="[title]"]').name = `tedavi_kartlari[${newIndex}][title]`;
                    kart.querySelector('input[name*="[desc]"]').name = `tedavi_kartlari[${newIndex}][desc]`;
                    kart.querySelector('input[name*="[image]"]').name = `tedavi_kartlari[${newIndex}][image]`;
                    kart.querySelector('input[name*="[id]"]').name = `tedavi_kartlari[${newIndex}][id]`;
                });
            }
        });
    }
});
// ========== UZMANLIK ALANLARI - YENİ TABLO ==========


// Modal
function openUzmanlikModal() {
    document.getElementById('uzmanlikModal').style.display = 'flex';
    document.getElementById('yeni_grup_baslik').value = '';
    document.getElementById('yeni_grup_maddeler').value = '';
}
function closeUzmanlikModal() {
    document.getElementById('uzmanlikModal').style.display = 'none';
}

// Yeni grup ekle (API'ye POST)
function addNewUzmanlikGroupFromModal() {
    const baslik = document.getElementById('yeni_grup_baslik').value.trim();
    const maddelerText = document.getElementById('yeni_grup_maddeler').value;
    if (!baslik) { alert('Başlık girin'); return; }
    const maddeler = maddelerText.split('\n').filter(m => m.trim() !== '');
    
    fetch('/api/uzmanlik-gruplari.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ baslik: baslik, maddeler: maddeler })
    })
    .then(res => res.json())
    .then(() => location.reload());
    closeUzmanlikModal();
}

// Grup sil (data-grup-id kullanıyor)
function removeUzmanlikGroup(btn, grupId) {
    if (confirm('Bu grubu silmek istediğinize emin misiniz?')) {
        fetch(`/api/uzmanlik-gruplari.php?id=${grupId}`, { method: 'DELETE' })
            .then(() => location.reload());
    }
}

// Madde ekle - (uzmanlik-kart içinde)
function addMadde(btn, grupId) {
    const kart = btn.closest('.uzmanlik-kart');
    const input = kart.querySelector('.yeni-madde-input');
    const text = input.value.trim();
    if (!text) return;
    
    const liste = kart.querySelector('.uzmanlik-madde-listesi');
    const div = document.createElement('div');
    div.className = 'madde-item';
    div.style.cssText = 'background: white; border-radius: 10px; padding: 8px 12px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #e2e8f0;';
    div.innerHTML = `
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-check-circle" style="color: #10b981; font-size: 14px;"></i>
            <span class="madde-text">${escapeHtml(text)}</span>
        </div>
        <span class="remove-madde" onclick="removeMadde(this, ${grupId})" style="color: #ef4444; cursor: pointer;">✖</span>
        <input type="hidden" class="madde-value" value="${escapeHtml(text)}">
    `;
    liste.appendChild(div);
    input.value = '';
    
    const sayiSpan = kart.querySelector('.madde-sayisi');
    sayiSpan.innerText = parseInt(sayiSpan.innerText) + 1;
    
    // OTOMATİK KAYDET
    const gruplar = [];
    document.querySelectorAll('.uzmanlik-kart').forEach(k => {
        const id = k.getAttribute('data-grup-id');
        const baslik = k.querySelector('.grup-baslik-input').value;
        const maddeler = [];
        k.querySelectorAll('.madde-value').forEach(m => maddeler.push(m.value));
        gruplar.push({ id: id, baslik: baslik, maddeler: maddeler });
    });
    
    fetch('/api/uzmanlik-gruplari.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ gruplar: gruplar })
    })
    .then(() => console.log('Otomatik kaydedildi'))
    .catch(err => console.error('Kayıt hatası:', err));
}

// Madde sil
function removeMadde(el, grupId) {
    if (!confirm('Bu maddeyi silmek istediğinize emin misiniz?')) return;
    
    const kart = el.closest('.uzmanlik-kart');
    const item = el.closest('.madde-item');
    const baslik = kart.querySelector('.grup-baslik-input').value;
    
    // Silinecek maddeyi kaldır
    item.remove();
    
    // Kalan maddeleri topla
    const maddeler = [];
    kart.querySelectorAll('.madde-value').forEach(m => maddeler.push(m.value));
    
    // Sayacı güncelle
    const sayiSpan = kart.querySelector('.madde-sayisi');
    if (sayiSpan) sayiSpan.innerText = maddeler.length;
    
    // API'ye kaydet
    fetch('/api/uzmanlik-gruplari.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            gruplar: [{
                id: grupId,
                baslik: baslik,
                maddeler: maddeler
            }]
        })
    })
    .then(res => res.json())
    .then(() => {
        console.log('✅ Madde silindi ve kaydedildi');
    })
    .catch(err => {
        console.error('Kayıt hatası:', err);
        alert('Kaydedilirken hata oluştu, sayfa yenileniyor...');
        location.reload();
    });
}

// Tüm grupları kaydet
function kaydetTumGruplar() {
    const gruplar = [];
    document.querySelectorAll('.uzmanlik-kart').forEach(kart => {
        const id = kart.getAttribute('data-grup-id');
        const baslik = kart.querySelector('.grup-baslik-input').value;
        const maddeler = [];
        kart.querySelectorAll('.madde-value').forEach(m => maddeler.push(m.value));
        gruplar.push({ id: id, baslik: baslik, maddeler: maddeler });
    });
    
    fetch('/api/uzmanlik-gruplari.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ gruplar: gruplar })
    })
    .then(res => res.json())
    .then(() => { alert('✅ Kaydedildi!'); location.reload(); });
}

// Enter tuşu ile madde ekleme (yeni-madde-input için)
document.addEventListener('keypress', function(e) {
    if (e.target.classList.contains('yeni-madde-input') && e.key === 'Enter') {
        e.preventDefault();
        const btn = e.target.parentElement.querySelector('.madde-ekle-btn');
        if (btn) btn.click();
    }
});

// ============================================
// 8. KAYDETME ve GENEL FONKSİYONLAR
// ============================================

function saveTab(tabName) {
    // YÖNTEMLER TAB'I İÇİN AYRI FONKSİYON KULLAN
    if (tabName === 'yontemler') {
        saveYontemlerAjax();
        return;
    }
        if (editorMode === 'html') {
        const htmlArea = document.getElementById('hakkimizda_yazi_html');
        const hiddenInput = document.getElementById('hakkimizda_yazi');
        if (htmlArea && hiddenInput) {
            hiddenInput.value = htmlArea.value;
        }
    }
    // DİĞER TABLAR İÇİN (sayfa yenilemeli)
    const activePane = document.getElementById('tab-' + tabName);
    const inputs = activePane.querySelectorAll('[name]');
    const data = {};
    
    inputs.forEach(input => {
        const name = input.name;
        let value = input.value;
        if (input.type === 'checkbox') value = input.checked ? 1 : 0;
        
        if (data[name]) {
            if (!Array.isArray(data[name])) data[name] = [data[name]];
            data[name].push(value);
        } else {
            data[name] = value;
        }
    });
    
    data.csrf_token = csrfToken;
    
    const btn = event.target;
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
            alert('✅ Kaydedildi!');
            // AYNI TAB'A YÖNLENDİR - KESİN ÇÖZÜM
            window.location.href = window.location.pathname + '?modul=kurumsal&tab=' + tabName;
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


function updatePreview() {
    const preview = document.getElementById('previewContent');
    
    // Eğer sayfada previewContent diye bir yer yoksa, fonksiyonu burada öldür.
    // Bu sayede hata vermez, kodun geri kalanı çalışmaya devam eder.
    if (!preview) return;

    const activeTab = document.querySelector('.kurumsal-tab.active')?.getAttribute('data-tab');
    let html = '';
    
    if (activeTab === 'hakkimizda') {
        const baslik = document.querySelector('[name="hakkimizda_baslik"]')?.value || '';
        const yazi = document.querySelector('[name="hakkimizda_yazi"]')?.value || '';
        html = `<div style="padding:10px;"><h3>${escapeHtml(baslik)}</h3><div>${yazi}</div></div>`;
    } else {
        html = `<div style="padding:10px;">${activeTab ? activeTab.toUpperCase() : '...' } verileri burada görünecek</div>`;
    }
    
    preview.innerHTML = html;
}

function showAlert(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.style.cssText = `
        position: fixed; bottom: 20px; right: 20px;
        background: ${type === 'success' ? '#10b981' : type === 'warning' ? '#f59e0b' : '#3b82f6'};
        color: white; padding: 12px 20px; border-radius: 10px; font-size: 13px;
        z-index: 9999; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideIn 0.3s ease;
    `;
    alertDiv.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-info-circle'}"></i> ${message}`;
    document.body.appendChild(alertDiv);
    setTimeout(() => alertDiv.remove(), 2000);
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

// ============================================
// 9. CSS ANİMASYONLARI
// ============================================

const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    .uzmanlik-item:hover {
        transform: translateX(2px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .remove-item:hover {
        background: #fee2e2;
        transform: scale(1.1);
    }
`;
document.head.appendChild(style);

// ============================================
// 10. TAB GEÇİŞLERİ ve ENTER DİNLEYİCİLERİ
// ============================================

document.querySelectorAll('.kurumsal-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const tabName = this.getAttribute('data-tab');
        document.querySelectorAll('.kurumsal-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        document.querySelectorAll('.tab-pane').forEach(pane => pane.style.display = 'none');
        document.getElementById('tab-' + tabName).style.display = 'block';
        document.getElementById('activeTab').value = tabName;
        updatePreview();
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tabName);
        window.history.pushState({}, '', url);
    });
});

// Vizyon kutusu enter tuşu
document.getElementById('vizyon_kutu_input')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        addToList('vizyon_kutu_listesi', 'vizyon_kutu', 'vizyon_kutu_input');
    }
});

// Uzmanlık grupları enter tuşu (1-3 arası)
<?php for($g=1; $g<=3; $g++): ?>
document.getElementById('uzmanlik_grup<?php echo $g; ?>_input')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        addUzmanlikItem(<?php echo $g; ?>);
    }
});
<?php endfor; ?>

// Karakter sayacı
const yaziTextarea = document.querySelector('textarea[name="hakkimizda_yazi"]');
if (yaziTextarea) {
    function updateCharCount() {
        const count = yaziTextarea.value.length;
        const counter = document.getElementById('karakterSayaci');
        if (counter) {
            counter.innerHTML = count + ' karakter';
            if (count > 5000) {
                counter.style.color = '#ef4444';
            } else if (count > 3000) {
                counter.style.color = '#f59e0b';
            } else {
                counter.style.color = '#94a3b8';
            }
        }
    }
    yaziTextarea.addEventListener('input', updateCharCount);
    updateCharCount();
}

// Tüm input ve textarea'lar için önizleme
document.querySelectorAll('input, textarea').forEach(el => {
    el.addEventListener('input', updatePreview);
});

// ========== QUILL EDITOR ==========
let quillEditor = null;

function initQuillEditor() {
    const editorContainer = document.getElementById('hakkimizda_yazi_editor');
    if (!editorContainer) return;
    
    // SADECE basit HTML'i Quill'e yükle (article, header vs. varsa dokunma)
    const existingContent = document.getElementById('hakkimizda_yazi').value || '';
    
    quillEditor = new Quill(editorContainer, {
        theme: 'snow',
        placeholder: 'Metninizi buraya yazın...',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'align': [] }],
                ['link', 'clean'],
                [{ 'direction': 'rtl' }]
            ]
        }
    });
    
    // ⚠️ ÖNEMLİ: text-change SADECE görsel moddayken gizli input'u güncelle
    quillEditor.on('text-change', function() {
        if (editorMode === 'gorsel') {
            const htmlContent = quillEditor.root.innerHTML;
            document.getElementById('hakkimizda_yazi').value = htmlContent;
        }
    });
}
// Sayfa yüklendiğinde Quill'i başlat
document.addEventListener('DOMContentLoaded', function() {
    initQuillEditor();
});

// Form submit öncesi içeriği güncelle (tekrar etmesin diye kontrol)
const form = document.getElementById('kurumsalForm');
if (form) {
    form.addEventListener('submit', function() {
        if (quillEditor) {
            document.getElementById('hakkimizda_yazi').value = quillEditor.root.innerHTML;
        }
    });
}
// ========== TEDAVİ YÖNTEMLERİ ==========

let editingYontemIndex = null;

function openYontemModal() {
    editingYontemIndex = null;
    document.getElementById('yontemBaslik').value = '';
    document.getElementById('yontemIkon').value = 'Microscope';
    document.getElementById('yontemDesc').value = '';
    document.getElementById('yontemResim').value = '';
    document.getElementById('yontemResimPreview').style.display = 'none';
    document.getElementById('yontemModal').style.display = 'flex';
}

function closeYontemModal() {
    document.getElementById('yontemModal').style.display = 'none';
}

function editYontem(index) {
    editingYontemIndex = index;
    const kart = document.querySelector(`.yontem-kart[data-index="${index}"]`);
    const baslik = kart.querySelector('input[name*="[title]"]').value;
    const ikon = kart.querySelector('input[name*="[icon]"]').value;
    const desc = kart.querySelector('input[name*="[desc]"]').value;
    const resim = kart.querySelector('input[name*="[image]"]').value;
    
    document.getElementById('yontemBaslik').value = baslik;
    document.getElementById('yontemIkon').value = ikon;
    document.getElementById('yontemDesc').value = desc;
    document.getElementById('yontemResim').value = resim;
    
    if (resim) {
        document.getElementById('yontemResimPreviewImg').src = resim;
        document.getElementById('yontemResimPreview').style.display = 'block';
    }
    document.getElementById('yontemModal').style.display = 'flex';
}

function deleteYontem(index) {
    if (confirm('Bu yöntemi silmek istediğinize emin misiniz?')) {
        document.querySelector(`.yontem-kart[data-index="${index}"]`).remove();
    }
}

function uploadYontemImage() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (!file) return;
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
                document.getElementById('yontemResim').value = data.url;
                document.getElementById('yontemResimPreviewImg').src = data.url;
                document.getElementById('yontemResimPreview').style.display = 'block';
                alert('✅ Resim yüklendi');
            } else {
                alert('Hata: ' + data.message);
            }
        });
    };
    input.click();
}

function saveYontem() {
    const baslik = document.getElementById('yontemBaslik').value.trim();
    if (!baslik) {
        alert('Başlık giriniz!');
        return;
    }
    
    const yeniYontem = {
        title: baslik,
        icon: document.getElementById('yontemIkon').value,
        desc: document.getElementById('yontemDesc').value,
        image: document.getElementById('yontemResim').value || ''
    };
    
    const mevcutYontemler = [];
    document.querySelectorAll('.yontem-kart').forEach(kart => {
        mevcutYontemler.push({
            title: kart.querySelector('input[name*="[title]"]').value,
            icon: kart.querySelector('input[name*="[icon]"]').value,
            desc: kart.querySelector('input[name*="[desc]"]').value,
            image: kart.querySelector('input[name*="[image]"]').value
        });
    });
    
    if (editingYontemIndex !== null) {
        mevcutYontemler[editingYontemIndex] = yeniYontem;
    } else {
        mevcutYontemler.push(yeniYontem);
    }
    
    // Sayfayı yenilemeden önce verileri kaydet
    const baslikInput = document.querySelector('input[name="yontemler_baslik"]')?.value || '';
    const altInput = document.querySelector('input[name="yontemler_alt"]')?.value || '';
    
    const saveData = {
        yontemler_baslik: baslikInput,
        yontemler_alt: altInput,
        yontemler_liste: mevcutYontemler,
        csrf_token: csrfToken
    };
    
    fetch(ajaxUrl + '?islem=kaydet', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(saveData)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('✅ Yöntem kaydedildi! Sayfa yenileniyor...');
            location.reload();
        } else {
            alert('❌ Kayıt hatası: ' + (result.message || 'Bilinmeyen hata'));
        }
    })
    .catch(err => console.error('Hata:', err));
    
    closeYontemModal();
}

function saveYontemlerAjax() {
    const yontemler = [];
    document.querySelectorAll('.yontem-kart').forEach(kart => {
        yontemler.push({
            title: kart.querySelector('input[name*="[title]"]').value,
            icon: kart.querySelector('input[name*="[icon]"]').value,
            desc: kart.querySelector('input[name*="[desc]"]').value,
            image: kart.querySelector('input[name*="[image]"]').value
        });
    });
    
    const data = {
        yontemler_baslik: document.querySelector('input[name="yontemler_baslik"]')?.value || '',
        yontemler_alt: document.querySelector('input[name="yontemler_alt"]')?.value || '',
        yontemler_liste: yontemler,
        csrf_token: csrfToken
    };
    
    const btn = event.target;
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
            alert('✅ Yöntemler kaydedildi! Sayfa yenileniyor...');
            location.reload();
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

// ========== HEKİM PROFİLİ ==========

// ========== HEKİM PROFİLİ ÖNİZLEME ==========
const hekimFotoInput = document.getElementById('hekim_foto');
if (hekimFotoInput) {
    hekimFotoInput.addEventListener('change', function() {
        const preview = document.getElementById('hekimFotoPreview');
        if (preview && this.value) {
            preview.src = this.value;
        }
    });
}

// Badge önizleme (isteğe bağlı)
function updateHekimPreview() {
    const unvan = document.querySelector('input[name="hekim_unvan"]')?.value || '';
    const isim = document.querySelector('input[name="hekim_isim"]')?.value || '';
    // const previewDiv = document.getElementById('hekimPreviewInfo');
    // if (previewDiv) {
        // previewDiv.innerHTML = `<strong>${unvan} ${isim}</strong>`;
    // }
}

// Badge input'larını dinle
for (let i = 1; i <= 4; i++) {
    const badgeInput = document.querySelector(`input[name="hekim_badge_${i}"]`);
    if (badgeInput) {
        badgeInput.addEventListener('input', updateHekimPreview);
    }
}

// Ünvan ve isim değişikliklerini dinle
const unvanInput = document.querySelector('input[name="hekim_unvan"]');
const isimInput = document.querySelector('input[name="hekim_isim"]');
if (unvanInput) unvanInput.addEventListener('input', updateHekimPreview);
if (isimInput) isimInput.addEventListener('input', updateHekimPreview);



document.querySelectorAll('.kurumsal-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const tabName = this.getAttribute('data-tab');
        // Session'a kaydetmek için AJAX isteği
        fetch(ajaxUrl + '?islem=set_tab&tab=' + tabName, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ tab: tabName, csrf_token: csrfToken })
        });
        
        // ... mevcut kod devam ediyor ...
    });
});

// ========== TEDAVİLERİ KAYDETME FONKSİYONU (KURŞUN GEÇİRMEZ VERSİYON) ==========
function saveTedaviler(e) {
    // Eğer event fırlatılmışsa bodoslama yakala ve tarayıcıyı dizginle reis
    if (e) {
        e.preventDefault();
    }

    const tedaviler = {};
    // Veri tabanındaki key'leri ve textarea içeriklerini nizamla topla reis
    document.querySelectorAll('#tab-tedaviler .tedavi-kart').forEach(kart => {
        const key = kart.getAttribute('data-key');
        const textarea = kart.querySelector('textarea');
        if (key && textarea) {
            tedaviler[key] = textarea.value;
        }
    });
    
    // Sadece bu taba ait olan spesifik inputları nokta atışı çekiyoruz reis
    const baslikInput = document.querySelector('#tab-tedaviler input[name="tedaviler_baslik"]');
    const altInput = document.querySelector('#tab-tedaviler input[name="tedaviler_alt"]');

    const data = {
        tedaviler_baslik: baslikInput ? baslikInput.value : 'Tedavilerimiz',
        tedaviler_alt: altInput ? altInput.value : '',
        tedaviler_metin: tedaviler, // JSON.stringify yapmıyoruz, backend direkt array bekliyor reis
        csrf_token: csrfToken
    };
    
    // Butonu DOM üzerinden güvenli şekilde yakala, event.target tuzağına düşme reis!
    const btn = document.getElementById('tedaviKaydetBtn');
    let originalText = '💾 Tedavileri Kaydet';
    
    if (btn) {
        originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Kaydediliyor...';
        btn.disabled = true;
    }
    
    console.log('Mühendisbey AI Entegrasyonu ile Veri Post Ediliyor:', data);
    
    fetch(ajaxUrl + '?islem=kaydet', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Mühendis bey sunucu HTTP hatası fırlattı: ' + response.status);
        }
        return response.json();
    })
    .then(result => {
        if (result.success) {
            alert('✅ Tedaviler başarıyla veri tabanına mühürlendi! Sayfa yenileniyor...');
            // Kullanıcı hangi tabdaysa sayfa yenilenince o gıcır gıcır taba geri dönsün reis
            window.location.href = window.location.pathname + '?modul=kurumsal&tab=tedaviler';
        } else {
            alert('❌ Hata oluştu: ' + (result.message || 'Bilinmeyen CSRF veya Yetki hatası'));
            if (btn) {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }
    })
    .catch(err => {
        alert('❌ Bağlantı veya Sunucu Hatası: ' + err.message);
        if (btn) {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });
}

// ========== TEDAVİLERİ KAYDETME FONKSİYONU (ÇALIŞAN) ==========
document.getElementById('tedaviKaydetBtn')?.addEventListener('click', function(e) {
    e.preventDefault();
    
    const tedaviler = {};
    document.querySelectorAll('#tab-tedaviler .tedavi-content').forEach(textarea => {
        const key = textarea.getAttribute('data-key');
        if (key) {
            tedaviler[key] = textarea.value;
        }
    });
    
    const data = {
        tedaviler_baslik: document.getElementById('tedaviler_baslik')?.value || 'Tedavilerimiz',
        tedaviler_alt: document.getElementById('tedaviler_alt')?.value || '',
        tedaviler_metin: tedaviler,
        csrf_token: csrfToken
    };
    
    const btn = this;
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
            alert('✅ Tedaviler başarıyla kaydedildi! Sayfa yenileniyor...');
            window.location.href = window.location.pathname + '?modul=kurumsal&tab=tedaviler';
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
});
// Sayfa yüklenince önizleme
updatePreview();


// ========== SEO DİL TABS ==========
document.querySelectorAll('.seo-lang-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.seo-lang-btn').forEach(b => {
            b.style.color = '#94a3b8';
            b.style.borderBottom = '2px solid transparent';
        });
        this.style.color = '#3b82f6';
        this.style.borderBottom = '2px solid #3b82f6';
        
        const lang = this.dataset.lang;
        document.querySelectorAll('.seo-lang-content').forEach(el => {
            el.style.display = el.dataset.lang === lang ? 'block' : 'none';
        });
    });
});

// ========== KARAKTER SAYACI ==========
function updateCharCount(el, counterId) {
    const counter = document.getElementById(counterId);
    if (counter) {
        const max = el.maxLength || 160;
        counter.textContent = el.value.length;
        counter.style.color = el.value.length > max ? '#ef4444' : '#94a3b8';
    }
}
// ============================================
// HTML KAYNAK MODU - Quill Editör Değiştirici
// ============================================
let editorMode = 'gorsel';

function switchEditorMode(mode) {
    editorMode = mode;
    const quillDiv = document.getElementById('hakkimizda_yazi_editor');
    const htmlArea = document.getElementById('hakkimizda_yazi_html');
    const hiddenInput = document.getElementById('hakkimizda_yazi');
    const btnGorsel = document.getElementById('btn_gorsel_mod');
    const btnHtml = document.getElementById('btn_html_mod');
    
    if (!quillDiv || !htmlArea) return;
    
    if (mode === 'html') {
        // Quill'deki mevcut içeriği HTML textarea'ya taşı
        let currentHtml = '';
        if (quillEditor) {
            currentHtml = quillEditor.root.innerHTML;
        } else {
            currentHtml = hiddenInput.value || '';
        }
        // Quill'in boş içerik gösterimini temizle
        if (currentHtml === '<p><br></p>' || currentHtml === '<p></p>') {
            currentHtml = '';
        }
        htmlArea.value = currentHtml;
        hiddenInput.value = currentHtml;
        
        // Görünürlük değiştir
        quillDiv.style.display = 'none';
        htmlArea.style.display = 'block';
        
        // Buton stilleri
        btnGorsel.style.background = '#e2e8f0';
        btnGorsel.style.color = '#475569';
        btnHtml.style.background = '#3b82f6';
        btnHtml.style.color = 'white';
        
    } else {
        // HTML textarea'daki içeriği Quill'e taşı
        const htmlContent = htmlArea.value;
        if (quillEditor) {
            quillEditor.root.innerHTML = htmlContent || '<p><br></p>';
        }
        hiddenInput.value = htmlContent;
        
        // Görünürlük değiştir
        quillDiv.style.display = 'block';
        htmlArea.style.display = 'none';
        
        // Buton stilleri
        btnGorsel.style.background = '#3b82f6';
        btnGorsel.style.color = 'white';
        btnHtml.style.background = '#e2e8f0';
        btnHtml.style.color = '#475569';
    }
}

// HTML textarea'da yazı yazıldıkça gizli input'u güncelle
// Sayfa açılışında: içerik varsa HTML modunda başlat
document.addEventListener('DOMContentLoaded', function() {
    const htmlArea = document.getElementById('hakkimizda_yazi_html');
    const hiddenInput = document.getElementById('hakkimizda_yazi');
    
    if (!htmlArea || !hiddenInput) return;
    
    // Mevcut içeriği HTML textarea'ya yükle
    const mevcutIcerik = hiddenInput.value || '';
    htmlArea.value = mevcutIcerik;
    
    // Yazıldıkça gizli input'u senkronize et
    htmlArea.addEventListener('input', function() {
        hiddenInput.value = this.value;
    });
    
    // İçerik varsa HTML modu ile başlat (Quill'i tetikleme)
    if (mevcutIcerik.trim() !== '') {
        setTimeout(function() {
            switchEditorMode('html');
        }, 100);
    }
});

// ========== SEO ÖNİZLEME ==========
document.querySelector('input[name="seo_title_tr"]')?.addEventListener('input', function() {
    document.getElementById('seo_preview_title').textContent = this.value || 'Başlık Giriniz';
});
document.querySelector('textarea[name="seo_description_tr"]')?.addEventListener('input', function() {
    document.getElementById('seo_preview_desc').textContent = this.value || 'Açıklama Giriniz';
});
</script>