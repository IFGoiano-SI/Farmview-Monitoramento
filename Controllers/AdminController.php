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


    public function __construct() {
        $this->usuario = new UsuarioController();
        $this->conexao = new Conexao();
        
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

        return $this->loadView('adm/index', $data);


    }








   
}
