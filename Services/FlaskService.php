<?php

namespace Services;

/**
 * Único ponto de comunicação com o serviço Flask do Raspberry Pi.
 *
 * - Todas as chamadas têm timeout curto (o PHP não fica preso se a placa cair).
 * - Leituras de status/sensores ficam em cache por alguns segundos, evitando que
 *   cada aba aberta gere uma requisição ao Raspberry a cada 3 s.
 * - O token compartilhado (RASPBERRY_TOKEN) é enviado no cabeçalho X-Api-Key.
 */
class FlaskService
{
    public const TOMADAS = [1, 2, 3, 4];

    private const CACHE_OK_SEGUNDOS = 2;
    private const CACHE_FALHA_SEGUNDOS = 5;

    private string $base;

    public function __construct(?string $host = null, ?int $porta = null)
    {
        $host = $host ?? config('rasp.host');
        $porta = $porta ?? config('rasp.port');
        $this->base = "http://{$host}:{$porta}";
    }

    public function urlBase(): string
    {
        return $this->base;
    }

    /**
     * @return array{ok: bool, code: int, data: array, erro: ?string}
     */
    public function requisicao(string $metodo, string $rota, array $dados = [], ?int $timeout = null): array
    {
        $ch = curl_init($this->base . '/' . ltrim($rota, '/'));
        $cabecalhos = ['Accept: application/json'];

        if (config('rasp.token')) {
            $cabecalhos[] = 'X-Api-Key: ' . config('rasp.token');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => $timeout ?? config('rasp.timeout', 3),
            CURLOPT_HTTPHEADER => $cabecalhos,
        ]);

        if ($metodo === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($dados));
        }

        $corpo = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $erroCurl = curl_errno($ch) ? curl_error($ch) : null;
        curl_close($ch);

        $data = is_string($corpo) ? (json_decode($corpo, true) ?? []) : [];
        $ok = $erroCurl === null && $code >= 200 && $code < 300;

        if (!$ok) {
            Logger::aviso('Falha na comunicação com o Raspberry', [
                'rota' => $rota,
                'http' => $code,
                'erro' => $erroCurl ?? ($data['erro'] ?? null),
            ]);
        }

        return [
            'ok' => $ok,
            'code' => $code,
            'data' => is_array($data) ? $data : [],
            'erro' => $erroCurl ?? ($data['erro'] ?? ($ok ? null : "HTTP {$code}")),
        ];
    }

    /**
     * Estado real da bancada: câmera, gravação, tomadas, sensores e limites.
     * Retorna null se o Raspberry não respondeu.
     */
    public function status(): ?array
    {
        $resposta = $this->comCache('status', fn() => $this->requisicao('GET', '/status'));

        return $resposta['ok'] ? $resposta['data'] : null;
    }

    /**
     * @return array{v: array, pct: array, online: bool, atualizado_em: ?string}
     */
    public function getSensores(): array
    {
        $resposta = $this->comCache('sensores', fn() => $this->requisicao('GET', '/api_sensores'));
        $dados = $resposta['data'];

        return [
            'v' => $dados['solo_v'] ?? [],
            // Percentual calculado no Raspberry com a calibração de cada sensor.
            'pct' => $dados['solo_pct'] ?? [],
            'online' => $resposta['ok'],
            'atualizado_em' => $dados['atualizado_em'] ?? null,
        ];
    }

    public function definirTomada(int $tomada, string $acao): bool
    {
        if (!in_array($tomada, self::TOMADAS, true) || !in_array($acao, ['on', 'off'], true)) {
            return false;
        }

        $ok = $this->requisicao('POST', "/toggle_pin_{$tomada}", ['acao' => $acao])['ok'];
        $this->limparCache();

        return $ok;
    }

    /**
     * Liga/desliga a câmera de forma idempotente (funciona também com versões
     * antigas do Flask, em que /toggle_camera apenas alterna o estado).
     */
    public function definirCamera(string $acao): bool
    {
        if (!in_array($acao, ['on', 'off'], true)) {
            return false;
        }

        $status = $this->requisicao('GET', '/status');
        $ligada = (bool) ($status['data']['camera_on'] ?? false);

        if ($status['ok'] && $ligada === ($acao === 'on')) {
            return true;
        }

        $ok = $this->requisicao('POST', '/toggle_camera', ['acao' => $acao, 'api' => 1], 10)['ok'];
        $this->limparCache();

        return $ok;
    }

    public function iniciarGravacao(): array
    {
        $resposta = $this->requisicao('POST', '/start_recording', [], 10);
        $this->limparCache();

        return $resposta;
    }

    /** A conversão para mp4 acontece no Raspberry e pode levar alguns minutos. */
    public function pararGravacao(): array
    {
        $resposta = $this->requisicao('POST', '/stop_recording', [], 300);
        $this->limparCache();

        return $resposta;
    }

    public function baixarGravacao(string $arquivo, string $destino): bool
    {
        $fp = fopen($destino, 'wb');

        if (!$fp) {
            return false;
        }

        $ch = curl_init($this->base . '/gravacoes/' . rawurlencode(basename($arquivo)));
        $cabecalhos = config('rasp.token') ? ['X-Api-Key: ' . config('rasp.token')] : [];

        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_HTTPHEADER => $cabecalhos,
        ]);

        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        if ($code !== 200) {
            @unlink($destino);
            Logger::aviso('Falha ao baixar gravação do Raspberry', ['arquivo' => $arquivo, 'http' => $code]);
            return false;
        }

        return true;
    }

    /**
     * Desliga tomadas, gravação e câmera da bancada.
     * @return bool true se o Raspberry confirmou o desligamento
     */
    public function desligarTudo(): bool
    {
        $resposta = $this->requisicao('POST', '/desligar_tudo', [], 15);

        if ($resposta['code'] !== 404) {
            $this->limparCache();
            return $resposta['ok'];
        }

        // Compatibilidade com versões do Flask sem /desligar_tudo.
        $ok = true;

        foreach (self::TOMADAS as $tomada) {
            $ok = $this->requisicao('POST', "/toggle_pin_{$tomada}", ['acao' => 'off'])['ok'] && $ok;
        }

        return $this->definirCamera('off') && $ok;
    }

    private function comCache(string $chave, callable $buscar): array
    {
        $arquivo = $this->arquivoCache($chave);

        if ($arquivo && is_file($arquivo)) {
            $cache = json_decode((string) @file_get_contents($arquivo), true);
            $validade = ($cache['ok'] ?? false) ? self::CACHE_OK_SEGUNDOS : self::CACHE_FALHA_SEGUNDOS;

            if (is_array($cache) && (time() - ($cache['_em'] ?? 0)) < $validade) {
                return $cache;
            }
        }

        $resposta = $buscar();

        if ($arquivo) {
            @file_put_contents($arquivo, json_encode($resposta + ['_em' => time()]), LOCK_EX);
        }

        return $resposta;
    }

    private function limparCache(): void
    {
        foreach (['status', 'sensores'] as $chave) {
            $arquivo = $this->arquivoCache($chave);

            if ($arquivo && is_file($arquivo)) {
                @unlink($arquivo);
            }
        }
    }

    private function arquivoCache(string $chave): ?string
    {
        $pasta = dirname(__DIR__) . '/storage/cache';

        if (!is_dir($pasta) || !is_writable($pasta)) {
            return null;
        }

        return $pasta . '/rasp_' . $chave . '_' . md5($this->base) . '.json';
    }
}
