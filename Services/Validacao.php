<?php

namespace Services;

use DateTimeImmutable;

/**
 * Regras de validação únicas para experimentos e usuários.
 * Cada método devolve a mensagem de erro ou null quando os dados são válidos.
 */
class Validacao
{
    public const ESCOLARIDADES = ['Ensino Médio', 'Graduação', 'Mestrado', 'Doutorado'];
    public const LUZES = ['RGW', 'Branca', 'Vermelha', 'Azul'];
    public const NIVEIS = [1, 2];

    public const DIAS_TOLERANCIA_INICIO = 10;
    public const SENHA_MINIMA = 8;

    public static function data(?string $valor): ?DateTimeImmutable
    {
        if (!$valor) {
            return null;
        }

        $data = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);

        return ($data && $data->format('Y-m-d') === $valor) ? $data : null;
    }

    public static function experimento(array $dados, ?DateTimeImmutable $hoje = null): ?string
    {
        $hoje = ($hoje ?? new DateTimeImmutable('today'))->setTime(0, 0);

        $titulo = trim((string) ($dados['experimentos_titulo'] ?? ''));
        $sala = trim((string) ($dados['experimentos_sala'] ?? ''));
        $inicio = self::data($dados['experimentos_inicio'] ?? null);
        $final = self::data($dados['experimentos_final'] ?? null);

        if (!$inicio) {
            return 'Informe a data de início.';
        }

        if (!$final) {
            return 'Informe a data final.';
        }

        $dias = self::DIAS_TOLERANCIA_INICIO;

        if ($inicio < $hoje->modify("-{$dias} days")) {
            return "A data de início não pode ser anterior a {$dias} dias atrás.";
        }

        if ($inicio > $hoje->modify("+{$dias} days")) {
            return "A data de início não pode ser maior que {$dias} dias.";
        }

        if ($final < $hoje) {
            return 'A data final do experimento não pode ser inferior a data atual.';
        }

        if ($final < $inicio) {
            return 'A data final do experimento não pode ser menor que a inicial';
        }

        if (mb_strlen($titulo) < 5) {
            return 'O título deve possuir pelo menos 5 caracteres.';
        }

        if (mb_strlen($titulo) > 80) {
            return 'O título deve possuir menos de 80 caracteres.';
        }

        if (mb_strlen($sala) < 5) {
            return 'É necessário ter ao menos 5 caracteres no campo de sala!';
        }

        if (mb_strlen($sala) > 30) {
            return 'O campo de sala deve possuir no máximo 30 caracteres.';
        }

        return null;
    }

    /**
     * @param bool $exigirLuzes Na edição pelo administrador as luzes não são alteradas.
     */
    public static function bancada(array $dados, bool $exigirLuzes = true): ?string
    {
        $tipo = trim((string) ($dados['bancadas_tipo_plantacao'] ?? ''));
        $nomeCientifico = trim((string) ($dados['bancadas_nome_cientifico'] ?? ''));
        $quantidade = filter_var($dados['bancadas_quantidade'] ?? null, FILTER_VALIDATE_INT);

        if ($tipo === '') {
            return 'Informe o tipo de plantação';
        }

        if (mb_strlen($tipo) < 5) {
            return 'É necessário ter ao menos 5 caracteres para tipo de plantação!';
        }

        if ($nomeCientifico === '') {
            return 'Informe o nome científico.';
        }

        if (mb_strlen($nomeCientifico) < 5) {
            return 'É necessário ter ao menos 5 caracteres para informar o nome cientifico!';
        }

        if (mb_strlen($tipo) > 100 || mb_strlen($nomeCientifico) > 100) {
            return 'Tipo de plantação e nome científico devem ter no máximo 100 caracteres.';
        }

        if ($quantidade === false) {
            return 'Informe uma quantidade de plantas válida.';
        }

        if ($quantidade > 99) {
            return 'Não é permitido informar essa quantidade de plantação!';
        }

        if ($quantidade <= 0) {
            return 'A quantidade de plantas não pode ser igual ou menor que 0';
        }

        if ($exigirLuzes) {
            $luzes = $dados['bancadas_luzes'] ?? [];

            if (!is_array($luzes) || count($luzes) === 0) {
                return 'É necessário ter ao menos 1 tipo de luz cadastrado no experimento.';
            }

            if (array_diff($luzes, self::LUZES)) {
                return 'Tipo de luz inválido.';
            }
        }

        return null;
    }

    public static function usuario(array $dados, bool $exigirSenha = true): ?string
    {
        $nome = trim((string) ($dados['usuarios_nome'] ?? ''));
        $email = trim((string) ($dados['usuarios_email'] ?? ''));
        $escolaridade = $dados['usuarios_escolaridade'] ?? '';

        if ($nome === '') {
            return 'O campo nome não pode ser vazio!';
        }

        if (mb_strlen($nome) < 3) {
            return 'O campo nome precisa ter mais de 3 caracteres!';
        }

        if (mb_strlen($nome) > 40) {
            return 'O campo nome precisa ter menos de 40 caracteres!';
        }

        if (!in_array($escolaridade, self::ESCOLARIDADES, true)) {
            return 'Escolha o grau de escolaridade';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
            return 'Formato de email inválido!';
        }

        if ($exigirSenha) {
            $erro = self::senha((string) ($dados['usuarios_senha'] ?? ''));

            if ($erro) {
                return $erro;
            }
        }

        if (array_key_exists('usuarios_nivel', $dados) && !in_array((int) $dados['usuarios_nivel'], self::NIVEIS, true)) {
            return 'Nível de usuário inválido.';
        }

        if (array_key_exists('usuarios_ativo', $dados) && !in_array((string) $dados['usuarios_ativo'], ['0', '1'], true)) {
            return 'Status de usuário inválido.';
        }

        return null;
    }

    public static function senha(string $senha): ?string
    {
        if (mb_strlen($senha) < self::SENHA_MINIMA) {
            return 'A senha deve ter pelo menos ' . self::SENHA_MINIMA . ' caracteres!';
        }

        // bcrypt considera apenas os primeiros 72 bytes.
        if (strlen($senha) > 72) {
            return 'A senha deve ter no máximo 72 caracteres.';
        }

        return null;
    }
}
