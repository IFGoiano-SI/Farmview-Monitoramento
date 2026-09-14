<?php
namespace Ultis;
class FlaskService
{
    private string $base;

    public function __construct()
    {
        $this->base = 'http://localhost:5000'; // IP do Raspberry Pi
    }


    public function getStatusDispositivos(): array
    {
        // Lê a rota unificada /api/status que criamos no Python
        $json = @file_get_contents($this->base . '/api/status');
        return $json ? json_decode($json, true) : [];
    }

    public function getSensores(): array
    {
        // Puxa do status unificado para evitar travar o barramento I2C do Pi
        $status = $this->getStatusDispositivos();
        return $status['sensores'] ?? ['v' => [], 'pct' => []];
    }
    public function toggleDispositivo(string $rota, string $acao): bool
    {
        $ch = curl_init($this->base . '/' . $rota);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query(['acao' => $acao]),
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
