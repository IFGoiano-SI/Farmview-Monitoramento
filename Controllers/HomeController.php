<?php

namespace Controllers;

use Models\ExperimentoModel;
use Models\VideoModel;
use Services\FlaskService;
use Services\Logger;

class HomeController extends BaseController
{
    private ExperimentoModel $experimentos;
    private FlaskService $flask;

    public function __construct()
    {
        $this->experimentos = new ExperimentoModel();
        $this->flask = new FlaskService();
    }

    public function index(): void
    {
        $experimento = $this->experimentos->ativoDoUsuario($this->usuarioId());

        if (!$experimento) {
            $this->loadView('home/index', ['pagina' => 'Experimento de usuario']);
            return;
        }

        $this->loadView('home/dashboard', [
            'pagina' => 'Experimento de usuario',
            'usuarioExperimento' => $experimento,
        ] + $this->estadoBancada());
    }

    /** JSON consultado periodicamente pelo dashboard. */
    public function sensores(): void
    {
        // Libera o lock da sessão: o polling não bloqueia outras abas do usuário.
        session_write_close();

        $estado = $this->estadoBancada();

        $this->json([
            'success' => $estado['online'],
            'online' => $estado['online'],
            'v' => $estado['sensores']['v'],
            'pct' => $estado['sensores']['pct'],
            'atualizado_em' => $estado['sensores']['atualizado_em'],
            'idade_segundos' => $estado['idadeLeitura'],
            'camera_on' => $estado['camera_on'],
            'gravando' => $estado['gravando'],
            'tomadas' => $estado['tomadas'],
            'limites' => $estado['limites'],
        ]);
    }

    public function status(): void
    {
        $this->sensores();
    }

    public function toggleDispositivo(): void
    {
        $tomada = filter_var($_POST['tomada'] ?? null, FILTER_VALIDATE_INT);
        $acao = ($_POST['acao'] ?? 'off') === 'on' ? 'on' : 'off';

        if (!$this->experimentos->ativoDoUsuario($this->usuarioId())) {
            $this->redirect(base_url('home'), 'Você precisa de um experimento ativo para controlar a bancada.', 'danger');
        }

        if (!in_array($tomada, FlaskService::TOMADAS, true)) {
            $this->redirect(base_url('home/dashboard'), 'Tomada inválida.', 'danger');
        }

        if (!$this->flask->definirTomada($tomada, $acao)) {
            $this->redirect(base_url('home/dashboard'), "A bancada não respondeu. A tomada {$tomada} pode não ter sido alterada.", 'warning');
        }

        Logger::info('Tomada alterada', ['tomada' => $tomada, 'acao' => $acao]);
        $this->redirect(base_url('home/dashboard'));
    }

    public function ligarCamera(): void
    {
        $acao = ($_POST['acao'] ?? '') === 'off' ? 'off' : 'on';

        if (!$this->experimentos->ativoDoUsuario($this->usuarioId())) {
            $this->redirect(base_url('home'), 'Você precisa de um experimento ativo para controlar a bancada.', 'danger');
        }

        if (!$this->flask->definirCamera($acao)) {
            $this->redirect(base_url('home/dashboard'), 'A câmera não respondeu. Verifique a conexão com a bancada.', 'warning');
        }

        $this->redirect(base_url('home/dashboard'));
    }

    public function iniciarGravacao(): void
    {
        $experimento = $this->experimentos->ativoDoUsuario($this->usuarioId());

        if (!$experimento) {
            $this->json(['erro' => 'Nenhum experimento ativo para vincular a gravação.'], 409);
        }

        $resposta = $this->flask->iniciarGravacao();

        if ($resposta['code'] === 0) {
            $this->json(['erro' => 'Sem comunicação com o sistema da câmera'], 503);
        }

        if (!$resposta['ok']) {
            $this->json(['erro' => $resposta['data']['erro'] ?? 'Não foi possível iniciar a gravação'], $resposta['code']);
        }

        $_SESSION['gravacao_experimento_id'] = (int) $experimento->experimentos_id;
        $this->json($resposta['data']);
    }

    public function pararGravacao(): void
    {
        $usuarioId = $this->usuarioId();
        $experimento = $this->experimentos->ativoDoUsuario($usuarioId);
        $experimentoId = (int) ($experimento->experimentos_id ?? ($_SESSION['gravacao_experimento_id'] ?? 0));

        // A parada inclui a conversão para mp4 no Raspberry e o download do vídeo,
        // o que pode levar minutos. Liberamos a sessão para não travar o usuário.
        session_write_close();
        set_time_limit(320);

        $resultado = $this->finalizarGravacao($usuarioId, $experimentoId);

        session_start();
        unset($_SESSION['gravacao_experimento_id']);

        if (!$resultado['ok']) {
            $this->json($resultado, 500);
        }

        $this->json($resultado);
    }

    private function finalizarGravacao(int $usuarioId, int $experimentoId): array
    {
        $resposta = $this->flask->pararGravacao();

        if (!$resposta['ok']) {
            return ['ok' => false, 'erro' => $resposta['data']['erro'] ?? 'Falha ao parar a gravação'];
        }

        if ($experimentoId <= 0) {
            return ['ok' => false, 'erro' => 'Gravação parada, mas não há experimento ativo para vinculá-la.'];
        }

        $arquivo = basename((string) ($resposta['data']['arquivo'] ?? ''));

        if (!preg_match('/^rec_\d{8}_\d{6}\.(mp4|avi)$/', $arquivo)) {
            return ['ok' => false, 'erro' => 'Nome de arquivo de gravação inválido'];
        }

        $pasta = GravacaoController::pastaVideos();

        if (!is_dir($pasta) && !mkdir($pasta, 0775, true)) {
            Logger::erro('Não foi possível criar a pasta de vídeos', ['pasta' => $pasta]);
            return ['ok' => false, 'erro' => 'Não foi possível salvar o vídeo no servidor'];
        }

        if (!$this->flask->baixarGravacao($arquivo, $pasta . $arquivo)) {
            return ['ok' => false, 'erro' => 'Falha ao baixar o vídeo do sistema da câmera'];
        }

        $dados = [
            'path_video' => 'storage/videos/' . $arquivo,
            'usuarios_id' => $usuarioId,
            'experimentos_id' => $experimentoId,
            'duracao_segundos' => (int) ($resposta['data']['duracao'] ?? 0),
            'tamanho_bytes' => (int) filesize($pasta . $arquivo),
            'videos_status' => 'Ativo',
            'criado_em' => date('Y-m-d H:i:s'),
        ];

        if (!(new VideoModel())->insert($dados)) {
            return ['ok' => false, 'erro' => 'Vídeo baixado, mas falhou ao salvar no banco'];
        }

        return ['ok' => true, 'arquivo' => $arquivo, 'duracao' => $dados['duracao_segundos'], 'tamanho' => $dados['tamanho_bytes']];
    }

    /**
     * Estado real da bancada, lido do Raspberry (não da sessão do usuário).
     */
    private function estadoBancada(): array
    {
        $status = $this->flask->status();
        $sensores = $this->flask->getSensores();
        $config = $status['config'] ?? [];

        $tomadas = [];
        foreach (FlaskService::TOMADAS as $n) {
            $valor = $status['tomadas'][$n] ?? ($status['tomadas'][(string) $n] ?? null);
            $tomadas[$n] = $valor === null ? null : (bool) $valor;
        }

        return [
            'online' => $status !== null || $sensores['online'],
            'sensores' => $sensores,
            'idadeLeitura' => $status['sensores']['idade_segundos'] ?? null,
            'camera_on' => (bool) ($status['camera_on'] ?? false),
            'gravando' => (bool) ($status['gravando'] ?? false),
            'tomadas' => $tomadas,
            'limites' => [
                'baixa' => (float) ($config['limite_umidade_baixa'] ?? 35),
                'alta' => (float) ($config['limite_umidade_alta'] ?? 60),
            ],
            'videoUrl' => base_url('camera/stream'),
        ];
    }
}
