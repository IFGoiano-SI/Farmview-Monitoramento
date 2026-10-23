<?php

namespace Controllers;

use Models\Database as Conexao;
use \PDO;
use \Ultis\RenderView;
use \Ultis\Email;
use \Ultis\FlaskService;

/**
 * Código Corrigido:
 * 1. Nome da classe ajustado para PascalCase (LoginController).
 * 2. Adicionado o 'extends RenderView' para permitir o carregamento das vistas.
 * 3. Substituída a função inexistente 'view()' por '$this->loadView()'.
 */
class LoginController extends RenderView
{
    private $usuarios;
    private $experimentos;
    private $participantes;
    private $homeController;
    private $flask;

    public function __construct()
    {
        // Certifique-se de que a tabela no banco de dados se chama 'usuarios'
        $this->usuarios = new Conexao('usuarios');
        $this->experimentos = new Conexao('experimentos');
        $this->participantes = new Conexao('participantes');
        $this->homeController = new HomeController;
        $this->flask = new FlaskService();
    }

    /**
     * Método auxiliar para redirecionamento com mensagens de sessão
     */
    protected function redirect($path, $mensagem = null)
    {
        if ($mensagem) {
            $_SESSION['msg'] = $mensagem;
        }
        header("Location: {$path}");
        exit();
    }

    /**
     * Exibe a tela de login
     */
    public function index()
    {
        $data = [];
        $data['pagina'] = 'Login';

        // Verifica se existe uma pasta 'login' dentro de 'views' com um 'index.php'
        return $this->loadView('login/index', $data);
    }

    public function autenticar()
    {
        $post = $_POST;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            die('Autenticar foi chamado sem POST. URL: ' . $_SERVER['REQUEST_URI']);
        }

        if (!isset($post['usuarios_email']) || !isset($post['usuarios_senha'])) {
            $_SESSION['msg'] = [
                'texto' => 'Preencha os campos de login!',
                'color' => 'danger',
            ];

            $this->redirect(base_url('login'));
        }

        $login = $post['usuarios_email'];
        $senha = $post['usuarios_senha'];

        $where = "usuarios_email = '$login'";

        $usuario = $this->usuarios->select(null, $where)->fetchObject();

    

        if (password_verify($senha, $usuario->usuarios_senha)) {
            $_SESSION['usuarios_logado'] = $usuario;

            if ($usuario) {

                if ($usuario->usuarios_nivel == 2) {
                    $_SESSION['usuarios_logado'] = $usuario;
                    $_SESSION['msg'] = [
                        'texto' => "Login realizado com sucesso! - Seja bem vindo <strong>" . $_SESSION['usuarios_logado']->usuarios_nome . "</strong>",
                        'color' => 'success',
                    ];
                    $this->redirect(base_url('adm/index'));
                } else if ($usuario->usuarios_nivel == 1) {
                    $_SESSION['usuarios_logado'] = $usuario;

                    $_SESSION['msg'] = [
                        'texto' => "Login realizado com sucesso! - Seja bem vindo <strong>" . $_SESSION['usuarios_logado']->usuarios_nome . "</strong>",
                        'color' => 'success',
                    ];

                    $usuario_id = $_SESSION['usuarios_logado']->usuarios_id;


                    $join = 'usuarios on usuarios_id = participantes_usuarios_id
                 INNER JOIN experimentos on experimentos_id = participantes_experimentos_id
                 INNER JOIN bancadas_experimentos on be_experimentos_id = experimentos_id
                 INNER JOIN bancadas on bancadas_id = be_bancadas_id';
                    $where = "experimentos_status = 'Ativo' AND participantes_usuarios_id =" . $usuario_id;
                    $limit  = 1;

                    $usuarioExperimento = $this->participantes->select($join, $where, $order = null, $limit)->fetch(PDO::FETCH_OBJ);

                    if (!$usuarioExperimento) {
                        $this->redirect(base_url('home'));
                    } else {
                        $this->redirect(base_url('home/dashboard'));
                    }
                } else {
                    $_SESSION['msg'] = [
                        'texto' => "Você não tem permissão para acessar esta área.",
                        'color' => 'danger',
                    ];
                    $this->redirect(base_url('login'));
                }
            } else {
                $_SESSION['msg'] = [
                    'texto' => 'Usuário ou senha inválida!',
                    'color' => 'danger',
                ];

                $_SESSION['old'] = $post;
                $this->redirect(base_url('login'));
            }
        } else {

            $_SESSION['msg'] = [
                'texto' => 'Usuário ou senha inválida!',
                'color' => 'danger',
            ];

            $_SESSION['old'] = $post;
            $this->redirect(base_url('login'));
        }
    }

    public function esqueciSenha()
    {

        return $this->loadView('login/esqueciSenha', []);
    }



    public function enviarEmail()
    {

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->loadView('login/esqueciSenha', []);
        }



        $email = $_POST['usuarios_email'] ?? '';

        if (empty($email)) {
            $_SESSION['msg'] = [
                'texto' => 'Informe um email',
                'color' => 'danger',
            ];

            return $this->loadView('login/esqueciSenha', []);
        }


        $where = "usuarios_email = '$email'";
        $usuario = $this->usuarios->select(null, $where)->fetch(PDO::FETCH_OBJ);

        if (!$usuario) {
            $_SESSION['msg'] = [
                'texto' => "Email inválido. Tente novamente!",
                'color' => 'danger',
            ];

            return $this->loadView('login/esqueciSenha', []);
        }

        $token = bin2hex(random_bytes(32));

        $usuarios_expira_em = date('Y-m-d H:i:s', strtotime('+1 hour'));


        $values = [
            'usuarios_reset_hash' => $token,
            'usuarios_expira_em' => $usuarios_expira_em,
        ];

        $whereUpdate = "usuarios_id = '$usuario->usuarios_id'";

        $this->usuarios->update($whereUpdate, $values);

        $emailClass = new Email();

        $emailC = $emailClass->enviarEmai($usuario->usuarios_email, $token);

        if ($emailC) {
            $_SESSION['msg'] = [
                'texto' => 'Email enviado com sucesso!',
                'color' => 'success',
            ];
        } else {

            $values = [
                'usuarios_reset_hash' => null,
                'usuarios_expira_em' => null,
            ];

            $whereUpdate = "usuarios_id = '$usuario->usuarios_id'";

            $this->usuarios->update($whereUpdate, $values);

            $_SESSION['msg'] = [
                'texto' => "Ocorreu um erro ao enviar seu email",
                'color' => 'danger',
            ];
        }

        return $this->loadView('login/esqueciSenha', []);
    }

    public function alterarSenha()
    {

        $token = $_GET['token'] ?? '';

        if (!$token) {
            $_SESSION['msg'] = [
                'texto' => 'Token inválido',
                'color' => 'danger',
            ];

            return $this->redirect(base_url('login'));
        }

        $where = "
        usuarios_reset_hash = '$token'
        and usuarios_expira_em > NOW()
        
        ";

        $usuario = $this->usuarios->select(null, $where)->fetch(PDO::FETCH_OBJ);


        if (!$usuario) {
            $_SESSION['msgm'] = [
                'texto' => 'Link expirado, tente novamente!',
                'color' => 'danger',
            ];
            return $this->redirect(base_url('login'));
        }

        $_SESSION['token'] = $token;
        return $this->loadView('login/alterarSenha', []);
    }


    public function salvarSenha()
    {

        $senha = $_POST['usuarios_senha'] ?? '';
        $confirmarSenha = $_POST['confirmarSenha'] ?? '';
        $token = $_POST['token'] ?? '';


        if (empty($senha) || empty($confirmarSenha)) {
            $_SESSION['msg'] = [
                'texto' => "Preencha os campos abaixo!",
                'color' => 'danger',
            ];

            return $this->loadView('login/alterarSenha', []);
        }


        if ($senha !== $confirmarSenha) {

            $_SESSION['msg'] = [
                'texto' => 'Os campos de senha não podem ser diferentes!',
                'color' => 'danger',
            ];

            return $this->loadView('login/alterarSenha', []);
        }


        if (!$token) {
            $_SESSION['msg'] = [
                'texto' => "Token inválido",
                'color' => 'danger',
            ];

            return $this->redirect(base_url('login'));
        }



        $where = "usuarios_reset_hash ='$token'
         AND usuarios_expira_em > NOW()";




        $usuario = $this->usuarios->select(null, $where)->fetch(PDO::FETCH_OBJ);

        if (!$usuario) {
            $_SESSION['msg'] = [
                'texto' => "Token expirado!",
                'color' => 'danger',
            ];

            return $this->redirect(base_url('login'));
        }


        $values = [
            'usuarios_senha' => password_hash($senha, PASSWORD_DEFAULT),

            'usuarios_reset_hash' => null,
            'usuarios_expira_em' => null,
        ];


        $whereUpdate = "usuarios_id ='$usuario->usuarios_id'";

        if (!$this->usuarios->update($whereUpdate, $values)) {
            $_SESSION['msg'] = [
                'texto' => "Erro ao alterar senha!",
                'color' => 'danger',
            ];

            return $this->loadView('login/alterarSenha', []);
        }

        unset($_SESSION['token']);

        $_SESSION['msg'] = [
            'texto' => 'Senha alterada com sucesso!',
            'color' => 'success',
        ];

        return $this->redirect(base_url('login'));
    }


    /**
     * Encerra a sessão
     */
    public function logout()
    {


        try {

            $this->flask->toggleDispositivo('api/lampada', 'off');
            $this->flask->toggleDispositivo('api/cooler', 'off');
            $this->flask->toggleDispositivo('api/bomba', 'off');
            $this->flask->toggleDispositivo('api/camera/toggle', 'off');
            $this->flask->toggleDispositivo('api/camera/gravar', 'off');

            $this->homeController->controladorTomada(1, 'off');
            $this->homeController->controladorTomada(2, 'off');
            $this->homeController->controladorTomada(3, 'off');
            $this->homeController->controladorTomada(4, 'off');

            $this->flask->toggleDispositivo('api/camera/toggle', 'off');
        } catch (\Exception $e) {
            error_log($e->getMessage());
        }

        $_SESSION['msg'] = [
            'texto' => 'Sessão encerrada com sucesso!',
            'color' => 'success',
        ];
        unset($_SESSION['usuarios_logado']);
        $this->redirect(base_url('login'));
    }
}
