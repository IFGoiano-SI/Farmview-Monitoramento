<?php

namespace Controllers;

use Services\FlaskService;

/**
 * Entrega a câmera ao navegador sem expor o Raspberry na rede do usuário.
 *
 * Com o Nginx do docker-compose, /camera/stream é servido diretamente pelo
 * Nginx (proxy com auth_request em /camera/autorizar). Sem Nginx (ex.: XAMPP),
 * este controller faz o proxy do MJPEG.
 */
class CameraController extends BaseController
{
    /** Usado pelo auth_request do Nginx: 204 se logado, 401 caso contrário. */
    public function autorizar(): void
    {
        $logado = !empty($_SESSION['usuarios_logado']);
        session_write_close();
        http_response_code($logado ? 204 : 401);
    }

    public function stream(): void
    {
        session_write_close();
        set_time_limit(0);
        ignore_user_abort(false);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $flask = new FlaskService();
        $cabecalhos = config('rasp.token') ? ['X-Api-Key: ' . config('rasp.token')] : [];
        $ch = curl_init($flask->urlBase() . '/video_feed');

        curl_setopt_array($ch, [
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_HTTPHEADER => $cabecalhos,
            CURLOPT_HEADERFUNCTION => function ($ch, $linha) {
                if (stripos($linha, 'Content-Type:') === 0) {
                    header(trim($linha));
                }

                return strlen($linha);
            },
            CURLOPT_WRITEFUNCTION => function ($ch, $dados) {
                if (connection_aborted()) {
                    return -1;
                }

                if (curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200) {
                    return -1;
                }

                echo $dados;
                flush();

                return strlen($dados);
            },
        ]);

        header('Cache-Control: no-store');
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200 && !headers_sent()) {
            http_response_code(503);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Câmera indisponível';
        }
    }
}
