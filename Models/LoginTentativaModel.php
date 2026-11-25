<?php

namespace Models;

/**
 * Limita tentativas de login/recuperação por IP + e-mail.
 */
class LoginTentativaModel extends Database
{
    public const MAX_TENTATIVAS = 5;
    public const JANELA_MINUTOS = 15;

    public function __construct()
    {
        parent::__construct('login_tentativas');
    }

    public function bloqueado(string $ip, string $email): bool
    {
        $total = (int) $this->buscarValor(
            'SELECT COUNT(*) FROM login_tentativas
             WHERE ip = ? AND email = ? AND tentativa_em > (NOW() - INTERVAL ' . self::JANELA_MINUTOS . ' MINUTE)',
            [$ip, mb_strtolower($email)]
        );

        return $total >= self::MAX_TENTATIVAS;
    }

    public function registrarFalha(string $ip, string $email): void
    {
        $this->insert(['ip' => $ip, 'email' => mb_strtolower($email), 'tentativa_em' => date('Y-m-d H:i:s')]);
    }

    public function limpar(string $ip, string $email): void
    {
        $this->execute('DELETE FROM login_tentativas WHERE ip = ? AND email = ?', [$ip, mb_strtolower($email)]);
        // Remove registros antigos para a tabela não crescer indefinidamente.
        $this->execute('DELETE FROM login_tentativas WHERE tentativa_em < (NOW() - INTERVAL 1 DAY)');
    }
}
