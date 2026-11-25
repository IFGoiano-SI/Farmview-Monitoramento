<?php

use PHPUnit\Framework\TestCase;
use Services\Validacao;

class ValidacaoTest extends TestCase
{
    private DateTimeImmutable $hoje;

    protected function setUp(): void
    {
        // Dia > 12: no código antigo strtotime(date('d/m/Y')) retornava false.
        $this->hoje = new DateTimeImmutable('2026-11-25');
    }

    private function experimento(array $sobrescrever = []): array
    {
        return $sobrescrever + [
            'experimentos_titulo' => 'Alface hidropônica',
            'experimentos_sala' => 'Sala 101',
            'experimentos_inicio' => '2026-11-25',
            'experimentos_final' => '2026-12-20',
        ];
    }

    public function testExperimentoValido(): void
    {
        $this->assertNull(Validacao::experimento($this->experimento(), $this->hoje));
    }

    public function testDataFinalNoPassadoERecusada(): void
    {
        $erro = Validacao::experimento($this->experimento(['experimentos_final' => '2026-11-24']), $this->hoje);
        $this->assertStringContainsString('inferior a data atual', $erro);
    }

    public function testDataFinalHojeEAceita(): void
    {
        $this->assertNull(Validacao::experimento($this->experimento(['experimentos_final' => '2026-11-25']), $this->hoje));
    }

    public function testInicioForaDaJanelaDeDezDias(): void
    {
        $this->assertStringContainsString('anterior a 10 dias', Validacao::experimento($this->experimento(['experimentos_inicio' => '2026-11-14']), $this->hoje));
        $this->assertNull(Validacao::experimento($this->experimento(['experimentos_inicio' => '2026-11-15']), $this->hoje));
        $this->assertStringContainsString('maior que 10 dias', Validacao::experimento($this->experimento(['experimentos_inicio' => '2026-12-06', 'experimentos_final' => '2026-12-30']), $this->hoje));
    }

    public function testFinalAntesDoInicio(): void
    {
        $erro = Validacao::experimento($this->experimento(['experimentos_inicio' => '2026-12-01', 'experimentos_final' => '2026-11-28']), $this->hoje);
        $this->assertStringContainsString('menor que a inicial', $erro);
    }

    public function testDataInvalidaERecusada(): void
    {
        $this->assertSame('Informe a data de início.', Validacao::experimento($this->experimento(['experimentos_inicio' => '25/11/2026']), $this->hoje));
        $this->assertSame('Informe a data final.', Validacao::experimento($this->experimento(['experimentos_final' => '2026-02-30']), $this->hoje));
    }

    public function testTituloESala(): void
    {
        $this->assertStringContainsString('pelo menos 5', Validacao::experimento($this->experimento(['experimentos_titulo' => 'abc']), $this->hoje));
        $this->assertStringContainsString('menos de 80', Validacao::experimento($this->experimento(['experimentos_titulo' => str_repeat('a', 81)]), $this->hoje));
        $this->assertStringContainsString('5 caracteres no campo de sala', Validacao::experimento($this->experimento(['experimentos_sala' => 'S1']), $this->hoje));
        $this->assertStringContainsString('30 caracteres', Validacao::experimento($this->experimento(['experimentos_sala' => str_repeat('s', 31)]), $this->hoje));
    }

    private function bancada(array $sobrescrever = []): array
    {
        return $sobrescrever + [
            'bancadas_tipo_plantacao' => 'Hidroponia',
            'bancadas_nome_cientifico' => 'Lactuca sativa',
            'bancadas_quantidade' => '10',
            'bancadas_luzes' => ['Azul'],
        ];
    }

    public function testBancada(): void
    {
        $this->assertNull(Validacao::bancada($this->bancada()));
        $this->assertStringContainsString('ao menos 1 tipo de luz', Validacao::bancada($this->bancada(['bancadas_luzes' => []])));
        $this->assertSame('Tipo de luz inválido.', Validacao::bancada($this->bancada(['bancadas_luzes' => ['Ultravioleta']])));
        $this->assertNull(Validacao::bancada($this->bancada(['bancadas_luzes' => []]), false));
        $this->assertStringContainsString('menor que 0', Validacao::bancada($this->bancada(['bancadas_quantidade' => '0'])));
        $this->assertStringContainsString('essa quantidade', Validacao::bancada($this->bancada(['bancadas_quantidade' => '100'])));
        $this->assertStringContainsString('válida', Validacao::bancada($this->bancada(['bancadas_quantidade' => 'dez'])));
    }

    public function testUsuario(): void
    {
        $valido = [
            'usuarios_nome' => 'Maria Souza',
            'usuarios_escolaridade' => 'Graduação',
            'usuarios_email' => 'maria@exemplo.com',
            'usuarios_senha' => 'senhaSegura1',
        ];

        $this->assertNull(Validacao::usuario($valido));
        $this->assertStringContainsString('mais de 3', Validacao::usuario(['usuarios_nome' => 'Jo'] + $valido));
        $this->assertSame('Escolha o grau de escolaridade', Validacao::usuario(['usuarios_escolaridade' => 'Outro'] + $valido));
        $this->assertSame('Formato de email inválido!', Validacao::usuario(['usuarios_email' => 'maria'] + $valido));
        $this->assertStringContainsString('pelo menos 8', Validacao::usuario(['usuarios_senha' => '1234'] + $valido));
        $this->assertNull(Validacao::usuario(['usuarios_senha' => ''] + $valido, false));
        $this->assertSame('Nível de usuário inválido.', Validacao::usuario(['usuarios_nivel' => '9'] + $valido));
        $this->assertNull(Validacao::usuario(['usuarios_nivel' => '2', 'usuarios_ativo' => '0'] + $valido));
        $this->assertSame('Status de usuário inválido.', Validacao::usuario(['usuarios_ativo' => '5'] + $valido));
    }
}
