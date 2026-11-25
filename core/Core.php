<?php

use Models\UsuarioModel;
use Services\Logger;

/**
 * Roteador da aplicação.
 *
 * Cada rota é: [Controller, método, acesso, verbo HTTP]
 *   acesso: 'publico' | 'usuario' (qualquer usuário logado) | 'admin'
 *   verbo:  'GET' | 'POST' | 'ANY'
 *
 * As verificações de acesso e CSRF acontecem AQUI, antes de o controller rodar.
 */
class Core
{
    public function run(array $routes): void
    {
        $url = '/' . trim((string) ($_GET['url'] ?? ''), '/');
        $url = preg_replace('#/+#', '/', $url);

        foreach ($routes as $path => $rota) {
            $pattern = '#^' . preg_replace('/:[a-zA-Z]+/', '(\w+)', $path) . '$#';

            if (!preg_match($pattern, $url, $matches)) {
                continue;
            }

            array_shift($matches);
            [$controller, $acao] = $rota;
            $acesso = $rota[2] ?? 'usuario';
            $verbo = $rota[3] ?? 'ANY';
            $classe = 'Controllers\\' . $controller;

            if (!class_exists($classe) || !method_exists($classe, $acao)) {
                break;
            }

            $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

            if ($verbo !== 'ANY' && $metodo !== $verbo) {
                http_response_code(405);
                header('Allow: ' . $verbo);
                echo 'Método não permitido.';
                return;
            }

            if ($metodo === 'POST' && !$this->csrfValido()) {
                $this->negarCsrf();
                return;
            }

            if ($acesso !== 'publico' && !$this->autorizar($acesso)) {
                return;
            }

            call_user_func_array([new $classe(), $acao], $matches);
            return;
        }

        (new Controllers\NotFoundController())->index();
    }

    private function csrfValido(): bool
    {
        $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

        return csrf_valido($token);
    }

    private function negarCsrf(): void
    {
        Logger::aviso('Requisição POST com token CSRF inválido');

        // POST vazio costuma ser upload maior que post_max_size.
        $mensagem = empty($_POST) && empty($_FILES)
            ? 'Arquivo muito grande! Máximo permitido foi excedido.'
            : 'Sua sessão expirou. Tente novamente.';

        if ($this->ehAjax()) {
            http_response_code(419);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['erro' => $mensagem]);
            return;
        }

        flash($mensagem, 'danger');
        header('Location: ' . $this->paginaAnterior());
    }

    private function autorizar(string $acesso): bool
    {
        $sessao = $_SESSION['usuarios_logado'] ?? null;
        $usuario = $sessao ? (new UsuarioModel())->buscarPorId((int) $sessao->usuarios_id) : null;

        // Usuário excluído/desativado depois do login perde a sessão.
        if (!$usuario || (int) $usuario->usuarios_ativo !== 1) {
            unset($_SESSION['usuarios_logado']);

            if ($this->ehAjax()) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['erro' => 'Sessão expirada. Faça login novamente.']);
                return false;
            }

            flash('Faça login para acessar o sistema.', 'warning');
            header('Location: ' . base_url('login'));
            return false;
        }

        // Mantém nome, foto e nível atualizados (ex.: alterados pelo administrador).
        $_SESSION['usuarios_logado'] = $usuario;

        if ($acesso === 'admin' && (int) $usuario->usuarios_nivel !== 2) {
            Logger::aviso('Acesso negado à área administrativa');
            http_response_code(403);

            if ($this->ehAjax()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['erro' => 'Acesso negado.']);
                return false;
            }

            flash('Você não tem permissão para acessar esta área.', 'danger');
            header('Location: ' . base_url('home'));
            return false;
        }

        return true;
    }

    private function ehAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== ''
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    private function paginaAnterior(): string
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $host = parse_url($referer, PHP_URL_HOST);

        return ($referer && $host === parse_url(base_url(), PHP_URL_HOST)) ? $referer : base_url('login');
    }
}
