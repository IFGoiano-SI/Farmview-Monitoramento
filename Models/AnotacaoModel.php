<?php

namespace Models;

class AnotacaoModel extends Database
{
    public function __construct()
    {
        parent::__construct('anotacoes');
    }

    public function doExperimento(int $experimentoId): ?object
    {
        return $this->buscarUm(
            'SELECT * FROM anotacoes WHERE anotacoes_experimentos_id = ? ORDER BY anotacoes_id DESC LIMIT 1',
            [$experimentoId]
        );
    }

    /** Uma anotação por experimento: atualiza se existir, senão cria. */
    public function salvar(int $experimentoId, string $conteudo): string
    {
        $agora = date('Y-m-d H:i:s');
        $existente = $this->doExperimento($experimentoId);

        if ($existente) {
            $this->update('anotacoes_id = ?', [
                'anotacoes_conteudo' => $conteudo,
                'anotacoes_atualizado_em' => $agora,
            ], [$existente->anotacoes_id]);

            return 'atualizada';
        }

        $this->insert([
            'anotacoes_conteudo' => $conteudo,
            'anotacoes_criado_em' => $agora,
            'anotacoes_atualizado_em' => $agora,
            'anotacoes_experimentos_id' => $experimentoId,
        ]);

        return 'criada';
    }
}
