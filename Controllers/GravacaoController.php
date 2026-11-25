<?php

namespace Controllers;

use Models\VideoModel;
use Services\Logger;

class GravacaoController extends BaseController
{
    private VideoModel $videos;

    public function __construct()
    {
        $this->videos = new VideoModel();
    }

    /** Vídeos ficam fora da pasta pública e só são entregues ao dono. */
    public static function pastaVideos(): string
    {
        return dirname(__DIR__) . '/storage/videos/';
    }

    public function index(): void
    {
        $this->loadView('home/gravacao', [
            'dadosVideo' => $this->videos->ativosDoUsuario($this->usuarioId()),
        ]);
    }

    public function download(string $id): void
    {
        $video = $this->videos->doUsuario($this->idDaUrlOu400($id), $this->usuarioId());
        $arquivo = $video ? self::arquivoDoVideo($video->path_video) : null;

        if (!$arquivo) {
            $this->redirect(base_url('gravacao/index'), 'Arquivo nao encontrado!', 'danger');
        }

        session_write_close();

        $tipo = str_ends_with($arquivo, '.avi') ? 'video/x-msvideo' : 'video/mp4';
        header('Content-Type: ' . $tipo);
        header('Content-Length: ' . filesize($arquivo));
        header('Content-Disposition: attachment; filename="' . basename($arquivo) . '"');
        header('Cache-Control: private, no-store');
        readfile($arquivo);
        exit;
    }

    public function deletarGravacao(): void
    {
        $videoId = (int) ($_POST['videos_id'] ?? 0);
        $video = $this->videos->doUsuario($videoId, $this->usuarioId());

        if (!$video) {
            $this->redirect(base_url('gravacao/index'), 'Arquivo nao encontrado!', 'danger');
        }

        $this->videos->desativar($videoId);
        $this->removerArquivo($video->path_video);

        $this->redirect(base_url('gravacao/index'), 'Arquivo deletado com sucesso!');
    }

    public function deletarTodasGravacao(): void
    {
        $videos = $this->videos->ativosDoUsuario($this->usuarioId());

        foreach ($videos as $video) {
            $this->videos->desativar((int) $video->videos_id);
            $this->removerArquivo($video->path_video);
        }

        $this->redirect(base_url('gravacao/index'), 'Todos os videos foram deletados com sucesso!');
    }

    /**
     * Resolve o arquivo a partir do nome salvo no banco, procurando apenas nas
     * pastas de vídeo conhecidas (inclui a pasta antiga Public/UploadsVideos).
     */
    public static function arquivoDoVideo(string $caminhoBanco): ?string
    {
        $nome = basename($caminhoBanco);

        if (!preg_match('/^[\w.-]+\.(mp4|avi)$/', $nome)) {
            return null;
        }

        foreach ([self::pastaVideos(), dirname(__DIR__) . '/Public/UploadsVideos/'] as $pasta) {
            if (is_file($pasta . $nome)) {
                return $pasta . $nome;
            }
        }

        return null;
    }

    private function removerArquivo(string $caminhoBanco): void
    {
        $arquivo = self::arquivoDoVideo($caminhoBanco);

        if ($arquivo && !@unlink($arquivo)) {
            Logger::aviso('Não foi possível remover o arquivo de vídeo', ['arquivo' => $arquivo]);
        }
    }
}
