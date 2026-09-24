<?php
// ========== E-POSTA GÖNDERME FONKSİYONU (mail() İLE) ==========


function sendMail($to, $subject, $message, $from = null) {
    
    // Gönderici adresi
    $from_email = $from ?? 'noreply@dribrahimdurandentalclinic.com';
    
    // Headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . $from_email . "\r\n";
    $headers .= "Reply-To: " . $from_email . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    
    // Ekstra başlıklar (spam'e düşmemek için)
    $headers .= "Message-ID: <" . time() . "-" . md5($to) . "@dribrahimdurandentalclinic.com>" . "\r\n";
    $headers .= "Date: " . date('r') . "\r\n";
    
    // E-posta gönder
    if (@mail($to, $subject, $message, $headers)) {
        error_log("✅ mail() başarılı: $to");
        return true;
    } else {
        $error = error_get_last();
        $error_msg = $error['message'] ?? 'Bilinmeyen hata';
        error_log("❌ mail() başarısız: $to - $error_msg");
        return 'mail() hatası: ' . $error_msg;
    }
}

// ============================================================
// ========== BURAYA SLIDER FONKSİYONLARINI EKLE !!! ==========
// ============================================================

/**
 * Sayfanın slider verilerini getirir
 * @param string $page_slug - Sayfa slug'ı (anasayfa, kurumsal, galeri, vb.)
 * @param string $lang - Dil (tr/en)
 * @return array - Slider verileri
 */
function get_page_slider($page_slug, $lang = 'tr') {
    global $db;
    
    try {
        $stmt = $db->prepare("SELECT * FROM page_slider WHERE page_slug = ? AND aktif = 1 ORDER BY sira ASC");
        $stmt->execute([$page_slug]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $data = [];
    }
    
    // Veri yoksa FALLBACK (manuel slider)
    if (empty($data)) {
        return get_fallback_slider($page_slug, $lang);
    }
    
    $slides = [];
    foreach ($data as $row) {
        $slides[] = [
            'title' => $lang === 'en' ? ($row['title_en'] ?: $row['title_tr']) : $row['title_tr'],
            'subtitle' => $lang === 'en' ? ($row['subtitle_en'] ?: $row['subtitle_tr']) : $row['subtitle_tr'],
            'badge' => $lang === 'en' ? ($row['badge_en'] ?: $row['badge_tr']) : $row['badge_tr'],
            'features' => array_map('trim', explode(',', $lang === 'en' ? ($row['features_en'] ?: $row['features_tr']) : $row['features_tr'])),
            'image' => $row['image_url'],
            'button_text' => $lang === 'en' ? ($row['button_text_en'] ?: $row['button_text_tr']) : $row['button_text_tr'],
            'button_link' => $row['button_link']
        ];
    }
    
    return $slides;
}

/**
 * Fallback slider (veritabanı boşsa manuel)
 */
function get_fallback_slider($page_slug, $lang = 'tr') {
    $t = function($text) use ($lang) {
        return t_cevir($text);
    };
    
    $fallbacks = [
        'anasayfa' => [
            [
                'title' => $t('Geleceğin Gülüşünü'),
                'subtitle' => $t('Bugünden Tasarlıyoruz'),
                'badge' => 'PROF. DR. İBRAHİM DURAN',
                'features' => [$t('Şeffaf Plak'), $t('Estetik Dolgu'), $t('İmplant')],
                'image' => 'https://images.unsplash.com/photo-1629909613654-28e377c37b09?auto=format&fit=crop&q=80&w=1200',
                'button_text' => $t('İletişime Geç'),
                'button_link' => '/iletisim/'
            ]
        ],
        'kurumsal' => [
            [
                'title' => $t('Kurumsal Vizyon'),
                'subtitle' => $t('30 Yıllık Tecrübe'),
                'badge' => $t('KURUMSAL'),
                'features' => [$t('Kalite'), $t('Güven'), $t('Tecrübe')],
                'image' => 'https://images.unsplash.com/photo-1606811841689-23dfddce3e95?auto=format&fit=crop&q=80&w=1200',
                'button_text' => $t('Detayları Gör'),
                'button_link' => '/kurumsal/'
            ]
        ],
        'galeri' => [
            [
                'title' => $t('Galerimiz'),
                'subtitle' => $t('En İyi Tedaviler'),
                'badge' => $t('GALERİ'),
                'features' => [$t('Önce'), $t('Sonra'), $t('Başarı')],
                'image' => 'https://images.unsplash.com/photo-1629909613654-28e377c37b09?auto=format&fit=crop&q=80&w=1200',
                'button_text' => $t('Hemen İncele'),
                'button_link' => '/galeri/'
            ]
        ],
        'teknolojiler' => [
            [
                'title' => $t('Teknolojik Altyapı'),
                'subtitle' => $t('Dijital Dönüşüm'),
                'badge' => $t('TEKNOLOJİ'),
                'features' => [$t('Dijital Tasarım'), $t('Lazer Tedavi'), $t('3D Tomografi')],
                'image' => 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?auto=format&fit=crop&q=80&w=1200',
                'button_text' => $t('Keşfet'),
                'button_link' => '/teknolojiler/'
            ]
        ],
        'blog' => [
            [
                'title' => $t('Blog'),
                'subtitle' => $t('Sağlıklı Gülüşler'),
                'badge' => $t('BLOG'),
                'features' => [$t('Makaleler'), $t('Haberler'), $t('İpuçları')],
                'image' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&q=80&w=1200',
                'button_text' => $t('Tüm Yazılar'),
                'button_link' => '/blog/'
            ]
        ],
        'kvkk' => [
            [
                'title' => $t('KVKK & Gizlilik'),
                'subtitle' => $t('Verileriniz Güvende'),
                'badge' => 'KVKK',
                'features' => [$t('Veri Güvenliği'), $t('Gizlilik'), $t('Koruma')],
                'image' => 'https://images.unsplash.com/photo-1583912267553-a90a2f0dc1a7?auto=format&fit=crop&q=80&w=1200',
                'button_text' => $t('Detaylar'),
                'button_link' => '/kvkk/'
            ]
        ]
    ];
    
    return $fallbacks[$page_slug] ?? $fallbacks['anasayfa'];
}
?>