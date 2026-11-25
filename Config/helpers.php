<?php

function base_url($path = '')
{
    $configurada = config('app.url');

    if ($configurada) {
        return $configurada . '/' . ltrim($path, '/');
    }

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Pega o subdiretório automaticamente (ex: /CEBIOHUB)
    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');

    return $protocol . '://' . $host . $base . '/' . ltrim($path, '/');
}

function esc($valor)
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Renderiza um alerta Bootstrap. O texto é sempre escapado.
 */
function msg($texto, $tipo = 'success')
{
    $tiposValidos = ['success', 'danger', 'warning', 'info'];
    $tipo = in_array($tipo, $tiposValidos, true) ? $tipo : 'info';

    $icone = match ($tipo) {
        'danger' => '<i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>',
        'warning' => '<i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>',
        default => '<i class="bi bi-check-circle-fill" aria-hidden="true"></i>',
    };

    return '
    <div class="alert alert-' . $tipo . ' alert-dismissible fade show" role="alert">
    ' . $icone . ' <strong>' . esc($texto) . '</strong>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>';
}

function flash(string $texto, string $cor = 'success'): void
{
    $_SESSION['msg'] = ['texto' => $texto, 'color' => $cor];
}

/** Valor enviado anteriormente em um formulário (padrão PRG). */
function old(string $campo, $padrao = '')
{
    return $_SESSION['old'][$campo] ?? $padrao;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . esc(csrf_token()) . '">';
}

function csrf_valido(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function usuario_logado(): ?object
{
    return $_SESSION['usuarios_logado'] ?? null;
}

function eh_admin(): bool
{
    return (int) (usuario_logado()->usuarios_nivel ?? 0) === 2;
}

/** Codifica IDs na URL no mesmo formato já usado pelas telas de administração. */
function id_url(int $id): string
{
    return rtrim(base64_encode((string) $id), '=');
}

function id_da_url(string $valor): ?int
{
    $decodificado = base64_decode($valor, true);

    if ($decodificado === false || !ctype_digit($decodificado)) {
        return null;
    }

    return (int) $decodificado;
}

/**
 * Mantido por compatibilidade com as views. A autenticação agora é feita no
 * roteador (core/Core.php) ANTES da execução do controller.
 */
function redirectPages()
{
    if (!usuario_logado()) {
        flash('Faça login para acessar o sistema.', 'warning');
        header('Location: ' . base_url('login'));
        exit;
    }
}

/** Inclui um trecho de view reutilizável (views/partials). */
function partial(string $nome, array $dados = []): void
{
    extract($dados);
    include dirname(__DIR__) . "/views/partials/{$nome}.php";
}

/** Formata data do banco para exibição; devolve "-" quando vazia. */
function data_br($valor, string $formato = 'd/m/Y'): string
{
    if (empty($valor) || str_starts_with((string) $valor, '0000-00-00')) {
        return '-';
    }

    $timestamp = strtotime((string) $valor);

    return $timestamp ? date($formato, $timestamp) : '-';
}

/** Lista as luzes salvas em JSON (aceita registros antigos gravados como string). */
function luzes_texto($json, string $vazio): string
{
    $luzes = json_decode((string) $json, true);

    return is_array($luzes) && $luzes ? implode(', ', $luzes) : $vazio;
}
