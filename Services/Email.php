<?php

namespace Services;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Email
{
    /**
     * Envia o link de redefinição de senha. Credenciais SMTP vêm do ambiente.
     */
    public function enviarRecuperacaoSenha(string $email, string $token): bool
    {
        if (!config('smtp.user') || !config('smtp.pass')) {
            Logger::erro('SMTP não configurado (SMTP_USER/SMTP_PASS ausentes).');
            return false;
        }

        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';

        try {
            $mail->isSMTP();
            $mail->Host = config('smtp.host');
            $mail->SMTPAuth = true;
            $mail->Username = config('smtp.user');
            $mail->Password = config('smtp.pass');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = config('smtp.port');

            $mail->setFrom(config('smtp.from'), config('smtp.from_name'));
            $mail->addAddress($email);

            $link = base_url('login/alterarSenha?token=' . urlencode($token));

            $mail->isHTML(true);
            $mail->Subject = 'Recuperação de senha';
            $mail->Body = '<h2>Recuperação de senha</h2>
                <p>Clique no link abaixo para alterar sua senha. O link expira em 1 hora.</p>
                <a href="' . esc($link) . '">Clique aqui para alterar sua senha</a>
                <p>Se você não solicitou a alteração, ignore este e-mail.</p>';
            $mail->AltBody = "Acesse o link para alterar sua senha (expira em 1 hora): {$link}";

            return $mail->send();
        } catch (Exception $e) {
            Logger::erro('Falha ao enviar e-mail de recuperação', ['erro' => $mail->ErrorInfo]);
            return false;
        }
    }
}
