<?php

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function testEscEMsgEscapamHtml(): void
    {
        $this->assertSame('&lt;script&gt;', esc('<script>'));
        $this->assertStringNotContainsString('<script>', msg('<script>alert(1)</script>', 'danger'));
        $this->assertStringContainsString('alert-info', msg('x', 'tipo-invalido'));
    }

    public function testCsrf(): void
    {
        $_SESSION = [];
        $token = csrf_token();

        $this->assertSame(64, strlen($token));
        $this->assertTrue(csrf_valido($token));
        $this->assertFalse(csrf_valido('outro'));
        $this->assertFalse(csrf_valido(null));
        $this->assertStringContainsString($token, csrf_field());
    }

    public function testIdsNaUrl(): void
    {
        $this->assertSame(42, id_da_url(id_url(42)));
        $this->assertNull(id_da_url('abc$'));
        $this->assertNull(id_da_url(base64_encode('1 OR 1=1')));
    }

    public function testDatasELuzes(): void
    {
        $this->assertSame('-', data_br(null));
        $this->assertSame('-', data_br('0000-00-00 00:00:00'));
        $this->assertSame('25/11/2026', data_br('2026-11-25'));
        $this->assertSame('Azul, Branca', luzes_texto('["Azul","Branca"]', 'nenhuma'));
        $this->assertSame('nenhuma', luzes_texto('"[]"', 'nenhuma'));
    }
}
