<?php

namespace Controllers;

use Models\ExperimentoModel;
use Models\LoginTentativaModel;
use Models\UsuarioModel;
use Services\Email;
use Services\Logger;
use Services\Validacao;

class LoginController extends BaseController
{
    private UsuarioModel $usuarios;
    private LoginTentativaModel $tentativas;

    public function __construct()
    {
        $this->usuarios = new UsuarioModel();
        $this->tentativas = new LoginTentativaModel();
    }

    public function index(): void
    {
        $this->loadView('login/index', ['pagina' => 'Login']);
    }

    public function autenticar(): void
    {
        $email = trim((string) ($_POST['usuarios_email'] ?? ''));
        $senha = (string) ($_POST['usuarios_senha'] ?? '');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
        $old = ['usuarios_email' => $email];

        if ($email === '' || $senha === '') {
            $this->voltarComErro(base_url('login'), 'Preencha os campos de login!', $old);
        }

        if ($this->tentativas->bloqueado($ip, $email)) {
            Logger::aviso('Login bloqueado por excesso de tentativas', ['email' => $email]);
            $this->voltarComErro(
                base_url('login'),
                'Muitas tentativas de login. Aguarde ' . LoginTentativaModel::JANELA_MINUTOS . ' minutos e tente novamente.',
                $old
            );
        }

        $usuario = $this->usuarios->buscarParaLogin($email);

        if (!$usuario || !password_verify($senha, (string) $usuario->usuarios_senha)) {
            $this->tentativas->registrarFalha($ip, $email);
            $this->voltarComErro(base_url('login'), 'Usuário ou senha inválida!', $old);
        }

        // A conta é verificada ANTES de criar a sessão.
        if ((int) $usuario->usuarios_ativo !== 1) {
            $this->voltarComErro(base_url('login'), 'Sua conta foi excluída, certifique com o administrador!', $old);
        }

        $nivel = (int) $usuario->usuarios_nivel;

        if (!in_array($nivel, [1, 2], true)) {
            $this->voltarComErro(base_url('login'), 'Você não tem permissão para acessar esta área.', $old);
        }

        $this->tentativas->limpar($ip, $email);

        if (password_needs_rehash($usuario->usuarios_senha, PASSWORD_DEFAULT)) {
            $this->usuarios->atualizar((int) $usuario->usuarios_id, [
                'usuarios_senha' => password_hash($senha, PASSWORD_DEFAULT),
            ]);
        }

        // Novo ID de sessão a cada login (evita fixação de sessão).
        session_regenerate_id(true);
        $_SESSION['usuarios_logado'] = $this->usuarios->buscarPorId((int) $usuario->usuarios_id);

        flash('Login realizado com sucesso! - Seja bem vindo ' . $usuario->usuarios_nome);

        if ($nivel === 2) {
            $this->redirect(base_url('adm/index'));
        }

        $temExperimento = (new ExperimentoModel())->ativoDoUsuario((int) $usuario->usuarios_id);
        $this->redirect(base_url($temExperimento ? 'home/dashboard' : 'home'));
    }

    public function esqueciSenha(): void
    {
        $this->loadView('login/esqueciSenha');
    }

    public function enviarEmail(): void
    {
        $email = trim((string) ($_POST['usuarios_email'] ?? ''));
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
        $mensagemGenerica = 'Se o email estiver cadastrado, você receberá um link para alterar a senha.';

        if ($email === '') {
            $this->redirect(base_url('login/esqueciSenha'), 'Informe um email', 'danger');
        }

        if ($this->tentativas->bloqueado($ip, 'reset:' . $email)) {
            $this->redirect(base_url('login/esqueciSenha'), 'Muitas solicitações. Aguarde alguns minutos.', 'danger');
        }

        $this->tentativas->registrarFalha($ip, 'reset:' . $email);
        $usuario = $this->usuarios->buscarPorEmail($email);

        // Resposta idêntica para e-mails cadastrados ou não (evita enumeração).
        if (!$usuario || (int) $usuario->usuarios_ativo !== 1) {
            $this->redirect(base_url('login/esqueciSenha'), $mensagemGenerica);
        }

        $token = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $this->usuarios->definirTokenReset((int) $usuario->usuarios_id, hash('sha256', $token), $expira);

        if (!(new Email())->enviarRecuperacaoSenha($usuario->usuarios_email, $token)) {
            $this->usuarios->limparTokenReset((int) $usuario->usuarios_id);
            $this->redirect(base_url('login/esqueciSenha'), 'Ocorreu um erro ao enviar seu email', 'danger');
        }

        $this->redirect(base_url('login/esqueciSenha'), $mensagemGenerica);
    }

    public function alterarSenha(): void
    {
        $token = (string) ($_GET['token'] ?? '');

        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            $this->redirect(base_url('login'), 'Token inválido', 'danger');
        }

        if (!$this->usuarios->buscarPorTokenReset(hash('sha256', $token))) {
            $this->redirect(base_url('login'), 'Link expirado, tente novamente!', 'danger');
        }

        $_SESSION['token'] = $token;
        $this->loadView('login/alterarSenha', ['token' => $token]);
    }

    public function salvarSenha(): void
    {
        $senha = (string) ($_POST['usuarios_senha'] ?? '');
        $confirmar = (string) ($_POST['confirmarSenha'] ?? '');
        $token = (string) ($_POST['token'] ?? '');
        $voltar = base_url('login/alterarSenha?token=' . urlencode($token));

        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            $this->redirect(base_url('login'), 'Token inválido', 'danger');
        }

        if ($senha === '' || $confirmar === '') {
            $this->redirect($voltar, 'Preencha os campos abaixo!', 'danger');
        }

        if ($senha !== $confirmar) {
            $this->redirect($voltar, 'Os campos de senha não podem ser diferentes!', 'danger');
        }

        if ($erro = Validacao::senha($senha)) {
            $this->redirect($voltar, $erro, 'danger');
        }

        $usuario = $this->usuarios->buscarPorTokenReset(hash('sha256', $token));

        if (!$usuario || !hash_equals((string) $usuario->usuarios_reset_hash, hash('sha256', $token))) {
            $this->redirect(base_url('login'), 'Token expirado!', 'danger');
        }

        $this->usuarios->redefinirSenha((int) $usuario->usuarios_id, password_hash($senha, PASSWORD_DEFAULT));
        unset($_SESSION['token']);
        Logger::info('Senha redefinida por token', ['alvo' => $usuario->usuarios_id]);

        $this->redirect(base_url('login'), 'Senha alterada com sucesso!');
    }

    /**
     * Encerra apenas a sessão. Os dispositivos da bancada NÃO são desligados:
     * sair do sistema não deve interromper um experimento em andamento.
     */
    public function logout(): void
    {
        unset($_SESSION['usuarios_logado'], $_SESSION['gravacao_experimento_id']);
        session_regenerate_id(true);

        $this->redirect(base_url('login'), 'Sessão encerrada com sucesso!');
    }
}
