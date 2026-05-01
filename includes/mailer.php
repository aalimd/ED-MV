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
        error_log('SMTP mail is not configured.');
        return false;
    }

    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!is_file($autoload)) {
        error_log('Composer autoload not found. Run composer install.');
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
<body style="font-family:Arial,sans-serif;color:#0f172a;line-height:1.6;">
  <h2 style="margin:0 0 12px;">Reset your {$appName} password</h2>
  <p>We received a request to reset your password. This link expires in 1 hour.</p>
  <p><a href="{$safeUrl}" style="display:inline-block;background:#2563eb;color:#fff;text-decoration:none;padding:12px 16px;border-radius:8px;font-weight:700;">Reset password</a></p>
  <p>If the button does not work, copy and paste this link into your browser:</p>
  <p style="word-break:break-all;"><a href="{$safeUrl}">{$safeUrl}</a></p>
  <p>If you did not request this, you can ignore this email.</p>
</body>
</html>
HTML;
    $text = "Reset your {$appName} password\n\n"
        . "Open this link within 1 hour:\n{$resetUrl}\n\n"
        . "If you did not request this, you can ignore this email.";

    return send_app_email($to, $subject, $html, $text);
}
