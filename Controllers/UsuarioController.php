<?php

namespace Controllers;

// require_once("Models/Database.php");
// require_once("Config/Helpers.php");
// require_once("Ultis/RenderView.php");

use Models\Database as Conexao;
use \PDO;
use \Ultis\RenderView;

class UsuarioController extends RenderView


{
    private $usuario;

    public function __construct()
    {

        $this->usuario = new Conexao('usuarios');
    }

    public function index()
    {
        return $this->loadView('login/cadastro', []);
    }

    public function flash(string $texto, string $tipo = 'success'): array
    {
        return [
            'texto' => $texto,
            'tipo' => $tipo
        ];
    }


    public function cadastrar()
    {
        // var_dump($_POST);
        $data = [];
        $usuarios_imagem = 'uploadImages/default.png';


        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->loadView('login/cadastro', []);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES)) {
            $_SESSION['msg'] = [
                'texto' => 'Arquivo muito grande! Máximo permitido foi excedido.',
                'color' => 'danger'
            ];

            return $this->loadView('login/cadastro', $data);
        }


        // Verifica se existe valor na varivel
        if (
            !isset($_FILES['usuarios_imagem']) ||
            empty($_FILES['usuarios_imagem']['name']) ||
            $_FILES['usuarios_imagem']['error'] !== 0
        ) {

            $_SESSION['msg'] = [
                'texto' => 'Atenção! Informe uma imagem ao seu perfil!',
                'color' => 'warning'
            ];
            return $this->loadView('login/cadastro', $data);
        }
        $arquivo = $_FILES['usuarios_imagem']; //atribui o caminho para variavel


        // Validação se a imagem for maior que 2MB
        if (!$this->usuario->validaImagem($arquivo)) {
            $_SESSION['msg'] = [
                'texto' => "Erro! Selecione outra imagem até 2MB",
                'color' => 'danger',
            ];

            return $this->loadView('login/cadastro', $data);
        }
        // Validação para ver se a imagem esta no formato permitido
        if (!$this->usuario->tipoImagem($arquivo)) {
            $_SESSION['msg'] = [
                'texto' => 'Formato da imagem não permitido - Selecione apenas JPEG, JPG ou PNG',
                'color' => 'danger',
            ];


            return $this->loadView('login/cadastro', $data);
        }

        // Validação para inserção de imagem no tamanho permitido
        if (!$this->usuario->tamanhoImagem($arquivo)) {
            $_SESSION['msg'] = [
                'texto' => 'Erro! Tamanho de imagem menor que 300x300. Selecione uma imagem maior!',
                'color' => 'danger',
            ];



            return $this->loadView('login/cadastro', $data);
        }
        $pasta = __DIR__ . '/../Public/uploadImages/';
        $nomearquivo = uniqid() . "-" . basename($arquivo['name']);
        $caminhoFinal = $pasta . $nomearquivo; //String para o banco de dados

        if (!move_uploaded_file($arquivo['tmp_name'], $caminhoFinal)) {
            $_SESSION['msg'] = [
                'texto' => 'Erro ao fazer o upload da imagem',
                'color' => 'danger',
            ];
            return $this->loadView('login/cadastro', $data);
        }

        $usuarios_imagem = 'Public/uploadImages/' . $nomearquivo; //atribue valor para a variavel

        $post = $_POST;

        $values = [

            'usuarios_nome' => $post['usuarios_nome'] ?? '',
            'usuarios_escolaridade' => $post['usuarios_escolaridade'] ?? '',
            'usuarios_email' => $post['usuarios_email'],
            'usuarios_senha' => password_hash($post['usuarios_senha'], PASSWORD_DEFAULT),
            'usuarios_imagem' => $usuarios_imagem,
            'usuarios_ativo' => 1,
            'usuarios_nivel' => 1,


        ];

        $emailUsuario = $values['usuarios_email'];

        $where = "usuarios_email = '$emailUsuario'";

        $usuarioEmail = $this->usuario->select(null, $where)->fetchObject();


        // Se cadastrar sem nome
        if (empty($values['usuarios_nome'])) {
            $_SESSION['msg'] = [
                'texto' => 'O campo nome não pode ser vazio!',
                'color' => 'danger',
            ];

            return $this->loadView('login/cadastro', []);
        }

        // Cadastrar nomes menores que 3 caracteres
        if (strlen($values['usuarios_nome']) < 3) {
            $_SESSION['msg'] = [
                'texto' => 'O campo nome precisa ter mais de 3 caracteres!',
                'color' => 'danger',
            ];

            return $this->loadView('login/cadastro', []);
        }


        // Cadatrar nomes maiores que 40 caracteres
        if (strlen($values['usuarios_nome']) > 40) {
            $_SESSION['msg'] = [
                'texto' => 'O campo nome precisa ter menos de 40 caracteres!',
                'color' => 'danger',
            ];

            return $this->loadView('login/cadastro', []);
        }

        // Se a escolaridade vier vazia
        if (empty($values['usuarios_escolaridade'])) {
            $_SESSION['msg'] = [
                'texto' => 'Escolha o grau de escolaridade',
                'color' => 'danger',
            ];

            return $this->loadView('login/cadastro', []);
        }

        // Validação do email do usuario
        if (!filter_var($values['usuarios_email'], FILTER_VALIDATE_EMAIL)) {
            $_SESSION['msg'] = [
                'texto' => 'Formato de email inválido!',
                'color' => 'danger',
            ];

            return $this->loadView('login/cadastro', []);
        }

        if ($usuarioEmail) {
            $_SESSION['msg'] = [
                'texto' => 'Email ja cadastrado!',
                'color' => 'danger',
            ];

            return $this->loadView('login/cadastro', []);
        }


        // Validação de senha

        if (strlen($values['usuarios_senha']) < 5) {
            $_SESSION['msg'] = [
                'texto' => 'Não é permitido criar uma senha com menos de 5 caracteres!',
                'color' => 'danger',
            ];

            return $this->loadView('login/cadastro', []);
        }

        // echo '<pre>';

        // var_dump($emailUsuario);
        // var_dump($where);
        // var_dump($usuarioEmail);

        // exit;


        if ($this->usuario->insert($values)) {
            $_SESSION['msg'] = [
                'texto' => 'Cadastro realizado com sucesso!',
                'color' => 'success',
            ];


            header('Location: ' . base_url('login'));
            exit();
        } else {
            $_SESSION['msg'] = [
                'texto' => 'Erro ao realizar o cadastro, tente novamente!',
                'color' => 'danger',
            ];

            return $this->loadView('login/cadastro', []);
        }
    }

    public function listarUsuario()
    {
        $usuarioId = $_SESSION['usuarios_logado']->usuarios_id;
        $where = "usuarios_ativo = '1' and usuarios_id != '$usuarioId'";

        $usuariosCadastrado = $this->usuario->select($join = null, $where, $order = null, $limit = null)->fetchAll(PDO::FETCH_OBJ);
        return $_SESSION['usuarios'] = $usuariosCadastrado;
    }
}
