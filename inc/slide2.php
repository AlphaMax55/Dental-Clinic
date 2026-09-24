<?php 
// ============================================================
// ========== SLIDER - YENİ page_slider TABLOSUNDAN ÇEK ==========
// ============================================================

$lang = $_SESSION['lang'] ?? 'tr';

// Hangi sayfada olduğumuzu belirle
$current_page_slug = $page_slug ?? 'anasayfa';

// EĞER TEDAVİ DETAY SAYFASIYSA (tedavi slug'ı var mı?)
if (isset($tedavi_slug) && !empty($tedavi_slug)) {
    $current_page_slug = 'tedavi-' . $tedavi_slug;
}

// Yeni tablodan verileri çek
try {
    $stmt = $db->prepare("SELECT * FROM page_slider WHERE page_slug = ? AND aktif = 1 ORDER BY sira ASC");
    $stmt->execute([$current_page_slug]);
    $slider_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $slider_data = [];
    error_log("❌ Slider hatası: " . $e->getMessage());
}

// Eğer bu sayfaya özel slider yoksa FALLBACK
if (empty($slider_data)) {
    // Önce tedaviler genel slider'ı dene
    try {
        $stmt = $db->prepare("SELECT * FROM page_slider WHERE page_slug = 'tedaviler' AND aktif = 1 ORDER BY sira ASC");
        $stmt->execute();
        $slider_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $slider_data = [];
    }
    
    // O da yoksa anasayfa slider'ını dene
    if (empty($slider_data)) {
        try {
            $stmt = $db->prepare("SELECT * FROM page_slider WHERE page_slug = 'anasayfa' AND aktif = 1 ORDER BY sira ASC");
            $stmt->execute();
            $slider_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $slider_data = [];
        }
    }
}

// FALLBACK: Veritabanı tamamen boşsa manuel array
if (empty($slider_data)) {
    $hero_slides = [
        [
            'title' => t_cevir('Geleceğin Gülüşünü'),
            'subtitle' => t_cevir('Bugünden Tasarlıyoruz'),
            'badge' => 'PROF. DR. İBRAHİM DURAN',
            'features' => [t_cevir('Şeffaf Plak'), t_cevir('Estetik Dolgu'), t_cevir('İmplant')],
            'image' => 'https://images.unsplash.com/photo-1629909613654-28e377c37b09?auto=format&fit=crop&q=80&w=1200',
            'button_text' => t_cevir('İletişime Geç'),
            'button_link' => '/iletisim/'
        ],
        [
            'title' => t_cevir('Sağlıklı Dişler'),
            'subtitle' => t_cevir('Özgür Gülüşler'),
            'badge' => t_cevir('MODERN TEKNOLOJİ'),
            'features' => [t_cevir('Dijital Tasarım'), t_cevir('Lazer Tedavi'), t_cevir('Çocuk Diş'), t_cevir('Ortodonti')],
            'image' => 'https://images.unsplash.com/photo-1606811841689-23dfddce3e95?auto=format&fit=crop&q=80&w=1200',
            'button_text' => t_cevir('İletişime Geç'),
            'button_link' => '/iletisim/'
        ]
    ];
} else {
    $hero_slides = [];
    foreach ($slider_data as $row) {
        // Dil seçimine göre al
        $title = $lang === 'en' ? ($row['title_en'] ?: $row['title_tr']) : $row['title_tr'];
        $subtitle = $lang === 'en' ? ($row['subtitle_en'] ?: $row['subtitle_tr']) : $row['subtitle_tr'];
        $badge = $lang === 'en' ? ($row['badge_en'] ?: $row['badge_tr']) : $row['badge_tr'];
        $button_text = $lang === 'en' ? ($row['button_text_en'] ?: $row['button_text_tr']) : $row['button_text_tr'];
        $button_link = $row['button_link'] ?? '/iletisim/';
        
        // Features'ı virgülle ayır
        $features_text = $lang === 'en' ? ($row['features_en'] ?: $row['features_tr']) : $row['features_tr'];
        $features = array_filter(array_map('trim', explode(',', $features_text ?: '')));
        
        $hero_slides[] = [
            'title' => t_cevir($title),
            'subtitle' => t_cevir($subtitle),
            'badge' => $badge,
            'features' => array_map('t_cevir', $features),
            'image' => $row['image_url'] ?? '',
            'button_text' => t_cevir($button_text),
            'button_link' => $button_link
        ];
    }
}
?>
<style>
/* ========== SLIDER RESİM DÜZELTME ========== */
#heroSlider .hero-slide .absolute.inset-0 {
    overflow: hidden !important;
}


/* ========== MOBİL İÇİN AYRI ========== */
@media (max-width: 768px) {
    #heroSlider .hero-slide .absolute.inset-0 img {
        object-position: center !important;
    }
}

/* ========== SADECE MOBİL (max-width: 768px) ========== */
@media (max-width: 768px) {
    
/* ========== SADECE SLIDER - MOBİL ========== */
@media (max-width: 768px) {
    
    /* Slider yüksekliği */
    #heroSlider {
        height: 320px !important;
        min-height: 320px !important;
    }
    
    /* Tüm içeriği ortala */
    #heroSlider .relative.z-20 {
        text-align: center !important;
        justify-content: center !important;
        padding-left: 12px !important;
        padding-right: 12px !important;
    }
    
    #heroSlider .max-w-2xl {
        max-width: 100% !important;
        text-align: center !important;
    }
    
    /* Badge */
    #heroSlider .inline-flex.items-center.gap-2 {
        font-size: 7px !important;
        padding: 2px 8px !important;
        margin-bottom: 4px !important;
        justify-content: center !important;
        margin-left: auto !important;
        margin-right: auto !important;
    }
    #heroSlider .text-cyan-400.text-\[10px\] {
        font-size: 7px !important;
    }
    #heroSlider .lucide-sparkles {
        width: 8px !important;
        height: 8px !important;
    }
    
    /* Ana Başlık (h1) */
    #heroSlider h1 {
        font-size: 20px !important;
        line-height: 1.2 !important;
        margin-bottom: 2px !important;
        text-align: center !important;
    }
    
    /* Alt Başlık (h2) */
    #heroSlider h2 {
        font-size: 18px !important;
        line-height: 1.2 !important;
        margin-bottom: 4px !important;
        text-align: center !important;
    }
    
    /* Features (etiketler) */
    #heroSlider .flex.flex-wrap.gap-2 {
        justify-content: center !important;
        gap: 4px !important;
        margin-bottom: 6px !important;
    }
    #heroSlider .flex.items-center.gap-1\\.5 {
        font-size: 8px !important;
        padding: 2px 8px !important;
        gap: 3px !important;
        border-radius: 6px !important;
    }
    #heroSlider .lucide-circle-check {
        width: 8px !important;
        height: 8px !important;
    }
    
    /* Butonlar */
    #heroSlider .flex.flex-wrap.gap-3 {
        justify-content: center !important;
        gap: 6px !important;
    }
    #heroSlider .px-6.py-3 {
        padding: 4px 12px !important;
        font-size: 10px !important;
        border-radius: 8px !important;
    }
    #heroSlider .lucide-arrow-right {
        width: 12px !important;
        height: 12px !important;
    }
    
    /* Slider okları - mobilde görünür */
    #heroPrevBtn, #heroNextBtn {
        display: flex !important;
        width: 28px !important;
        height: 28px !important;
        background: rgba(0,0,0,0.4) !important;
        border: 1px solid rgba(255,255,255,0.15) !important;
    }
    #heroPrevBtn svg, #heroNextBtn svg {
        width: 14px !important;
        height: 14px !important;
    }
    #heroPrevBtn {
        left: 2px !important;
    }
    #heroNextBtn {
        right: 2px !important;
    }
    
    /* SVG dalgayı küçült */
    #heroSlider .h-\[50px\] {
        height: 24px !important;
    }
}
    
    /* 4 KART - MOBİLDE 2'Lİ YAN YANA */
    .grid-cols-1.sm\:grid-cols-2.lg\:grid-cols-4 {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 6px !important;
    }
    
    /* Kart içi */
    .group.relative.bg-white\/95 {
        padding: 8px !important;
        border-radius: 10px !important;
        min-height: 100px !important;
    }
    
    /* İkon */
    .group.relative.bg-white\/95 .w-10.h-10 {
        width: 24px !important;
        height: 24px !important;
    }
    .group.relative.bg-white\/95 .w-10.h-10 svg {
        width: 12px !important;
        height: 12px !important;
    }
    
    /* Kart başlığı */
    .group.relative.bg-white\/95 .text-base {
        font-size: 9px !important;
        margin-bottom: 2px !important;
    }
    
    /* Kart açıklaması - mobilde KISA GÖSTER */
    .group.relative.bg-white\/95 .text-\[11px\] {
        font-size: 7px !important;
        margin-bottom: 4px !important;
        line-height: 1.2 !important;
    }
    
    /* Kart buton */
    .group.relative.bg-white\/95 .text-\[8px\] {
        font-size: 5px !important;
    }
    .group.relative.bg-white\/95 .gap-1 {
        gap: 2px !important;
    }
    .group.relative.bg-white\/95 .pt-3 {
        padding-top: 4px !important;
    }
    .group.relative.bg-white\/95 .mb-3 {
        margin-bottom: 4px !important;
    }
    .group.relative.bg-white\/95 .gap-2 {
        gap: 4px !important;
    }
    
    /* Sparkles ikonu */
    .group.relative.bg-white\/95 .lucide-sparkles {
        width: 10px !important;
        height: 10px !important;
    }
    
    /* Üst etiket (KİŞİYE ÖZEL PLANLAMA) */
    .group.relative.bg-white\/95 .text-\[8px\] {
        font-size: 5px !important;
        letter-spacing: 0.05em !important;
    }
    
    /* Mutlak blur efektini kapat */
    .group.relative.bg-white\/95 .absolute {
        display: none !important;
    }
    
    /* Border-t */
    .group.relative.bg-white\/95 .border-t {
        padding-top: 4px !important;
    }
}

/* ========== SADECE MASAÜSTÜ (min-width: 1024px) ========== */
@media (min-width: 1024px) {

    
    /* Kart iç padding'i azalt */
    .group.relative.bg-white\/95 {
        padding: 14px 16px !important;
    }
}
</style>

<main class="min-h-screen bg-white w-full font-sans selection:bg-cyan-100 selection:text-cyan-900">
<section class="relative w-full md:h-[400px] lg:h-[460px] overflow-hidden" id="heroSlider" style="height:410px; background: linear-gradient(to bottom right, #0A2540, #1E3A6F, #2B5B84) !important;">

<?php foreach($hero_slides as $index => $slide): ?>
<div class="hero-slide absolute inset-0 transition-all duration-700 ease-in-out <?php echo $index === 0 ? 'opacity-100 scale-100 z-10' : 'opacity-0 scale-110 z-0'; ?>" data-slide="<?php echo $index; ?>">
    
<!-- ===== RESİM - SAĞA YASLI (FİRE VERİYOR) ===== -->
<div class="absolute inset-0 overflow-hidden flex justify-end">
    <img src="<?php echo $slide['image']; ?>" 
         alt="<?php echo htmlspecialchars($slide['title']); ?>" 
         class="h-full"
         style="width: 48%;margin-right: 40px"
         fetchpriority="<?php echo $index === 0 ? 'high' : 'low'; ?>"
         loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>">
    <div class="absolute inset-0 bg-gradient-to-r from-slate-900/60 via-slate-900/20 to-transparent"></div>
</div>
    <div class="relative z-20 h-full max-w-[1400px] mx-auto px-6 lg:px-12 flex items-center">
        <div class="max-w-2xl">
            <div class="inline-flex items-center gap-2 py-1.5 px-3 rounded-full bg-white/10 backdrop-blur-sm border border-white/20 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkles text-cyan-400"><path d="M11.017 2.814a1 1 0 0 1 1.966 0l1.051 5.558a2 2 0 0 0 1.594 1.594l5.558 1.051a1 1 0 0 1 0 1.966l-5.558 1.051a2 2 0 0 0-1.594 1.594l-1.051 5.558a1 1 0 0 1-1.966 0l-1.051-5.558a2 2 0 0 0-1.594-1.594l-5.558-1.051a1 1 0 0 1 0-1.966l5.558-1.051a2 2 0 0 0 1.594-1.594z"></path><path d="M20 2v4"></path><path d="M22 4h-4"></path><circle cx="4" cy="20" r="2"></circle></svg>
                <span class="text-cyan-400 text-[10px] font-semibold tracking-wider uppercase"><?php echo $slide['badge']; ?></span>
            </div>
            <h1 class="text-3xl md:text-5xl lg:text-6xl font-bold text-white leading-tight mb-3"><?php echo $slide['title']; ?></h1>
            <h2 class="text-2xl md:text-4xl lg:text-5xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-500 mb-5"><?php echo $slide['subtitle']; ?></h2>
            <div class="flex flex-wrap gap-2 mb-6">
                <?php foreach($slide['features'] as $feature): ?>
                <?php if (trim($feature) !== ''): ?>
                <div class="flex items-center gap-1.5 text-white/80 text-xs font-medium bg-white/10 px-3 py-1.5 rounded-lg backdrop-blur-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check text-cyan-400"><circle cx="12" cy="12" r="10"></circle><path d="m9 12 2 2 4-4"></path></svg>
                    <span><?php echo $feature; ?></span>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <div class="flex flex-wrap gap-3">
                <a class="group flex items-center gap-2 bg-gradient-to-r from-[#007aff] to-blue-600 text-white px-6 py-3 rounded-xl font-semibold text-sm hover:shadow-lg hover:-translate-y-0.5 transition-all" href="<?php echo SITE_PATH . $slide['button_link']; ?>">
                    <?php echo $slide['button_text']; ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right group-hover:translate-x-1 transition-transform"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
                </a>
                <?php if ($current_page_slug !== 'tedaviler'): ?>
                <a class="group flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-white/20 text-white px-6 py-3 rounded-xl font-semibold text-sm hover:bg-white/20 transition-all" href="<?php echo SITE_PATH; ?>/tedaviler/">
                    <?php echo t_cevir("Tedaviler"); ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
    

    
    <div class="absolute bottom-0 left-0 w-full z-30 leading-[0] pointer-events-none">
        <svg class="relative block w-full h-[50px] lg:h-[80px]" viewBox="0 0 1440 120" preserveAspectRatio="none">
            <path fill="#f8fafc" fill-opacity="1" d="M0,64L80,74.7C160,85,320,107,480,106.7C640,107,800,85,960,74.7C1120,64,1280,64,1360,64L1440,64L1440,120L1360,120C1280,120,1120,120,960,120C800,120,640,120,480,120C320,120,160,120,80,120L0,120Z"></path>
        </svg>
    </div>
    
    <div id="stickyRandevuBtn" class="fixed bottom-0 left-0 right-0 p-3 z-[100] md:hidden hidden animate-in fade-in slide-in-from-bottom-5 duration-300">
        <a href="<?php echo SITE_PATH . ($hero_slides[0]['button_link'] ?? '/iletisim/'); ?>" class="flex items-center justify-center gap-2 bg-gradient-to-r from-[#007aff] to-blue-600 text-white w-full py-3.5 rounded-xl font-semibold text-sm shadow-lg active:scale-95 transition-all">
            <?php echo $hero_slides[0]['button_text'] ?? t_cevir("Hemen Randevu Al"); ?> 
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
        </a>
    </div>
</section>

<!-- JS AYNI -->
<script>
// ========== OPTİMİZE EDİLMİŞ HERO SLIDER ==========
(function() {
    'use strict';
    
    let currentSlide = 0;
    let totalSlides = 0;
    let slideInterval = null;
    let isTransitioning = false;
    let cachedSlides = null;
    
    const heroSlider = document.getElementById('heroSlider');
    if (!heroSlider) return;
    
    const prevBtn = document.getElementById('heroPrevBtn');
    const nextBtn = document.getElementById('heroNextBtn');
    const stickyBtn = document.getElementById('stickyRandevuBtn');
    
    function showSlide(index) {
        if (isTransitioning || !cachedSlides.length) return;
        if (index < 0) index = totalSlides - 1;
        if (index >= totalSlides) index = 0;
        if (index === currentSlide) return;
        
        isTransitioning = true;
        const previousSlide = currentSlide;
        currentSlide = index;
        
        requestAnimationFrame(() => {
            cachedSlides[previousSlide].style.opacity = '0';
            cachedSlides[previousSlide].style.transform = 'scale(1.02)';
            cachedSlides[previousSlide].style.zIndex = '0';
            cachedSlides[previousSlide].setAttribute('aria-hidden', 'true');
            
            cachedSlides[currentSlide].style.opacity = '1';
            cachedSlides[currentSlide].style.transform = 'scale(1)';
            cachedSlides[currentSlide].style.zIndex = '10';
            cachedSlides[currentSlide].setAttribute('aria-hidden', 'false');
            
            setTimeout(() => {
                isTransitioning = false;
            }, 600);
        });
    }
    
    function nextSlide() {
        if (isTransitioning) return;
        clearInterval(slideInterval);
        showSlide(currentSlide + 1);
        startAutoSlide();
    }
    
    function prevSlide() {
        if (isTransitioning) return;
        clearInterval(slideInterval);
        showSlide(currentSlide - 1);
        startAutoSlide();
    }
    
    function startAutoSlide() {
        if (slideInterval) clearInterval(slideInterval);
        if (totalSlides > 1) {
            slideInterval = setInterval(() => {
                if (!isTransitioning && !document.hidden) {
                    nextSlide();
                }
            }, 5000);
        }
    }
    
    function stopAutoSlide() {
        if (slideInterval) {
            clearInterval(slideInterval);
            slideInterval = null;
        }
    }
    
    function handleVisibilityChange() {
        document.hidden ? stopAutoSlide() : startAutoSlide();
    }
    
    function initStickyButton() {
        if (!stickyBtn) return;
        let ticking = false;
        let lastScrollY = 0;
        
        window.addEventListener('scroll', () => {
            if (!ticking) {
                requestAnimationFrame(() => {
                    const currentScrollY = window.scrollY;
                    const isVisible = currentScrollY > 350;
                    
                    if (isVisible) {
                        stickyBtn.classList.remove('hidden', 'translate-y-20');
                        stickyBtn.classList.add('translate-y-0');
                    } else {
                        stickyBtn.classList.add('hidden', 'translate-y-20');
                        stickyBtn.classList.remove('translate-y-0');
                    }
                    
                    lastScrollY = currentScrollY;
                    ticking = false;
                });
                ticking = true;
            }
        }, { passive: true });
    }
    
    function addKeyboardSupport() {
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') { e.preventDefault(); prevSlide(); }
            else if (e.key === 'ArrowRight') { e.preventDefault(); nextSlide(); }
        });
    }
    
    function addTouchSupport() {
        let touchStartX = 0;
        
        heroSlider.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
            stopAutoSlide();
        }, { passive: true });
        
        heroSlider.addEventListener('touchend', (e) => {
            const diff = e.changedTouches[0].screenX - touchStartX;
            if (Math.abs(diff) > 50) {
                diff > 0 ? prevSlide() : nextSlide();
            }
            startAutoSlide();
        });
        
        heroSlider.addEventListener('touchcancel', () => {
            startAutoSlide();
        });
    }
    
    function init() {
        cachedSlides = Array.from(heroSlider.querySelectorAll('.hero-slide'));
        totalSlides = cachedSlides.length;
        
        if (totalSlides === 0) return;
        
        if (prevBtn) {
            prevBtn.addEventListener('click', (e) => { e.preventDefault(); prevSlide(); });
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', (e) => { e.preventDefault(); nextSlide(); });
        }
        
        showSlide(0);
        startAutoSlide();
        initStickyButton();
        addKeyboardSupport();
        addTouchSupport();
        document.addEventListener('visibilitychange', handleVisibilityChange);
        
        window.addEventListener('beforeunload', () => {
            stopAutoSlide();
        });
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>

<script>
// ========== OPTİMİZE EDİLMİŞ TEDAVİ SLIDER ==========
(function() {
    'use strict';
    
    let currentSlide = 0;
    let totalSlides = 0;
    let slideTimer = null;
    let cachedItems = null;
    let dots = [];
    
    const grid = document.getElementById('treatmentGrid');
    const dotsContainer = document.getElementById('treatmentDots');
    
    function updateTreatmentGrid() {
        if (!grid || cachedItems.length === 0) return;
        
        const start = currentSlide * 6;
        const end = Math.min(start + 6, cachedItems.length);
        
        requestAnimationFrame(() => {
            for (let i = 0; i < cachedItems.length; i++) {
                cachedItems[i].style.display = 'none';
            }
            for (let i = start; i < end; i++) {
                cachedItems[i].style.display = 'block';
            }
            updateDots();
            updateNavigationButtons();
        });
    }
    
    function updateDots() {
        if (!dotsContainer || dots.length === 0) return;
        
        for (let i = 0; i < dots.length; i++) {
            const dot = dots[i];
            if (i === currentSlide) {
                dot.classList.remove('w-2', 'bg-gray-300', 'hover:bg-blue-300');
                dot.classList.add('w-8', 'bg-blue-500');
                dot.setAttribute('aria-current', 'true');
            } else {
                dot.classList.remove('w-8', 'bg-blue-500');
                dot.classList.add('w-2', 'bg-gray-300', 'hover:bg-blue-300');
                dot.setAttribute('aria-current', 'false');
            }
        }
    }
    
    function updateNavigationButtons() {
        const prevBtn = document.getElementById('prevTreatmentBtn');
        const nextBtn = document.getElementById('nextTreatmentBtn');
        
        if (prevBtn) {
            prevBtn.disabled = currentSlide === 0;
            prevBtn.style.opacity = currentSlide === 0 ? '0.5' : '1';
            prevBtn.style.cursor = currentSlide === 0 ? 'not-allowed' : 'pointer';
        }
        if (nextBtn) {
            nextBtn.disabled = currentSlide === totalSlides - 1;
            nextBtn.style.opacity = currentSlide === totalSlides - 1 ? '0.5' : '1';
            nextBtn.style.cursor = currentSlide === totalSlides - 1 ? 'not-allowed' : 'pointer';
        }
    }
    
    function createDots() {
        if (!dotsContainer) return;
        
        dotsContainer.innerHTML = '';
        const fragment = document.createDocumentFragment();
        dots = [];
        
        for (let i = 0; i < totalSlides; i++) {
            const btn = document.createElement('button');
            btn.className = `h-2 rounded-full transition-all duration-300 ${
                i === currentSlide ? 'w-8 bg-blue-500' : 'w-2 bg-gray-300 hover:bg-blue-300'
            }`;
            btn.setAttribute('aria-label', `Sayfa ${i + 1}`);
            btn.setAttribute('aria-current', i === currentSlide ? 'true' : 'false');
            btn.addEventListener('click', (function(slideIndex) {
                return function() {
                    goToSlide(slideIndex);
                };
            })(i));
            fragment.appendChild(btn);
            dots.push(btn);
        }
        
        dotsContainer.appendChild(fragment);
    }
    
    function goToSlide(slideIndex) {
        if (slideIndex < 0) slideIndex = 0;
        if (slideIndex >= totalSlides) slideIndex = totalSlides - 1;
        if (slideIndex === currentSlide) return;
        
        currentSlide = slideIndex;
        updateTreatmentGrid();
        resetTimer();
    }
    
    function nextSlide() {
        if (totalSlides > 1) {
            goToSlide((currentSlide + 1) % totalSlides);
        }
    }
    
    function prevSlide() {
        if (totalSlides > 1) {
            goToSlide(currentSlide - 1);
        }
    }
    
    function startTimer() {
        if (slideTimer) clearInterval(slideTimer);
        if (totalSlides > 1) {
            slideTimer = setInterval(() => {
                nextSlide();
            }, 5000);
        }
    }
    
    function resetTimer() {
        if (slideTimer) {
            clearInterval(slideTimer);
            startTimer();
        }
    }
    
    function stopTimer() {
        if (slideTimer) {
            clearInterval(slideTimer);
            slideTimer = null;
        }
    }
    
    window.nextTreatmentSlide = nextSlide;
    window.prevTreatmentSlide = prevSlide;
    window.goToTreatmentSlide = goToSlide;
    
    function addHoverPause() {
        if (!grid) return;
        grid.addEventListener('mouseenter', stopTimer);
        grid.addEventListener('mouseleave', startTimer);
        grid.addEventListener('touchstart', stopTimer);
        grid.addEventListener('touchend', () => {
            setTimeout(startTimer, 3000);
        });
    }
    
    function init() {
        if (!grid) return;
        cachedItems = Array.from(grid.querySelectorAll('.treatment-item'));
        if (cachedItems.length === 0) return;
        
        totalSlides = Math.ceil(cachedItems.length / 6);
        
        if (totalSlides <= 1) {
            if (dotsContainer) dotsContainer.style.display = 'none';
            cachedItems.forEach(item => item.style.display = 'block');
            return;
        }
        
        createDots();
        updateTreatmentGrid();
        startTimer();
        addHoverPause();
        
        document.addEventListener('visibilitychange', () => {
            document.hidden ? stopTimer() : startTimer();
        });
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    window.addEventListener('beforeunload', () => {
        if (slideTimer) clearInterval(slideTimer);
    });
})();
</script>