<?php
require_once 'config.php';

function login($kullanici_adi, $sifre) {
    global $db;
    
    $sorgu = $db->prepare("SELECT * FROM kullanicilar WHERE (kullanici_adi = ? OR email = ?) AND durum = 1");
    $sorgu->execute([$kullanici_adi, $kullanici_adi]);
    $kullanici = $sorgu->fetch();
    
    if ($kullanici && password_verify($sifre, $kullanici['sifre'])) {
        $_SESSION['admin_id'] = $kullanici['id'];
        $_SESSION['admin_adi'] = $kullanici['ad_soyad'];
        $_SESSION['admin_rol'] = $kullanici['rol'];
        
        // Son giriş güncelle
        $guncelle = $db->prepare("UPDATE kullanicilar SET son_giris = NOW() WHERE id = ?");
        $guncelle->execute([$kullanici['id']]);
        
        return true;
    }
    
    return false;
}

function kontrol() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: ' . ADMIN_URL . '/login.php');
        exit;
    }
}

function yetkiKontrol($gerekli_rol = 'admin') {
    if (!isset($_SESSION['admin_rol'])) {
        return false;
    }
    
    $roller = [
        'superadmin' => 3,
        'admin' => 2,
        'editor' => 1
    ];
    
    $kullanici_seviye = $roller[$_SESSION['admin_rol']] ?? 0;
    $gerekli_seviye = $roller[$gerekli_rol] ?? 2;
    
    return $kullanici_seviye >= $gerekli_seviye;
}

function logout() {
    session_destroy();
    header('Location: ' . ADMIN_URL . '/login.php');
    exit;
}

// Modül bazlı yetki kontrolü
function yetkiVar($modul, $yetki) {
    if (!isset($_SESSION['admin_id'])) {
        return false;
    }
    
    // Süper Admin her şeyi yapabilir
    if ($_SESSION['admin_rol'] == 'superadmin') {
        return true;
    }
    
    global $db;
    $stmt = $db->prepare("
        SELECT deger FROM yetkiler 
        WHERE kullanici_id = ? AND modul = ? AND yetki = ?
    ");
    $stmt->execute([$_SESSION['admin_id'], $modul, $yetki]);
    
    return $stmt->fetchColumn() == 1;
}

// Modüle erişim kontrolü (sayfayı görebilir mi?)
function modulErisim($modul) {
    if (!isset($_SESSION['admin_id'])) {
        return false;
    }
    
    if ($_SESSION['admin_rol'] == 'superadmin') {
        return true;
    }
    
    global $db;
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM yetkiler 
        WHERE kullanici_id = ? AND modul = ? AND deger = 1
    ");
    $stmt->execute([$_SESSION['admin_id'], $modul]);
    
    return $stmt->fetchColumn() > 0;
}

// Yetki mesajı göster
function yetkiYok($modul, $yetki = null) {
    if ($yetki) {
        $mesaj = "Bu işlem için '{$modul} - {$yetki}' yetkisine sahip değilsiniz!";
    } else {
        $mesaj = "Bu sayfaya erişim yetkiniz yok!";
    }
    
    echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-6 rounded-lg text-center max-w-md mx-auto mt-20">
            <i class="fas fa-lock text-4xl mb-3 block"></i>
            <p class="font-bold text-lg mb-2">Yetkiniz Yok!</p>
            <p>' . $mesaj . '</p>
            <a href="?modul=dashboard" class="inline-block mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Ana Sayfaya Dön</a>
          </div>';
    exit;
}

// CSRF Token oluştur
function csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF Token doğrula
function csrf_verify($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>