<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

require_once __DIR__ . '/../email_config.php';

final class Mailer
{
    
    public static function enviarMFA(string $email, string $nome, string $codigo): void
    {
        self::validarConfiguracao();
        self::carregarPhpMailer();

        $mailer = new PHPMailer(true);
        $mailer->CharSet = 'UTF-8';
        $mailer->Encoding = 'base64';
        $mailer->isSMTP();
        $mailer->Host = SMTP_HOST;
        $mailer->Port = (int) SMTP_PORT;
        $mailer->SMTPAuth = true;
        $mailer->Username = SMTP_USER;
        $mailer->Password = SMTP_PASS;
        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        $mailer->setFrom(SMTP_FROM, defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'PCU Campus');
        $mailer->addAddress($email, $nome);
        $mailer->isHTML(true);
        $mailer->Subject = 'Código MFA - PCU Campus';

        $codigoSeguro = htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8');
        $nomeSeguro = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');

        $mailer->Body = <<<HTML
<p>Olá {$nomeSeguro},</p>
<p>O teu código de verificação MFA é:</p>
<p style="font-size:24px;font-weight:700;letter-spacing:4px;">{$codigoSeguro}</p>
<p>Este código expira em 10 minutos.</p>
HTML;

        $mailer->AltBody = "O teu código MFA é {$codigo}. Expira em 10 minutos.";
        $mailer->send();
    }

    private static function carregarPhpMailer(): void
    {
        $autoloadPath = __DIR__ . '/../vendor/autoload.php';

        if (!file_exists($autoloadPath)) {
            throw new RuntimeException('PHPMailer não está instalado. Executa composer install na pasta do projeto.');
        }

        require_once $autoloadPath;

        if (!class_exists(PHPMailer::class)) {
            throw new RuntimeException('PHPMailer não foi encontrado no autoload do Composer.');
        }
    }

    private static function validarConfiguracao(): void
    {
        foreach (['SMTP_HOST', 'SMTP_PORT', 'SMTP_USER', 'SMTP_PASS', 'SMTP_FROM'] as $constante) {
            if (!defined($constante) || trim((string) constant($constante)) === '') {
                throw new RuntimeException("Configuração SMTP incompleta: {$constante}.");
            }
        }
    }
}
