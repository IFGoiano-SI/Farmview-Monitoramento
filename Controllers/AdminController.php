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


    public function __construct()
    {
        $this->usuario = new UsuarioController();
        $this->conexao = new Conexao();
        $this->conexaoMoreUser = new Conexao('participantes');
        $this->bancadasExperimento = new Conexao('bancadas_experimentos');
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


        // try {
        //     $resultado = $this->bancadasExperimento
        //         ->UpdateExperimentosADM($join, $where, $valuesEdit);

        //     var_dump($resultado);
        //     exit;
        // } catch (\Throwable $erro) {
        //     header('Content-Type: text/plain; charset=utf-8');

        //     echo 'Tipo: ' . get_class($erro) . PHP_EOL;
        //     echo 'Erro: ' . $erro->getMessage() . PHP_EOL;
        //     echo 'Arquivo: ' . $erro->getFile() . PHP_EOL;
        //     echo 'Linha: ' . $erro->getLine();
        //     exit;
        // }

        // var_dump($valuesEdit);
        // exit;



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
