<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
kontrol();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$soru = '';
$cevap = '';
$kategori = 'Genel';
$sira = 0;

if($id > 0) {
    $stmt = $db->prepare("SELECT * FROM sss WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if($row) {
        $soru = $row['soru'];
        $cevap = $row['cevap'];
        $kategori = $row['kategori'];
        $sira = $row['sira'];
    }
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $soru = trim($_POST['soru'] ?? '');
    $cevap = trim($_POST['cevap'] ?? '');
    $kategori = $_POST['kategori'] ?? 'Genel';
    $sira = intval($_POST['sira'] ?? 0);
    
    if($id > 0) {
        $stmt = $db->prepare("UPDATE sss SET soru = ?, cevap = ?, kategori = ?, sira = ? WHERE id = ?");
        $stmt->execute([$soru, $cevap, $kategori, $sira, $id]);
        $_SESSION['mesaj'] = 'Soru güncellendi.';
    } else {
        $stmt = $db->prepare("INSERT INTO sss (soru, cevap, kategori, sira) VALUES (?, ?, ?, ?)");
        $stmt->execute([$soru, $cevap, $kategori, $sira]);
        $_SESSION['mesaj'] = 'Soru eklendi.';
    }
    header('Location: ?modul=sss');
    exit;
}
?>

<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-xl font-bold text-gray-800"><?php echo $id > 0 ? 'Soru Düzenle' : 'Yeni Soru Ekle'; ?></h2>
        <a href="?modul=sss" class="px-3 py-1.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
            <i class="fas fa-arrow-left"></i> Geri
        </a>
    </div>
    
    <div class="p-6">
        <form method="POST">
            <div class="mb-4">
                <label class="block text-sm font-semibold mb-1">Kategori</label>
                <select name="kategori" class="w-full md:w-1/3 px-3 py-2 border rounded-lg">
                    <option value="Genel" <?php echo $kategori == 'Genel' ? 'selected' : ''; ?>>Genel</option>
                    <option value="İmplant Tedavisi" <?php echo $kategori == 'İmplant Tedavisi' ? 'selected' : ''; ?>>İmplant Tedavisi</option>
                    <option value="Estetik Diş Hekimliği" <?php echo $kategori == 'Estetik Diş Hekimliği' ? 'selected' : ''; ?>>Estetik Diş Hekimliği</option>
                    <option value="Ortodonti" <?php echo $kategori == 'Ortodonti' ? 'selected' : ''; ?>>Ortodonti</option>
                    <option value="Protetik Diş Tedavisi" <?php echo $kategori == 'Protetik Diş Tedavisi' ? 'selected' : ''; ?>>Protetik Diş Tedavisi</option>
                    <option value="Periodontoloji" <?php echo $kategori == 'Periodontoloji' ? 'selected' : ''; ?>>Periodontoloji</option>
                    <option value="Endodonti" <?php echo $kategori == 'Endodonti' ? 'selected' : ''; ?>>Endodonti</option>
                    <option value="Kurumsal" <?php echo $kategori == 'Kurumsal' ? 'selected' : ''; ?>>Kurumsal</option>
                    <option value="Ücretlendirme" <?php echo $kategori == 'Ücretlendirme' ? 'selected' : ''; ?>>Ücretlendirme</option>
                </select>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-semibold mb-1">Soru</label>
                <input type="text" name="soru" value="<?php echo htmlspecialchars($soru); ?>" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-semibold mb-1">Cevap</label>
                <textarea name="cevap" rows="6" required class="w-full px-3 py-2 border rounded-lg"><?php echo htmlspecialchars($cevap); ?></textarea>
                <p class="text-xs text-gray-400 mt-1">Detaylı ve açıklayıcı bir cevap yazın.</p>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-semibold mb-1">Sıra</label>
                <input type="number" name="sira" value="<?php echo $sira; ?>" class="w-32 px-3 py-2 border rounded-lg">
                <p class="text-xs text-gray-400 mt-1">Küçük sayı önce gösterilir.</p>
            </div>
            
            <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-save"></i> Kaydet
            </button>
        </form>
    </div>
</div>