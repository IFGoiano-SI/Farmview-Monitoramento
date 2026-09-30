<?php

namespace Controllers;

use Models\Database as Conexao;
use \PDO;
use Ultis\RenderView;
use \Ultis\FlaskService;

class GravacaoController extends RenderView
{

    private $gravacao;


    public function __construct()
    {
        $this->gravacao = new Conexao('videos_experimentos');
    }


    public function index()
    {

        $usuario_id = $_SESSION['usuarios_logado']->usuarios_id ?? null;

        $where = "usuarios_id = $usuario_id";
        $orderBy = "criado_em DESC";
        $dadosGravacao = $this->gravacao->select(null, $where, $orderBy)->fetchAll(PDO::FETCH_OBJ);


        $data['dadosVideo'] = $dadosGravacao ?? '';


        return $this->loadView('home/gravacao', $data);
    }
}
