<?php

namespace Services;

/**
 * Log simples em arquivo (storage/logs/app.log), uma linha JSON por evento.
 */
class Logger
{
    public static function info(string $mensagem, array $contexto = []): void
    {
        self::escrever('INFO', $mensagem, $contexto);
    }

    public static function aviso(string $mensagem, array $contexto = []): void
    {
        self::escrever('WARNING', $mensagem, $contexto);
    }

    public static function erro(string $mensagem, array $contexto = []): void
    {
        self::escrever('ERROR', $mensagem, $contexto);
    }

    private static function escrever(string $nivel, string $mensagem, array $contexto): void
    {
        $contexto['usuario_id'] = $_SESSION['usuarios_logado']->usuarios_id ?? null;
        $contexto['ip'] = $_SERVER['REMOTE_ADDR'] ?? null;
        $contexto['rota'] = $_GET['url'] ?? null;

        $linha = json_encode([
            'data' => date('Y-m-d H:i:s'),
            'nivel' => $nivel,
            'mensagem' => $mensagem,
            'contexto' => $contexto,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $pasta = dirname(__DIR__) . '/storage/logs';

        if (is_dir($pasta) && is_writable($pasta)) {
            file_put_contents($pasta . '/app.log', $linha . PHP_EOL, FILE_APPEND | LOCK_EX);
        } else {
            error_log($linha);
        }
    }
}
