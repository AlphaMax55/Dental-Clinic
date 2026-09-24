<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once dirname(__DIR__) . '/admin/includes/config.php';

// Parametreler
$site_adresi = $_GET['site'] ?? 'sc-domain:dribrahimdurandentalclinic.com';
$baslangic_tarihi = $_GET['start'] ?? date('Y-m-d', strtotime('-90 days'));
$bitis_tarihi = $_GET['end'] ?? date('Y-m-d');
$row_limit = isset($_GET['limit']) ? intval($_GET['limit']) : 1000;

// JSON dosyasi yolu
$anahtar_yolu = dirname(__DIR__) . '/admin/includes/search-console-api-493519-f4f1689d2249.json';

if (!file_exists($anahtar_yolu)) {
    echo json_encode(['success' => false, 'error' => 'Anahtar dosyasi bulunamadi: ' . $anahtar_yolu]);
    exit;
}

$kimlik_verileri = json_decode(file_get_contents($anahtar_yolu), true);

if (!$kimlik_verileri) {
    echo json_encode(['success' => false, 'error' => 'JSON dosyasi okunamadi veya gecersiz']);
    exit;
}

$istemci_eposta = $kimlik_verileri['client_email'];
$ozel_anahtar = $kimlik_verileri['private_key'];

// JWT Token olustur
function base64_url_kodla($veri) {
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($veri));
}

$jwt_baslik = base64_url_kodla(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
$jwt_icerik = base64_url_kodla(json_encode([
    'iss' => $istemci_eposta,
    'scope' => 'https://www.googleapis.com/auth/webmasters.readonly',
    'aud' => 'https://oauth2.googleapis.com/token',
    'exp' => time() + 3600,
    'iat' => time()
]));

$imza = '';
$anahtar_id = openssl_pkey_get_private($ozel_anahtar);
if (!$anahtar_id) {
    echo json_encode(['success' => false, 'error' => 'Ozel anahtar gecersiz!']);
    exit;
}

openssl_sign($jwt_baslik . '.' . $jwt_icerik, $imza, $anahtar_id, 'sha256WithRSAEncryption');
$jwt_token = $jwt_baslik . '.' . $jwt_icerik . '.' . base64_url_kodla($imza);

// Access Token al
$curl = curl_init('https://oauth2.googleapis.com/token');
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query([
    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
    'assertion' => $jwt_token
]));
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
$token_yaniti = json_decode(curl_exec($curl), true);
curl_close($curl);

if (!isset($token_yaniti['access_token'])) {
    echo json_encode(['success' => false, 'error' => 'Access Token alinamadi', 'detay' => $token_yaniti]);
    exit;
}

$erisim_anahtari = $token_yaniti['access_token'];

// Özet istatistikler için ayrı istek (dimensions'siz)
$ozet_istek = json_encode([
    'startDate' => $baslangic_tarihi,
    'endDate' => $bitis_tarihi,
    'rowLimit' => 1
]);

$curl = curl_init("https://www.googleapis.com/webmasters/v3/sites/" . urlencode($site_adresi) . "/searchAnalytics/query");
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $erisim_anahtari,
    'Content-Type: application/json'
]);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, $ozet_istek);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
$ozet_yanit = curl_exec($curl);
curl_close($curl);

$ozet_data = json_decode($ozet_yanit, true);
$ozet = [];
if (isset($ozet_data['rows'][0])) {
    $ozet = [
        'toplam_tiklama' => $ozet_data['rows'][0]['clicks'] ?? 0,
        'toplam_gosterim' => $ozet_data['rows'][0]['impressions'] ?? 0,
        'ortalama_ctr' => round(($ozet_data['rows'][0]['ctr'] ?? 0) * 100, 2),
        'ortalama_pozisyon' => round($ozet_data['rows'][0]['position'] ?? 0, 1)
    ];
}

// Detayli veri (dimensions ile)
$detay_istek = json_encode([
    'startDate' => $baslangic_tarihi,
    'endDate' => $bitis_tarihi,
    'dimensions' => ['query', 'page', 'device', 'country'],
    'rowLimit' => $row_limit
]);

$curl = curl_init("https://www.googleapis.com/webmasters/v3/sites/" . urlencode($site_adresi) . "/searchAnalytics/query");
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $erisim_anahtari,
    'Content-Type: application/json'
]);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, $detay_istek);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
$detay_yanit = curl_exec($curl);
$http_durum = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($http_durum !== 200) {
    echo json_encode(['success' => false, 'error' => 'Google API Hatasi', 'kod' => $http_durum, 'mesaj' => $detay_yanit]);
    exit;
}

$gelen_veri = json_decode($detay_yanit, true);
$satirlar = $gelen_veri['rows'] ?? [];

$sonuc_listesi = [];
foreach ($satirlar as $satir) {
    $keys = $satir['keys'] ?? ['', '', '', ''];
    $sonuc_listesi[] = [
        'kelime' => $keys[0] ?? '',
        'sayfa' => $keys[1] ?? '',
        'cihaz' => $keys[2] ?? '',
        'ulke' => $keys[3] ?? '',
        'tiklama' => $satir['clicks'] ?? 0,
        'gosterim' => $satir['impressions'] ?? 0,
        'ctr' => round(($satir['ctr'] ?? 0) * 100, 2),
        'sira' => round($satir['position'] ?? 0, 1)
    ];
}

// Cihaz bazlı istatistikler
$cihaz_istatistik = [];
foreach ($sonuc_listesi as $item) {
    $cihaz = $item['cihaz'] ?: 'DESKTOP';
    if (!isset($cihaz_istatistik[$cihaz])) {
        $cihaz_istatistik[$cihaz] = ['tiklama' => 0, 'gosterim' => 0];
    }
    $cihaz_istatistik[$cihaz]['tiklama'] += $item['tiklama'];
    $cihaz_istatistik[$cihaz]['gosterim'] += $item['gosterim'];
}

// Ulke bazlı istatistikler
$ulke_istatistik = [];
foreach ($sonuc_listesi as $item) {
    $ulke = $item['ulke'] ?: 'TUR';
    if (!isset($ulke_istatistik[$ulke])) {
        $ulke_istatistik[$ulke] = ['tiklama' => 0, 'gosterim' => 0];
    }
    $ulke_istatistik[$ulke]['tiklama'] += $item['tiklama'];
    $ulke_istatistik[$ulke]['gosterim'] += $item['gosterim'];
}

// Aylık istatistikler için ayrı istek
$aylik_istatistik = [];
for ($i = 5; $i >= 0; $i--) {
    $ay_baslangic = date('Y-m-01', strtotime("-$i months"));
    $ay_bitis = date('Y-m-t', strtotime("-$i months"));
    
    $aylik_istek = json_encode([
        'startDate' => $ay_baslangic,
        'endDate' => $ay_bitis,
        'rowLimit' => 1
    ]);
    
    $curl = curl_init("https://www.googleapis.com/webmasters/v3/sites/" . urlencode($site_adresi) . "/searchAnalytics/query");
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $erisim_anahtari, 'Content-Type: application/json']);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $aylik_istek);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    $yanit = curl_exec($curl);
    curl_close($curl);
    
    $veri = json_decode($yanit, true);
    $aylik_istatistik[] = [
        'ay' => date('M Y', strtotime($ay_baslangic)),
        'tiklama' => $veri['rows'][0]['clicks'] ?? 0,
        'gosterim' => $veri['rows'][0]['impressions'] ?? 0,
        'ctr' => round(($veri['rows'][0]['ctr'] ?? 0) * 100, 2),
        'pozisyon' => round($veri['rows'][0]['position'] ?? 0, 1)
    ];
}

// Sonucu gonder
echo json_encode([
    'success' => true,
    'ozet' => $ozet,
    'cihaz' => $cihaz_istatistik,
    'ulke' => $ulke_istatistik,
    'aylik' => $aylik_istatistik,
    'detay' => $sonuc_listesi,
    'meta' => [
        'site' => $site_adresi,
        'baslangic' => $baslangic_tarihi,
        'bitis' => $bitis_tarihi,
        'toplam_satir' => count($sonuc_listesi),
        'limit' => $row_limit
    ]
], JSON_UNESCAPED_UNICODE);
?>