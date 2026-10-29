<?php

namespace Controllers;

use DateTime;
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

        $usuario_id = $_SESSION['usuarios_logado']->usuarios_id ?? null;


        $join = "usuarios u on u.usuarios_id = videos_experimentos.usuarios_id INNER JOIN experimentos e on e.experimentos_id = videos_experimentos.experimentos_id";



        $where = "u.usuarios_id = '$usuario_id' AND videos_status = 'Ativo'";
        $orderBy = "videos_experimentos.criado_em ASC";
        $dadosGravacao = $this->gravacao->select($join, $where, $orderBy)->fetchAll(PDO::FETCH_OBJ);


        $data['dadosVideo'] = $dadosGravacao ?? '';



        return $this->loadView('home/gravacao', $data);
    }

    public function deletarGravacao()
    {
        $videoDeletado = $_POST['videos_id'];


        $caminhoVideo = $_POST['path_video'];

        if (file_exists($caminhoVideo)) {
            if (unlink($caminhoVideo)) {
                $_SESSION['msg'] = [
                    'texto' => "Arquivo deletado com sucesso!",
                    'color' => 'success',
                ];

                $this->redirect(base_url('gravacao/index'));
            } else {
                $_SESSION['msg'] = [
                    'texto' => "Erro ao deletar video: $videoDeletado!",
                    'color' => 'danger',
                ];

                $this->redirect(base_url('gravacao/index'));
            }
        } else {
            $_SESSION['msg'] = [
                'texto' => "Arquivo nao encontrado!",
                'color' => 'danger',
            ];
        }


        $this->redirect(base_url('gravacao/index'));
    }

    public function deletarTodasGravacao()
    {
        $videoUsuarioDeletado = $_POST['usuarios_id'];

        $valueASerDeletado = [
            'videos_status' => 'Desativado',
            'deletado_em' => date('Y-m-d H:i:s'),
        ];

        $where = "usuarios_id = $videoUsuarioDeletado";

        if ($this->gravacao->update($where, $valueASerDeletado)) {
            $_SESSION['msg'] = [
                'texto' => "Todos os videos foram deletados com sucesso!",
                'color' => 'success',
            ];
        } else {
            $_SESSION['msg'] = [
                'texto' => "Erro ao deletar os vídeos!",
                'color' => 'danger',
            ];
        }

        $this->redirect(base_url('gravacao/index'));
    }
}
