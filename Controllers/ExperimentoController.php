<?php

namespace Controllers;

use Models\ExperimentoModel;
use RuntimeException;
use Services\FlaskService;
use Services\Logger;
use Services\Upload;
use Services\Validacao;

class ExperimentoController extends BaseController
{
    private ExperimentoModel $experimentos;

    public function __construct()
    {
        $this->experimentos = new ExperimentoModel();
    }

    public function cadastrar(): void
    {
        $post = $_POST;
        $usuarioId = $this->usuarioId();
        $voltar = base_url('home');

        // Regra: apenas um experimento ativo por usuário.
        if ($this->experimentos->contarAtivosDoUsuario($usuarioId) > 0) {
            $this->redirect(base_url('home/dashboard'), 'Você já possui um experimento ativo. Finalize-o antes de cadastrar outro.', 'danger');
        }

        $luzes = array_values(array_filter((array) ($post['bancadas_luzes'] ?? []), 'is_string'));
        $dispositivos = array_values(array_filter((array) ($post['bancadas_dispositivos'] ?? []), 'is_string'));
        $orientador = trim((string) ($post['participantes_orientador'] ?? ''));
        $externos = trim((string) ($post['participantes_externos'] ?? ''));

        $erro = Validacao::experimento($post)
            ?? Validacao::bancada($post + ['bancadas_luzes' => $luzes])
            ?? ($orientador === '' ? 'Não é possivel cadastar um experimento sem um orientador!' : null)
            ?? (mb_strlen($orientador) > 255 || mb_strlen($externos) > 255
                ? 'Os campos de participantes e orientador aceitam até 255 caracteres.' : null);

        if ($erro) {
            $this->voltarComErro($voltar, $erro, $post);
        }

        try {
            $imagem = Upload::imagem($_FILES['experimentos_imagem'] ?? null, 'Public/templates/Images');
        } catch (RuntimeException $e) {
            $this->voltarComErro($voltar, $e->getMessage(), $post, 'warning');
        }

        try {
            $this->experimentos->criarCompleto(
                [
                    'experimentos_titulo' => trim($post['experimentos_titulo']),
                    'experimentos_inicio' => $post['experimentos_inicio'],
                    'experimentos_final' => $post['experimentos_final'],
                    'experimentos_sala' => trim($post['experimentos_sala']),
                    'experimentos_status' => ExperimentoModel::STATUS_ATIVO,
                    'experimentos_imagem' => $imagem,
                ],
                [
                    'bancadas_tipo_plantacao' => trim($post['bancadas_tipo_plantacao']),
                    'bancadas_nome_cientifico' => trim($post['bancadas_nome_cientifico']),
                    'bancadas_quantidade' => (int) $post['bancadas_quantidade'],
                    'bancadas_luzes' => json_encode($luzes, JSON_UNESCAPED_UNICODE),
                    'bancadas_dispositivos' => json_encode($dispositivos, JSON_UNESCAPED_UNICODE),
                ],
                [
                    'participantes_nome' => $externos,
                    'participantes_orientador' => $orientador,
                    'participantes_usuarios_id' => $usuarioId,
                ]
            );
        } catch (\Throwable $e) {
            Upload::remover($imagem);
            Logger::erro('Falha ao cadastrar experimento', ['erro' => $e->getMessage()]);
            $this->voltarComErro($voltar, 'Erro ao cadastrar experimento!', $post);
        }

        $this->redirect(base_url('home/dashboard'), 'Experimento cadastrado com sucesso! Boa pesquisa!');
    }

    public function pesquisarExperimento(): void
    {
        $termo = trim((string) ($_REQUEST['pesquisar'] ?? ''));

        if ($termo === '') {
            $this->listarExperimentos();
            return;
        }

        $resultado = $this->experimentos->pesquisarDoUsuario($this->usuarioId(), $termo);

        $this->loadView('home/experimentos', [
            'pagina' => 'Pesquisar experimento',
            'pesquisa' => $termo,
            'experimentosAtivos' => array_filter($resultado, fn($e) => $e->experimentos_status === ExperimentoModel::STATUS_ATIVO),
            'experimentosFinalizados' => array_filter($resultado, fn($e) => $e->experimentos_status === ExperimentoModel::STATUS_FINALIZADO),
        ]);
    }

    public function finalizarExperimento(string $experimentoId): void
    {
        $experimento = $this->experimentoAtivoDoUsuarioOuVoltar((int) $experimentoId);

        $bancadaDesligada = (new FlaskService())->desligarTudo();
        $this->experimentos->alterarStatus((int) $experimento->experimentos_id, ExperimentoModel::STATUS_FINALIZADO);

        Logger::info('Experimento finalizado', ['experimento' => $experimento->experimentos_id, 'bancada_desligada' => $bancadaDesligada]);

        if (!$bancadaDesligada) {
            $this->redirect(
                base_url('home'),
                'Experimento finalizado, mas a bancada não confirmou o desligamento dos dispositivos. Verifique a bancada.',
                'warning'
            );
        }

        $this->redirect(base_url('home'), 'Experimento finalizado com sucesso!');
    }

    public function editar(string $experimentoId): void
    {
        $this->loadView('home/alterarExperimento', [
            'editarExperimentos' => $this->experimentoAtivoDoUsuarioOuVoltar((int) $experimentoId),
        ]);
    }

    public function editarExperimentosAtivo(string $experimentoId): void
    {
        $antigo = $this->experimentoAtivoDoUsuarioOuVoltar((int) $experimentoId);
        $voltar = base_url('experimento/editar/' . $antigo->experimentos_id);

        $dados = [
            'experimentos_titulo' => trim((string) ($_POST['experimentos_titulo'] ?? $antigo->experimentos_titulo)),
            'experimentos_sala' => trim((string) ($_POST['experimentos_sala'] ?? $antigo->experimentos_sala)),
            'experimentos_inicio' => $_POST['experimentos_inicio'] ?? $antigo->experimentos_inicio,
            'experimentos_final' => $_POST['experimentos_final'] ?? $antigo->experimentos_final,
        ];

        if ($erro = Validacao::experimento($dados)) {
            $this->redirect($voltar, $erro, 'danger');
        }

        $this->experimentos->atualizarDados((int) $antigo->experimentos_id, $dados);

        $this->redirect($voltar, 'Experimento editado com sucesso!');
    }

    public function excluirExperimento(string $experimentoId): void
    {
        $experimento = $this->experimentoAtivoDoUsuarioOuVoltar((int) $experimentoId);

        // Mesmo comportamento da finalização: a bancada é desligada.
        $bancadaDesligada = (new FlaskService())->desligarTudo();
        $this->experimentos->alterarStatus((int) $experimento->experimentos_id, ExperimentoModel::STATUS_EXCLUIDO);

        Logger::info('Experimento excluído', ['experimento' => $experimento->experimentos_id]);

        $this->redirect(
            base_url('home'),
            $bancadaDesligada
                ? 'Experimento excluído com sucesso!'
                : 'Experimento excluído, mas a bancada não confirmou o desligamento dos dispositivos.',
            $bancadaDesligada ? 'success' : 'warning'
        );
    }

    public function listarExperimentos(): void
    {
        $usuarioId = $this->usuarioId();

        $this->loadView('home/experimentos', [
            'experimentosAtivos' => $this->experimentos->listarDoUsuarioPorStatus($usuarioId, ExperimentoModel::STATUS_ATIVO),
            'experimentosFinalizados' => $this->experimentos->listarDoUsuarioPorStatus($usuarioId, ExperimentoModel::STATUS_FINALIZADO),
        ]);
    }

    /** Garante que o experimento existe, está ativo e pertence ao usuário logado. */
    private function experimentoAtivoDoUsuarioOuVoltar(int $experimentoId): object
    {
        $experimento = $this->experimentos->doUsuario($experimentoId, $this->usuarioId(), ExperimentoModel::STATUS_ATIVO);

        if (!$experimento) {
            $this->redirect(base_url('home'), 'Experimento não encontrado ou não está ativo.', 'danger');
        }

        return $experimento;
    }
}
