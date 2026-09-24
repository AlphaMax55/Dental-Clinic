<?php
// www/site/#index.php
require_once dirname(__DIR__) . '/inc/config.php';

// Session başlat
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF Token
if(!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Başarılı mesajı kontrol et (session'dan)
$showSuccess = false;
if(isset($_SESSION['randevu_success']) && $_SESSION['randevu_success'] === true) {
    $showSuccess = true;
    unset($_SESSION['randevu_success']);
}

$csrf_error = false;
$error_message = '';

$tc_no = trim($_POST['tcNo'] ?? '');
if(!empty($tc_no) && (!preg_match('/^[0-9]{11}$/', $tc_no) || $tc_no[0] == '0')) {
    // TC hatalı, uyarı göster
    $csrf_error = true;
    $error_message = "TC Kimlik No 11 haneli ve 0 ile başlamamalıdır!";
}
// FORM GÖNDERİLDİYSE kısmını şunla değiştir
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_token = $_POST['csrf_token'] ?? '';
    
    if(!isset($_SESSION['csrf_token']) || $posted_token !== $_SESSION['csrf_token']) {
        $csrf_error = true;
        $error_message = "Güvenlik hatası! Lütfen sayfayı yenileyip tekrar deneyin.";
    } else {
        $ad_soyad = trim($_POST['fullName'] ?? '');
        $telefon = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $tarih = trim($_POST['appointmentDate'] ?? '');
        $saat = trim($_POST['appointmentTime'] ?? '');
        $tedavi = trim($_POST['treatmentType'] ?? '');
        $sikayet = trim($_POST['complaint'] ?? '');
        
        // YENİ ALANLAR
        $cinsiyet = trim($_POST['gender'] ?? '');
        $dogum_tarihi = trim($_POST['birthDate'] ?? '');
        $tc_no = trim($_POST['tcNo'] ?? '');
        $hasta_tipi = isset($_POST['patientType']) ? ($_POST['patientType'] == 'new' ? 'Yeni Hasta' : 'Mevcut Hasta') : '';
        
        $ilac_kullaniyor = isset($_POST['hasMedication']) ? 1 : 0;
        $ilac_detay = trim($_POST['medicationDetail'] ?? '');
        
        $alerji_var = isset($_POST['hasAllergy']) ? 1 : 0;
        $alerji_detay = trim($_POST['allergyDetail'] ?? '');
        
        $hamile = isset($_POST['isPregnant']) ? 1 : 0;
        $diyabet = isset($_POST['hasDiabetes']) ? 1 : 0;
        $kalp_hastaligi = isset($_POST['hasHeartDisease']) ? 1 : 0;
        $kan_sulandirici = isset($_POST['hasBloodThinner']) ? 1 : 0;
        
        if($ad_soyad && $telefon && $tarih && $saat && $tedavi) {
            $konu = $tedavi;
            $mesaj = $sikayet;
            $not_icerik = "Tedavi: " . $tedavi;
            if(!empty($sikayet)) {
                $not_icerik .= " | Şikayet: " . $sikayet;
            }
            
            $stmt = $db->prepare("INSERT INTO randevular (
                ad_soyad, telefon, email, cinsiyet, dogum_tarihi, tc_no, hasta_tipi,
                konu, mesaj, randevu_tarihi, randevu_saati, `not`,
                ilac_kullaniyor, ilac_detay, alerji_var, alerji_detay,
                hamile, diyabet, kalp_hastaligi, kan_sulandirici,
                durum, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                0, NOW()
            )");
            
            $stmt->execute([
                $ad_soyad, $telefon, $email, $cinsiyet, $dogum_tarihi, $tc_no, $hasta_tipi,
                $konu, $mesaj, $tarih, $saat, $not_icerik,
                $ilac_kullaniyor, $ilac_detay, $alerji_var, $alerji_detay,
                $hamile, $diyabet, $kalp_hastaligi, $kan_sulandirici
            ]);
            
            $_SESSION['randevu_success'] = true;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            
            header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
            exit;
        }
    }
}

$iletisim = [
    'adres' => '',
    'telefon' => '',
    'eposta' => '',
    'calisma_saatleri' => '',
    'harita_link' => '',
    'whatsapp' => ''
];

$stmt = $db->prepare("SELECT ayar_key, ayar_value FROM site_ayarlari WHERE grup = 'iletisim'");
$stmt->execute();
$ayarlar = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

foreach($ayarlar as $key => $value) {
    switch($key) {
        case 'adres': $iletisim['adres'] = $value; break;
        case 'telefon': $iletisim['telefon'] = $value; break;
        case 'eposta': $iletisim['eposta'] = $value; break;
        case 'calisma_saatleri': $iletisim['calisma_saatleri'] = $value; break;
        case 'harita_link': $iletisim['harita_link'] = $value; break;
        case 'whatsapp': $iletisim['whatsapp'] = $value; break;
    }
}

if(empty($iletisim['adres'])) {
    $iletisim['adres'] = 'Mimar Sinan Mah. Atatürk Bulvarı Riva İş Merkezi No:260 Kat:1 Atakum/Samsun';
}
if(empty($iletisim['harita_link'])) {
    $iletisim['harita_link'] = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d11899.23122176461!2d36.2721827!3d41.3364363!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x4088791e38f19103%3A0x40b759d884614220!2zUml2YURlbnQgQXRha3VtIEHEn8SxeiB2ZSBEacWfIFNhxJ9sxLHEn8SxIFBvbGlrbGluacSfaSwgTWltYXJzaW5hbiwgQXRhdMO8cmsgQmwuIFJpdmEgxLDFnyBNZXJrZXppIE5vOiAyNjAgS2F0OjEsIDU1MjAwIEF0YWt1bS9TYW1zdW4!5e0!3m2!1str!2str!4v1700000000000';
}

$page_title = "Randevu | " . $site_baslik;
$page_description = "Prof. Dr. İbrahim Duran kliniğinde online randevu oluşturun.";

$treatmentGroups = [
    "İmplantoloji ve Cerrahi" => ["Dikişsiz İmplant Tedavisi (Flapsiz)", "İmplant Cerrahisi ve Ameliyatı", "İmplant Üstü Sabit/Hareketli Protez Yapımı", "Vidalı İmplant Üstü Diş Protezi Uygulamaları", "Zygoma (Elmacık Kemiği) İmplant Uygulamaları"],
    "Estetik Diş Hekimliği" => ["Dijital Gülüş Tasarımı ve Gülüş Estetiği", "Estetik ve Multidisipliner Gülüş Tasarımı", "Lamina Venere (Yaprak Porselen) Seramik Uygulamaları", "Zirkonyum Kron ve Köprü Restorasyonları", "Fiber Destekli Konservatif Sabit Diş Uygulamaları", "Vital ve Devital Diş Beyazlatma (Bleaching)"],
    "Çene Eklemi ve Bruksizm" => ["Çene Ekleminin Ameliyatsız Fonksiyonel Tedavisi", "Çene Eklemi Sorunları ve Tıkırdama Tedavisi", "Bruksizm (Diş Sıkma ve Gıcırdatma) Tedavisi", "Gece Plağı ve Stabilizasyon Splinti Uygulamaları"],
    "Protez ve Özel Uygulamalar" => ["Klasik Hareketli ve Sabit Protez Restorasyonları", "Hassas Tutuculu (Vakumlu) Diş Protezleri", "Çene Protezleri ve Maksillofasiyal Epitezler"],
    "Klinik ve Genel Tedaviler" => ["Ağız Yaraları ve Oral Mukozal Hastalıkların Tedavisi", "Kanal Tedavisi (Endodonti)", "Estetik Kompozit Dolgu Uygulamaları", "Diş Eti Hastalıkları (Periodontoloji) Tedavisi", "Diş Taşı Temizliği ve Acil Diş Ağrısı Müdahalesi"]
];

$availableTimes = ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'];

include dirname(__DIR__) . '/inc/header.php';
include dirname(__DIR__) . '/inc/slide.php';
?>
<style>
/* Randevu Formu Stilleri */
.step-1, .step-2, .step-3 { transition: all 0.3s ease; }
.step-active { display: block; }
.step-inactive { display: none; }
.radio-group input:checked + span { color: #3b82f6; }
</style>


    <!-- RANDEVU FORMU - PREMIUM PANEL -->
    <section class="py-24 lg:py-10 mb-5 px-6 bg-slate-900 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-blue-600/10 blur-[150px] rounded-full -mr-80 -mt-80"></div>
        <div class="absolute bottom-0 left-0 w-[600px] h-[600px] bg-indigo-600/10 blur-[150px] rounded-full -ml-80 -mb-80"></div>

        <div class="max-w-6xl mx-auto relative z-10">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row items-end justify-between mb-12 gap-8">
                <div class="text-left space-y-4">
                    <div class="flex items-center gap-3 text-blue-400 font-black text-xs tracking-[0.3em] uppercase">
                        <div class="w-12 h-[2px] bg-blue-500"></div>
                        Dijital Randevu Sistemi
                    </div>
                    <h2 class="text-4xl lg:text-6xl font-black text-white tracking-tighter leading-none uppercase">
                        RANDEVU <br/> <span class="text-blue-700">OLUŞTURUN</span>
                    </h2>
                </div>
                
                <!-- Progress Stepper -->
                <div class="flex items-center gap-4 bg-white/5 backdrop-blur-xl px-8 py-4 rounded-[2rem] border border-white/10">
                    <div class="step-indicator w-10 h-10 rounded-full flex items-center justify-center font-black text-sm bg-blue-600 text-white" data-step="1">1</div>
                    <div class="w-12 h-[2px] bg-white/10 step-line" data-step="1"></div>
                    <div class="step-indicator w-10 h-10 rounded-full flex items-center justify-center font-black text-sm bg-white/10 text-slate-500" data-step="2">2</div>
                    <div class="w-12 h-[2px] bg-white/10 step-line" data-step="2"></div>
                    <div class="step-indicator w-10 h-10 rounded-full flex items-center justify-center font-black text-sm bg-white/10 text-slate-500" data-step="3">3</div>
                </div>
            </div>


<?php if($showSuccess): ?>
<div class="mb-8 bg-emerald-500/20 backdrop-blur-xl border border-emerald-500/30 rounded-[2rem] p-8 text-center">
    <div class="w-20 h-20 bg-emerald-500 rounded-full flex items-center justify-center mx-auto mb-6">
        <svg class="w-10 h-10 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <h3 class="text-3xl font-black text-white mb-4">Randevu Talebiniz Alındı!</h3>
    <p class="text-slate-300 text-lg max-w-2xl mx-auto">
        Randevu talebiniz başarıyla oluşturuldu. En kısa sürede size dönüş yapacağız.
        SMS ve e-posta ile bilgilendirileceksiniz.
    </p>
</div>
<?php endif; ?>
            <?php if(!$showSuccess): ?>
            <form method="POST" action="" class="grid grid-cols-1 lg:grid-cols-12 gap-6" id="randevuForm">
                <?php echo csrf_field(); ?>
                <div class="lg:col-span-8 bg-white/5 backdrop-blur-3xl p-6 lg:p-10 rounded-[3rem] border border-white/10 shadow-2xl">
                    
                    <!-- STEP 1: Hasta Bilgileri -->
                    <div class="step step-1" id="step1">
                        <div class="space-y-6">
                            <h3 class="text-2xl font-black text-white mb-6 flex items-center gap-3">
                                <svg class="w-6 h-6 text-blue-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                Hasta Bilgileri
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2 md:col-span-2">
                                    <label class="text-[10px] font-black text-blue-400 uppercase tracking-widest ml-4 flex items-center gap-1">Ad Soyad <span class="text-red-500">*</span></label>
                                    <input type="text" name="fullName" required class="w-full px-6 py-5 rounded-2xl bg-white/5 border border-white/10 focus:border-blue-500 text-white outline-none transition-all font-bold placeholder:text-slate-800" placeholder="Örn: Ahmet Yılmaz">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-blue-400 uppercase tracking-widest ml-4 flex items-center gap-1">Telefon <span class="text-red-500">*</span></label>
                                    <input type="tel" name="phone" required class="w-full px-6 py-5 rounded-2xl bg-white/5 border border-white/10 focus:border-blue-500 text-white outline-none transition-all font-bold placeholder:text-slate-800" placeholder="0 (5xx) xxx xx xx">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-blue-400 uppercase tracking-widest ml-4">E-posta (Opsiyonel)</label>
                                    <input type="email" name="email" class="w-full px-6 py-5 rounded-2xl bg-white/5 border border-white/10 focus:border-blue-500 text-white outline-none transition-all font-bold placeholder:text-slate-800" placeholder="ornek@email.com">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-blue-400 uppercase tracking-widest ml-4">Doğum Tarihi</label>
                                    <input type="date" name="birthDate" max="<?php echo date('Y-m-d'); ?>" class="w-full px-6 py-5 rounded-2xl bg-white/5 border border-white/10 focus:border-blue-500 text-white outline-none transition-all font-bold">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-blue-400 uppercase tracking-widest ml-4">Cinsiyet</label>
                                    <select name="gender" class="w-full px-6 py-5 rounded-2xl bg-white/5 border border-white/10 focus:border-blue-500 text-white outline-none transition-all font-bold">
                                        <option value="" class="bg-slate-900">Seçiniz</option>
                                        <option value="male" class="bg-slate-900">Erkek</option>
                                        <option value="female" class="bg-slate-900">Kadın</option>
                                    </select>
                                </div>
<div class="space-y-2 md:col-span-2">
    <label class="text-[10px] font-black text-blue-400 uppercase tracking-widest ml-4 flex items-center gap-2">
        TC Kimlik No 
        <span class="text-slate-500 font-normal text-[8px]">(Opsiyonel)</span>
        <span id="tcNoUyari" class="text-red-400 text-[8px] font-normal hidden">❌ 11 haneli sayı giriniz</span>
        <span id="tcNoTamam" class="text-green-400 text-[8px] font-normal hidden">✅ Geçerli TC No</span>
    </label>
    <input type="text" 
           name="tcNo" 
           id="tcNo" 
           maxlength="11" 
           class="w-full px-6 py-5 rounded-2xl bg-white/5 border border-white/10 focus:border-blue-500 text-white outline-none transition-all font-bold placeholder:text-slate-800" 
           placeholder="11 haneli TC numaranız"
           oninput="validateTC(this)">

</div>
                                <div class="md:col-span-2">
                                    <label class="text-[10px] font-black text-blue-400 uppercase tracking-widest ml-4 block mb-3">Daha önce kliniğe geldiniz mi?</label>
                                    <div class="flex gap-6">
                                        <label class="flex items-center gap-3 cursor-pointer">
                                            <input type="radio" name="patientType" value="new" class="w-5 h-5 accent-blue-600" checked> <span class="text-white font-medium">Yeni Hasta</span>
                                        </label>
                                        <label class="flex items-center gap-3 cursor-pointer">
                                            <input type="radio" name="patientType" value="existing" class="w-5 h-5 accent-blue-600"> <span class="text-white font-medium">Mevcut Hasta</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: Randevu Bilgileri -->
                    <div class="step step-2" id="step2" style="display: none;">
                        <div class="space-y-6">
                            <h3 class="text-2xl font-black text-white mb-6 flex items-center gap-3">
                                <svg class="w-6 h-6 text-blue-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v4"/><path d="M16 2v4"/><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18"/></svg>
                                Randevu Bilgileri
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-blue-400 uppercase tracking-widest ml-4 flex items-center gap-1">Randevu Tarihi <span class="text-red-500">*</span></label>
                                    <input type="date" name="appointmentDate" required min="<?php echo date('Y-m-d'); ?>" class="w-full px-6 py-5 rounded-2xl bg-white/5 border border-white/10 focus:border-blue-500 text-white outline-none transition-all font-bold">
                                    <p class="text-[10px] text-slate-500 mt-1 ml-4">* Pazar günleri ve resmi tatillerde hizmet verilmemektedir</p>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-blue-400 uppercase tracking-widest ml-4 flex items-center gap-1">Randevu Saati <span class="text-red-500">*</span></label>
<select name="appointmentTime" required class="w-full px-6 py-5 rounded-2xl bg-slate-800 border border-slate-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/50 text-white outline-none transition-all font-bold appearance-none cursor-pointer" style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"20\" height=\"20\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"white\" stroke-width=\"2\"><polyline points=\"6 9 12 15 18 9\"/></svg>'); background-repeat: no-repeat; background-position: right 1.5rem center; background-size: 1.2rem;">
    <option value="" disabled selected class="text-slate-800 bg-slate-800">⏰ -- Saat seçiniz --</option>
    <?php foreach($availableTimes as $time): ?>
    <option value="<?php echo $time; ?>" class="bg-slate-800 text-white font-medium py-2" style="background-color: #0f172a; color: #e2e8f0; padding: 10px;">
        🕐 <?php echo $time; ?>
    </option>
    <?php endforeach; ?>
</select>                            </div>
 
                                <div class="space-y-2 w-full md:col-span-2">
                                    <label class="text-[10px] font-black text-blue-400 uppercase tracking-widest ml-4 flex items-center gap-1">Tedavi Türü <span class="text-red-500">*</span></label>

<select name="treatmentType" required class="w-full px-6 py-5 rounded-2xl bg-slate-800 border border-slate-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/50 text-white outline-none transition-all font-medium appearance-none cursor-pointer" style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"20\" height=\"20\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"white\" stroke-width=\"2\"><polyline points=\"6 9 12 15 18 9\"/></svg>'); background-repeat: no-repeat; background-position: right 1.5rem center; background-size: 1.2rem;">
    <option value="" disabled selected class="text-gray-400 bg-slate-800">── Tedavi Seçiniz ──</option>
    <?php foreach($treatmentGroups as $groupName => $treatments): ?>
    <optgroup label="📌 <?php echo htmlspecialchars($groupName); ?>" class="bg-slate-700 text-cyan-400 font-bold tracking-wider text-xs" style="background-color: #1e293b; color: #22d3ee; padding: 10px;">
        <?php foreach($treatments as $treatment): ?>
        <option value="<?php echo htmlspecialchars($treatment); ?>" class="bg-slate-800 text-gray-200 font-normal text-sm py-2 px-3 hover:bg-slate-700" style="background-color: #0f172a; color: #e2e8f0; padding: 8px 12px;">  • <?php echo htmlspecialchars($treatment); ?></option>
        <?php endforeach; ?>
    </optgroup>
    <?php endforeach; ?>
</select>
                                </div>
                                <div class="md:col-span-2 space-y-2">
                                    <label class="text-[10px] font-black text-blue-400 uppercase tracking-widest ml-4">Şikayetinizi Yazınız</label>
                                    <textarea name="complaint" rows="4" class="w-full px-6 py-5 rounded-3xl bg-white/5 border border-white/10 focus:border-blue-500 text-white outline-none transition-all font-medium resize-none placeholder:text-slate-800" placeholder="Şikayetinizi detaylıca yazın, doktorunuz ön hazırlık yapabilsin..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 3: Sağlık Bilgileri ve Onaylar -->
                    <div class="step step-3" id="step3" style="display: none;">
                        <div class="space-y-8">
                            <h3 class="text-2xl font-black text-white mb-6 flex items-center gap-3">
                                <svg class="w-6 h-6 text-blue-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                                Sağlık Bilgileri ve Onaylar
                            </h3>

                            <div class="space-y-4">
                                <h4 class="text-white font-bold text-lg">Önemli Sağlık Bilgileri</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <label class="flex items-center gap-3 cursor-pointer p-4 bg-white/5 rounded-2xl border border-white/10">
                                        <input type="checkbox" name="hasMedication" class="w-5 h-5 accent-blue-600"> <span class="text-white font-medium">Sürekli kullandığınız ilaç var mı?</span>
                                    </label>
                                    <label class="flex items-center gap-3 cursor-pointer p-4 bg-white/5 rounded-2xl border border-white/10">
                                        <input type="checkbox" name="hasAllergy" class="w-5 h-5 accent-blue-600"> <span class="text-white font-medium">Alerjiniz var mı?</span>
                                    </label>
                                    <label class="flex items-center gap-3 cursor-pointer p-4 bg-white/5 rounded-2xl border border-white/10">
                                        <input type="checkbox" name="isPregnant" class="w-5 h-5 accent-blue-600"> <span class="text-white font-medium">Hamile misiniz?</span>
                                    </label>
                                    <label class="flex items-center gap-3 cursor-pointer p-4 bg-white/5 rounded-2xl border border-white/10">
                                        <input type="checkbox" name="hasDiabetes" class="w-5 h-5 accent-blue-600"> <span class="text-white font-medium">Diyabet</span>
                                    </label>
                                    <label class="flex items-center gap-3 cursor-pointer p-4 bg-white/5 rounded-2xl border border-white/10">
                                        <input type="checkbox" name="hasHeartDisease" class="w-5 h-5 accent-blue-600"> <span class="text-white font-medium">Kalp Hastalığı</span>
                                    </label>
                                    <label class="flex items-center gap-3 cursor-pointer p-4 bg-white/5 rounded-2xl border border-white/10">
                                        <input type="checkbox" name="hasBloodThinner" class="w-5 h-5 accent-blue-600"> <span class="text-white font-medium">Kan sulandırıcı kullanımı</span>
                                    </label>
                                </div>

                                <div id="medicationDetailDiv" style="display: none;">
                                    <textarea name="medicationDetail" rows="2" class="w-full px-6 py-4 rounded-2xl bg-white/5 border border-white/10 focus:border-blue-500 text-white outline-none" placeholder="Kullandığınız ilaçları yazınız..."></textarea>
                                </div>
                                <div id="allergyDetailDiv" style="display: none;">
                                    <textarea name="allergyDetail" rows="2" class="w-full px-6 py-4 rounded-2xl bg-white/5 border border-white/10 focus:border-blue-500 text-white outline-none" placeholder="Alerji detaylarınızı yazınız..."></textarea>
                                </div>
                            </div>

                            <div class="space-y-4 pt-6 border-t border-white/10">
                                <h4 class="text-white font-bold text-lg">Onaylar</h4>
                                <label class="flex items-center gap-3 cursor-pointer p-4 bg-white/5 rounded-2xl border border-white/10">
                                    <input type="checkbox" name="kvkkAccepted" required class="w-5 h-5 accent-blue-600"> <span class="text-white font-medium">KVKK Aydınlatma Metnini okudum, kişisel verilerimin işlenmesini kabul ediyorum.<span class="text-red-500 ml-1">*</span></span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer p-4 bg-white/5 rounded-2xl border border-white/10">
                                    <input type="checkbox" name="smsPermission" class="w-5 h-5 accent-blue-600"> <span class="text-white font-medium">Randevu hatırlatmaları için SMS / WhatsApp bildirimi almak istiyorum.</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="flex justify-between mt-8 pt-6 border-t border-white/10">
                        <button type="button" id="prevBtn" class="px-8 py-4 bg-white/10 text-white rounded-2xl font-bold hover:bg-white/20 transition-all" style="display: none;">Geri</button>
                        <button type="button" id="nextBtn" class="px-8 py-4 bg-blue-600 text-white rounded-2xl font-bold hover:bg-blue-500 transition-all ml-auto">İleri</button>
                        <button type="submit" id="submitBtn" class="px-8 py-4 bg-blue-600 text-white rounded-2xl font-bold hover:bg-blue-500 transition-all ml-auto disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2" style="display: none;">Randevu Oluştur</button>
                    </div>
                </div>

                <!-- Sağ Panel -->
                <div class="lg:col-span-4 space-y-6">
                    <!-- Premium Bilgi Paneli -->
                    <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-[3rem] p-8 shadow-xl">
                        <div class="w-14 h-14 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center text-white mb-6">
                            <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.268 21a2 2 0 0 0 3.464 0"/><path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"/></svg>
                        </div>
                        <h4 class="text-2xl font-black text-white mb-4">Hızlı Randevu</h4>
                        <p class="text-blue-200 text-sm mb-6">Formu doldurun, asistanlarımız en kısa sürede size dönüş yapsın.</p>
                        <div class="space-y-3">
                            <div class="flex items-center gap-3 text-white/80"><svg class="w-4 h-4 text-blue-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.801 10A10 10 0 1 1 17 3.335"/><path d="m9 11 3 3L22 4"/></svg><span class="text-xs">15 dk içinde bilgilendirme</span></div>
                            <div class="flex items-center gap-3 text-white/80"><svg class="w-4 h-4 text-blue-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.801 10A10 10 0 1 1 17 3.335"/><path d="m9 11 3 3L22 4"/></svg><span class="text-xs">SMS & E-posta onayı</span></div>
                            <div class="flex items-center gap-3 text-white/80"><svg class="w-4 h-4 text-blue-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.801 10A10 10 0 1 1 17 3.335"/><path d="m9 11 3 3L22 4"/></svg><span class="text-xs">Google Takvim entegrasyonu</span></div>
                        </div>
                    </div>

                    <!-- Acil WhatsApp Desteği -->
                    <div class="bg-white/5 backdrop-blur-md border border-white/10 rounded-[2.5rem] p-8">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-12 h-12 bg-emerald-500/20 rounded-full flex items-center justify-center text-emerald-400">
                                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 17a2 2 0 0 1-2 2H6.828a2 2 0 0 0-1.414.586l-2.202 2.202A.71.71 0 0 1 2 21.286V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2z"/></svg>
                            </div>
                            <div><p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">7/24 Acil</p><p class="text-white font-black text-lg">WhatsApp Hattı</p></div>
                        </div>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $telefon); ?>" target="_blank" class="block w-full py-4 bg-emerald-500 text-white rounded-2xl font-black text-xs tracking-widest uppercase text-center hover:bg-emerald-600 transition-all">Hemen Yaz</a>
                    </div>

                    <!-- Güvenlik Bilgisi -->
                    <div class="bg-white/5 backdrop-blur-md border border-white/10 rounded-[2rem] p-6">
                        <div class="flex items-center gap-3 text-slate-800">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            <span class="text-xs font-medium">Bilgileriniz 256-bit SSL ile şifrelenir</span>
                        </div>
                    </div>
                </div>
				<?php if(isset($csrf_error) && $csrf_error): ?>
<div class="mb-8 bg-red-500/20 backdrop-blur-xl border border-red-500/30 rounded-[2rem] p-8 text-center">
    <div class="w-20 h-20 bg-red-500 rounded-full flex items-center justify-center mx-auto mb-6">
        <svg class="w-10 h-10 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    </div>
    <h3 class="text-3xl font-black text-white mb-4">Güvenlik Hatası!</h3>
    <p class="text-slate-300 text-lg max-w-2xl mx-auto"><?php echo $error_message; ?></p>
</div>
<?php endif; ?>
            </form>
            <?php endif; ?>
        </div>
    </section>
<!-- HARİTA - Full genişlik, kart sağda ve yukarıda -->
<section id="harita" class="w-full relative h-[600px] bg-slate-100 overflow-hidden">
    <!-- Full Genişlik Harita -->
    <iframe 
        src="<?php echo htmlspecialchars($iletisim['harita_link'] ?: 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d11899.23122176461!2d36.2721827!3d41.3364363!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x4088791e38f19103%3A0x40b759d884614220!2zUml2YURlbnQgQXRha3VtIEHEn8SxeiB2ZSBEacWfIFNhxJ9sxLHEn8SxIFBvbGlrbGluacSfaSwgTWltYXJzaW5hbiwgQXRhdMO8cmsgQmwuIFJpdmEgxLDFnyBNZXJrZXppIE5vOiAyNjAgS2F0OjEsIDU1MjAwIEF0YWt1bS9TYW1zdW4!5e0!3m2!1str!2str!4v1700000000000'); ?>" 
        class="absolute inset-0 w-full h-full border-0" 
        allowfullscreen="" 
        loading="lazy">
    </iframe>
    
    <!-- SENİN KARTIN - Sağda, yukarıda -->
    <div class="absolute top-3 right-6 lg:right-12 z-20 w-full max-w-sm lg:max-w-md">
        <div class="bg-[#0a0f1c] from-blue-900/95 to-blue-800/95 backdrop-blur-md rounded-2xl p-6 lg:p-8 shadow-2xl border border-blue-500/30">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-blue-500/20 rounded-xl flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
                </div>
                <span class="text-blue-300 font-black text-[10px] uppercase tracking-widest">Konum Bilgisi</span>
            </div>
            
            <h3 class="text-xl lg:text-2xl font-black text-white mb-3">Kliniğimiz</h3>
            
            <p class="text-blue-200 font-medium leading-relaxed mb-6 text-sm lg:text-base">
                <?php echo htmlspecialchars($iletisim['adres']); ?>
            </p>
            
            <div class="flex flex-wrap gap-3">
                <a href="https://maps.google.com/?q=<?php echo urlencode($iletisim['adres']); ?>" target="_blank" class="inline-flex items-center justify-center gap-2 bg-white text-blue-700 font-bold text-xs px-5 py-2.5 rounded-xl hover:bg-blue-50 transition-all group shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    Yol Tarifi Al
                </a>
                <a href="https://www.google.com/maps/place/<?php echo urlencode($iletisim['adres']); ?>" target="_blank" class="inline-flex items-center justify-center gap-2 bg-blue-600/30 backdrop-blur-sm border border-blue-500/40 text-white font-bold text-xs px-5 py-2.5 rounded-xl hover:bg-blue-600/50 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    Navigasyon
                </a>
            </div>
            
            <div class="mt-5 pt-4 border-t border-blue-500/20">
                <div class="flex items-center gap-4 text-xs">
                    <div class="flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                        <span class="text-blue-200">7/24 Hizmet</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <span class="text-blue-200">Uzman Hekim</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<script>
// ============= OPTİMİZE EDİLMİŞ RANDEVU SİSTEMİ =============
(function() {
    'use strict';
    
    // ============= TC VALIDASYON =============
    function initTCValidation() {
        const tcInput = document.getElementById('tcNo');
        if(!tcInput) return;
        
        const uyariSpan = document.getElementById('tcNoUyari');
        const tamamSpan = document.getElementById('tcNoTamam');
        let uyariTimeout = null;
        
        const showUyari = (mesaj, isError = true) => {
            if(uyariSpan) {
                uyariSpan.innerHTML = isError ? '❌ ' + mesaj : '⚠️ ' + mesaj;
                uyariSpan.classList.remove('hidden');
                if(tamamSpan) tamamSpan.classList.add('hidden');
                tcInput.classList.remove('border-green-500/50');
                tcInput.classList.add('border-red-500');
            }
            
            if(uyariTimeout) clearTimeout(uyariTimeout);
            uyariTimeout = setTimeout(() => {
                if(uyariSpan) uyariSpan.classList.add('hidden');
                if(tcInput.value.length === 11 && /^\d{11}$/.test(tcInput.value) && tcInput.value[0] !== '0') {
                    tcInput.classList.remove('border-red-500');
                    tcInput.classList.add('border-green-500/50');
                } else {
                    tcInput.classList.remove('border-green-500/50', 'border-red-500');
                }
            }, 2500);
        };
        
        const showSuccess = () => {
            if(uyariSpan) uyariSpan.classList.add('hidden');
            if(tamamSpan) tamamSpan.classList.remove('hidden');
            tcInput.classList.remove('border-red-500');
            tcInput.classList.add('border-green-500/50');
            if(uyariTimeout) clearTimeout(uyariTimeout);
        };
        
        const validateAndClean = () => {
            let value = tcInput.value;
            
            if(/[A-Za-zğüşıöçĞÜŞİÖÇ]/i.test(value)) {
                tcInput.value = '';
                showUyari('Sadece rakam girebilirsiniz! Harf kullanmayın!', true);
                return;
            }
            
            let newValue = value.replace(/[^0-9]/g, '');
            
            if(newValue.length > 0 && newValue[0] === '0') {
                newValue = '';
                showUyari('TC Kimlik No 0 ile başlayamaz!', true);
                tcInput.value = newValue;
                return;
            }
            
            if(newValue.length > 11) newValue = newValue.substring(0, 11);
            if(newValue !== value) tcInput.value = newValue;
            
            if(newValue.length === 11 && /^\d{11}$/.test(newValue) && newValue[0] !== '0') {
                showSuccess();
            } else if(newValue.length > 0) {
                showUyari(`11 haneli sayı giriniz (Şu an ${newValue.length} hane)`, true);
            } else {
                if(uyariSpan) uyariSpan.classList.add('hidden');
                if(tamamSpan) tamamSpan.classList.add('hidden');
                tcInput.classList.remove('border-red-500', 'border-green-500/50');
            }
        };
        
        tcInput.addEventListener('input', validateAndClean);
        
        tcInput.addEventListener('keydown', (e) => {
            const allowedKeys = new Set(['Backspace', 'Delete', 'Tab', 'Enter', 'Escape', 
                                        'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End']);
            
            if(allowedKeys.has(e.key)) return true;
            if((e.ctrlKey || e.metaKey) && e.key === 'v') {
                setTimeout(validateAndClean, 10);
                return true;
            }
            if((e.ctrlKey || e.metaKey) && (e.key === 'c' || e.key === 'x')) return true;
            if(!/^\d$/.test(e.key)) {
                e.preventDefault();
                showUyari('Sadece rakam girebilirsiniz!', true);
                return false;
            }
            if(e.key === '0' && tcInput.value.length === 0) {
                e.preventDefault();
                showUyari('TC Kimlik No 0 ile başlayamaz!', true);
                return false;
            }
            return true;
        });
        
        tcInput.addEventListener('paste', () => setTimeout(validateAndClean, 10));
        validateAndClean();
    }
    
    // ============= TOAST MESAJLARI =============
    const showToast = (message, type = 'error') => {
        const oldToast = document.querySelector('.toast-message');
        if(oldToast) oldToast.remove();
        
        const toast = document.createElement('div');
        toast.className = 'toast-message fixed bottom-8 left-1/2 -translate-x-1/2 z-50 px-6 py-3 rounded-xl text-white font-semibold text-sm shadow-xl transition-all duration-300';
        toast.style.backgroundColor = type === 'error' ? '#ef4444' : '#10b981';
        toast.style.animation = 'slideUp 0.3s ease';
        toast.innerHTML = `<div class="flex items-center gap-3">${type === 'error' ? 
            '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>' : 
            '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>'}<span>${message}</span></div>`;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(20px)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    };
    
    // ============= STEPPER YÖNETİMİ =============
    class StepperManager {
        constructor() {
            this.currentStep = 1;
            this.elements = {
                step1: document.getElementById('step1'),
                step2: document.getElementById('step2'),
                step3: document.getElementById('step3'),
                nextBtn: document.getElementById('nextBtn'),
                prevBtn: document.getElementById('prevBtn'),
                submitBtn: document.getElementById('submitBtn'),
                indicators: document.querySelectorAll('.step-indicator'),
                lines: document.querySelectorAll('.step-line')
            };
            
            this.init();
        }
        
        validateStep1() {
            const fullName = document.querySelector('input[name="fullName"]')?.value.trim();
            const phone = document.querySelector('input[name="phone"]')?.value.trim();
            
            if(!fullName) { showToast('Lütfen adınızı ve soyadınızı giriniz.'); return false; }
            if(fullName.length < 2) { showToast('Lütfen geçerli bir isim giriniz.'); return false; }
            if(!phone) { showToast('Lütfen telefon numaranızı giriniz.'); return false; }
            if(phone.length < 10) { showToast('Lütfen geçerli bir telefon numarası giriniz.'); return false; }
            return true;
        }
        
        validateStep2() {
            const appointmentDate = document.querySelector('input[name="appointmentDate"]')?.value;
            const appointmentTime = document.querySelector('select[name="appointmentTime"]')?.value;
            const treatmentType = document.querySelector('select[name="treatmentType"]')?.value;
            
            if(!appointmentDate) { showToast('Lütfen randevu tarihi seçiniz.'); return false; }
            if(!appointmentTime) { showToast('Lütfen randevu saati seçiniz.'); return false; }
            if(!treatmentType) { showToast('Lütfen tedavi türü seçiniz.'); return false; }
            return true;
        }
        
        validateStep3() {
            const kvkkAccepted = document.querySelector('input[name="kvkkAccepted"]')?.checked;
            if(!kvkkAccepted) { showToast('KVKK Aydınlatma Metnini kabul etmelisiniz.'); return false; }
            return true;
        }
        
        updateSteps() {
            const { step1, step2, step3, indicators, lines, prevBtn, nextBtn, submitBtn } = this.elements;
            
            if(step1) step1.style.display = this.currentStep === 1 ? 'block' : 'none';
            if(step2) step2.style.display = this.currentStep === 2 ? 'block' : 'none';
            if(step3) step3.style.display = this.currentStep === 3 ? 'block' : 'none';
            
            indicators.forEach((ind, i) => {
                if(i + 1 <= this.currentStep) {
                    ind.classList.remove('bg-white/10', 'text-slate-500');
                    ind.classList.add('bg-blue-600', 'text-white');
                } else {
                    ind.classList.remove('bg-blue-600', 'text-white');
                    ind.classList.add('bg-white/10', 'text-slate-500');
                }
            });
            
            lines.forEach((line, i) => {
                if(i + 1 < this.currentStep) {
                    line.classList.remove('bg-white/10');
                    line.classList.add('bg-blue-600');
                } else {
                    line.classList.remove('bg-blue-600');
                    line.classList.add('bg-white/10');
                }
            });
            
            if(prevBtn) prevBtn.style.display = this.currentStep === 1 ? 'none' : 'flex';
            if(nextBtn) nextBtn.style.display = this.currentStep === 3 ? 'none' : 'flex';
            if(submitBtn) submitBtn.style.display = this.currentStep === 3 ? 'flex' : 'none';
        }
        
        next() {
            if(this.currentStep === 1 && !this.validateStep1()) return;
            if(this.currentStep === 2 && !this.validateStep2()) return;
            if(this.currentStep < 3) {
                this.currentStep++;
                this.updateSteps();
            }
        }
        
        prev() {
            if(this.currentStep > 1) {
                this.currentStep--;
                this.updateSteps();
            }
        }
        
        init() {
            const { nextBtn, prevBtn, submitBtn } = this.elements;
            
            if(nextBtn) nextBtn.addEventListener('click', () => this.next());
            if(prevBtn) prevBtn.addEventListener('click', () => this.prev());
            if(submitBtn) {
                submitBtn.addEventListener('click', (e) => {
                    if(!this.validateStep3()) e.preventDefault();
                });
            }
            
            this.updateSteps();
        }
    }
    
    // ============= CHECKBOX DETAY ALANLARI =============
    function initCheckboxDetails() {
        const medicationCheckbox = document.querySelector('input[name="hasMedication"]');
        const allergyCheckbox = document.querySelector('input[name="hasAllergy"]');
        const medicationDiv = document.getElementById('medicationDetailDiv');
        const allergyDiv = document.getElementById('allergyDetailDiv');
        
        if(medicationCheckbox && medicationDiv) {
            medicationCheckbox.addEventListener('change', () => {
                medicationDiv.style.display = medicationCheckbox.checked ? 'block' : 'none';
            });
        }
        
        if(allergyCheckbox && allergyDiv) {
            allergyCheckbox.addEventListener('change', () => {
                allergyDiv.style.display = allergyCheckbox.checked ? 'block' : 'none';
            });
        }
    }
    
    // ============= FORM SUBMIT KONTROLÜ =============
    function initFormSubmit() {
        const form = document.getElementById('randevuForm');
        if(!form) return;
        
        form.addEventListener('submit', function(e) {
            const tokenInput = document.querySelector('input[name="csrf_token"]');
            const sessionToken = "<?php echo $_SESSION['csrf_token']; ?>";
            
            if(!tokenInput || !tokenInput.value) {
                showToast('Güvenlik hatası! Sayfayı yenileyin.');
                e.preventDefault();
                return false;
            }
            
            if(tokenInput.value !== sessionToken) {
                showToast('Güvenlik hatası! Sayfayı yenileyin.');
                e.preventDefault();
                return false;
            }
        });
    }
    
    // ============= SCROLL YÖNETİMİ =============
    function initScrollManagement() {
        const hataMesaji = document.querySelector('.bg-red-500\\/20, .bg-emerald-500\\/20');
        const formSubmitted = <?php echo isset($_POST['csrf_token']) ? 'true' : 'false'; ?>;
        
        if(hataMesaji || formSubmitted || window.location.hash === '#randevu') {
            const randevuSection = document.querySelector('section.py-24');
            if(randevuSection) {
                setTimeout(() => {
                    randevuSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 100);
            }
        }
    }
    
    // ============= ANİMASYON KEYFRAMES =============
    function addAnimations() {
        if(!document.querySelector('#dynamic-styles')) {
            const styleEl = document.createElement('style');
            styleEl.id = 'dynamic-styles';
            styleEl.textContent = `@keyframes slideUp { 
                from { opacity: 0; transform: translateY(20px); } 
                to { opacity: 1; transform: translateY(0); } 
            }`;
            document.head.appendChild(styleEl);
        }
    }
    
    // ============= BAŞLAT =============
    function init() {
        initTCValidation();
        new StepperManager();
        initCheckboxDetails();
        initFormSubmit();
        initScrollManagement();
        addAnimations();
    }
    
    // DOM yüklendiğinde başlat
    if(document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>


<?php include dirname(__DIR__) . '/inc/footer.php'; ?>