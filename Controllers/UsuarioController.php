<?php

namespace Controllers;

use Models\UsuarioModel;
use RuntimeException;
use Services\Upload;
use Services\Validacao;

class UsuarioController extends BaseController
{
    private UsuarioModel $usuarios;

    public function __construct()
    {
        $this->usuarios = new UsuarioModel();
    }

    public function index(): void
    {
        $this->loadView('login/cadastro');
    }

    /** Autocadastro: sempre cria usuário comum (nível 1). */
    public function cadastrar(): void
    {
        $post = $_POST;
        $voltar = base_url('registrar');

        $dados = [
            'usuarios_nome' => trim((string) ($post['usuarios_nome'] ?? '')),
            'usuarios_escolaridade' => $post['usuarios_escolaridade'] ?? '',
            'usuarios_email' => trim((string) ($post['usuarios_email'] ?? '')),
            'usuarios_senha' => (string) ($post['usuarios_senha'] ?? ''),
        ];

        // Toda a validação acontece antes do upload (não deixa arquivos órfãos).
        if ($erro = Validacao::usuario($dados)) {
            $this->voltarComErro($voltar, $erro, $post);
        }

        if ($this->usuarios->emailEmUso($dados['usuarios_email'])) {
            $this->voltarComErro($voltar, 'Email ja cadastrado!', $post);
        }

        try {
            $imagem = Upload::imagem($_FILES['usuarios_imagem'] ?? null, 'Public/uploadImages');
        } catch (RuntimeException $e) {
            $this->voltarComErro($voltar, $e->getMessage(), $post, 'warning');
        }

        try {
            $this->usuarios->criar([
                'usuarios_nome' => $dados['usuarios_nome'],
                'usuarios_escolaridade' => $dados['usuarios_escolaridade'],
                'usuarios_email' => $dados['usuarios_email'],
                'usuarios_senha' => password_hash($dados['usuarios_senha'], PASSWORD_DEFAULT),
                'usuarios_imagem' => $imagem,
                'usuarios_ativo' => 1,
                'usuarios_nivel' => 1,
            ]);
        } catch (\PDOException $e) {
            Upload::remover($imagem);

            // 23000 = violação de UNIQUE (cadastro simultâneo com o mesmo e-mail).
            $mensagem = $e->getCode() === '23000' ? 'Email ja cadastrado!' : 'Erro ao realizar o cadastro, tente novamente!';
            $this->voltarComErro($voltar, $mensagem, $post);
        }

        $this->redirect(base_url('login'), 'Cadastro realizado com sucesso!');
    }
}
