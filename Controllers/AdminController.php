<?php

namespace Controllers;

use Exception;
use Models\Database as Conexao;
use \PDO;
use \Ultis\RenderView;
use Datetime;


class AdminController extends RenderView
{

    private $usuario;
    private $experimentos;
    private $conexao;
    private $conexaoMoreUser;
    private $bancadasExperimento;
    private $allUsers;


    public function __construct()
    {
        $this->usuario = new UsuarioController();
        $this->conexao = new Conexao();
        $this->conexaoMoreUser = new Conexao('participantes');
        $this->bancadasExperimento = new Conexao('bancadas_experimentos');
        $this->allUsers = new Conexao('usuarios');
    }

    protected function redirect($path, $mensagem = null)
    {
        if ($mensagem) {
            $_SESSION['msg'] = $mensagem;
        }
        header("Location: {$path}");
        exit();
    }


    public function index()
    {
        $data = [];

        $data['pagina'] = "Experimento de usuario";

        $this->usuario->listarUsuario();

        $data['quantidadeExperimentos'] = $this->conexao->selectQuantidadeExperimentos();
        $data['topExperimentos'] = $this->conexao->mostrarMaiorQuantidadeExperimentoUsuario();
        $data['totalUsuarios'] = $this->conexao->totalQuantidadeUsuarios();
        $data['user'] = $this->moreUser();

        return $this->loadView('adm/index', $data);
    }


    public function infoUser($id)
    {
        $experimentos_id = base64_decode($id, true);

        if ($experimentos_id === false || !ctype_digit($experimentos_id)) {
            http_response_code(400);
            exit('ID inválido');
        }

        $where = "e.experimentos_id = " . (int) $experimentos_id;

        $data['infoUser'] = $this->moreUser($where)[0] ?? null;

        return $this->loadView('adm/infoUser', $data);
    }

    // Função para ativar os experimentos finalizados, contendo regras de apenas 1 experimento por pessoa

    public function ativarExperimentos()
    {

        $usuario = $_POST['usuarios_id'];
        $experimento = (int) $_POST['experimentos_id'];

        $idCriptografado = rtrim(base64_encode((string) $experimento), '=');

        $validar = $this->conexao->verificarExperimentosAtivo($usuario);

        if ($validar >= 1) {
            $_SESSION['msg'] = [
                'texto' => 'O usuário ja possui um experimento ativo!',
                'color' => 'danger',
            ];

            $this->redirect(base_url('adm/infoUser/' . $idCriptografado));
        }

        $join = "usuarios as u on u.usuarios_id = participantes_usuarios_id INNER JOIN experimentos as e on e.experimentos_id = participantes_experimentos_id";

        $where = "e.experimentos_id = " . (int) $experimento;
        $values = "e.experimentos_status = 'Ativo', e.experimentos_encerrado = '0000-00-00 00:00:00'";

        if (!$this->conexaoMoreUser->UpdateExperimentos($join, $where, $values)) {

            $_SESSION['msg'] = [
                'texto' => 'Erro ao ativar o experimento!',
                'color' => 'danger',
            ];

            $this->redirect(base_url('adm/infoUser/' . $idCriptografado));
        }


        $_SESSION['msg'] = [
            'texto' => 'Experimento ativado com sucesso!',
            'color' => 'success',
        ];

        $this->redirect(base_url('adm/infoUser/' . $idCriptografado));
    }


    // Função para editar experimentos de forma especifica
    public function editarExperimentos()
    {

        $post = $_POST;

        $experimentoId = $post['experimentos_id'];
        $idCriptografado = rtrim(base64_encode((string) $experimentoId), '=');
        $join = "bancadas AS b
        ON b.bancadas_id = bancadas_experimentos.be_bancadas_id
        INNER JOIN experimentos AS e
        ON e.experimentos_id = bancadas_experimentos.be_experimentos_id";

        $where = "e.experimentos_id = " . (int) $experimentoId;

        $experimento =  $this->moreUser($where)[0] ?? null;



        if (!$experimento) {
            $_SESSION['msg'] = [
                'texto' => 'Experimento não encontrado.',
                'color' => 'danger',
            ];

            $this->redirect(base_url('adm/infoUser/' . $idCriptografado));
        }


        $valuesEdit = [
            'experimentos_titulo' => $post['experimentos_titulo'] ?? $experimento->experimentos_titulo,
            'experimentos_inicio' => $post['experimentos_inicio'] ?? $experimento->experimentos_inicio,
            'experimentos_final'  => $post['experimentos_final'] ?? $experimento->experimentos_final,
            'experimentos_sala'   => $post['experimentos_sala'] ?? $experimento->experimentos_sala,
            'bancadas_tipo_plantacao' => $post['bancadas_tipo_plantacao'] ?? $experimento->bancadas_tipo_plantacao,
            'bancadas_nome_cientifico' => $post['bancadas_nome_cientifico'] ?? $experimento->bancadas_nome_cientifico,
            'bancadas_quantidade' => $post['bancadas_quantidade'] ?? $experimento->bancadas_quantidade,
        ];





        $dataLimite = (new DateTime())->modify('-10 days');
        $dataLimitePosterior = (new DateTime())->modify('+10 days');
        $dataHoje = strtotime(date('d/m/Y'));


        // Validação de 10 dias antes do dia atual.
        // O inicio do experimento nao pode ser menor que 10 dias que hoje
        if (strtotime($valuesEdit['experimentos_inicio']) < strtotime($dataLimite->format('Y-m-d'))) {
            $_SESSION['msg'] = ['texto' => "A data de início não pode ser anterior a 10 dias atrás.", 'color' => 'danger'];
            $this->redirect(base_url('adm/infoUser/' . $idCriptografado));
        }


        // Validação de 10 dias depois do dia atual.
        // O inicio do experimento nao pode ser maior que 10 dias que hoje
        if (strtotime($valuesEdit['experimentos_inicio']) > strtotime($dataLimitePosterior->format('Y-m-d'))) {
            $_SESSION['msg'] = ['texto' => "A data de início não pode ser maior que 10 dias. ", 'color' => 'danger'];
            $this->redirect(base_url('adm/infoUser/' . $idCriptografado));
        }

        // data do experimento final do experimento nao pode ser menor que hoje
        if (strtotime($valuesEdit['experimentos_final']) < $dataHoje) {
            $_SESSION['msg'] = ['texto' => "A data final do experimento não pode ser inferior a data atual.", 'color' => 'danger'];
            $this->redirect(base_url('adm/infoUser/' . $idCriptografado));
        }



        // Data final nao pode ser menor que a data de inicio
        if (strtotime($valuesEdit['experimentos_final']) < strtotime($valuesEdit['experimentos_inicio'])) {
            $_SESSION['msg'] = [
                'texto' => "A data final do experimento não pode ser menor que a inicial",
                'color' => 'danger',
            ];

            $this->redirect(base_url('adm/infoUser/' . $idCriptografado));
        }


        //titulo nao pode ter menos que 5
        if (strlen(trim($valuesEdit['experimentos_titulo'])) < 5) {
            $_SESSION['msg'] = [
                'texto' => 'O título deve possuir pelo menos 5 caracteres.',
                'color' => 'danger'
            ];

            $this->redirect(base_url('adm/infoUser/' . $idCriptografado));
        }

        // Validação de limites de caracteres
        if (strlen(trim($valuesEdit['experimentos_titulo'])) > 80) {
            $_SESSION['msg'] = [
                'texto' => 'O título deve possuir menos de 80 caracteres.',
                'color' => 'danger'
            ];

            $this->redirect(base_url('adm/infoUser/' . $idCriptografado));
        }



        if (strlen(trim($valuesEdit['experimentos_sala'])) < 5) {
            $_SESSION['msg'] = [
                'texto' => 'É necessário ter ao menos 5 caracteres no campo de sala!',
                'color' => 'danger'
            ];

            $this->redirect(base_url('adm/infoUser/' . $idCriptografado));
        }


        if (!$this->bancadasExperimento->UpdateExperimentosADM($join, $where, $valuesEdit)) {
            $_SESSION['msg'] = [
                'texto' => 'Erro ao editar este experimento',
                'color' => 'danger'
            ];

            $this->redirect(base_url('adm/infoUser/' . $idCriptografado));
        }


        $_SESSION['msg'] = [
            'texto' => 'Experimento editado com sucesso!',
            'color' => 'success',
        ];

        $this->redirect(base_url('adm/infoUser/' . $idCriptografado));
    }


    public function listarUsuarios()
    {

        $data['usuarios'] = $this->allUsers->queryList();
        return $this->loadView('adm/usuarios', $data);
    }

    public function createUser()
    {

        $usuarios_imagem = 'uploadImages/default.png';


        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(base_url('adm/usuarios'));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES)) {
            $_SESSION['msg'] = [
                'texto' => 'Arquivo muito grande! Máximo permitido foi excedido.',
                'color' => 'danger'
            ];

            $this->redirect(base_url('adm/usuarios'));
        }


        // Verifica se existe valor na varivel
        if (
            !isset($_FILES['usuarios_imagem']) ||
            empty($_FILES['usuarios_imagem']['name']) ||
            $_FILES['usuarios_imagem']['error'] !== 0
        ) {

            $_SESSION['msg'] = [
                'texto' => 'Atenção! Informe uma imagem para o perfil!',
                'color' => 'warning'
            ];
            $this->redirect(base_url('adm/usuarios'));
        }
        $arquivo = $_FILES['usuarios_imagem']; //atribui o caminho para variavel


        // Validação se a imagem for maior que 2MB
        if (!$this->conexao->validaImagem($arquivo)) {
            $_SESSION['msg'] = [
                'texto' => "Erro! Selecione outra imagem até 2MB",
                'color' => 'danger',
            ];

            $this->redirect(base_url('adm/usuarios'));
        }
        // Validação para ver se a imagem esta no formato permitido
        if (!$this->conexao->tipoImagem($arquivo)) {
            $_SESSION['msg'] = [
                'texto' => 'Formato da imagem não permitido - Selecione apenas JPEG, JPG ou PNG',
                'color' => 'danger',
            ];


            $this->redirect(base_url('adm/usuarios'));
        }


        $pasta = __DIR__ . '/../Public/uploadImages/';
        $nomearquivo = uniqid() . "-" . basename($arquivo['name']);
        $caminhoFinal = $pasta . $nomearquivo; //String para o banco de dados

        if (!move_uploaded_file($arquivo['tmp_name'], $caminhoFinal)) {
            $_SESSION['msg'] = [
                'texto' => 'Erro ao fazer o upload da imagem',
                'color' => 'danger',
            ];


            $this->redirect(base_url('adm/usuarios'));
        }

        $usuarios_imagem = 'Public/uploadImages/' . $nomearquivo; //atribue valor para a variavel


        $post = $_POST;

        $valuesCreate = [
            'usuarios_nome' => $post['usuarios_nome'],
            'usuarios_escolaridade' => $post['usuarios_escolaridade'],
            'usuarios_email' => $post['usuarios_email'],
            'usuarios_senha' => password_hash($post['usuarios_senha'], PASSWORD_DEFAULT),
            'usuarios_imagem' => $usuarios_imagem,
            'usuarios_ativo' => 1,
            'usuarios_nivel' => $post['usuarios_nivel'],

        ];


        $emailUsuario = $valuesCreate['usuarios_email'];

        $where = "usuarios_email = '$emailUsuario'";

        $usuarioEmail = $this->allUsers->select(null, $where)->fetchObject();


        // Se cadastrar sem nome
        if (empty($valuesCreate['usuarios_nome'])) {
            $_SESSION['msg'] = [
                'texto' => 'O campo nome não pode ser vazio!',
                'color' => 'danger',
            ];

            $this->redirect(base_url('adm/usuarios'));
        }

        // Cadastrar nomes menores que 3 caracteres
        if (strlen($valuesCreate['usuarios_nome']) < 3) {
            $_SESSION['msg'] = [
                'texto' => 'O campo nome precisa ter mais de 3 caracteres!',
                'color' => 'danger',
            ];

            $this->redirect(base_url('adm/usuarios'));
        }


        // Cadatrar nomes maiores que 40 caracteres
        if (strlen($valuesCreate['usuarios_nome']) > 40) {
            $_SESSION['msg'] = [
                'texto' => 'O campo nome precisa ter menos de 40 caracteres!',
                'color' => 'danger',
            ];

            $this->redirect(base_url('adm/usuarios'));
        }

        // Se a escolaridade vier vazia
        if (empty($valuesCreate['usuarios_escolaridade'])) {
            $_SESSION['msg'] = [
                'texto' => 'Escolha o grau de escolaridade',
                'color' => 'danger',
            ];

            $this->redirect(base_url('adm/usuarios'));
        }

        // Validação do email do usuario
        if (!filter_var($valuesCreate['usuarios_email'], FILTER_VALIDATE_EMAIL)) {
            $_SESSION['msg'] = [
                'texto' => 'Formato de email inválido!',
                'color' => 'danger',
            ];

            $this->redirect(base_url('adm/usuarios'));
        }

        if ($usuarioEmail) {
            $_SESSION['msg'] = [
                'texto' => 'Email ja cadastrado!',
                'color' => 'danger',
            ];

            $this->redirect(base_url('adm/usuarios'));
        }


        // Validação de senha

        if (strlen($valuesCreate['usuarios_senha']) < 5) {
            $_SESSION['msg'] = [
                'texto' => 'Não é permitido criar uma senha com menos de 5 caracteres!',
                'color' => 'danger',
            ];

            $this->redirect(base_url('adm/usuarios'));
        }

        if ($this->allUsers->insert($valuesCreate)) {
            $_SESSION['msg'] = [
                'texto' => 'Cadastro realizado com sucesso!',
                'color' => 'success',
            ];


            $this->redirect(base_url('adm/usuarios'));
        } else {
            $_SESSION['msg'] = [
                'texto' => 'Erro ao realizar o cadastro, tente novamente!',
                'color' => 'danger',
            ];

            $this->redirect(base_url('adm/usuarios'));
        }
    }

    // FUnção para listar os experimentos de todos os usuarios, usado no index 
    private function moreUser($where = null)
    {

        $join = "usuarios as u on u.usuarios_id = participantes_usuarios_id
                INNER JOIN experimentos as e on e.experimentos_id = participantes_experimentos_id
                INNER JOIN bancadas_experimentos as bc on bc.be_experimentos_id = e.experimentos_id
                INNER JOIN bancadas as b on b.bancadas_id = bc.be_bancadas_id";

        $order = "experimentos_id ASC";

        return $this->conexaoMoreUser->selectPesquisar($join, $where, $order)->fetchAll(PDO::FETCH_OBJ);
    }
}
