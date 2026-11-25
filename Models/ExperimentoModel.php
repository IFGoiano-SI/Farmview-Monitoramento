<?php

namespace Models;

class ExperimentoModel extends Database
{
    public const STATUS_ATIVO = 'Ativo';
    public const STATUS_FINALIZADO = 'Finalizado';
    public const STATUS_EXCLUIDO = 'Excluido';

    /** Colunas explícitas para evitar sobrescrita de campos homônimos em JOINs. */
    private const SELECT_DETALHADO = '
        SELECT e.experimentos_id, e.experimentos_titulo, e.experimentos_inicio, e.experimentos_final,
               e.experimentos_sala, e.experimentos_status, e.experimentos_encerrado, e.experimentos_imagem,
               u.usuarios_id, u.usuarios_nome, u.usuarios_imagem,
               p.participantes_id, p.participantes_nome, p.participantes_orientador,
               b.bancadas_id, b.bancadas_tipo_plantacao, b.bancadas_nome_cientifico, b.bancadas_quantidade,
               b.bancadas_luzes, b.bancadas_dispositivos
        FROM participantes p
        INNER JOIN usuarios u ON u.usuarios_id = p.participantes_usuarios_id
        INNER JOIN experimentos e ON e.experimentos_id = p.participantes_experimentos_id
        INNER JOIN bancadas_experimentos be ON be.be_experimentos_id = e.experimentos_id
        INNER JOIN bancadas b ON b.bancadas_id = be.be_bancadas_id';

    public function __construct()
    {
        parent::__construct('experimentos');
    }

    /** Experimento ativo mais recente do usuário (com bancada e participante). */
    public function ativoDoUsuario(int $usuarioId): ?object
    {
        return $this->buscarUm(
            self::SELECT_DETALHADO . ' WHERE e.experimentos_status = ? AND p.participantes_usuarios_id = ?
            ORDER BY e.experimentos_id DESC LIMIT 1',
            [self::STATUS_ATIVO, $usuarioId]
        );
    }

    public function contarAtivosDoUsuario(int $usuarioId): int
    {
        return (int) $this->buscarValor(
            'SELECT COUNT(DISTINCT e.experimentos_id)
             FROM participantes p
             INNER JOIN experimentos e ON e.experimentos_id = p.participantes_experimentos_id
             WHERE e.experimentos_status = ? AND p.participantes_usuarios_id = ?',
            [self::STATUS_ATIVO, $usuarioId]
        );
    }

    public function listarDoUsuarioPorStatus(int $usuarioId, string $status): array
    {
        $ordem = $status === self::STATUS_FINALIZADO ? 'e.experimentos_encerrado DESC' : 'e.experimentos_id DESC';

        return $this->buscarTodos(
            self::SELECT_DETALHADO . " WHERE e.experimentos_status = ? AND p.participantes_usuarios_id = ? ORDER BY {$ordem}",
            [$status, $usuarioId]
        );
    }

    public function pesquisarDoUsuario(int $usuarioId, string $termo): array
    {
        return $this->buscarTodos(
            self::SELECT_DETALHADO . ' WHERE e.experimentos_titulo LIKE ? AND p.participantes_usuarios_id = ?
              AND e.experimentos_status <> ? ORDER BY e.experimentos_id DESC',
            ['%' . $termo . '%', $usuarioId, self::STATUS_EXCLUIDO]
        );
    }

    /** Retorna o experimento somente se pertencer ao usuário. */
    public function doUsuario(int $experimentoId, int $usuarioId, ?string $status = null): ?object
    {
        $sql = self::SELECT_DETALHADO . ' WHERE e.experimentos_id = ? AND p.participantes_usuarios_id = ?';
        $params = [$experimentoId, $usuarioId];

        if ($status !== null) {
            $sql .= ' AND e.experimentos_status = ?';
            $params[] = $status;
        }

        return $this->buscarUm($sql . ' LIMIT 1', $params);
    }

    public function detalhado(int $experimentoId): ?object
    {
        return $this->buscarUm(self::SELECT_DETALHADO . ' WHERE e.experimentos_id = ? LIMIT 1', [$experimentoId]);
    }

    public function buscarPorId(int $experimentoId): ?object
    {
        return $this->buscarUm('SELECT * FROM experimentos WHERE experimentos_id = ?', [$experimentoId]);
    }

    /** Listagem paginada no banco (painel do administrador). */
    public function listarPaginado(int $limite, int $offset): array
    {
        return $this->buscarTodos(
            self::SELECT_DETALHADO . ' ORDER BY e.experimentos_id ASC LIMIT ? OFFSET ?',
            [$limite, $offset]
        );
    }

    public function totalComBancada(): int
    {
        return (int) $this->buscarValor(
            'SELECT COUNT(*) FROM participantes p
             INNER JOIN usuarios u ON u.usuarios_id = p.participantes_usuarios_id
             INNER JOIN experimentos e ON e.experimentos_id = p.participantes_experimentos_id
             INNER JOIN bancadas_experimentos be ON be.be_experimentos_id = e.experimentos_id
             INNER JOIN bancadas b ON b.bancadas_id = be.be_bancadas_id'
        );
    }

    public function total(): int
    {
        return (int) $this->buscarValor('SELECT COUNT(*) FROM experimentos');
    }

    public function usuarioComMaisExperimentos(): ?object
    {
        return $this->buscarUm(
            'SELECT COUNT(DISTINCT p.participantes_experimentos_id) AS total_experimentos,
                    u.usuarios_nome AS nome, u.usuarios_imagem AS imagem
             FROM participantes p
             INNER JOIN usuarios u ON u.usuarios_id = p.participantes_usuarios_id
             GROUP BY p.participantes_usuarios_id, u.usuarios_nome, u.usuarios_imagem
             ORDER BY total_experimentos DESC LIMIT 1'
        );
    }

    /**
     * Cria experimento, bancada, vínculo e participante numa única transação.
     */
    public function criarCompleto(array $experimento, array $bancada, array $participante): int
    {
        return $this->transacao(function () use ($experimento, $bancada, $participante) {
            $experimentoId = $this->insert($experimento);

            $bancadaId = (new Database('bancadas'))->insert($bancada);

            (new Database('bancadas_experimentos'))->insert([
                'be_bancadas_id' => $bancadaId,
                'be_experimentos_id' => $experimentoId,
            ]);

            $participante['participantes_experimentos_id'] = $experimentoId;
            (new Database('participantes'))->insert($participante);

            return $experimentoId;
        });
    }

    public function atualizarDados(int $experimentoId, array $dados): int
    {
        return $this->update('experimentos_id = ?', $dados, [$experimentoId]);
    }

    public function atualizarBancada(int $experimentoId, array $dados): int
    {
        if (!$dados) {
            return 0;
        }

        $set = implode(' = ?, ', array_keys($dados)) . ' = ?';

        return $this->execute(
            "UPDATE bancadas b INNER JOIN bancadas_experimentos be ON be.be_bancadas_id = b.bancadas_id
             SET {$set} WHERE be.be_experimentos_id = ?",
            array_merge(array_values($dados), [$experimentoId])
        )->rowCount();
    }

    public function alterarStatus(int $experimentoId, string $status): int
    {
        $dados = ['experimentos_status' => $status];

        if ($status === self::STATUS_FINALIZADO) {
            $dados['experimentos_encerrado'] = date('Y-m-d H:i:s');
        } elseif ($status === self::STATUS_ATIVO) {
            $dados['experimentos_encerrado'] = null;
        }

        return $this->atualizarDados($experimentoId, $dados);
    }
}
