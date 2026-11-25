<?php

use Models\Database;
use Models\ExperimentoModel;
use Models\UsuarioModel;
use PHPUnit\Framework\TestCase;

/**
 * Testes com banco real. Rodam quando DB_TEST=1 (ex.: no CI, com MySQL
 * carregado a partir de db/schema.sql). Usam transação e desfazem tudo.
 */
class ModelosTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        if (getenv('DB_TEST') !== '1') {
            $this->markTestSkipped('Defina DB_TEST=1 e as variáveis DB_* para rodar os testes com banco.');
        }

        Database::definirConexao(null);
        $this->pdo = Database::conexao();
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        if (isset($this->pdo) && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    private function criarUsuario(string $email): int
    {
        return (new UsuarioModel())->criar([
            'usuarios_nome' => 'Teste',
            'usuarios_escolaridade' => 'Graduação',
            'usuarios_email' => $email,
            'usuarios_senha' => password_hash('senhaSegura1', PASSWORD_DEFAULT),
            'usuarios_ativo' => 1,
            'usuarios_nivel' => 1,
        ]);
    }

    private function criarExperimento(int $usuarioId): int
    {
        // Sem transação própria: o teste já está dentro de uma.
        $exp = new Database('experimentos');
        $id = $exp->insert([
            'experimentos_titulo' => 'Experimento teste',
            'experimentos_inicio' => date('Y-m-d'),
            'experimentos_final' => date('Y-m-d', strtotime('+10 days')),
            'experimentos_sala' => 'Sala 1',
            'experimentos_status' => 'Ativo',
        ]);
        $bancada = (new Database('bancadas'))->insert([
            'bancadas_tipo_plantacao' => 'Hidroponia', 'bancadas_nome_cientifico' => 'Lactuca sativa',
            'bancadas_quantidade' => 5, 'bancadas_luzes' => '["Azul"]', 'bancadas_dispositivos' => '[]',
        ]);
        (new Database('bancadas_experimentos'))->insert(['be_bancadas_id' => $bancada, 'be_experimentos_id' => $id]);
        (new Database('participantes'))->insert([
            'participantes_nome' => '', 'participantes_orientador' => 'Henrique',
            'participantes_experimentos_id' => $id, 'participantes_usuarios_id' => $usuarioId,
        ]);

        return $id;
    }

    public function testEmailUnicoNoBanco(): void
    {
        $this->criarUsuario('unico@teste.com');
        $this->expectException(PDOException::class);
        $this->criarUsuario('unico@teste.com');
    }

    public function testExperimentoSoEAcessivelPeloDono(): void
    {
        $dono = $this->criarUsuario('dono@teste.com');
        $outro = $this->criarUsuario('outro@teste.com');
        $exp = $this->criarExperimento($dono);
        $model = new ExperimentoModel();

        $this->assertNotNull($model->doUsuario($exp, $dono, ExperimentoModel::STATUS_ATIVO));
        $this->assertNull($model->doUsuario($exp, $outro));
        $this->assertSame(1, $model->contarAtivosDoUsuario($dono));
    }

    public function testStatusFinalizadoEReativado(): void
    {
        $exp = $this->criarExperimento($this->criarUsuario('status@teste.com'));
        $model = new ExperimentoModel();

        $model->alterarStatus($exp, ExperimentoModel::STATUS_FINALIZADO);
        $this->assertNotNull($model->buscarPorId($exp)->experimentos_encerrado);

        $model->alterarStatus($exp, ExperimentoModel::STATUS_ATIVO);
        $this->assertNull($model->buscarPorId($exp)->experimentos_encerrado);
    }

    public function testBancoRecusaStatusInvalido(): void
    {
        $exp = $this->criarExperimento($this->criarUsuario('enum@teste.com'));
        $this->expectException(PDOException::class);
        (new ExperimentoModel())->atualizarDados($exp, ['experimentos_status' => 'Qualquer']);
    }

    public function testTokenDeSenhaExpira(): void
    {
        $id = $this->criarUsuario('token@teste.com');
        $model = new UsuarioModel();
        $hash = hash('sha256', 'abc');

        $model->definirTokenReset($id, $hash, date('Y-m-d H:i:s', strtotime('+1 hour')));
        $this->assertNotNull($model->buscarPorTokenReset($hash), 'token válido (fuso PHP e MySQL alinhados)');

        $model->definirTokenReset($id, $hash, date('Y-m-d H:i:s', strtotime('-1 minute')));
        $this->assertNull($model->buscarPorTokenReset($hash));
    }
}
