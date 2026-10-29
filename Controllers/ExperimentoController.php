<?php


namespace Controllers;

use DateTime;
use Models\Database as Conexao;
use \PDO;
use Ultis\RenderView;
use \Ultis\FlaskService;

class ExperimentoController extends RenderView
{

    private $experimento;
    private $bancadas;
    private $usuario;
    private $participantes;
    private $bancadasExperimentos;
    private $home;
    private $flask;

    public function __construct()
    {
        $this->experimento = new Conexao('experimentos');
        $this->bancadas = new Conexao('bancadas');
        $this->participantes = new Conexao('participantes');
        $this->bancadasExperimentos = new Conexao('bancadas_experimentos');
        $this->usuario = new UsuarioController();
        $this->home = new HomeController();
        $this->flask = new FlaskService();
    }


    protected function redirect($path, $message = null)
    {
        if ($message) {
            $_SESSION['msg'] = $message;
        }
        header("Location: {$path}");
        exit;
    }


    // FUnção para retornar e exibir a tela index
    public function index()
    {
        $data = [];
        $data['pagina'] = 'experimento';

        return $this->loadView('experimento/index', $data);
    }

    private function flash(string $texto, string $color = 'success'): array
    {
        return [
            'texto' => $texto,
            'color' => $color
        ];
    }

    // Função para cadastar experimento
    public function cadastrar()
    {
        $data = [];
        $post = $_POST;

        $experimento_imagem = 'uploadImages/default.png';


        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->loadView('home/index', []);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES)) {
            $_SESSION['msg'] = [
                'texto' => 'Arquivo muito grande! Máximo permitido foi excedido.',
                'color' => 'danger'
            ];

            return $this->loadView('home/index', $data);
        }


        // Verifica se existe valor na varivel
        if (
            !isset($_FILES['experimentos_imagem']) ||
            empty($_FILES['experimentos_imagem']['name']) ||
            $_FILES['experimentos_imagem']['error'] !== 0
        ) {

            $_SESSION['msg'] = [
                'texto' => 'Atenção! Informe uma imagem',
                'color' => 'warning'
            ];
            return $this->loadView('home/index', $data);
        }
        $arquivo = $_FILES['experimentos_imagem']; //atribui o caminho para variavel


        // Validação se a imagem for maior que 2MB
        if (!$this->experimento->validaImagem($arquivo)) {
            $_SESSION['msg'] = [
                'texto' => "Erro! Selecione outra imagem até 2MB",
                'color' => 'danger',
            ];

            return $this->loadView('home/index', $data);
        }
        // Validação para ver se a imagem esta no formato permitido
        if (!$this->experimento->tipoImagem($arquivo)) {
            $_SESSION['msg'] = [
                'texto' => 'Formato da imagem não permitido - Selecione apenas JPEG, JPG ou PNG',
                'color' => 'danger',
            ];


            return $this->loadView('home/index', $data);
        }

        // Validação para inserção de imagem no tamanho permitido
        // if (!$this->experimento->tamanhoImagem($arquivo)) {
        //     $_SESSION['msg'] = [
        //         'texto' => 'Erro! Tamanho de imagem menor que 400x400. Selecione uma imagem maior!',
        //         'color' => 'danger',
        //     ];


        //     return $this->loadView('home/index', $data);
        // }

        $pasta = __DIR__ . '/../Public/templates/Images/';
        $nomearquivo = uniqid() . "-" . basename($arquivo['name']);
        $caminhoFinal = $pasta . $nomearquivo; //String para o banco de dados

        if (!move_uploaded_file($arquivo['tmp_name'], $caminhoFinal)) {
            $_SESSION['msg'] = [
                'texto' => 'Erro ao fazer o upload da imagem',
                'color' => 'danger',
            ];


            return $this->loadView('home/index', $data);
        }

        $experimento_imagem = 'Public/templates/Images/' . $nomearquivo; //atribue valor para a variavel

        $values = [

            'experimentos_titulo' => $post['experimentos_titulo'] ?? '',
            'experimentos_inicio' => $post['experimentos_inicio'] ?? '',
            'experimentos_final'  => $post['experimentos_final'] ?? '',
            'experimentos_sala'   => $post['experimentos_sala'] ?? '',
            'experimentos_status' => 'Ativo',
            'experimentos_imagem' => $experimento_imagem,


        ];

        $dataLimite = (new DateTime())->modify('-10 days');
        $dataLimitePosterior = (new DateTime())->modify('+10 days');
        $dataHoje = strtotime(date('d/m/Y'));

        // Validação se esta vazio o campo de inicio
        if (empty($values['experimentos_inicio'])) {
            $_SESSION['msg'] = [
                'texto' => 'Informe a data de início.',
                'color' => 'danger'
            ];

            return $this->loadView('home/index', []);
        }


        if (empty($values['experimentos_final'])) {
            $_SESSION['msg'] = [
                'texto' => 'Informe a data final.',
                'color' => 'danger'
            ];

            return $this->loadView('home/index', []);
        }

        if (strtotime($values['experimentos_inicio']) < strtotime($dataLimite->format('Y-m-d'))) {
            $_SESSION['msg'] = ['texto' => "A data de início não pode ser anterior a 10 dias atrás.", 'color' => 'danger'];
            return $this->loadView('home/index', []);
        }



        if (strtotime($values['experimentos_inicio']) > strtotime($dataLimitePosterior->format('Y-m-d'))) {
            $_SESSION['msg'] = ['texto' => "A data de início não pode ser maior que 10 dias. ", 'color' => 'danger'];
            return $this->loadView('home/index', []);
        }


        if (strtotime($values['experimentos_final']) < $dataHoje) {
            $_SESSION['msg'] = ['texto' => "A data final do experimento não pode ser inferior a data atual.", 'color' => 'danger'];
            return $this->loadView('home/index', []);
        }




        if (strtotime($values['experimentos_final']) < strtotime($values['experimentos_inicio'])) {
            $_SESSION['msg'] = [
                'texto' => "A data final do experimento não pode ser menor que a inicial",
                'color' => 'danger',
            ];

            return $this->loadView('home/index', []);
        }

        if (strlen(trim($values['experimentos_titulo'])) < 5) {
            $_SESSION['msg'] = [
                'texto' => 'O título deve possuir pelo menos 5 caracteres.',
                'color' => 'danger'
            ];

            return $this->loadView('home/index', []);
        }


        if (strlen(trim($values['experimentos_sala'])) < 5) {
            $_SESSION['msg'] = [
                'texto' => 'É necessário ter ao menos 5 caracteres no campo de sala!',
                'color' => 'danger'
            ];

            return $this->loadView('home/index', []);
        }




        if (empty(trim($values['experimentos_sala']))) {
            $_SESSION['msg'] = [
                'texto' => 'Informe a sala do experimento.',
                'color' => 'danger'
            ];

            return $this->loadView('home/index', []);
        }



        $experimentoID = $this->experimento->insert($values);

        if ($experimentoID > 0) {

            $valuesBancada = [
                'bancadas_tipo_plantacao' => $post['bancadas_tipo_plantacao'] ?? '',
                'bancadas_nome_cientifico' => $post['bancadas_nome_cientifico'] ?? '',
                'bancadas_quantidade' => $post['bancadas_quantidade'] ?? '',
                'bancadas_luzes'      => json_encode($post['bancadas_luzes'] ?? '[]'),
                'bancadas_dispositivos' => json_encode($post['bancadas_dispositivos'] ?? []),
            ];


            if (empty(trim($valuesBancada['bancadas_tipo_plantacao']))) {
                $_SESSION['msg'] = [
                    'texto' => 'Informe o tipo de plantação',
                    'color' => 'danger'
                ];

                return $this->loadView('home/index', []);
            }

            if (strlen(trim($valuesBancada['bancadas_tipo_plantacao'])) < 5) {
                $_SESSION['msg'] = [
                    'texto' => 'É necessário ter ao menos 5 caracteres para tipo de plantação!',
                    'color' => 'danger'
                ];

                return $this->loadView('home/index', []);
            }

            if (strlen(trim($valuesBancada['bancadas_nome_cientifico'])) < 5) {
                $_SESSION['msg'] = [
                    'texto' => 'É necessário ter ao menos 5 caracteres para informar o nome cientifico!',
                    'color' => 'danger'
                ];

                return $this->loadView('home/index', []);
            }


            if (empty(trim($valuesBancada['bancadas_nome_cientifico']))) {
                $_SESSION['msg'] = [
                    'texto' => 'Informe o nome científico.',
                    'color' => 'danger'
                ];

                return $this->loadView('home/index', []);
            }



            if ($valuesBancada['bancadas_quantidade'] > 99) {
                $_SESSION['msg'] = [
                    'texto' => "Não é permitido informar essa quantidade de plantação!",
                    'color' => 'danger',
                ];

                return $this->loadView('home/index', []);
            }


            if ($valuesBancada['bancadas_quantidade'] <= 0) {
                $_SESSION['msg'] = [
                    'texto' => "A quantidade de plantas não pode ser igual ou menor que 0",
                    'color' => 'danger',
                ];

                return $this->loadView('home/index', []);
            }

            if (empty(trim($valuesBancada['bancadas_luzes']))) {
                $_SESSION['msg'] = [
                    'texto' => 'É necessário ter ao menos 1 tipo de luz cadastrado no experimento.',
                    'color' => 'danger'
                ];

                return $this->loadView('home/index', []);
            }


            if (empty(trim($valuesBancada['bancadas_dispositivos']))) {
                $_SESSION['msg'] = [
                    'texto' => 'É necessário ter ao menos 1 tipo de dispositivo cadastrado no experimento.',
                    'color' => 'danger'
                ];

                return $this->loadView('home/index', []);
            }



            $bancadasId = $this->bancadas->insert($valuesBancada);

            $valuesBancadasExperimentos = [
                'be_bancadas_id' => $bancadasId,
                'be_experimentos_id' => $experimentoID,

            ];

            if ($this->bancadasExperimentos->insert($valuesBancadasExperimentos)) {

                // $participantesNome = $post['participantes_nome'] ?? '';
                $participantesExternos = $post['participantes_externos'] ?? '';
                $usuarioLogado = $_SESSION['usuarios_logado']->usuarios_id;

                $valuesParticipantes = [
                    'participantes_nome' => $participantesExternos,
                    'participantes_orientador' => $post['participantes_orientador'] ?? '',
                    'participantes_experimentos_id' => $experimentoID,
                    'participantes_usuarios_id' => $usuarioLogado,
                ];

                if (empty(trim($valuesParticipantes['participantes_orientador']))) {
                    $_SESSION['msg'] = [
                        'texto' => 'Não é possivel cadastar um experimento sem um orientador!',
                        'color' => 'danger'
                    ];

                    return $this->loadView('home/index', []);
                }



                if ($this->participantes->insert($valuesParticipantes)) {
                    $_SESSION['msg'] = [
                        'texto' => "Experimento cadastrado com sucesso! Boa pesquisa!",
                        'color' => 'success',
                    ];

                    $this->redirect(base_url('home/dashboard'));
                }
            }
        } else {

            $_SESSION['msg'] = [
                'texto' => 'Erro ao cadastrar experimento!',
                'color' => 'danger',
            ];

            return $this->loadView('home/index', []);
        }
    }


    // Metodo de pesquistar na tela de experimento
    public function pesquisarExperimento()
    {
        $usuario = $_SESSION['usuarios_logado']->usuarios_id;
        $pesquisa = trim($_POST['pesquisar'] ?? '');

        if ($pesquisa === '') {
            return $this->index();
        }

        $join = "experimentos
             ON experimentos_id = participantes_experimentos_id
             INNER JOIN usuarios
             ON usuarios_id = participantes_usuarios_id";

        $where = "experimentos_titulo LIKE ?
              AND participantes_usuarios_id = ?";

        $params = ["%{$pesquisa}%", $usuario];

        $data['experimentos'] = $this->participantes
            ->selectPesquisar($join, $where, null, null, '*', $params)
            ->fetchAll(PDO::FETCH_CLASS);

        $data['total'] = count($data['experimentos']);
        $data['pesquisa'] = $pesquisa;
        $data['pagina'] = 'Pesquisar experimento';

        if ($data['total'] == 0) {
            $data['msg'] = $this->flash('Nenhum experimento encontrado.');
        } else {
            $data['msg'] = $this->flash("Total de registros: {$data['total']}");
        }

        return $this->loadView('home/experimentos', $data);
    }

    // Função para finalizar o experimento
    public function finalizarExperimento($experimentoId)
    {
        date_default_timezone_set('America/Sao_Paulo');

        try {

            $this->flask->toggleDispositivo('api/lampada', 'off');
            $this->flask->toggleDispositivo('api/cooler', 'off');
            $this->flask->toggleDispositivo('api/bomba', 'off');
            $this->flask->toggleDispositivo('api/camera/toggle', 'off');
            $this->flask->toggleDispositivo('api/camera/gravar', 'off');

            $this->home->controladorTomada(1, 'off');
            $this->home->controladorTomada(2, 'off');
            $this->home->controladorTomada(3, 'off');
            $this->home->controladorTomada(4, 'off');

            $this->flask->toggleDispositivo('api/camera/toggle', 'off');
        } catch (\Exception $e) {
            error_log($e->getMessage());
        }

        // Pega o tempo e agora
        $ts = time();
        // Transforma ele em horas com segundos
        $curDate = date('Y-m-d H:i:s', $ts);

        $usuarioId = $_SESSION['usuarios_logado']->usuarios_id;

        $join = 'participantes on participantes_experimentos_id = experimentos_id';
        $where = 'participantes_usuarios_id = ' . $usuarioId . ' and ' . 'participantes_experimentos_id = ' . $experimentoId;
        $values = "experimentos_status = 'Finalizado', experimentos_encerrado =  '$curDate'";

        $experimentoFinalizado =  $this->experimento->UpdateExperimentos($join, $where, $values);

        if ($experimentoFinalizado) {
            $_SESSION['msg'] = [
                'texto' => "Experimento finalizado com sucesso!",
                'color' => "success",
            ];

            return $this->redirect(base_url('home'));
        } else {

            $_SESSION['msg'] = [
                'texto' => "Erro ao finalizar o experimento!",
                'color' => 'danger',
            ];


            return $this->redirect(base_url('home'));
        }
    }


    public function editar($experimentosID)
    {

        $data['editarExperimentos'] = $this->SelectEditExperimento($experimentosID);

        return $this->loadView('home/alterarExperimento', $data);
    }

    // Função para editar dados do experimentos
    public function editarExperimentosAtivo($experimentosID)
    {

        $experimentoAntigo = $this->SelectEditExperimento($experimentosID);

        $valuesEditarExperimentos = [
            'experimentos_titulo' => $_POST['experimentos_titulo'] ?? $experimentoAntigo->experimentos_titulo,
            'experimentos_sala' => $_POST['experimentos_sala'] ?? $experimentoAntigo->experimentos_sala,
            'experimentos_inicio' => $_POST['experimentos_inicio'] ?? $experimentoAntigo->experimentos_inicio,
            'experimentos_final' => $_POST['experimentos_final'] ?? $experimentoAntigo->experimentos_final,
        ];

        $where = "experimentos_id ='$experimentosID'";

        $dataLimite = (new DateTime())->modify('-10 days');
        $dataLimitePosterior = (new DateTime())->modify('+10 days');
        $dataHoje = strtotime(date('d/m/Y'));


            // Validação de 10 dias antes do dia atual.
            // O inicio do experimento nao pode ser menor que 10 dias que hoje
        if (strtotime($valuesEditarExperimentos['experimentos_inicio']) < strtotime($dataLimite->format('Y-m-d'))) {
            $_SESSION['msg'] = ['texto' => "A data de início não pode ser anterior a 10 dias atrás.", 'color' => 'danger'];
            $this->redirect(base_url('gravacao/index'));
        }


            // Validação de 10 dias depois do dia atual.
            // O inicio do experimento nao pode ser maior que 10 dias que hoje
        if (strtotime($valuesEditarExperimentos['experimentos_inicio']) > strtotime($dataLimitePosterior->format('Y-m-d'))) {
            $_SESSION['msg'] = ['texto' => "A data de início não pode ser maior que 10 dias. ", 'color' => 'danger'];
            $this->redirect(
                base_url('experimento/editar/' . $experimentoAntigo->experimentos_id)
            );
        }

            // data do experimento final do experimento nao pode ser menor que hoje
        if (strtotime($valuesEditarExperimentos['experimentos_final']) < $dataHoje) {
            $_SESSION['msg'] = ['texto' => "A data final do experimento não pode ser inferior a data atual.", 'color' => 'danger'];
            $this->redirect(
                base_url('experimento/editar/' . $experimentoAntigo->experimentos_id)
            );
        }



        // Data final nao pode ser menor que a data de inicio
        if (strtotime($valuesEditarExperimentos['experimentos_final']) < strtotime($valuesEditarExperimentos['experimentos_inicio'])) {
            $_SESSION['msg'] = [
                'texto' => "A data final do experimento não pode ser menor que a inicial",
                'color' => 'danger',
            ];

            $this->redirect(
                base_url('experimento/editar/' . $experimentoAntigo->experimentos_id)
            );
        }


        //titulo nao pode ter menos que 5
        if (strlen(trim($valuesEditarExperimentos['experimentos_titulo'])) < 5) {
            $_SESSION['msg'] = [
                'texto' => 'O título deve possuir pelo menos 5 caracteres.',
                'color' => 'danger'
            ];

            $this->redirect(
                base_url('experimento/editar/' . $experimentoAntigo->experimentos_id)
            );
        }

        // Validação de limites de caracteres
         if (strlen(trim($valuesEditarExperimentos['experimentos_titulo'])) > 80) {
            $_SESSION['msg'] = [
                'texto' => 'O título deve possuir menos de 80 caracteres.',
                'color' => 'danger'
            ];

            $this->redirect(
                base_url('experimento/editar/' . $experimentoAntigo->experimentos_id)
            );
        }



        if (strlen(trim($valuesEditarExperimentos['experimentos_sala'])) < 5) {
            $_SESSION['msg'] = [
                'texto' => 'É necessário ter ao menos 5 caracteres no campo de sala!',
                'color' => 'danger'
            ];

            $this->redirect(
                base_url('experimento/editar/' . $experimentoAntigo->experimentos_id)
            );
        }


        if (!$this->experimento->update($where, $valuesEditarExperimentos)) {
            $_SESSION['msg'] = [
                'texto' => 'Erro ao editar este experimento',
                'color' => 'danger'
            ];

            $this->redirect(
                base_url('experimento/editar/' . $experimentoAntigo->experimentos_id)
            );
        }


        $_SESSION['msg'] = [
            'texto' => 'Experimento editado com sucesso!',
            'color' => 'success',
        ];

        $this->redirect(
            base_url('experimento/editar/' . $experimentoAntigo->experimentos_id)
        );
    }

    public function excluirExperimento($experimentoID)
    {
        $valuesExcluir = [
            'experimentos_status' => "Excluido",
        ];

        $where = "experimentos_id ='$experimentoID'";


        if (!$this->experimento->update($where, $valuesExcluir)) {
            $_SESSION['msg'] = [
                'texto' => 'Erro ao excluir o experimento!',
                'color' => 'danger',
            ];

            $this->redirect(
                base_url('experimento/editar/' . $experimentoID)
            );
        }

        $_SESSION['msg'] = [
            'texto' => 'Experimento excluído com sucesso!',
            'color' => 'success',
        ];

        return $this->home->index();
    }


    // Função que envia para a tela de experimentos - historico
    public function listarExperimentos()
    {
        // Vai trazer todos os experiemntos vinculados ao usuarios onde ele vai estar ativo
        $joinAtivos = 'usuarios on usuarios_id = participantes_usuarios_id
                 INNER JOIN experimentos on experimentos_id = participantes_experimentos_id
                 INNER JOIN bancadas_experimentos on be_experimentos_id = experimentos_id
                 INNER JOIN bancadas on bancadas_id = be_bancadas_id
                 LEFT JOIN anotacoes on anotacoes_experimentos_id = experimentos_id';

        $usuarioLogado = $_SESSION['usuarios_logado']->usuarios_id;

        $whereAtivos = "experimentos_status = 'Ativo' AND participantes_usuarios_id =" . $usuarioLogado;
        $orderAtivos = 'experimentos_status DESC';

        $usuarioExperimento = $this->participantes->select($joinAtivos, $whereAtivos, $orderAtivos, $limit = null)->fetchAll(PDO::FETCH_OBJ);
        $data['experimentosAtivos'] = $usuarioExperimento;


        // Vai trazer todos os experimentos vinculados ao usuario onde ele estiver com status finalizado

        $joinFinalizado = 'usuarios on usuarios_id = participantes_usuarios_id
                 INNER JOIN experimentos on experimentos_id = participantes_experimentos_id
                 INNER JOIN bancadas_experimentos on be_experimentos_id = experimentos_id
                 INNER JOIN bancadas on bancadas_id = be_bancadas_id
                 LEFT JOIN anotacoes on anotacoes_experimentos_id = experimentos_id';
        $whereFinalizado = "experimentos_status = 'Finalizado' AND participantes_usuarios_id =" . $usuarioLogado;
        $orderFinalizado = 'experimentos_encerrado DESC';
        // $limitAtivos  = 1;

        $usuarioExperimento = $this->participantes->select($joinFinalizado, $whereFinalizado, $orderFinalizado, $limit = null)->fetchAll(PDO::FETCH_OBJ);
        $data['experimentosFinalizados'] = $usuarioExperimento;


        return $this->loadView('home/experimentos', $data);
    }


    private function SelectEditExperimento($experimentosID)
    {

        $whereAtivos = "experimentos_status = 'Ativo' and experimentos_id ='$experimentosID'";

        return  $this->experimento->select(null, $whereAtivos, null, $limit = null)->fetch(PDO::FETCH_OBJ);
    }
}
