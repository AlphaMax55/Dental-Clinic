<?php
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Referrer-Policy: strict-origin-when-cross-origin");

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim']);
    exit;
}

require_once dirname(__DIR__) . '/inc/config.php';

try {
    // Son 50 kayıt
    $stmt = $db->prepare("SELECT 
        id, sayfa, ip, oturum_id, user_agent, tarih,
        sayfa_sayisi, oturum_suresi,
        ulke, sehir, ilce, yayin_adi,
        is_bot, bot_tipi, ai_kaynak
        FROM site_istatistikler 
        WHERE tarih > DATE_SUB(NOW(), INTERVAL 5 MINUTE) 
        ORDER BY id DESC LIMIT 50");
    $stmt->execute();
    $visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $visitors,
        'total' => count($visitors),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>