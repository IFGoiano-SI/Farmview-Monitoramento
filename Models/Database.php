<?php

namespace Models;

use \PDO;
use \PDOException;

class Database
{
    const HOST = 'localhost';

    const NAME = 'CEBIOHUB';

    const USER = 'root';

    const PASS = "root";

    const PORT = '3309';

    private ?string $tabela;

    private PDO $conection;


    public function __construct(?string $tabela = null)
    {
        $this->tabela = $tabela; // essa tabela vai receber a variavel de toda tabela
        $this->setConexao();
    }


    private function setConexao()
    {
        try {

            // Usa variáveis de ambiente (Docker) e mantém as constantes como fallback.
            $host = getenv('DB_HOST') ?: self::HOST;
            $name = getenv('DB_NAME') ?: self::NAME;
            $user = getenv('DB_USER') ?: self::USER;
            $pass = (getenv('DB_PASS') !== false) ? getenv('DB_PASS') : self::PASS;
            $port = getenv('DB_PORT') ?: self::PORT;

            $this->conection = new PDO("mysql:host=" . $host . ';dbname=' . $name . ';port=' . $port . ';charset=utf8mb4', $user, $pass);
            $this->conection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function execute($query, $param = [])
    {
        try {
            $stmt = $this->conection->prepare($query);
            $stmt->execute($param);
            return $stmt;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Criar dados
     * LER dados
     * Alterar dados
     * Deletar dados
     */

    /**
     * @param Array de inserção de dados completos
     * @return LastId - Ultimo ID inserido no banco de dados
     */

    public function insert($values)
    {

        $campo = array_keys($values); //vai pegar a chave do array
        $posicao = array_pad([], count($values), '?'); //aqui vai pegar o array, contar quantos elementos tem e vai passar como interrogação a quantidade

        // Insert into <tabela> (campos que posição desta tabela) values (?) a quantidade de itens que tem dentro da tabela


        $query = "INSERT into " . $this->tabela . ' (' . implode(',', $campo) . ') VALUES (' . implode(',', $posicao) . ')';

        // Insere os valores que foram passados
        $this->execute($query, array_values($values));


        return (int) $this->conection->lastInsertId();
    }

    // Validação de foto de perfil
    public function validaImagem(array $usuario_imagem)
    {
        // Verificando se o arquivo é mairo que 2MB
        if ($usuario_imagem['size'] > (1024 * 1024 * 2)) {
            return false;
        } else {
            return true;
        }
    }

    // Verifica o tipo da imagem
    public function tipoImagem(array $usuario_imagem)
    {

        $tipoImagem = ['image/jpeg', 'image/jpg', 'image/png'];


        if (!in_array($usuario_imagem['type'], $tipoImagem)) {
            return false;
        } else {
            return true;
        }
    }

    public function tamanhoImagem(array $usuario_imagem)
    {
        $dimensoes = getimagesize($usuario_imagem['tmp_name']);



        if ($dimensoes[0] < 300 || $dimensoes[1] < 300) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * @param Id do usuario para pegar o ultimo usuario cadastrado
     * @return usuario como parametro para sessao
     * @uso Usado em usuarioController
     */
    public function buscarUsuario($id)
    {

        $stmt = $this->conection->prepare('SELECT * FROM usuarios WHERE usuarios_id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function select($join = null, $where = null, $order = null, $limit = null, $campo = '*')
    {

        $join = !empty($join) ? 'INNER JOIN ' . $join : '';
        $where = !empty($where) ? "WHERE " . $where : '';
        $order = !empty($order) ? "ORDER BY " . $order : '';
        $limit = !empty($limit) ? "LIMIT " . $limit : '';


        $query = 'SELECT ' . $campo . ' FROM ' . $this->tabela . ' ' . $join . ' ' . $where . ' ' . $order . ' ' . $limit;

        return $this->execute($query);
    }


    public function update($where, $values)
    {

        $fields = array_keys($values);


        $set = implode(' = ?, ', $fields) . ' = ?';
        $query = "UPDATE  {$this->tabela} SET {$set} WHERE {$where}";

        $this->execute($query, array_values($values));


        return true;
    }


    // FUnção para atualizar os experimentos quando forem finalizados
    public function UpdateExperimentos($join, $where, $values)
    {
        $fields = $values;
        $join = !empty($join) ? 'INNER JOIN ' . $join : '';
        $where = !empty($where) ? "WHERE " . $where : '';

        $query = 'UPDATE ' . $this->tabela . ' ' . $join . ' SET ' . $fields . ' ' . $where;

        $this->execute($query);

        return true;
    }


    public function selectPesquisar($join = null, $where = null, $order = null, $limit = null, $campo = '*', $params = [])
    {
        $join  = !empty($join)  ? 'INNER JOIN ' . $join  : '';
        $where = !empty($where) ? 'WHERE '      . $where : '';
        $order = !empty($order) ? 'ORDER BY '   . $order : '';
        $limit = !empty($limit) ? 'LIMIT '      . $limit : '';

        $query = 'SELECT ' . $campo . ' FROM ' . $this->tabela . ' '
            . $join . ' ' . $where . ' ' . $order . ' ' . $limit;



        return $this->execute($query, $params);
    }

    public function buscarExperimento($id)
    {
        // var_dump($id);

        $stmt = $this->conection->prepare(
            "SELECT * FROM experimentos WHERE experimentos_id = ?"
        );

        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_OBJ);
    }


    public function selectQuantidadeExperimentos()
    {
        $stmt = $this->conection->prepare("SELECT COUNT(*) FROM experimentos");

        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_COLUMN);
    }


    /***
     * SELECT
    participantes_usuarios_id AS usuario_id,
    COUNT(DISTINCT participantes_experimentos_id) AS total_experimentos
    FROM participantes
    GROUP BY participantes_usuarios_id
    ORDER BY total_experimentos DESC
    LIMIT 1;
     * 
     * 
     */


    public function mostrarMaiorQuantidadeExperimentoUsuario() {

    $stmt = $this->conection->prepare("SELECT COUNT(DISTINCT participantes_experimentos_id) AS total_experimentos, usuarios.usuarios_nome as nome, usuarios.usuarios_imagem as imagem FROM participantes INNER JOIN usuarios  on usuarios_id = participantes.participantes_usuarios_id GROUP BY participantes.participantes_usuarios_id ORDER BY total_experimentos DESC LIMIT 1");
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_OBJ);
    }




    

}
