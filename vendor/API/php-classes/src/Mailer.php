<?php

namespace Hcode;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private $mail;

    public function __construct()
    {
        $host = (string) self::env('SMTP_HOST', '');
        $user = (string) self::env('SMTP_USER', '');
        $pass = (string) self::env('SMTP_PASSWORD', '');
        $port = (int) self::env('SMTP_PORT', 465);
        $fromEmail = (string) self::env('SMTP_FROM_EMAIL', $user);
        $fromName = (string) self::env('SMTP_FROM_NAME', 'Prato Cheio');

        if ($host === '' || $user === '' || $pass === '' || $fromEmail === '') {
            throw new \Exception('SMTP nao configurado. Defina SMTP_HOST, SMTP_USER, SMTP_PASSWORD e SMTP_FROM_EMAIL.');
        }

        $this->mail = new PHPMailer(true);

        try {
            $this->mail->isSMTP();
            $this->mail->Host = gethostbyname($host);
            $this->mail->SMTPAuth = true;
            $this->mail->Username = $user;
            $this->mail->Password = $pass;
            $this->mail->SMTPSecure = self::smtpSecure();
            $this->mail->Port = $port;
            $this->mail->CharSet = 'UTF-8';
            $this->mail->Timeout = 30;
            $this->mail->SMTPDebug = self::envBool('SMTP_DEBUG', false) ? 2 : 0;

            if (self::envBool('SMTP_ALLOW_INSECURE_SSL', false)) {
                $this->mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];
            }

            $this->mail->setFrom($fromEmail, $fromName);
        } catch (Exception $e) {
            throw new \Exception("Erro ao configurar e-mail: " . $e->getMessage());
        }
    }

    public function send(
        string $toAddress,
        string $toName,
        string $subject,
        string $htmlBody,
        string $altBody = ''
    ): bool {
        try {
            $this->mail->clearAddresses();
            $this->mail->clearAttachments();

            $this->mail->addAddress($toAddress, $toName);
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body = $htmlBody;
            $this->mail->AltBody = $altBody !== '' ? $altBody : strip_tags($htmlBody);

            if (!$this->mail->send()) {
                throw new \Exception($this->mail->ErrorInfo);
            }

            return true;
        } catch (Exception $e) {
            throw new \Exception("Erro ao enviar e-mail: " . $e->getMessage());
        }
    }

    public static function quickSend(
        string $toAddress,
        string $toName,
        string $subject,
        string $htmlBody,
        string $altBody = ''
    ): bool {
        $mailer = new self();

        return $mailer->send(
            $toAddress,
            $toName,
            $subject,
            $htmlBody,
            $altBody
        );
    }

    private static function env(string $key, $default = null)
    {
        if (function_exists('\\pc_env')) {
            return \pc_env($key, $default);
        }

        $value = getenv($key);
        return ($value === false || $value === '') ? $default : $value;
    }

    private static function envBool(string $key, bool $default = false): bool
    {
        if (function_exists('\\pc_env_bool')) {
            return \pc_env_bool($key, $default);
        }

        $value = self::env($key, null);
        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    private static function smtpSecure(): string
    {
        $secure = strtolower((string) self::env('SMTP_SECURE', 'smtps'));
        if ($secure === 'tls') {
            return PHPMailer::ENCRYPTION_STARTTLS;
        }

        return PHPMailer::ENCRYPTION_SMTPS;
    }
}
