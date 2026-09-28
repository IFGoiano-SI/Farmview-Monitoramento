<?php

namespace Controllers;

// require_once("Models/Database.php");
// require_once("Config/Helpers.php");
// require_once("Ultis/RenderView.php");

use Exception;
use Models\Database as Conexao;
use \PDO;
use \Ultis\RenderView;
use \Ultis\FlaskService;

class HomeController extends RenderView
{

    private $participantes;
    private $usuario;
    private $flask;


    public function __construct()
    {
        $this->participantes = new Conexao('participantes');
        $this->usuario = new UsuarioController();
        $this->flask = new FlaskService();
    }



    public function index()
    {
        $data = [];

        $data['pagina'] = "Experimento de usuario";

        $this->usuario->listarUsuario();

        if ($this->tela()) {

            $usuario_id = $_SESSION['usuarios_logado']->usuarios_id;

            $join = 'usuarios on usuarios_id = participantes_usuarios_id
            INNER JOIN experimentos on experimentos_id = participantes_experimentos_id
            INNER JOIN bancadas_experimentos on be_experimentos_id = experimentos_id
            INNER JOIN bancadas on bancadas_id = be_bancadas_id';

            $where = "experimentos_status = 'Ativo'
            AND participantes_usuarios_id = $usuario_id";

            $order = 'experimentos_id DESC';

            $usuarioExperimento = $this->participantes
                ->select($join, $where, $order, 1)
                ->fetch(PDO::FETCH_OBJ);

            $data['usuarioExperimento'] = $usuarioExperimento ?? '';

            // ==============================
            // DADOS DO RASPBERRY / FLASK
            // ==============================

            $data['sensores'] = $this->flask->getSensores();

            $data['videoUrl'] = $this->flask->getVideoUrl();

            // ==============================

            return $this->loadView('home/dashboard', $data);
        } else {

            return $this->loadView('home/index', $data);
        }
    }

    public function sensores()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {

            $sensores = $this->flask->getSensores();

            echo json_encode([
                'success' => true,
                'v'       => $sensores['v'] ?? [],
                'pct'     => $sensores['pct'] ?? []
            ]);
        } catch (\Exception $e) {

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'erro' => $e->getMessage()
            ]);
        }

        exit;
    }



    public function tela()
    {

        $usuario_id = $_SESSION['usuarios_logado']->usuarios_id;
        $join = "usuarios on usuarios_id = participantes_usuarios_id
                 INNER JOIN experimentos on experimentos_id = participantes_experimentos_id";
        $where = "experimentos_status = 'Ativo' AND participantes_usuarios_id = '$usuario_id'";

        $resultado =  $this->participantes->select($join, $where)->fetch(PDO::FETCH_OBJ);

        return $resultado ? true : false;
    }
    public function toggleDispositivo()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . base_url('home/index'));
            return;
        }

        $rotasPermitidas = [
            'api/cooler',
            'api/lampada',
            'api/bomba',
            'api/camera/toggle',
            'api/camera/gravar'
        ];

        $rota = $_POST['rota'] ?? '';
        $acao = $_POST['acao'] ?? 'off';
        $tomada = $_POST['tomada'] ?? null; // Pega o pino GPIO se for enviado

        // Se a View enviou um 'gpio', usamos a sua nova função
        if ($tomada !== null) {
            try {
                // Chama a função que você criou (ajuste se ela estiver em outro Model/Helper)
                $this->controladorTomada($tomada, $acao);
                $_SESSION['tomada_' . $tomada] = $acao;

                if ($acao === 'on') {
                    $_SESSION['tomada_' . $tomada] = 'Ligado';
                } else {
                    $_SESSION['tomada_' . $tomada] = 'Desligado';
                }
                // $_SESSION['msg'] = [
                //     'texto' => "Tomada {$tomada} alterada com sucesso para {$acao}!",
                //     'color' => 'success'
                // ];
            } catch (\Exception $e) {
                $_SESSION['msg'] = [
                    'texto' => "Erro ao controlar GPIO: " . $e->getMessage(),
                    'color' => 'danger'
                ];
            }
        }
        // Se não for GPIO, segue o fluxo antigo das rotas permitidas
        elseif (in_array($rota, $rotasPermitidas, true)) {
            $this->flask->toggleDispositivo($rota, $acao);
        } elseif ($rota === 'api/camera/toggle') {


            $url = "http://" . $_SESSION['url_rasp'] . "/api/camera/toggle";

            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query([
                    'acao' => $acao
                ]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10
            ]);

            $resposta = curl_exec($ch);

            if (curl_errno($ch)) {
                throw new Exception(curl_error($ch));
            }

            curl_close($ch);
        } elseif (in_array($rota, $rotasPermitidas, true)) {

            $this->flask->toggleDispositivo($rota, $acao);
        }

        // Recarrega a página inicial
        return $this->index();
    }


    function controladorTomada($tomada, $acao)
    {
        $url = "http://" . $_SESSION['url_rasp'] . "/toggle_pin_{$tomada}?acao={$acao}";

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);

        $resposta = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return [
            'http_code' => $httpCode,
            'response' => json_decode($resposta, true)
        ];
    }


    public function ligarCamera()
    {
        $url = "http://".$_SESSION['url_rasp']."/toggle_camera";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);

        return $this->index();
    }
}
