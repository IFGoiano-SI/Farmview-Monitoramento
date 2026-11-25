<?php

use PHPUnit\Framework\TestCase;
use Services\Upload;

class UploadTest extends TestCase
{
    public function testSemArquivo(): void
    {
        $this->expectExceptionMessage('Informe uma imagem');
        Upload::imagem(null, 'Public/uploadImages');
    }

    public function testArquivoGrandeDemais(): void
    {
        $this->expectExceptionMessage('até 2MB');
        Upload::imagem(['name' => 'a.png', 'error' => UPLOAD_ERR_INI_SIZE, 'tmp_name' => '', 'size' => 0], 'Public/uploadImages');
    }

    public function testArquivoQueNaoVeioDeUploadERecusado(): void
    {
        // is_uploaded_file() impede usar um caminho arbitrário do servidor.
        $this->expectExceptionMessage('Erro ao fazer o upload');
        Upload::imagem(['name' => 'a.png', 'error' => UPLOAD_ERR_OK, 'tmp_name' => __FILE__, 'size' => 10], 'Public/uploadImages');
    }

    public function testRemoverIgnoraCaminhosForaDePublic(): void
    {
        Upload::remover('../index.php');
        Upload::remover('Public/../index.php');
        $this->assertFileExists(dirname(__DIR__, 2) . '/index.php');
    }
}
