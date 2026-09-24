<?php
// Doğru yol: site/ klasöründen 4 değil, 2 yukarı çıkmak yeterli
require_once dirname(__DIR__, 2) . '/inc/config.php';

$page_title = "{{PAGE_TITLE}} | " . ($site_baslik ?? 'Site Başlığı');

// Bu sayfanın ID'sini bul
$stmt = $db->prepare("SELECT id FROM dinamik_sayfalar WHERE slug = '{{PAGE_SLUG}}'");
$stmt->execute();
$sayfa = $stmt->fetch();
$sayfa_id = $sayfa ? $sayfa['id'] : 0;

// Sayfa bloklarını çek
$bloklar = [];
if ($sayfa_id) {
    $stmt = $db->prepare("SELECT * FROM dinamik_sayfa_bloklari WHERE sayfa_id = ? AND aktif = 1 ORDER BY sira ASC");
    $stmt->execute([$sayfa_id]);
    $bloklar = $stmt->fetchAll();
}

include dirname(__DIR__, 2) . '/inc/header.php';
?>

<main class="dynamic-page">
    <?php if (!empty($bloklar)): ?>
        <?php foreach ($bloklar as $blok): ?>
            
            <?php if ($blok['blok_tip'] == 'hero'): ?>
            <section class="hero-section bg-gradient-to-r from-blue-600 to-purple-600 text-white py-20">
                <div class="container mx-auto px-4 text-center">
                    <?php if ($blok['baslik']): ?>
                        <h1 class="text-4xl md:text-5xl font-bold mb-4"><?php echo htmlspecialchars($blok['baslik']); ?></h1>
                    <?php endif; ?>
                    <?php if ($blok['aciklama']): ?>
                        <p class="text-lg max-w-2xl mx-auto"><?php echo nl2br(htmlspecialchars($blok['aciklama'])); ?></p>
                    <?php endif; ?>
                    <?php if ($blok['buton_text'] && $blok['buton_link']): ?>
                        <a href="<?php echo htmlspecialchars($blok['buton_link']); ?>" class="inline-block mt-6 bg-white text-blue-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
                            <?php echo htmlspecialchars($blok['buton_text']); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </section>
            
            <?php elseif ($blok['blok_tip'] == 'text'): ?>
            <section class="text-section py-12">
                <div class="container mx-auto px-4">
                    <?php if ($blok['baslik']): ?>
                        <h2 class="text-2xl font-bold mb-4 text-gray-800"><?php echo htmlspecialchars($blok['baslik']); ?></h2>
                    <?php endif; ?>
                    <div class="prose max-w-none text-gray-600">
                        <?php echo nl2br(htmlspecialchars($blok['aciklama'])); ?>
                    </div>
                </div>
            </section>
            
            <?php elseif ($blok['blok_tip'] == 'image'): ?>
            <section class="image-section py-12 bg-gray-50">
                <div class="container mx-auto px-4">
                    <div class="flex flex-col md:flex-row gap-8 items-center">
                        <?php if ($blok['resim']): ?>
                            <div class="md:w-1/2">
                                <img src="<?php echo htmlspecialchars($blok['resim']); ?>" class="w-full rounded-lg shadow-lg" alt="<?php echo htmlspecialchars($blok['baslik']); ?>">
                            </div>
                        <?php endif; ?>
                        <div class="md:w-1/2">
                            <?php if ($blok['baslik']): ?>
                                <h2 class="text-2xl font-bold mb-4"><?php echo htmlspecialchars($blok['baslik']); ?></h2>
                            <?php endif; ?>
                            <?php if ($blok['aciklama']): ?>
                                <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($blok['aciklama'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
            
            <?php elseif ($blok['blok_tip'] == 'cta'): ?>
            <section class="cta-section py-16 bg-gradient-to-r from-blue-600 to-purple-600 text-white">
                <div class="container mx-auto px-4 text-center">
                    <?php if ($blok['baslik']): ?>
                        <h2 class="text-3xl font-bold mb-4"><?php echo htmlspecialchars($blok['baslik']); ?></h2>
                    <?php endif; ?>
                    <?php if ($blok['aciklama']): ?>
                        <p class="text-lg mb-6"><?php echo htmlspecialchars($blok['aciklama']); ?></p>
                    <?php endif; ?>
                    <?php if ($blok['buton_text'] && $blok['buton_link']): ?>
                        <a href="<?php echo htmlspecialchars($blok['buton_link']); ?>" class="inline-block bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
                            <?php echo htmlspecialchars($blok['buton_text']); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </section>
            
            <?php endif; ?>
            
        <?php endforeach; ?>
    <?php else: ?>
        <section class="py-20 text-center">
            <div class="container mx-auto px-4">
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-8 max-w-2xl mx-auto">
                    <i class="fas fa-cog text-4xl text-yellow-500 mb-4"></i>
                    <h1 class="text-2xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($page_title); ?></h1>
                    <p class="text-gray-600">Bu sayfaya henüz içerik eklenmemiş.</p>
                    <p class="text-sm text-gray-500 mt-2">Admin panelinden blok ekleyerek içerik oluşturabilirsiniz.</p>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php include dirname(__DIR__, 2) . '/inc/footer.php'; ?>