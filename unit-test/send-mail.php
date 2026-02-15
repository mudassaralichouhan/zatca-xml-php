<?php

require_once '../vendor/autoload.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// load your .env or define these directly for testing
$smtpHost = $_ENV['SMTP_HOST'] ?? 'smtp.example.com';
$smtpUser = $_ENV['SMTP_USER'] ?? 'your-smtp-user';
$smtpPass = $_ENV['SMTP_PASS'] ?? 'your-smtp-password';
$smtpPort = $_ENV['SMTP_PORT'] ?? 587;

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = $smtpHost;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtpUser;
    $mail->Password   = $smtpPass;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $smtpPort;

    // Recipients
    $mail->setFrom('no-reply@yourdomain.com', 'Your App Name');
    $mail->addAddress('vobosyvu@dreamclarify.org', 'Test Recipient');

    // Content
    $mail->isHTML(false);
    $mail->Subject = 'PHPMailer Test Email';
    $mail->Body    = "Hello!\n\nThis is a test email sent via PHPMailer.\n\n— Your App";

    $mail->send();
    echo json_encode(['message' => 'Test email sent successfully']);
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Mailer Error: ' . $mail->ErrorInfo
    ]);
}
