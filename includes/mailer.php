<?php
/**
 * SMTP mail helper.
 */

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../config.php';

function mailer_enabled(): bool
{
    return defined('SMTP_HOST') && SMTP_HOST !== ''
        && defined('SMTP_USERNAME') && SMTP_USERNAME !== ''
        && defined('SMTP_PASSWORD') && SMTP_PASSWORD !== ''
        && defined('MAIL_FROM') && MAIL_FROM !== '';
}

function send_app_email(string $to, string $subject, string $htmlBody, ?string $textBody = null): bool
{
    if (!mailer_enabled()) {
        error_log("MAILER ERROR: SMTP not configured in config.php. Trying to send to: {$to}");
        return false;
    }

    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!is_file($autoload)) {
        error_log("MAILER ERROR: Composer autoload not found at {$autoload}. Run composer install.");
        return false;
    }

    require_once $autoload;

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->Port = (int) (defined('SMTP_PORT') ? SMTP_PORT : 587);
        $mail->Timeout = (int) (defined('SMTP_TIMEOUT') ? SMTP_TIMEOUT : 10);
        $mail->SMTPKeepAlive = false;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Encoding = 'base64';
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->setFrom(MAIL_FROM, defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : APP_NAME);
        $mail->addAddress($to);

        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody ?: trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody)));
        $mail->send();
        return true;
    } catch (Throwable $e) {
        error_log('Mail send failed: ' . $e->getMessage());
        return false;
    }
}

function send_password_reset_email(string $to, string $resetUrl): bool
{
    $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $appName = htmlspecialchars(APP_NAME, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $subject = APP_NAME . ' password reset';
    $html = <<<HTML
<!doctype html>
<html>
<body style="font-family:Arial,sans-serif;background-color:#ffffff;color:#000000;line-height:1.6;margin:0;padding:20px;">
  <div style="max-width:600px;margin:0 auto;background-color:#ffffff;padding:20px;border:1px solid #e2e8f0;border-radius:12px;">
    <h2 style="margin:0 0 16px;color:#000000;font-size:24px;font-weight:bold;">Reset your {$appName} password</h2>
    <p style="margin:0 0 20px;color:#333333;font-size:16px;">We received a request to reset your password. This link expires in 1 hour.</p>
    <p style="margin:0 0 24px;"><a href="{$safeUrl}" style="display:inline-block;background-color:#2563eb;color:#ffffff;text-decoration:none;padding:14px 24px;border-radius:8px;font-weight:bold;font-size:16px;">Reset password</a></p>
    <hr style="border:0;border-top:1px solid #e2e8f0;margin:24px 0;">
    <p style="margin:0 0 10px;color:#666666;font-size:14px;">If the button does not work, copy and paste this link into your browser:</p>
    <p style="word-break:break-all;margin:0;font-size:14px;"><a href="{$safeUrl}" style="color:#2563eb;">{$safeUrl}</a></p>
    <p style="margin:20px 0 0;color:#999999;font-size:12px;">If you did not request this, you can safely ignore this email.</p>
  </div>
</body>
</html>
HTML;
    $text = "Reset your {$appName} password\n\n"
        . "Open this link within 1 hour:\n{$resetUrl}\n\n"
        . "If you did not request this, you can ignore this email.";

    return send_app_email($to, $subject, $html, $text);
}

function send_email_verification_email(string $to, string $name, string $verifyUrl): bool
{
    $safeUrl = htmlspecialchars($verifyUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $safeName = htmlspecialchars($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $appName = htmlspecialchars(APP_NAME, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $subject = APP_NAME . ' email verification';
    $html = <<<HTML
<!doctype html>
<html>
<body style="font-family:Arial,sans-serif;background-color:#ffffff;color:#000000;line-height:1.6;margin:0;padding:20px;">
  <div style="max-width:600px;margin:0 auto;background-color:#ffffff;padding:20px;border:1px solid #e2e8f0;border-radius:12px;">
    <h2 style="margin:0 0 16px;color:#000000;font-size:24px;font-weight:bold;">Verify your {$appName} email</h2>
    <p style="margin:0 0 20px;color:#333333;font-size:16px;">Hi {$safeName}, please confirm this email address before signing in. This link expires in 24 hours.</p>
    <p style="margin:0 0 24px;"><a href="{$safeUrl}" style="display:inline-block;background-color:#2563eb;color:#ffffff;text-decoration:none;padding:14px 24px;border-radius:8px;font-weight:bold;font-size:16px;">Verify email</a></p>
    <hr style="border:0;border-top:1px solid #e2e8f0;margin:24px 0;">
    <p style="margin:0 0 10px;color:#666666;font-size:14px;">If the button does not work, copy and paste this link into your browser:</p>
    <p style="word-break:break-all;margin:0;font-size:14px;"><a href="{$safeUrl}" style="color:#2563eb;">{$safeUrl}</a></p>
    <p style="margin:20px 0 0;color:#999999;font-size:12px;">If you did not create this account, you can ignore this email.</p>
  </div>
</body>
</html>
HTML;
    $text = "Verify your {$appName} email\n\n"
        . "Open this link within 24 hours:\n{$verifyUrl}\n\n"
        . "If you did not create this account, you can ignore this email.";

    return send_app_email($to, $subject, $html, $text);
}
