<?php

namespace Models;

class UsuarioModel extends Database
{
    /** Colunas seguras para listagem (sem senha e sem tokens). */
    private const COLUNAS_PUBLICAS = 'usuarios_id, usuarios_nome, usuarios_escolaridade, usuarios_email,
        usuarios_imagem, usuarios_ativo, usuarios_nivel, usuarios_criado_em, usuarios_atualizado_em,
        usuarios_deletado_em';

    public function __construct()
    {
        parent::__construct('usuarios');
    }

    /** Inclui o hash da senha: usar apenas na autenticação. */
    public function buscarParaLogin(string $email): ?object
    {
        return $this->buscarUm('SELECT * FROM usuarios WHERE usuarios_email = ? LIMIT 1', [$email]);
    }

    public function buscarPorEmail(string $email): ?object
    {
        return $this->buscarUm('SELECT ' . self::COLUNAS_PUBLICAS . ' FROM usuarios WHERE usuarios_email = ? LIMIT 1', [$email]);
    }

    public function buscarPorId(int $id): ?object
    {
        return $this->buscarUm('SELECT ' . self::COLUNAS_PUBLICAS . ' FROM usuarios WHERE usuarios_id = ?', [$id]);
    }

    public function emailEmUso(string $email, ?int $exceto = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM usuarios WHERE usuarios_email = ?';
        $params = [$email];

        if ($exceto !== null) {
            $sql .= ' AND usuarios_id <> ?';
            $params[] = $exceto;
        }

        return (int) $this->buscarValor($sql, $params) > 0;
    }

    public function listarTodos(): array
    {
        return $this->buscarTodos('SELECT ' . self::COLUNAS_PUBLICAS . ' FROM usuarios ORDER BY usuarios_id');
    }

    public function total(): int
    {
        return (int) $this->buscarValor('SELECT COUNT(*) FROM usuarios');
    }

    public function criar(array $dados): int
    {
        return $this->insert($dados);
    }

    public function atualizar(int $id, array $dados): int
    {
        return $this->update('usuarios_id = ?', $dados, [$id]);
    }

    public function desativar(int $id): int
    {
        return $this->atualizar($id, [
            'usuarios_ativo' => 0,
            'usuarios_deletado_em' => date('Y-m-d H:i:s'),
        ]);
    }

    /** O token é guardado apenas como hash SHA-256. */
    public function definirTokenReset(int $id, string $tokenHash, string $expiraEm): int
    {
        return $this->atualizar($id, [
            'usuarios_reset_hash' => $tokenHash,
            'usuarios_expira_em' => $expiraEm,
        ]);
    }

    public function buscarPorTokenReset(string $tokenHash): ?object
    {
        return $this->buscarUm(
            'SELECT usuarios_id, usuarios_reset_hash FROM usuarios
             WHERE usuarios_reset_hash = ? AND usuarios_expira_em > NOW() AND usuarios_ativo = 1 LIMIT 1',
            [$tokenHash]
        );
    }

    public function redefinirSenha(int $id, string $senhaHash): int
    {
        return $this->atualizar($id, [
            'usuarios_senha' => $senhaHash,
            'usuarios_reset_hash' => null,
            'usuarios_expira_em' => null,
            'usuarios_atualizado_em' => date('Y-m-d H:i:s'),
        ]);
    }

    public function limparTokenReset(int $id): int
    {
        return $this->atualizar($id, ['usuarios_reset_hash' => null, 'usuarios_expira_em' => null]);
    }
}
