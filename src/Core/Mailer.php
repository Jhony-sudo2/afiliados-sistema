<?php

declare(strict_types=1);

namespace App\Core;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

final class Mailer
{
    private static function baseMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = app_env('MAIL_HOST', '');
        $mail->Port = (int) app_env('MAIL_PORT', '587');
        $mail->SMTPAuth = true;
        $mail->Username = app_env('MAIL_USERNAME', '');
        $mail->Password = app_env('MAIL_PASSWORD', '');
        $mail->SMTPSecure = app_env('MAIL_ENCRYPTION', 'tls');
        $mail->CharSet = 'UTF-8';
        $mail->setFrom(
            app_env('MAIL_FROM_ADDRESS', 'no-reply@example.com'),
            app_env('MAIL_FROM_NAME', app_env('APP_NAME', 'Sistema Territorial'))
        );
        $mail->isHTML(true);

        return $mail;
    }

    public static function sendVerificationEmail(string $toEmail, string $toName, string $token): bool
    {
        try {
            $mail = self::baseMailer();
            $mail->addAddress($toEmail, $toName);
            $url = app_url('/verify-email?token=' . urlencode($token));
            $mail->Subject = 'Verificación de correo';
            $mail->Body = '
                <p>Hola ' . e($toName) . ',</p>
                <p>Tu cuenta fue creada correctamente. Para habilitar el inicio de sesión, verifica tu correo con el siguiente enlace:</p>
                <p><a href="' . e($url) . '">' . e($url) . '</a></p>
                <p>Si no reconoces esta acción, puedes ignorar este mensaje.</p>
            ';
            $mail->AltBody = 'Verifica tu correo visitando: ' . $url;
            return $mail->send();
        } catch (Exception) {
            return false;
        }
    }

    public static function sendPasswordResetEmail(string $toEmail, string $toName, string $token): bool
    {
        try {
            $mail = self::baseMailer();
            $mail->addAddress($toEmail, $toName);
            $url = app_url('/reset-password?token=' . urlencode($token));
            $mail->Subject = 'Recuperación de contraseña';
            $mail->Body = '
                <p>Hola ' . e($toName) . ',</p>
                <p>Recibimos una solicitud para restablecer tu contraseña.</p>
                <p><a href="' . e($url) . '">' . e($url) . '</a></p>
                <p>El enlace tiene vigencia de 60 minutos.</p>
            ';
            $mail->AltBody = 'Restablece tu contraseña aquí: ' . $url;
            return $mail->send();
        } catch (Exception) {
            return false;
        }
    }
}
