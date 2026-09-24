<?php
function slugYap($metin) {
    $metin = preg_replace('/[^a-zA-Z0-9\-]/', '', str_replace(' ', '-', $metin));
    return strtolower(trim($metin, '-'));
}

function uploadImage($file, $klasor = 'genel') {
    $hedef_klasor = UPLOAD_PATH . $klasor . '/';
    
    if (!file_exists($hedef_klasor)) {
        mkdir($hedef_klasor, 0777, true);
    }
    
    $dosya_adi = time() . '_' . basename($file['name']);
    $hedef_dosya = $hedef_klasor . $dosya_adi;
    
    if (move_uploaded_file($file['tmp_name'], $hedef_dosya)) {
        return 'uploads/' . $klasor . '/' . $dosya_adi;
    }
    
    return false;
}

function guvenli($veri) {
    return htmlspecialchars(trim($veri), ENT_QUOTES, 'UTF-8');
}

function tarihFormat($tarih, $format = 'd.m.Y H:i') {
    return date($format, strtotime($tarih));
}

function sayfaYonlendir($sayfa) {
    header('Location: ' . $sayfa);
    exit;
}

function mesajGoster($mesaj, $tip = 'success') {
    $_SESSION['mesaj'] = [
        'text' => $mesaj,
        'tip' => $tip
    ];
}

function mesajOku() {
    if (isset($_SESSION['mesaj'])) {
        $mesaj = $_SESSION['mesaj'];
        unset($_SESSION['mesaj']);
        return $mesaj;
    }
    return null;
}
?>