<?php

namespace Controllers;

use Exception;
use Models\Database as Conexao;
use \PDO;
use \Ultis\RenderView;


class AdminController extends RenderView
{

    private $usuario;
    private $experimentos;
    private $conexao;
    private $conexaoMoreUser;


    public function __construct()
    {
        $this->usuario = new UsuarioController();
        $this->conexao = new Conexao();
        $this->conexaoMoreUser = new Conexao('participantes');
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
        $values = "e.experimentos_status = 'Ativo'";

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
