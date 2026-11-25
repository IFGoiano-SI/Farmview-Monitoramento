<?php

namespace Models;

use PDO;
use PDOStatement;

/**
 * Acesso ao banco de dados.
 *
 * - Uma única conexão PDO por requisição (compartilhada entre os models).
 * - Todos os valores vindos do usuário devem ir em $params (prepared statements).
 * - Nomes de tabela/coluna nunca vêm do usuário: são definidos no código.
 */
class Database
{
    private static ?PDO $conexaoCompartilhada = null;

    protected ?string $tabela;
    protected PDO $conexao;

    public function __construct(?string $tabela = null)
    {
        $this->tabela = $tabela;
        $this->conexao = self::conexao();
    }

    public static function conexao(): PDO
    {
        if (self::$conexaoCompartilhada === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                config('db.host'),
                config('db.port'),
                config('db.name')
            );

            self::$conexaoCompartilhada = new PDO($dsn, config('db.user'), config('db.pass'), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                PDO::ATTR_EMULATE_PREPARES => false,
                // rowCount() passa a contar linhas encontradas, não só as alteradas.
                PDO::MYSQL_ATTR_FOUND_ROWS => true,
            ]);

            // Alinha o fuso do MySQL ao do PHP: NOW() e date() passam a concordar
            // (antes, tokens de senha podiam "expirar" antes da hora no Docker/UTC).
            self::$conexaoCompartilhada->exec("SET time_zone = '" . date('P') . "'");
        }

        return self::$conexaoCompartilhada;
    }

    /** Permite injetar uma conexão (usado nos testes). */
    public static function definirConexao(?PDO $pdo): void
    {
        self::$conexaoCompartilhada = $pdo;
    }

    public function execute(string $query, array $params = []): PDOStatement
    {
        $stmt = $this->conexao->prepare($query);
        $stmt->execute($params);

        return $stmt;
    }

    public function buscarUm(string $query, array $params = []): ?object
    {
        $linha = $this->execute($query, $params)->fetch(PDO::FETCH_OBJ);

        return $linha ?: null;
    }

    public function buscarTodos(string $query, array $params = []): array
    {
        return $this->execute($query, $params)->fetchAll(PDO::FETCH_OBJ);
    }

    public function buscarValor(string $query, array $params = [])
    {
        return $this->execute($query, $params)->fetchColumn();
    }

    /**
     * @return int ID inserido
     */
    public function insert(array $values): int
    {
        $campos = array_keys($values);
        $marcadores = implode(',', array_fill(0, count($values), '?'));

        $query = "INSERT INTO {$this->tabela} (" . implode(',', $campos) . ") VALUES ({$marcadores})";
        $this->execute($query, array_values($values));

        return (int) $this->conexao->lastInsertId();
    }

    /**
     * @param string $where Condição com marcadores "?" (ex.: "usuarios_id = ?")
     * @return int Quantidade de linhas encontradas
     */
    public function update(string $where, array $values, array $whereParams = []): int
    {
        $set = implode(' = ?, ', array_keys($values)) . ' = ?';
        $query = "UPDATE {$this->tabela} SET {$set} WHERE {$where}";

        return $this->execute($query, array_merge(array_values($values), $whereParams))->rowCount();
    }

    public function transacao(callable $operacao)
    {
        $this->conexao->beginTransaction();

        try {
            $resultado = $operacao();
            $this->conexao->commit();

            return $resultado;
        } catch (\Throwable $e) {
            $this->conexao->rollBack();
            throw $e;
        }
    }
}
