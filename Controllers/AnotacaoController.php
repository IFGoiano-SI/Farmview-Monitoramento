<?php


namespace Controllers;

use Models\Database as Conexao;
use \PDO;
use Ultis\RenderView;

use Dompdf\Dompdf;
use Dompdf\Options;


class AnotacaoController extends RenderView
{

    private $anotacao;
    private $participantes;
    private $experimento;

    public function __construct()
    {
        $this->anotacao = new Conexao('anotacoes');
        $this->participantes = new Conexao('participantes');
        $this->experimento = new Conexao('experimentos');
    }

    protected function redirect($path, $message = null)
    {
        if ($message) {
            $_SESSION['msg'] = $message;
        }
        header("Location: {$path}");
        exit;
    }


    public function index()
    {
        $dadosAnotacao = $this->ListarDadosParaAnotacao();

        // Verifica se existe experimento ativo
        if (!$dadosAnotacao) {
            $_SESSION['msg'] = [
                'texto' => 'Nenhum experimento ativo encontrado!',
                'color' => 'danger'
            ];

            return $this->redirect(base_url('home/tela'));
        }

        // Busca a anotação do experimento
        $anotacao = $this->anotacao->selectPesquisar(
            null,
            'anotacoes_experimentos_id = ?',
            null,
            null,
            '*',
            [$dadosAnotacao->experimentos_id]
        )->fetch(PDO::FETCH_OBJ);

        return $this->loadView('home/anotar', [
            'pagina'        => 'Anotacoes',
            'dadosAnotacao' => $dadosAnotacao,
            'anotacao'      => $anotacao
        ]);
    }


    // public function criar()
    // {
    //     $data = [];
    //     $data['pagina'] = 'Anotacoes';
    //     $data['dadosAnotacao'] = $_SESSION['dadosAnotacao'];
    //     return $this->loadView('home/anotar', $data);
    // }



    public function salvar()
    {
        $conteudo      = $_POST['anotacoes_conteudo'] ?? '';
        $experimentoId = $_POST['experimento_id'] ?? '';
        $anotacaoId    = $_POST['anotacao_id'] ?? '';

        $experimento = $this->experimento->buscarExperimento($experimentoId);

        if ($experimento->experimentos_status === 'Finalizado') {
            $_SESSION['msg'] = [
                'texto' => 'Ação bloqueada: Este experimento está finalizado.',
                'color' => 'danger'
            ];

            return $this->redirect(base_url('home/dashboard'));
        }

        if (empty($conteudo) || empty($experimentoId)) {
            $_SESSION['msg'] = [
                'texto' => 'Preencha o campo de anotação!',
                'color' => 'danger'
            ];

            return $this->redirect(base_url('anotacao/index'));
        }

        if (!empty($anotacaoId)) {

            $this->anotacao->update(
                "anotacoes_id = '$anotacaoId'",
                [
                    'anotacoes_conteudo'      => $conteudo,
                    'anotacoes_atualizado_em' => date('Y-m-d H:i:s')
                ]
            );

            $_SESSION['msg'] = [
                'texto' => 'Anotação atualizada com sucesso!',
                'color' => 'success'
            ];
        } else {

            $this->anotacao->insert([
                'anotacoes_conteudo'        => $conteudo,
                'anotacoes_criado_em'       => date('Y-m-d H:i:s'),
                'anotacoes_atualizado_em'   => date('Y-m-d H:i:s'),
                'anotacoes_experimentos_id' => $experimentoId,
            ]);

            $_SESSION['msg'] = [
                'texto' => 'Anotação criada com sucesso!',
                'color' => 'success'
            ];
        }

        return $this->redirect(base_url('anotacao/index'));
    }

    private function ListarDadosParaAnotacao()
    {
        $usuariosId = $_SESSION['usuarios_logado']->usuarios_id;


        $join = "experimentos on experimentos_id = participantes_experimentos_id
        INNER JOIN usuarios on usuarios_id = participantes_usuarios_id";

        $where = "usuarios_id = '$usuariosId' and experimentos_status='Ativo'";

        return $this->participantes->select($join, $where)->fetch(PDO::FETCH_OBJ);
    }


    public function exportarPdf()
    {
        $conteudoHtml = $_POST['anotacoes_conteudo'] ?? ''; // vem do textarea do TinyMCE


        $html = '
            <!DOCTYPE html>
            <html lang="pt-br">
            <head>
                <meta charset="UTF-8">
                <style>
                    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.5; }
                    ul, ol { padding-left: 24px; margin: 8px 0; }
                    li { margin-bottom: 8px; }
                    li p { display: inline; margin: 0; padding: 0; }
                </style>
            </head>
            <body>' . $conteudoHtml . '</body>
            </html>';


        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true); // permite imagens externas, se precisar

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $dompdf->stream('documento.pdf', ['Attachment' => true]);
        exit();
    }
}
