<?php

namespace App\Services;

use Exception;
use PHPMailer\PHPMailer\PHPMailer;
use App\Config\Config;
use App\Exceptions\BadRequestException;

class MailService
{
    private static $smtpHost;
    private static $smtpPort;
    private static $smtpUser ;
    private static $smtpPass;

    public static function init()
    {
        self::$smtpHost = Config::MAIL_HOST();
        self::$smtpPort = Config::MAIL_PORT();
        self::$smtpUser  = Config::MAIL_USERNAME();
        self::$smtpPass = Config::MAIL_PASSWORD();
    }

    public static function sendMail($to, $subject, $body): bool
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = self::$smtpHost;
            $mail->SMTPAuth = Config::MAIL_AUTH();
            if ($mail->SMTPAuth) {
                $mail->Username = self::$smtpUser ;
                $mail->Password = self::$smtpPass;
            }
            $encryption = Config::MAIL_ENCRYPTION();
            if ($encryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->SMTPAutoTLS = true;
            } elseif ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->SMTPAutoTLS = false;
            } else {
                $mail->SMTPSecure = false;
                $mail->SMTPAutoTLS = false;
            }
            $mail->Port = self::$smtpPort;

            $mail->setFrom(Config::MAIL_FROM(), Config::APP_NAME());
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
            $mail->smtpClose();
            return true;
        } catch (Exception $e) {
            $mail->smtpClose();
            throw new BadRequestException([
                'message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}",
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
