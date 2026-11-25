<?php

namespace Services;

use RuntimeException;

/**
 * Upload seguro de imagens:
 * - tipo verificado pelo conteúdo (finfo), não pelo que o navegador informa;
 * - nome aleatório com extensão derivada do tipo real;
 * - tamanho máximo de 2 MB.
 */
class Upload
{
    public const TAMANHO_MAXIMO = 2 * 1024 * 1024;

    private const TIPOS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    /**
     * @param string $pastaPublica Pasta relativa à raiz do projeto (ex.: Public/uploadImages)
     * @return string Caminho relativo salvo no banco (ex.: Public/uploadImages/ab12.png)
     * @throws RuntimeException com mensagem amigável
     */
    public static function imagem(?array $arquivo, string $pastaPublica): string
    {
        $erro = $arquivo['error'] ?? UPLOAD_ERR_NO_FILE;

        if ($erro === UPLOAD_ERR_NO_FILE || empty($arquivo['name'])) {
            throw new RuntimeException('Atenção! Informe uma imagem.');
        }

        if (in_array($erro, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            throw new RuntimeException('Erro! Selecione outra imagem até 2MB');
        }

        if ($erro !== UPLOAD_ERR_OK || !is_uploaded_file($arquivo['tmp_name'])) {
            throw new RuntimeException('Erro ao fazer o upload da imagem');
        }

        if ($arquivo['size'] > self::TAMANHO_MAXIMO) {
            throw new RuntimeException('Erro! Selecione outra imagem até 2MB');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);

        if (!isset(self::TIPOS[$mime]) || @getimagesize($arquivo['tmp_name']) === false) {
            throw new RuntimeException('Formato da imagem não permitido - Selecione apenas JPEG, JPG ou PNG');
        }

        $pastaPublica = trim($pastaPublica, '/');
        $destino = dirname(__DIR__) . '/' . $pastaPublica;

        if (!is_dir($destino) && !mkdir($destino, 0775, true)) {
            throw new RuntimeException('Erro ao fazer o upload da imagem');
        }

        $nome = bin2hex(random_bytes(16)) . '.' . self::TIPOS[$mime];

        if (!move_uploaded_file($arquivo['tmp_name'], $destino . '/' . $nome)) {
            throw new RuntimeException('Erro ao fazer o upload da imagem');
        }

        return $pastaPublica . '/' . $nome;
    }

    /** Remove um arquivo enviado (usado quando a operação seguinte falha). */
    public static function remover(?string $caminhoRelativo): void
    {
        if (!$caminhoRelativo || !str_starts_with($caminhoRelativo, 'Public/')) {
            return;
        }

        $arquivo = realpath(dirname(__DIR__) . '/' . $caminhoRelativo);
        $raizPublica = realpath(dirname(__DIR__) . '/Public');

        if ($arquivo && $raizPublica && str_starts_with($arquivo, $raizPublica . DIRECTORY_SEPARATOR) && is_file($arquivo)) {
            @unlink($arquivo);
        }
    }
}
