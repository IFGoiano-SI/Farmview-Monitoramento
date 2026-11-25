<?php

namespace Models;

class VideoModel extends Database
{
    public function __construct()
    {
        parent::__construct('videos_experimentos');
    }

    public function ativosDoUsuario(int $usuarioId): array
    {
        return $this->buscarTodos(
            "SELECT v.videos_id, v.path_video, v.duracao_segundos, v.tamanho_bytes, v.videos_status, v.criado_em,
                    e.experimentos_titulo
             FROM videos_experimentos v
             INNER JOIN experimentos e ON e.experimentos_id = v.experimentos_id
             WHERE v.usuarios_id = ? AND v.videos_status = 'Ativo'
             ORDER BY v.criado_em ASC",
            [$usuarioId]
        );
    }

    public function doUsuario(int $videoId, int $usuarioId): ?object
    {
        return $this->buscarUm(
            "SELECT * FROM videos_experimentos WHERE videos_id = ? AND usuarios_id = ? AND videos_status = 'Ativo'",
            [$videoId, $usuarioId]
        );
    }

    public function desativar(int $videoId): int
    {
        return $this->update('videos_id = ?', [
            'videos_status' => 'Desativado',
            'deletado_em' => date('Y-m-d H:i:s'),
        ], [$videoId]);
    }
}
