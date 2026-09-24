<?php
// blog-content.php
$kategori_slug = isset($_GET['slug']) ? $_GET['slug'] : '';
$search_query = isset($_GET['q']) ? $_GET['q'] : '';
$aktif_kategori_adi = '';

// Eğer kategori_slug varsa ve 'tumu' değilse
if(!empty($kategori_slug) && $kategori_slug != 'tumu') {
    // Önce veritabanından kategori_slug'a göre ara
    $stmt = $db->prepare("SELECT kategori_adi FROM blog_kategoriler WHERE kategori_slug = ? AND durum = 1");
    $stmt->execute([$kategori_slug]);
    $kategori_adi = $stmt->fetchColumn();
    
    if($kategori_adi) {
        $aktif_kategori_adi = $kategori_adi;
    } else {
        // Eğer veritabanında yoksa, case-insensitive karşılaştırma yap
        foreach($kategoriler_db as $kat) {
            if(strtolower($kat['kategori_adi']) == strtolower($kategori_slug)) {
                $aktif_kategori_adi = $kat['kategori_adi'];
                break;
            }
        }
    }
}

// Yazıları filtrele (case-insensitive)
$filtrelenmis_yazilar = [];
foreach($blog_yazilar as $yazi) {
    $kategori_uygun = (empty($aktif_kategori_adi) || strtolower($yazi['kategori']) == strtolower($aktif_kategori_adi));
    $arama_uygun = empty($search_query) || 
                    stripos($yazi['baslik'], $search_query) !== false || 
                    stripos($yazi['ozet'], $search_query) !== false;
    if($kategori_uygun && $arama_uygun) {
        $filtrelenmis_yazilar[] = $yazi;
    }
}

$total_posts = count($filtrelenmis_yazilar);
$gosterilecek_yazilar = $filtrelenmis_yazilar;
?>

<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">
            <?php if(!empty($search_query)): ?>
                🔍 "<?php echo htmlspecialchars($search_query); ?>" için sonuçlar
            <?php else: ?>
                <?php echo empty($aktif_kategori_adi) ? 'Tüm Yazılar' : htmlspecialchars($aktif_kategori_adi); ?>
            <?php endif; ?>
            <span class="text-blue-600 text-lg ml-2">(<?php echo $total_posts; ?> makale)</span>
        </h1>
        <?php if(!empty($search_query)): ?>
        <a href="?kategori=tumu" class="text-sm text-gray-500 hover:text-blue-600">✕ Temizle</a>
        <?php endif; ?>
    </div>
    
    <div class="space-y-6">
        <?php if(count($gosterilecek_yazilar) > 0): ?>
            <?php foreach($gosterilecek_yazilar as $post): 
                // Post verisini hazırla
                $post_js = $post;
                $post_js['baslik'] = t_cevir($post['baslik']);
                $post_js['ozet'] = t_cevir($post['ozet']);
                $post_js['icerik'] = t_cevir($post['icerik']);
                $post_js['kategori'] = t_cevir($post['kategori']);
            ?>
            <div class="bg-white rounded-xl shadow overflow-hidden hover:shadow-lg transition">
                <div class="md:flex">
                    <div class="md:w-1/3 h-48 md:h-auto">
                        <img src="<?php echo htmlspecialchars($post['resim']); ?>" class="w-full h-full object-cover">
                    </div>
                    <div class="p-5 md:w-2/3">
                        <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                            <span><?php echo date('d.m.Y', strtotime($post['created_at'])); ?></span>
                            <span>•</span>
                            <span class="text-blue-600"><?php echo htmlspecialchars(t_cevir($post['kategori'])); ?></span>
                        </div>
                        <h2 class="text-xl font-bold mb-2 hover:text-blue-600 cursor-pointer" onclick="openModal(<?php echo htmlspecialchars(json_encode($post_js)); ?>)"><?php echo htmlspecialchars(t_cevir($post['baslik'])); ?></h2>
                        <p class="text-gray-600 mb-4 line-clamp-2"><?php echo htmlspecialchars(t_cevir($post['ozet'])); ?></p>
                        <button onclick="openModal(<?php echo htmlspecialchars(json_encode($post_js)); ?>)" class="text-blue-600 font-semibold">Devamını Oku →</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-12">
                <div class="text-6xl mb-4">🔍</div>
                <h3 class="text-xl font-semibold text-gray-700">Sonuç bulunamadı</h3>
                <p class="text-gray-500 mt-2"><?php echo !empty($search_query) ? '"' . htmlspecialchars($search_query) . '" ile ilgili yazı bulunamadı.' : 'Bu kategoride henüz yazı yok.'; ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>