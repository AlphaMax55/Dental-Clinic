<?php
// api/istatistik-kaydet.php
// ========== İSTATİSTİK KAYDETME API (TAMAMEN GÜNCELLEME MANTIĞI) ==========

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method Not Allowed');
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once dirname(__DIR__) . '/inc/config.php';

// ========== VERİLERİ AL ==========
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$sayfa = $input['sayfa'] ?? '/';
$slug = $input['slug'] ?? '';
$sayfa_sayisi = isset($input['sayfa_sayisi']) ? (int)$input['sayfa_sayisi'] : 1;

// ========== OTURUM ID ==========
if (!isset($_COOKIE['site_oturum'])) {
    $oturum_id = uniqid() . '_' . time();
    setcookie('site_oturum', $oturum_id, time() + 86400 * 30, '/', '', false, true);
} else {
    $oturum_id = $_COOKIE['site_oturum'];
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';

// 🔥 ========== KENDİ IP'LERİNİ HARİÇ TUT ==========
$own_ips = [
    '88.246.89.150',   // Senin IP'n

];

// 🔥 ADMIN PANELİNDEN GELENLERİ DE HARİÇ TUT
$is_admin = (strpos($referer, '/admin') !== false || strpos($sayfa, '/admin') !== false);

// Eğer senin IP'nsen veya admin panelinden geliyorsan kaydetme
if (in_array($ip, $own_ips) || $is_admin) {
    echo json_encode([
        'success' => true,
        'message' => 'Kendi IP veya Admin paneli, kaydedilmedi',
        'oturum_id' => $oturum_id,
        'ip' => $ip,
        'is_own_ip' => true
    ]);
    exit;
}

// ========== BOT TESPİTİ ==========
function isBot($user_agent) {
    $bots = [
        'googlebot', 'bingbot', 'yandexbot', 'yandex', 'baiduspider', 'duckduckbot',
        'slurp', 'msnbot', 'facebot', 'facebookexternalhit',
        'applebot', 'twitterbot', 'linkedinbot', 'pinterestbot',
        'semrushbot', 'ahrefsbot', 'majesticbot', 'mozbot',
        'gptbot', 'openai', 'claudebot', 'anthropic',
        'perplexitybot', 'google-extended', 'meta-externalagent'
    ];
    $user_agent_lower = strtolower($user_agent);
    foreach ($bots as $bot) {
        if (strpos($user_agent_lower, $bot) !== false) {
            return true;
        }
    }
    return false;
}

// ========== AI KAYNAK TESPİTİ ==========
function getAIKaynak($referer) {
    if (strpos($referer, 'gemini.google.com') !== false || strpos($referer, 'bard.google.com') !== false) return 'Gemini AI';
    if (strpos($referer, 'chatgpt.com') !== false || strpos($referer, 'openai.com') !== false) return 'ChatGPT AI';
    if (strpos($referer, 'claude.ai') !== false) return 'Claude AI';
    if (strpos($referer, 'perplexity.ai') !== false) return 'Perplexity AI';
    if (strpos($referer, 'deepseek.com') !== false) return 'DeepSeek AI';
    if (strpos($referer, 'copilot.microsoft.com') !== false) return 'Copilot AI';
    if (strpos($referer, 'you.com') !== false) return 'You.com AI';
    return '';
}

// ========== BOT VE AI KARARI ==========
$ai_kaynak = getAIKaynak($referer);
$is_bot = isBot($user_agent) ? 1 : 0;
$bot_tipi = NULL;

if ($ai_kaynak) {
    $is_bot = 0;
    $bot_tipi = '👤 İnsan (AI ile geldi)';
} else {
    if ($is_bot) {
        $user_agent_lower = strtolower($user_agent);
        if (strpos($user_agent_lower, 'googlebot') !== false) {
            $bot_tipi = 'Google Bot';
        } elseif (strpos($user_agent_lower, 'yandex') !== false) {
            $bot_tipi = 'Yandex Bot';
        } elseif (strpos($user_agent_lower, 'bingbot') !== false || strpos($user_agent_lower, 'msnbot') !== false) {
            $bot_tipi = 'Bing Bot';
        } elseif (strpos($user_agent_lower, 'facebook') !== false || strpos($user_agent_lower, 'facebot') !== false || strpos($user_agent_lower, 'meta-externalagent') !== false) {
            $bot_tipi = 'Facebook Bot';
        } elseif (strpos($user_agent_lower, 'gptbot') !== false || strpos($user_agent_lower, 'openai') !== false) {
            $bot_tipi = 'ChatGPT Bot';
        } elseif (strpos($user_agent_lower, 'claude') !== false || strpos($user_agent_lower, 'anthropic') !== false) {
            $bot_tipi = 'Claude Bot';
        } elseif (strpos($user_agent_lower, 'perplexity') !== false) {
            $bot_tipi = 'Perplexity Bot';
        } else {
            $bot_tipi = 'Bot';
        }
    }
}

// ========== LOKASYON TESPİTİ ==========
$ulke = $sehir = $ilce = $yayin_adi = '';
if ($ip && $ip != '127.0.0.1' && $ip != '::1' && !strpos($ip, '192.168.') && !strpos($ip, '10.') && !strpos($ip, '172.')) {
    $ch = curl_init("http://ip-api.com/json/{$ip}?fields=status,country,city,district,isp");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200 && $response) {
        $data = json_decode($response, true);
        if ($data && isset($data['status']) && $data['status'] == 'success') {
            $ulke = $data['country'] ?? '';
            $sehir = $data['city'] ?? '';
            $ilce = $data['district'] ?? '';
            $yayin_adi = $data['isp'] ?? '';
        }
    }
}

// ========== VERİTABANINA KAYDET (SADECE GÜNCELLEME YAPAR) ==========
try {
    $stmt = $db->prepare("SELECT id FROM site_istatistikler WHERE oturum_id = ? AND sayfa = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$oturum_id, $sayfa]);
    $mevcut = $stmt->fetch();

    if ($mevcut) {
        $stmt = $db->prepare("UPDATE site_istatistikler SET 
            sayfa_sayisi = sayfa_sayisi + 1, 
            tarih = NOW(), 
            last_activity = NOW() 
            WHERE id = ?");
        $stmt->execute([$mevcut['id']]);
        $id = $mevcut['id'];
    } else {
        $stmt = $db->prepare("INSERT INTO site_istatistikler 
            (ip, sayfa, slug, oturum_id, user_agent, referer, tarih, last_activity,
            ulke, sehir, ilce, yayin_adi,
            is_bot, bot_tipi, ai_kaynak, sayfa_sayisi, oturum_suresi) 
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), ?, ?, ?, ?, ?, ?, ?, ?, 0)");
        $stmt->execute([
            $ip, $sayfa, $slug, $oturum_id, $user_agent, $referer,
            $ulke, $sehir, $ilce, $yayin_adi,
            $is_bot, $bot_tipi, $ai_kaynak, $sayfa_sayisi
        ]);
        $id = $db->lastInsertId();
    }

    echo json_encode([
        'success' => true,
        'oturum_id' => $oturum_id,
        'sayfa' => $sayfa,
        'is_bot' => $is_bot,
        'bot_tipi' => $bot_tipi,
        'ai_kaynak' => $ai_kaynak
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>