<?php
/**
 * E-posta şablonları ve gönderim fonksiyonu
 * PHPMailer manuel olarak dahil edilir
 */

// PHPMailer'ı manuel olarak dahil et
require_once dirname(__DIR__) . '/inc/phpmailer/PHPMailer.php';
require_once dirname(__DIR__) . '/inc/phpmailer/SMTP.php';
require_once dirname(__DIR__) . '/inc/phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Veritabanından ayar çekme yardımcı fonksiyonu
function getDbSetting($key, $default = null) {
    global $db;
    try {
        $stmt = $db->prepare("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = ?");
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return $value !== false ? $value : $default;
    } catch (Exception $e) {
        return $default;
    }
}

// 🔥 YENİ: 2. SMTP ile e-posta gönderim fonksiyonu
function sendMailWithSMTP($to, $subject, $message, $from = null, $fromName = null, $useSecondSMTP = false) {
    $mail = new PHPMailer(true);
    
    try {
        if ($useSecondSMTP) {
            // 🔥 2. SMTP AYARLARI (HOCANIN)
            $smtp_host = getDbSetting('smtp_host_2', '');
            $smtp_username = getDbSetting('smtp_username_2', '');
            $smtp_password = getDbSetting('smtp_password_2', '');
            $smtp_secure = getDbSetting('smtp_encryption_2', 'tls');
            $smtp_port = getDbSetting('smtp_port_2', 587);
            $site_name = 'Prof. Dr. İbrahim Duran';
        } else {
            // 🔥 1. SMTP AYARLARI (SENİN)
            $smtp_host = getDbSetting('smtp_host', '');
            $smtp_username = getDbSetting('smtp_username', '');
            $smtp_password = getDbSetting('smtp_password', '');
            $smtp_secure = getDbSetting('smtp_encryption', 'tls');
            $smtp_port = getDbSetting('smtp_port', 587);
            $site_name = getDbSetting('site_name', 'Prof. Dr. İbrahim Duran');
        }
        
        $site_email = $smtp_username; // SMTP kullanıcı adını gönderen mail olarak kullan
        
        // SMTP ayarları varsa kullan
        if (!empty($smtp_host) && !empty($smtp_username) && !empty($smtp_password)) {
            $mail->isSMTP();
            $mail->Host       = $smtp_host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp_username;
            $mail->Password   = $smtp_password;
            $mail->SMTPSecure = $smtp_secure;
            $mail->Port       = (int)$smtp_port;
            $mail->SMTPDebug  = 0;
        } else {
            $mail->isMail();
        }
        
        // UTF-8 Ayarları
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        
        // Gönderici
        $from = $from ?? $site_email;
        $fromName = $fromName ?? $site_name;
        $mail->setFrom($from, $fromName);
        $mail->addAddress($to);
        
        // Yanıt adresi
        if ($from != $to) {
            $mail->addReplyTo($from, $fromName);
        }
        
        // İçerik
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = strip_tags($message);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        return "E-posta gönderilemedi: " . $mail->ErrorInfo;
    }
}

// Eski sendMail fonksiyonu (geriye uyumluluk için)
function sendMail($to, $subject, $message, $from = null, $fromName = null) {
    return sendMailWithSMTP($to, $subject, $message, $from, $fromName, false);
}

// İletişim formu e-posta şablonu
function getContactNotificationEmail($data) {
    $subject = "=?UTF-8?B?" . base64_encode("İletişim") . "?=";
    
    $body = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <style>
            body { font-family: Arial, sans-serif; background: #f4f7fc; padding: 20px; margin: 0; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
            .header { border-bottom: 3px solid #2563eb; padding-bottom: 15px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
            .header h2 { color: #1e293b; margin: 0; font-size: 20px; }
            .header small { color: #64748b; font-size: 12px; }
            .field { margin-bottom: 15px; }
            .field-label { font-weight: bold; color: #475569; display: block; margin-bottom: 3px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
            .field-value { background: #f8fafc; padding: 10px 14px; border-radius: 8px; color: #0f172a; border-left: 3px solid #2563eb; }
            .message-box { background: #f8fafc; padding: 14px; border-radius: 8px; border-left: 3px solid #2563eb; white-space: pre-wrap; color: #0f172a; min-height: 80px; }
            .footer { margin-top: 25px; padding-top: 15px; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 12px; text-align: center; }
            .badge { display: inline-block; background: #2563eb; color: white; padding: 2px 10px; border-radius: 20px; font-size: 11px; }
            .info-row { display: flex; gap: 20px; flex-wrap: wrap; }
            .info-item { flex: 1; min-width: 150px; }
            @media (max-width: 480px) { .info-row { flex-direction: column; } }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>📩 Yeni İletişim Mesajı <span class="badge">YENİ</span></h2>
                <small>' . date('d.m.Y H:i:s') . '</small>
            </div>
            
            <div class="info-row">
                <div class="info-item">
                    <div class="field">
                        <span class="field-label">👤 Ad Soyad</span>
                        <div class="field-value">' . htmlspecialchars($data['ad_soyad'], ENT_QUOTES, 'UTF-8') . '</div>
                    </div>
                </div>
                <div class="info-item">
                    <div class="field">
                        <span class="field-label">📞 Telefon</span>
                        <div class="field-value">' . htmlspecialchars($data['telefon'], ENT_QUOTES, 'UTF-8') . '</div>
                    </div>
                </div>
            </div>
            
            <div class="field">
                <span class="field-label">✉️ E-posta</span>
                <div class="field-value">' . htmlspecialchars($data['email'], ENT_QUOTES, 'UTF-8') . '</div>
            </div>
            
            <div class="field">
                <span class="field-label">📌 Konu</span>
                <div class="field-value">' . htmlspecialchars($data['konu'], ENT_QUOTES, 'UTF-8') . '</div>
            </div>
            
            <div class="field">
                <span class="field-label">💬 Mesaj</span>
                <div class="message-box">' . nl2br(htmlspecialchars($data['mesaj'], ENT_QUOTES, 'UTF-8')) . '</div>
            </div>
            
            <div class="footer">
                Bu mesaj <strong>' . htmlspecialchars($data['ad_soyad'], ENT_QUOTES, 'UTF-8') . '</strong> tarafından iletişim formu üzerinden gönderilmiştir.<br>
                <a href="mailto:' . htmlspecialchars($data['email'], ENT_QUOTES, 'UTF-8') . '">Yanıtla: ' . htmlspecialchars($data['email'], ENT_QUOTES, 'UTF-8') . '</a><br>
                © ' . date('Y') . ' Prof. Dr. İbrahim Duran Diş Kliniği
            </div>
        </div>
    </body>
    </html>';
    
    return [
        'subject' => $subject,
        'body' => $body
    ];
}