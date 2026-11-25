<?php

/**
 * Configuração central da aplicação.
 *
 * Os valores vêm de variáveis de ambiente (Docker) ou de um arquivo `.env`
 * na raiz do projeto (desenvolvimento local / XAMPP). Os padrões abaixo
 * reproduzem o comportamento anterior para não quebrar ambientes existentes.
 */

function carregarArquivoEnv(string $arquivo): void
{
    if (!is_file($arquivo) || !is_readable($arquivo)) {
        return;
    }

    foreach (file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linha) {
        $linha = trim($linha);

        if ($linha === '' || str_starts_with($linha, '#') || !str_contains($linha, '=')) {
            continue;
        }

        [$chave, $valor] = array_map('trim', explode('=', $linha, 2));
        $valor = trim($valor, "\"'");

        // Variáveis já definidas no ambiente (Docker) têm prioridade.
        if (getenv($chave) === false) {
            putenv("{$chave}={$valor}");
            $_ENV[$chave] = $valor;
        }
    }
}

function env(string $chave, $padrao = null)
{
    $valor = getenv($chave);

    if ($valor === false || $valor === '') {
        return $padrao;
    }

    return match (strtolower($valor)) {
        'true' => true,
        'false' => false,
        default => $valor,
    };
}

function config(string $chave, $padrao = null)
{
    static $config = null;

    if ($config === null) {
        carregarArquivoEnv(dirname(__DIR__) . '/.env');

        $config = [
            'app.debug' => (bool) env('APP_DEBUG', false),
            'app.url' => rtrim((string) env('APP_URL', ''), '/'),

            'db.host' => env('DB_HOST', 'localhost'),
            'db.port' => env('DB_PORT', '3309'),
            'db.name' => env('DB_NAME', 'CEBIOHUB'),
            'db.user' => env('DB_USER', 'root'),
            'db.pass' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'root',

            'smtp.host' => env('SMTP_HOST', 'smtp.gmail.com'),
            'smtp.port' => (int) env('SMTP_PORT', 587),
            'smtp.user' => env('SMTP_USER', ''),
            'smtp.pass' => env('SMTP_PASS', ''),
            'smtp.from' => env('SMTP_FROM', env('SMTP_USER', '')),
            'smtp.from_name' => env('SMTP_FROM_NAME', 'FarmView'),

            // RASPBERRY_PI é aceito por compatibilidade com o docker-compose antigo.
            'rasp.host' => env('RASPBERRY_HOST', env('RASPBERRY_PI', '192.168.0.127')),
            'rasp.port' => (int) env('RASPBERRY_PORT', 5000),
            'rasp.token' => env('RASPBERRY_TOKEN', ''),
            'rasp.timeout' => (int) env('RASPBERRY_TIMEOUT', 3),
        ];
    }

    return $config[$chave] ?? $padrao;
}
