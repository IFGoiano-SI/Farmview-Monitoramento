<?php

use PHPUnit\Framework\TestCase;

/**
 * Garante que nenhuma rota que altera dados fique pública ou aceite GET.
 */
class RoteadorTest extends TestCase
{
    private array $rotas;

    protected function setUp(): void
    {
        require __DIR__ . '/../../router/routes.php';
        $this->rotas = $routes;
    }

    public function testTodaRotaApontaParaMetodoExistente(): void
    {
        foreach ($this->rotas as $caminho => [$controller, $metodo]) {
            $this->assertTrue(method_exists('Controllers\\' . $controller, $metodo), "{$caminho} -> {$controller}::{$metodo}");
        }
    }

    public function testRotasAdministrativasExigemPerfilAdmin(): void
    {
        foreach ($this->rotas as $caminho => $rota) {
            if (str_starts_with($caminho, '/adm/')) {
                $this->assertSame('admin', $rota[2], $caminho);
            }
        }
    }

    public function testRotasPublicasSaoApenasAsDeAutenticacao(): void
    {
        $publicas = array_keys(array_filter($this->rotas, fn($r) => ($r[2] ?? null) === 'publico'));

        foreach ($publicas as $caminho) {
            $this->assertMatchesRegularExpression('#^/(login|registrar|usuario/cadastrar|camera/autorizar)#', $caminho);
        }
    }

    public function testAcoesQueAlteramDadosSoAceitamPost(): void
    {
        $padrao = '/(cadastrar|criar|salvar|editar[A-Z]|editarUsuario|deletar|excluir|finalizar|ativar|toggle|ligar|gravacao$|logout|autenticar|enviarEmail|createUser|exportar)/i';

        foreach ($this->rotas as $caminho => $rota) {
            if (preg_match($padrao, $rota[1])) {
                $this->assertSame('POST', $rota[3], "{$caminho} deveria aceitar apenas POST");
            }
        }
    }
}
