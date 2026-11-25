<?php

namespace Controllers;

use Dompdf\Dompdf;
use Dompdf\Options;
use Models\AnotacaoModel;
use Models\ExperimentoModel;

class AnotacaoController extends BaseController
{
    private AnotacaoModel $anotacoes;
    private ExperimentoModel $experimentos;

    public function __construct()
    {
        $this->anotacoes = new AnotacaoModel();
        $this->experimentos = new ExperimentoModel();
    }

    public function index(): void
    {
        $experimento = $this->experimentos->ativoDoUsuario($this->usuarioId());

        if (!$experimento) {
            $this->redirect(base_url('home/tela'), 'Nenhum experimento ativo encontrado!', 'danger');
        }

        $this->loadView('home/anotar', [
            'pagina' => 'Anotacoes',
            'dadosAnotacao' => $experimento,
            'anotacao' => $this->anotacoes->doExperimento((int) $experimento->experimentos_id),
        ]);
    }

    public function salvar(): void
    {
        $conteudo = trim((string) ($_POST['anotacoes_conteudo'] ?? ''));
        $experimentoId = (int) ($_POST['experimento_id'] ?? 0);

        $experimento = $this->experimentos->doUsuario($experimentoId, $this->usuarioId());

        if (!$experimento) {
            $this->redirect(base_url('home/dashboard'), 'Experimento não encontrado.', 'danger');
        }

        if ($experimento->experimentos_status !== ExperimentoModel::STATUS_ATIVO) {
            $this->redirect(base_url('home/dashboard'), 'Ação bloqueada: Este experimento está finalizado.', 'danger');
        }

        if ($conteudo === '') {
            $this->redirect(base_url('anotacao/index'), 'Preencha o campo de anotação!', 'danger');
        }

        $resultado = $this->anotacoes->salvar($experimentoId, $conteudo);

        $this->redirect(base_url('anotacao/index'), "Anotação {$resultado} com sucesso!");
    }

    /**
     * Exporta o conteúdo atual do editor. Recursos remotos ficam desabilitados
     * (impede que o servidor seja usado para acessar URLs internas).
     */
    public function exportarPdf(): void
    {
        $conteudoHtml = (string) ($_POST['anotacoes_conteudo'] ?? '');

        $html = '<!DOCTYPE html>
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
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('isJavascriptEnabled', false);
        $options->setChroot(dirname(__DIR__) . '/vendor/dompdf/dompdf');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        session_write_close();
        $dompdf->stream('documento.pdf', ['Attachment' => true]);
        exit;
    }
}
