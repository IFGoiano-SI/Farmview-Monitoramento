<?php

namespace Ultis;

class FlaskService
{
    private string $base;

    public function __construct()
    {
        // Flask está rodando na porta 5000
        $this->base = "http://".$_SESSION['url_rasp'];
    }

    public function getStatusDispositivos(): array
    {
        $json = @file_get_contents($this->base . '/api_sensores');

        return $json ? json_decode($json, true) : [];
    }

    public function getSensores(): array
    {
        $status = $this->getStatusDispositivos();

        $tensoes = $status['solo_v'] ?? [];
        $percentuais = [];

        foreach ($tensoes as $tensao) {
            $percentuais[] = $this->voltagemParaPorcentagem((float) $tensao);
        }

        return [
            'v'   => $tensoes,
            'pct' => $percentuais
        ];
    }

    private function voltagemParaPorcentagem(float $voltagem): float
    {
        $vMin = 0.30;
        $vMax = 2.50;

        $porcentagem = (($voltagem - $vMin) / ($vMax - $vMin)) * 100;

        // Limita entre 0% e 100%
        $porcentagem = max(0, min(100, $porcentagem));

        return round($porcentagem, 2);
    }

    public function toggleDispositivo(string $rota, string $acao): bool
    {
        $ch = curl_init($this->base . '/' . $rota);

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'acao' => $acao
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
        ]);

        $ok = curl_exec($ch) !== false;

        curl_close($ch);

        return $ok;
    }

    public function getVideoUrl(): string
    {
        return $this->base . '/video_feed';
    }
}
