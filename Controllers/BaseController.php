<?php

namespace Controllers;

/**
 * Funcionalidades comuns a todos os controllers.
 * Autenticação, perfil e CSRF são verificados antes, em core/Core.php.
 */
abstract class BaseController
{
    protected function loadView(string $view, array $dados = []): void
    {
        extract($dados);
        require dirname(__DIR__) . "/views/{$view}.php";
        // Dados de formulário só valem para a primeira renderização (padrão PRG).
        unset($_SESSION['old']);
    }

    protected function redirect(string $caminho, ?string $mensagem = null, string $cor = 'success'): never
    {
        if ($mensagem !== null) {
            flash($mensagem, $cor);
        }

        header('Location: ' . $caminho);
        exit;
    }

    /** Volta ao formulário com a mensagem de erro e os campos preenchidos. */
    protected function voltarComErro(string $caminho, string $mensagem, array $old = [], string $cor = 'danger'): never
    {
        unset($old['usuarios_senha'], $old['confirmarSenha'], $old['csrf_token']);
        $_SESSION['old'] = $old;

        $this->redirect($caminho, $mensagem, $cor);
    }

    protected function json(array $dados, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dados, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function usuarioId(): int
    {
        return (int) ($_SESSION['usuarios_logado']->usuarios_id ?? 0);
    }

    protected function idDaUrlOu400(string $valor): int
    {
        $id = id_da_url($valor);

        if ($id === null) {
            http_response_code(400);
            exit('ID inválido');
        }

        return $id;
    }
}
