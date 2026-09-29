from flask import Flask, Response, render_template_string, request, redirect, url_for, jsonify, send_from_directory
import threading
import atexit
import time
import os
import glob
import subprocess
import sqlite3
from datetime import datetime

import cv2

import json
import socket

# ==========================================================
# FarmView - Raspberry Pi / hardware real
# GPIO simulado removido para evitar conflitos e travamentos.
# ==========================================================

# ---------------- CONFIGURAÇÕES ----------------
PIN_1 = 22
PIN_2 = 17
PIN_3 = 10
PIN_4 = 27
ACTIVE_LOW = True
DB_NAME = "farmview.db"
CONFIG_FILE = "config_farmview.json"

PINOS = {
    1: PIN_1,
    2: PIN_2,
    3: PIN_3,
    4: PIN_4,
}

V_MIN, V_MAX = 0.30, 2.50

lock = threading.Lock()
gpio_lock = threading.Lock()
db_lock = threading.Lock()

# Sensores: uma thread faz a leitura física e as rotas apenas consultam o cache.
sensor_lock = threading.Lock()
dados_sensores = {
    "solo_v": [0.0, 0.0, 0.0, 0.0],
    "solo_pct": [0.0, 0.0, 0.0, 0.0],
}
SENSOR_SAVE_INTERVAL = 60
ultimo_registro_sensor = 0



# ---------------- GPIO / RELÉS REAIS ----------------
try:
    import RPi.GPIO as GPIO
except Exception as exc:
    raise RuntimeError(
        "RPi.GPIO não está disponível. Execute este sistema em um Raspberry Pi "
        "com a biblioteca RPi.GPIO instalada. O modo GPIO simulado foi removido."
    ) from exc

GPIO_REAL = True
MODO_EXECUCAO = "Raspberry Pi / hardware real"

GPIO.setwarnings(False)
GPIO.setmode(GPIO.BCM)
for pino in (PIN_1, PIN_2, PIN_3, PIN_4):
    GPIO.setup(pino, GPIO.OUT, initial=GPIO.HIGH if ACTIVE_LOW else GPIO.LOW)

def carregar_config():
    with open(CONFIG_FILE, "r", encoding="utf-8") as f:
        return json.load(f)

def ip_local():
    """
    Retorna o IP IPv4 da máquina.
    Funciona em Windows, Linux, Raspberry Pi e Ubuntu.
    """
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)

        # Não estabelece conexão de fato.
        s.connect(("8.8.8.8", 80))

        ip = s.getsockname()[0]

        s.close()

        return ip

    except Exception:
        return "127.0.0.1"


def config_padrao():
    return {
        "irrigacao_automatica": True,
        "limite_umidade_baixa": 35,
        "limite_umidade_alta": 60,
        "sensor_referencia": 1,
        "tomada_bomba": 1,
        "intervalo_verificacao_segundos": 10,
        "ip_aplicacao": ip_local()
    }


def carregar_config():
    if not os.path.exists(CONFIG_FILE) or os.path.getsize(CONFIG_FILE) == 0:
        config = config_padrao()
        salvar_config(config)
        return config

    try:
        with open(CONFIG_FILE, "r", encoding="utf-8") as f:
            return json.load(f)

    except json.JSONDecodeError:
        print("[AVISO] config_farmview.json inválido. Recriando configuração padrão.")
        config = config_padrao()
        salvar_config(config)
        return config


def salvar_config(config):
    with open(CONFIG_FILE, "w", encoding="utf-8") as f:
        json.dump(config, f, indent=4, ensure_ascii=False)

def ligar_pin(pin):
    # Protege o acesso ao GPIO contra chamadas simultâneas via navegador/curl/PHP.
    with gpio_lock:
        GPIO.output(pin, GPIO.LOW if ACTIVE_LOW else GPIO.HIGH)


def desligar_pin(pin):
    # Protege o acesso ao GPIO contra chamadas simultâneas via navegador/curl/PHP.
    with gpio_lock:
        GPIO.output(pin, GPIO.HIGH if ACTIVE_LOW else GPIO.LOW)


def pin_ligado(pin):
    # Lê o estado do GPIO de forma protegida.
    with gpio_lock:
        valor = GPIO.input(pin)
        return valor == GPIO.LOW if ACTIVE_LOW else valor == GPIO.HIGH


def gpio_cleanup():
    try:
        GPIO.cleanup()
    except Exception as exc:
        print(f"[AVISO] Falha ao executar GPIO.cleanup(): {exc}")


# Inicializa todos os relés desligados
for pino in (PIN_1, PIN_2, PIN_3, PIN_4):
    desligar_pin(pino)

# ---------------- ADS1115 / SENSORES 10HS REAIS ----------------
try:
    import board
    import busio
    import adafruit_ads1x15.ads1115 as ADS
    from adafruit_ads1x15.analog_in import AnalogIn

    i2c = busio.I2C(board.SCL, board.SDA)
    ads = ADS.ADS1115(i2c, address=0x48)
    ads.gain = 1
    channels = [
        AnalogIn(ads, ADS.P0),
        AnalogIn(ads, ADS.P1),
        AnalogIn(ads, ADS.P2),
        AnalogIn(ads, ADS.P3),
    ]
    ADS_REAL = True
except Exception as exc:
    raise RuntimeError(
        "ADS1115 real indisponível. Verifique conexões I2C e instale as bibliotecas "
        "adafruit-circuitpython-ads1x15, board e busio. Sensores simulados foram removidos."
    ) from exc


def v_to_pct(v):
    return round(max(0, min(100, (v - V_MIN) / (V_MAX - V_MIN) * 100)), 2)


def ler_sensor_medio(ch, n=5, delay=0.06):
    vals = []
    for _ in range(n):
        vals.append(ch.voltage)
        time.sleep(delay)
    v = sum(vals) / len(vals)
    return v, v_to_pct(v)


def atualizar_sensores():
    """Lê o ADS1115 em background e mantém a última leitura em memória."""
    global ultimo_registro_sensor

    while True:
        try:
            vs, ps = [], []
            agora = time.time()
            gravar_leitura = (agora - ultimo_registro_sensor) >= SENSOR_SAVE_INTERVAL

            for i, ch in enumerate(channels):
                v, p = ler_sensor_medio(ch)
                v = round(v, 4)
                p = round(p, 2)
                vs.append(v)
                ps.append(p)

                if gravar_leitura:
                    salvar_sensor(i + 1, v, p)

            with sensor_lock:
                dados_sensores["solo_v"] = vs
                dados_sensores["solo_pct"] = ps

            if gravar_leitura:
                ultimo_registro_sensor = agora

            time.sleep(1)

        except Exception as exc:
            print(f"[ERRO SENSOR] {exc}")
            time.sleep(1)


# ---------------- CÂMERA ----------------
camera = None
camera_on = False

# ---- Estado da gravação ----
# Tudo isto é protegido por `lock`. A escrita dos frames acontece na thread de
# captura (capturar_camera), então a gravação independe de haver alguém com o
# dashboard aberto.
gravando = False
video_writer = None
gravacao_atual = None      # nome do arquivo .avi em andamento
gravacao_inicio = None     # time.time() do início
frames_gravados = 0        # usado para calcular o FPS real na conversão

PASTA_GRAVACOES = os.path.join(os.getcwd(), "gravacoes")
os.makedirs(PASTA_GRAVACOES, exist_ok=True)

# A captura/compressão da câmera acontece fora da requisição HTTP.
camera_lock = threading.Lock()
ultimo_frame_jpeg = None


def abrir_camera():
    # Em PC/Ubuntu normalmente a webcam está em /dev/video0.
    # No Raspberry pode ser /dev/video0 ou /dev/video1.
    for idx in (0, 1, 2):
        cap = cv2.VideoCapture(idx)
        if cap.isOpened():
            for _ in range(3):
                cap.read()
                time.sleep(0.05)
            print(f"[INFO] Câmera aberta no índice {idx}")
            return cap
        try:
            cap.release()
        except Exception:
            pass
    print("[AVISO] Nenhuma câmera encontrada.")
    return None


def capturar_camera():
    """Captura um único fluxo da câmera e deixa o último JPEG pronto para /video_feed."""
    global camera, camera_on, ultimo_frame_jpeg, video_writer, frames_gravados

    fps = 15.0
    fourcc = cv2.VideoWriter_fourcc(*"XVID")

    while True:
        if not camera_on or camera is None:
            time.sleep(0.1)
            continue

        try:
            ok, frame = camera.read()
            if not ok:
                print("[AVISO] Falha ao capturar frame da câmera.")
                time.sleep(0.1)
                continue

            with lock:
                if gravando and gravacao_atual:
                    if video_writer is None:
                        h, w = frame.shape[:2]
                        caminho = os.path.join(PASTA_GRAVACOES, gravacao_atual)
                        video_writer = cv2.VideoWriter(caminho, fourcc, fps, (w, h))
                    video_writer.write(frame)
                    frames_gravados += 1
                elif video_writer is not None:
                    video_writer.release()
                    video_writer = None

            ret, buf = cv2.imencode(
                ".jpg",
                frame,
                [cv2.IMWRITE_JPEG_QUALITY, 75],
            )
            if not ret:
                continue

            with camera_lock:
                ultimo_frame_jpeg = buf.tobytes()

            time.sleep(1 / fps)

        except Exception as exc:
            print(f"[ERRO CÂMERA] {exc}")
            time.sleep(0.5)


# ---------------- GRAVAÇÃO ----------------
def _fechar_writer():
    """Fecha o arquivo de vídeo. Chamar SEMPRE com `lock` já adquirido."""
    global video_writer
    if video_writer is not None:
        video_writer.release()
        video_writer = None


def converter_para_mp4(avi_path, fps_real):
    """
    Converte o .avi (XVID) para .mp4 (H.264), que o navegador consegue tocar.
    Usa o FPS real medido na gravação para o vídeo não sair acelerado/lento.
    Se o ffmpeg não estiver instalado, devolve o próprio .avi.
    """
    mp4_path = avi_path.rsplit(".", 1)[0] + ".mp4"
    try:
        cmd = ["ffmpeg", "-y"]
        if fps_real:
            cmd += ["-r", f"{fps_real:.2f}"]   # antes do -i: assume esse FPS na entrada
        cmd += [
            "-i", avi_path,
            "-c:v", "libx264",
            "-preset", "ultrafast",
            "-pix_fmt", "yuv420p",
            "-movflags", "+faststart",
            mp4_path,
        ]
        subprocess.run(cmd, check=True, capture_output=True, timeout=300)
        os.remove(avi_path)
        return os.path.basename(mp4_path)
    except Exception as exc:
        print(f"[AVISO] Conversão para mp4 falhou ({exc}). Mantendo o .avi.")
        return os.path.basename(avi_path)


def iniciar_gravacao():
    """Retorna (True, nome_do_arquivo) ou (False, mensagem_de_erro)."""
    global gravando, gravacao_atual, gravacao_inicio, frames_gravados

    if not camera_on or camera is None:
        return False, "Câmera desligada"

    with lock:
        if gravando:
            return False, "Já está gravando"

        gravacao_atual = datetime.now().strftime("rec_%Y%m%d_%H%M%S.avi")
        gravacao_inicio = time.time()
        frames_gravados = 0
        gravando = True

    print(f"[INFO] Gravação iniciada: {gravacao_atual}")
    return True, gravacao_atual


def finalizar_gravacao():
    """Retorna (dados, None) em caso de sucesso ou (None, mensagem_de_erro)."""
    global gravando, gravacao_atual

    with lock:
        gravando = False
        _fechar_writer()
        nome = gravacao_atual
        inicio = gravacao_inicio
        frames = frames_gravados
        gravacao_atual = None

    if not nome:
        return None, "Nenhuma gravação em andamento"

    avi_path = os.path.join(PASTA_GRAVACOES, nome)
    if frames == 0 or not os.path.exists(avi_path):
        return None, "Nenhum frame foi gravado"

    duracao = time.time() - inicio
    fps_real = frames / duracao if duracao > 0 else None
    arquivo_final = converter_para_mp4(avi_path, fps_real)

    print(f"[INFO] Gravação finalizada: {arquivo_final} ({int(duracao)}s)")
    return {"arquivo": arquivo_final, "duracao": int(duracao)}, None


# ---------------- FLASK ----------------

app = Flask(__name__)


HTML_DADOS = """
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>FarmView — Dados Registrados</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="{{ url_for('static', filename='bootstrap-5.3.8/css/bootstrap.min.css') }}" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

<style>
body{
    background:#f6f8fb;
    font-family:system-ui;
}

.page-header{
    background:#1D7189;
    color:white;
    padding:18px 28px;
}

.card{
    border:0;
    border-radius:14px;
    box-shadow:0 .6rem 1.2rem rgba(0,0,0,.08);
}

.table-responsive{
    max-height:420px;
    overflow:auto;
}

.badge{
    font-size:.8rem;
}
</style>
</head>

<body>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1 class="h5 m-0">
            <i class="bi bi-database-fill me-2"></i>
            Dados Registrados — FarmView
        </h1>
        <small>Histórico armazenado no banco SQLite</small>
    </div>

    <a href="{{ url_for('index') }}" class="btn btn-light btn-sm">
        <i class="bi bi-arrow-left-circle me-1"></i>
        Voltar ao painel
    </a>
</div>

<div class="container-fluid p-4">

    <div class="row g-4">

        <div class="col-12 col-xl-7">
            <div class="card">
                <div class="card-body">

                    <h2 class="h6 fw-bold mb-3">
                        <i class="bi bi-droplet-half me-1 text-primary"></i>
                        Leituras dos Sensores 10HS
                    </h2>

                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>ID</th>
                                    <th>Sensor</th>
                                    <th>Tensão</th>
                                    <th>Umidade</th>
                                    <th>Data/Hora</th>
                                </tr>
                            </thead>
                            <tbody>
                                {% for s in sensores %}
                                <tr>
                                    <td>{{ s["id"] }}</td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary">
                                            10HS {{ s["sensor"] }}
                                        </span>
                                    </td>
                                    <td>{{ "%.3f"|format(s["tensao"]) }} V</td>
                                    <td>
                                        <strong>{{ "%.1f"|format(s["umidade"]) }}%</strong>
                                    </td>
                                    <td>{{ s["data_hora"] }}</td>
                                </tr>
                                {% else %}
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Nenhum dado de sensor registrado.
                                    </td>
                                </tr>
                                {% endfor %}
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card">
                <div class="card-body">

                    <h2 class="h6 fw-bold mb-3">
                        <i class="bi bi-plug-fill me-1 text-warning"></i>
                        Histórico das Tomadas
                    </h2>

                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>ID</th>
                                    <th>Tomada</th>
                                    <th>GPIO</th>
                                    <th>Status</th>
                                    <th>Data/Hora</th>
                                </tr>
                            </thead>
                            <tbody>
                                {% for t in tomadas %}
                                <tr>
                                    <td>{{ t["id"] }}</td>
                                    <td>Tomada {{ "%02d"|format(t["tomada"]) }}</td>
                                    <td>{{ t["gpio"] }}</td>
                                    <td>
                                        {% if t["status"] == "ligado" %}
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle-fill me-1"></i>
                                                Ligado
                                            </span>
                                        {% else %}
                                            <span class="badge bg-danger">
                                                <i class="bi bi-x-circle-fill me-1"></i>
                                                Desligado
                                            </span>
                                        {% endif %}
                                    </td>
                                    <td>{{ t["data_hora"] }}</td>
                                </tr>
                                {% else %}
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Nenhum acionamento registrado.
                                    </td>
                                </tr>
                                {% endfor %}
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

    </div>

</div>

<script src="{{ url_for('static', filename='bootstrap-5.3.8/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
"""

HTML = """<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Vertical Farming — Monitoramento</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link href="{{ url_for('static', filename='bootstrap-5.3.8/css/bootstrap.min.css') }}" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
<style>
:root{--radius:1rem;}
body{background:#f6f8fb;font-family:system-ui;}
.container-narrow{max-width:72rem;margin:0 auto;padding:0;}
.brandbar{
    width:100%;
    background:#1D7189;
    color:#fff;
    padding:12px 30px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin:0;
}
.brandbar img{
    max-height:80px;
    width:auto;
    object-fit:contain;
}
.logo-left,
.logo-center,
.logo-right{
    display:flex;
    align-items:center;
}

.logo-center{
    flex:1;
    justify-content:center;
}

.logo-right{
    justify-content:flex-end;
}

.status-bar{
    width:100%;
    background:#185c6f;
    color:#d8eef4;
    text-align:center;
    padding:6px;
    font-size:.9rem;
    letter-spacing:.5px;
}

.sensor-card{
    border-radius:14px;
}

.relay-switch{

    width:3.4rem;
    height:1.8rem;
    cursor:pointer;

}

.sensor-box{

    transition:.2s;
    border:1px solid #e5e7eb;

}

.sensor-box:hover{

    transform:translateY(-3px);
    box-shadow:0 .4rem 1rem rgba(0,0,0,.12);

}

.sensor-box{
    background:#f8fafc;
    border:1px solid #e5e7eb;
    border-radius:14px;
    padding:14px;
    height:100%;
}

.sensor-title{
    font-weight:700;
    font-size:.95rem;
    color:#111827;
}

.sensor-voltage{
    font-size:.8rem;
    color:#6b7280;
}

.sensor-value{
    font-size:1.8rem;
    font-weight:800;
    color:#111827;
    margin:6px 0 8px;
}

.sensor-progress{
    height:9px;
    border-radius:999px;
    background:#e5e7eb;
}

.sensor-progress .progress-bar{
    border-radius:999px;
    transition:width .4s ease;
}

.sensor-status{
    font-size:.72rem;
    font-weight:700;
    padding:.25rem .5rem;
    border-radius:999px;
    background:#e5e7eb;
    color:#374151;
}

.card{border:0;border-radius:var(--radius);box-shadow:0 .6rem 1.2rem rgba(0,0,0,.08);margin-top:1rem;}
.pill{padding:.35rem .7rem;border-radius:999px;font-size:.95rem;}
.muted{color:#6b7280;}
.camera-container{
    width:100%;
    aspect-ratio:16/9;          /* mesma proporção do vídeo */
    background:#000;
    border-radius:12px;
    overflow:hidden;
}

.camera-feed{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
}

.camera-off{
    width:100%;
    height:100%;
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
    background:#000;
    color:#fff;
    text-align:center;
}

.camera-off i{
    font-size:72px;
    color:#6c757d;
    margin-bottom:20px;
}

.camera-off h4{
    font-weight:600;
    margin-bottom:8px;
    letter-spacing:1px;
}

.row{
    margin-left:0;
    margin-right:0;
}


.form-switch .form-check-input{
    width:3.2em;
    height:1.7em;
    cursor:pointer;
}

.form-switch .form-check-input:checked{
    --bs-form-switch-bg: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3E%3Ccircle r='3' fill='%23ffffff'/%3E%3C/svg%3E");
    background-color:#198754;
    border-color:#198754;
}

.form-switch .form-check-input:not(:checked){
    --bs-form-switch-bg: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3E%3Ccircle r='3' fill='%23ffffff'/%3E%3C/svg%3E");
    background-color:#dc3545;
    border-color:#dc3545;
}

.card{
    border-radius:12px;
}

.badge{
    font-size:.85rem;
    padding:.45rem .7rem;
}


.camera-off p{
    color:#adb5bd;
    margin:0;
    font-size:.95rem;
}
.footer-logo{
    height:50px;
    width:auto;
    object-fit:contain;
    transition:.25s;
    opacity:.9;
}

.footer-logo:hover{
    opacity:1;
    transform:scale(1.05);
}

footer{
    background:#fff;
}
</style>
<script>
const TOTAL_SENSORES = 4;

async function refreshSensores(){
    try{
        const r = await fetch("{{ url_for('api_sensores') }}");
        const d = await r.json();

        for(let i = 0; i < TOTAL_SENSORES; i++){
            const pct = Number(d.solo_pct[i]);
            const volts = Number(d.solo_v[i]);

            document.getElementById("solo" + i).textContent = pct.toFixed(1) + " %";
            document.getElementById("soloV" + i).textContent = volts.toFixed(3) + " V";

            const bar = document.getElementById("soloBar" + i);
            const status = document.getElementById("soloStatus" + i);

            bar.style.width = pct + "%";

            bar.classList.remove("bg-danger", "bg-warning", "bg-success");
            status.className = "sensor-status";

            if(pct < 35){
                bar.classList.add("bg-danger");
                status.textContent = "Baixa";
                status.classList.add("bg-danger-subtle", "text-danger");
            }else if(pct < 70){
                bar.classList.add("bg-warning");
                status.textContent = "Média";
                status.classList.add("bg-warning-subtle", "text-warning");
            }else{
                bar.classList.add("bg-success");
                status.textContent = "Alta";
                status.classList.add("bg-success-subtle", "text-success");
            }
        }

    }catch(e){
        console.log(e);
    }
}

setInterval(refreshSensores, 2000);
window.onload = refreshSensores;
</script>
</head>
<body>

    <!-- ########## Topo do site ########## -->

    <div class="container-fluid g-0 px-0 py-0">
        <div class="brandbar">

        <div class="logo-left">
            <img src="/static/images/logo_farmview.png" alt="FarmView">
        </div>


        <div class="logo-right">
            <img src="/static/images/logo.png"
                alt="LabTi">
        </div>

    </div>

    <div class="status-bar">
        <a href="{{ url_for('dados') }}" class="btn btn-outline-light btn-sm">
            <i class="bi bi-database-fill me-1"></i>
            Ver dados registrados
        </a>

        <a href="{{ url_for('configuracoes') }}" class="btn btn-outline-light btn-sm">
            <i class="bi bi-sliders me-1"></i>
            Configurações
        </a>
        Automatização de Ambiente Controlado • {{ modo_execucao }} • IP da aplicação: <strong>{{ config["ip_aplicacao"] }}</strong>

    </div>

    
    <!-- ########## Conteúdo do site ##############-->
    <div class="row ps-2">

        <!-- ########## Camera ##############-->
        <div class="col-12 col-lg-7">
            <!-- CÂMERA -->
            <div class="mt-3 d-flex gap-2">
                <form method="post" action="{{ url_for('toggle_camera') }}">
                    
                        {% if camera_on %}
                            <button class="btn btn-danger" type="submit">
                                <i class="bi bi-camera-video-off me-1"></i> Desativar Câmera
                            </button>
                        {% else %}
                            <button class="btn btn-success" type="submit">
                                <i class="bi bi-camera-video me-1"></i> Ativar Câmera
                            </button>
                        {% endif %}
                    
                </form>
                <form method="post" action="{{ url_for('gravar') }}">
                    <button class="btn {{ 'btn-danger' if gravando else 'btn-primary' }}"
                            type="submit"
                            {% if not camera_on %}disabled{% endif %}>
                        <i class="bi {{ 'bi-stop-circle-fill' if gravando else 'bi-record-circle' }} me-1"></i>
                        {{ 'Parar Gravação' if gravando else 'Iniciar Gravação' }}
                    </button>
                </form>
                <a class="btn btn-outline-secondary" href="{{ url_for('baixar_ultima_gravacao') }}" download><i class="bi bi-arrow-down-circle"></i> Baixar último vídeo</a>
            </div>
            <div class="camera-container mt-1">
                {% if camera_on %}
                    <img src="{{ url_for('video_feed') }}" alt="Câmera ao vivo" class="camera-feed">
                {% else %}
                    <div class="camera-off">
                        <i class="bi bi-camera-video-off"></i>
                        <h4>Câmera desligada</h4>
                        <p>Clique em <strong>Ativar Câmera</strong> para iniciar o monitoramento.</p>
                    </div>
                {% endif %}
            </div>

        </div>

        <!-- ########## Tomadas e Sensores  ##############-->
        <div class="col-12 col-lg-5">
            <div class="card sensor-card">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-3">

                        <div>
                            <h2 class="h6 m-0 fw-bold">
                                <i class="bi bi-plug-fill me-1 text-warning"></i>
                                Gestão de Equipamentos Elétricos
                            </h2>
                            <small class="text-muted">
                                Controle das tomadas inteligentes
                            </small>
                        </div>

                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                            {{ tomadas|length }} equipamentos
                        </span>
                        

                    </div>

                    <div class="row g-3">

                        {% for device, ligado, route_on in tomadas %}

                        <div class="col-12 col-sm-6 col-lg-12 col-xl-6">

                            <div class="sensor-box">

                                <div class="d-flex justify-content-between align-items-start mb-3">

                                    <div>

                                        <div class="sensor-title">
                                            <i class="bi bi-plug-fill me-1"></i>
                                            {{ device }}
                                        </div>

                                        <div class="sensor-voltage">
                                            Equipamento elétrico
                                        </div>

                                    </div>

                                    <span class="sensor-status
                                        {{ 'bg-success-subtle text-success' if ligado else 'bg-danger-subtle text-danger' }}">

                                        <i class="bi {{ 'bi-check-circle-fill' if ligado else 'bi-x-circle-fill' }}"></i>

                                        {{ 'Ligado' if ligado else 'Desligado' }}

                                    </span>

                                </div>

                                <div class="d-flex justify-content-between align-items-center">

                                    <div>

                                        <div class="sensor-value"
                                            style="font-size:1.2rem">

                                            <i class="bi {{ 'bi-lightning-charge-fill text-success' if ligado else 'bi-power text-danger' }}"></i>

                                            {{ 'LIGADO' if ligado else 'DESLIGADO' }}

                                        </div>

                                    </div>

                                    <form method="POST"
                                        action="{{ url_for(route_on) }}?acao={{ 'off' if ligado else 'on' }}">

                                        <div class="form-check form-switch">

                                            <input
                                                class="form-check-input relay-switch"
                                                type="checkbox"
                                                onchange="this.form.submit()"
                                                {% if ligado %}checked{% endif %}>

                                        </div>

                                    </form>

                                </div>

                            </div>

                        </div>

                        {% endfor %}

                    </div>

                </div>

            </div>
            

            <!-- SENSORES -->
            <div class="card sensor-card">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h2 class="h6 m-0 fw-bold">
                                <i class="bi bi-droplet-half me-1 text-primary"></i>
                                Sensores 10HS
                            </h2>
                            <small class="text-muted">Umidade do solo via ADS1115</small>
                        </div>

                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                            4 canais
                        </span>
                    </div>

                    <div class="row g-3">
                        {% for i in range(4) %}
                        <div class="col-12 col-sm-6 col-lg-12 col-xl-6">

                            <div class="sensor-box">

                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <div class="sensor-title">
                                            <i class="bi bi-moisture me-1"></i>
                                            10HS {{ i + 1 }}
                                        </div>
                                        <div class="sensor-voltage">
                                            Leitura: <span id="soloV{{ i }}">-- V</span>
                                        </div>
                                    </div>

                                    <span class="sensor-status" id="soloStatus{{ i }}">
                                        Aguardando
                                    </span>
                                </div>

                                <div class="sensor-value">
                                    <span id="solo{{ i }}">-- %</span>
                                </div>

                                <div class="progress sensor-progress">
                                    <div id="soloBar{{ i }}"
                                        class="progress-bar"
                                        role="progressbar"
                                        style="width:0%">
                                    </div>
                                </div>

                            </div>

                        </div>
                        {% endfor %}
                    </div>

                </div>
            </div>
        </div>
        </div>
    </div>
</div>

<footer class="mt-5 pt-4 border-top">

    <!-- Instituições -->
    <div class="d-flex justify-content-center align-items-center flex-wrap gap-4 mb-3">

        <img src="/static/images/logo_ifgoiano_ceres.png"
             alt="IF Goiano"
             class="footer-logo">

        <img src="/static/images/cebio.webp"
             alt="CEBIO"
             class="footer-logo">

        <img src="/static/images/fapeg.png"
             alt="FAPEG"
             class="footer-logo">

        <img src="static/images/logo_cnpq.png"
             alt="CNPQ"
             class="footer-logo">

    </div>

    <!-- Créditos -->
    <div class="text-center text-muted small">
        © {{ year }} <strong>FarmView</strong> — 
        Foi desenvolvido por <strong>Vilson Soares de Siqueira</strong> &amp;  <strong>CEBIO</strong> &amp; <strong>Lab.TI</strong> &amp; Financiado por <strong>FAPEG</strong> e <strong>CNPq</strong> <br>
    </div>

</footer>
<script src="{{ url_for('static', filename='bootstrap-5.3.8/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
"""

HTML_CONFIG = """
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>FarmView — Configurações</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="{{ url_for('static', filename='bootstrap-5.3.8/css/bootstrap.min.css') }}" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

<style>
body{background:#f6f8fb;font-family:system-ui;}
.header{background:#1D7189;color:white;padding:18px 28px;}
.card{border:0;border-radius:14px;box-shadow:0 .6rem 1.2rem rgba(0,0,0,.08);}
</style>
</head>

<body>

<div class="header d-flex justify-content-between align-items-center">
    <div>
        <h1 class="h5 m-0">
            <i class="bi bi-sliders me-2"></i>
            Configurações da Irrigação
        </h1>
        <small>Controle automático da bomba por umidade do solo</small>
    </div>

    <a href="{{ url_for('index') }}" class="btn btn-light btn-sm">
        <i class="bi bi-arrow-left-circle me-1"></i>
        Voltar
    </a>
</div>

<div class="container py-4">

    <form method="POST" class="card">
        <div class="card-body">

            <div class="form-check form-switch mb-4">
                <input class="form-check-input"
                       type="checkbox"
                       name="irrigacao_automatica"
                       id="irrigacao_automatica"
                       {% if config.irrigacao_automatica %}checked{% endif %}>
                <label class="form-check-label fw-bold" for="irrigacao_automatica">
                    Irrigação automática ativa
                </label>
            </div>

            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">Umidade mínima para ligar a bomba (%)</label>
                    <input type="number"
                           step="0.1"
                           min="0"
                           max="100"
                           class="form-control"
                           name="limite_umidade_baixa"
                           value="{{ config.limite_umidade_baixa }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Umidade máxima para parar a irrigação (%)</label>
                    <input type="number"
                           step="0.1"
                           min="0"
                           max="100"
                           class="form-control"
                           name="limite_umidade_alta"
                           value="{{ config.limite_umidade_alta }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Sensor de referência</label>
                    <select class="form-select" name="sensor_referencia">
                        {% for i in range(1, 5) %}
                        <option value="{{ i }}" {% if config.sensor_referencia == i %}selected{% endif %}>
                            Sensor 10HS {{ i }}
                        </option>
                        {% endfor %}
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Tomada da bomba</label>
                    <select class="form-select" name="tomada_bomba">
                        {% for i in range(1, 5) %}
                        <option value="{{ i }}" {% if config.tomada_bomba == i %}selected{% endif %}>
                            Tomada {{ "%02d"|format(i) }}
                        </option>
                        {% endfor %}
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Intervalo de verificação</label>
                    <div class="input-group">
                        <input type="number"
                               min="2"
                               class="form-control"
                               name="intervalo_verificacao_segundos"
                               value="{{ config.intervalo_verificacao_segundos }}">
                        <span class="input-group-text">segundos</span>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">IP da aplicação</label>
                    <input type="text"
                           class="form-control"
                           name="ip_aplicacao"
                           value="{{ config.ip_aplicacao }}">
                    <div class="form-text">
                        IP detectado automaticamente, mas pode ser alterado manualmente.
                    </div>
                </div>

            </div>

            <div class="alert alert-info mt-4 mb-0">
                A bomba será ligada quando a umidade estiver abaixo do valor mínimo
                e desligada quando atingir o valor máximo configurado.
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ url_for('index') }}" class="btn btn-outline-secondary">
                    Cancelar
                </a>

                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle me-1"></i>
                    Salvar configurações
                </button>
            </div>

        </div>
    </form>

</div>

</body>
</html>
"""


def obter_acao():
    """Aceita acao por query string, formulário ou JSON."""
    acao = request.args.get("acao")

    if not acao:
        acao = request.form.get("acao")

    if not acao and request.is_json:
        dados = request.get_json(silent=True) or {}
        acao = dados.get("acao")

    return acao


def controle_irrigacao_automatica():
    """Controla a bomba usando a leitura já produzida pela thread dos sensores."""
    bomba_ligada_auto = False

    while True:
        try:
            config = carregar_config()
            intervalo = config.get("intervalo_verificacao_segundos", 10)

            if not config.get("irrigacao_automatica", False):
                time.sleep(intervalo)
                continue

            sensor_idx = int(config.get("sensor_referencia", 1)) - 1
            tomada = int(config.get("tomada_bomba", 1))
            limite_min = float(config.get("limite_umidade_baixa", 35))
            limite_max = float(config.get("limite_umidade_alta", 60))

            with sensor_lock:
                umidade = dados_sensores["solo_pct"][sensor_idx]

            if umidade <= limite_min and not bomba_ligada_auto:
                ligar_pin(PINOS[tomada])
                salvar_tomada(tomada, PINOS[tomada], "ligado")
                bomba_ligada_auto = True
                print(f"[IRRIGAÇÃO] Bomba ligada - Umidade: {umidade}%")

            elif umidade >= limite_max and bomba_ligada_auto:
                desligar_pin(PINOS[tomada])
                salvar_tomada(tomada, PINOS[tomada], "desligado")
                bomba_ligada_auto = False
                print(f"[IRRIGAÇÃO] Bomba desligada - Umidade: {umidade}%")

            time.sleep(intervalo)

        except Exception as exc:
            print(f"[ERRO IRRIGAÇÃO AUTOMÁTICA] {exc}")
            time.sleep(10)


def resposta_rele(numero, acao, estado):
    """Retorna JSON para curl/API e redirect para botões da interface web."""
    aceita_json = (
        request.args.get("api") == "1"
        or request.is_json
        or "application/json" in request.headers.get("Accept", "")
        or request.args.get("acao") is not None
    )

    if aceita_json:
        return jsonify({
            "gpio": PINOS[numero],
            "tomada": numero,
            "acao": acao,
            "estado": estado,
            "status": "ok",
            "gpio_real": GPIO_REAL,
            "modo": MODO_EXECUCAO,
        })

    return redirect(url_for("index"))


def controlar_rele(numero):
    acao = obter_acao()
    pin = PINOS[numero]

    if acao == "on":
        ligar_pin(pin)
        estado = "ligado"

    elif acao == "off":
        desligar_pin(pin)
        estado = "desligado"

    else:
        return jsonify({
            "erro": "acao inválida. Use acao=on ou acao=off",
            "exemplos": [
                f"curl.exe -X POST http://192.168.0.127:5000/toggle_pin_{numero}?acao=on",
                f"curl.exe -X POST http://192.168.0.127:5000/toggle_pin_{numero}?acao=off",
            ],
        }), 400

    salvar_tomada(numero, pin, estado)

    return resposta_rele(numero, acao, estado)


def conectar_db():
    """
    Abre conexão SQLite com timeout e WAL para evitar travamento quando
    sensores, irrigação automática e acionamento das tomadas gravam ao mesmo tempo.
    """
    conn = sqlite3.connect(DB_NAME, timeout=10, check_same_thread=False)
    conn.execute("PRAGMA busy_timeout = 5000")
    conn.execute("PRAGMA journal_mode = WAL")
    return conn

def init_db():
    conn = conectar_db()
    cursor = conn.cursor()

    cursor.execute("""
        CREATE TABLE IF NOT EXISTS sensores (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sensor INTEGER NOT NULL,
            tensao REAL NOT NULL,
            umidade REAL NOT NULL,
            data_hora TEXT NOT NULL
        )
    """)

    cursor.execute("""
        CREATE TABLE IF NOT EXISTS tomadas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tomada INTEGER NOT NULL,
            gpio INTEGER NOT NULL,
            status TEXT NOT NULL,
            data_hora TEXT NOT NULL
        )
    """)

    conn.commit()
    conn.close()

@app.route("/dados")
def dados():
    sensores, tomadas = buscar_dados_registrados(limite=100)

    return render_template_string(
        HTML_DADOS,
        sensores=sensores,
        tomadas=tomadas
    )



@app.route("/health")
def health():
    return jsonify({"status": "ok", "modo": MODO_EXECUCAO, "gpio_real": GPIO_REAL, "ads_real": ADS_REAL})


@app.route("/status")
def status():
    # Estado real da câmera/gravação, para o PHP não depender só de sessão.
    return jsonify({"camera_on": camera_on, "gravando": gravando})


# ---------------- ROTAS RELÉS ----------------
@app.route("/")
def index():
    tomadas = [
        ("Tomada 01", pin_ligado(PIN_1), "toggle_pin_1"),
        ("Tomada 02", pin_ligado(PIN_2), "toggle_pin_2"),
        ("Tomada 03", pin_ligado(PIN_3), "toggle_pin_3"),
        ("Tomada 04", pin_ligado(PIN_4), "toggle_pin_4"),
    ]
    config = carregar_config()
    return render_template_string(
        HTML,
        tomadas=tomadas,
        camera_on=camera_on,
        gravando=gravando,
        year=time.strftime("%Y"),
        modo_execucao=MODO_EXECUCAO,
        config=config,
    )




@app.route("/toggle_pin_1", methods=["POST"])
def toggle_pin_1():
    return controlar_rele(1)


@app.route("/toggle_pin_2", methods=["POST"])
def toggle_pin_2():
    return controlar_rele(2)


@app.route("/toggle_pin_3", methods=["POST"])
def toggle_pin_3():
    return controlar_rele(3)


@app.route("/toggle_pin_4", methods=["POST"])
def toggle_pin_4():
    return controlar_rele(4)

def salvar_sensor(sensor, tensao, umidade):
    with db_lock:
        conn = conectar_db()
        cursor = conn.cursor()

        cursor.execute("""
            INSERT INTO sensores (sensor, tensao, umidade, data_hora)
            VALUES (?, ?, ?, ?)
        """, (
            sensor,
            tensao,
            umidade,
            datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        ))

        conn.commit()
        conn.close()

def salvar_tomada(numero, gpio, status):
    with db_lock:
        conn = conectar_db()
        cursor = conn.cursor()

        cursor.execute("""
            INSERT INTO tomadas (tomada, gpio, status, data_hora)
            VALUES (?, ?, ?, ?)
        """, (
            numero,
            gpio,
            status,
            datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        ))

        conn.commit()
        conn.close()

def buscar_dados_registrados(limite=100):
    with db_lock:
        conn = conectar_db()
        conn.row_factory = sqlite3.Row
        cursor = conn.cursor()

        sensores = cursor.execute("""
            SELECT id, sensor, tensao, umidade, data_hora
            FROM sensores
            ORDER BY id DESC
            LIMIT ?
        """, (limite,)).fetchall()

        tomadas = cursor.execute("""
            SELECT id, tomada, gpio, status, data_hora
            FROM tomadas
            ORDER BY id DESC
            LIMIT ?
        """, (limite,)).fetchall()

        conn.close()

    return sensores, tomadas

# ---------------- ROTAS CÂMERA ----------------
@app.route("/toggle_camera", methods=["POST"])
def toggle_camera():
    global camera, camera_on, ultimo_frame_jpeg

    if not camera_on:
        camera = abrir_camera()
        camera_on = camera is not None
        if camera_on:
            print("[INFO] Câmera ativada.")
    else:
        # Se estiver gravando, fecha e converte o arquivo ANTES de desligar,
        # senão o vídeo ficaria corrompido.
        if gravando:
            finalizar_gravacao()

        # Sinaliza a thread antes de liberar o dispositivo.
        camera_on = False
        time.sleep(0.2)

        try:
            if camera is not None:
                camera.release()
        except Exception as exc:
            print(f"[AVISO] Erro ao liberar câmera: {exc}")

        camera = None
        with camera_lock:
            ultimo_frame_jpeg = None

        print("[INFO] Câmera desativada.")

    return redirect(url_for("index"))


@app.route("/video_feed")
def video_feed():
    if not camera_on or camera is None:
        return Response(
            "Câmera desligada",
            status=503,
            mimetype="text/plain; charset=utf-8",
        )

    def gerar_frames():
        while camera_on:
            with camera_lock:
                frame = ultimo_frame_jpeg

            if frame is None:
                time.sleep(0.05)
                continue

            yield (
                b"--frame\r\n"
                b"Content-Type: image/jpeg\r\n\r\n"
                + frame
                + b"\r\n"
            )
            time.sleep(1 / 15)

    return Response(
        gerar_frames(),
        mimetype="multipart/x-mixed-replace; boundary=frame",
    )


# ---------------- ROTAS GRAVAÇÃO ----------------
@app.route("/start_recording", methods=["POST"])
def start_recording():
    """API para o PHP: inicia a gravação (só com a câmera ligada)."""
    ok, info = iniciar_gravacao()
    if not ok:
        return jsonify({"erro": info}), 409
    return jsonify({"ok": True, "arquivo": info})


@app.route("/stop_recording", methods=["POST"])
def stop_recording():
    """API para o PHP: para, converte para mp4 e devolve o nome do arquivo."""
    dados, erro = finalizar_gravacao()
    if erro:
        return jsonify({"erro": erro}), 409
    return jsonify({"ok": True, **dados})


@app.route("/gravar", methods=["POST"])
def gravar():
    """Botão do painel do próprio Flask: alterna início/parada da gravação."""
    if not camera_on:
        return redirect(url_for("index"))

    if gravando:
        finalizar_gravacao()
    else:
        iniciar_gravacao()

    return redirect(url_for("index"))


@app.route("/gravacoes/<path:nome>")
def servir_gravacao(nome):
    """Entrega o arquivo para o PHP baixar (send_from_directory bloqueia path traversal)."""
    return send_from_directory(PASTA_GRAVACOES, nome)


@app.route("/ultima_gravacao")
def baixar_ultima_gravacao():
    """Botão 'Baixar último vídeo' do painel: pega o arquivo mais recente da pasta."""
    arquivos = [
        f for f in glob.glob(os.path.join(PASTA_GRAVACOES, "rec_*"))
        if f.endswith((".mp4", ".avi"))
    ]
    if not arquivos:
        return "Ainda não há gravação", 404

    mais_recente = max(arquivos, key=os.path.getmtime)
    return send_from_directory(
        PASTA_GRAVACOES,
        os.path.basename(mais_recente),
        as_attachment=True,
    )


# ---------------- SENSORES ----------------
@app.route("/api_sensores")
def api_sensores():
    # A rota não toca mais no ADS1115; apenas devolve a última leitura pronta.
    with sensor_lock:
        vs = dados_sensores["solo_v"].copy()
        ps = dados_sensores["solo_pct"].copy()

    return jsonify({
        "solo_v": vs,
        "solo_pct": ps,
        "quantidade": len(vs),
        "ads_real": ADS_REAL,
        "modo": MODO_EXECUCAO,
    })


@app.route("/configuracoes", methods=["GET", "POST"])
def configuracoes():
    config = carregar_config()

    if request.method == "POST":
        config["irrigacao_automatica"] = "irrigacao_automatica" in request.form
        config["limite_umidade_baixa"] = float(request.form.get("limite_umidade_baixa", 35))
        config["limite_umidade_alta"] = float(request.form.get("limite_umidade_alta", 60))
        config["sensor_referencia"] = int(request.form.get("sensor_referencia", 1))
        config["tomada_bomba"] = int(request.form.get("tomada_bomba", 1))
        config["intervalo_verificacao_segundos"] = int(request.form.get("intervalo_verificacao_segundos", 10))
        config["ip_aplicacao"] = request.form.get("ip_aplicacao", ip_local())

        salvar_config(config)
        return redirect(url_for("configuracoes"))

    return render_template_string(HTML_CONFIG, config=config)

# ---------------- CLEANUP ----------------
def _cleanup():
    global camera, camera_on, gravando, ultimo_frame_jpeg

    print("[INFO] Encerrando FarmView...")
    camera_on = False
    gravando = False

    try:
        with lock:
            _fechar_writer()

        if camera is not None:
            camera.release()
            camera = None

        with camera_lock:
            ultimo_frame_jpeg = None

    finally:
        for pino in (PIN_1, PIN_2, PIN_3, PIN_4):
            desligar_pin(pino)
        gpio_cleanup()


atexit.register(_cleanup)


@app.after_request
def adicionar_cors(response):
    response.headers['Access-Control-Allow-Origin'] = '*'
    response.headers['Access-Control-Allow-Methods'] = 'GET, POST, OPTIONS'
    response.headers['Access-Control-Allow-Headers'] = 'Content-Type'
    return response



if __name__ == "__main__":
    # host="0.0.0.0" permite acesso local no Raspberry e também pela rede.
    # debug=False/use_reloader=False evitam inicialização dupla do GPIO.
    # threaded=True permite atender stream, API e comandos simultaneamente.
    init_db()

    config = carregar_config()
    config["ip_aplicacao"] = ip_local()
    salvar_config(config)

    thread_sensores = threading.Thread(
        target=atualizar_sensores,
        daemon=True,
        name="FarmView-Sensores",
    )
    thread_camera = threading.Thread(
        target=capturar_camera,
        daemon=True,
        name="FarmView-Camera",
    )
    thread_irrigacao = threading.Thread(
        target=controle_irrigacao_automatica,
        daemon=True,
        name="FarmView-Irrigacao",
    )

    # Sensores primeiro: a irrigação passa a consumir o cache gerado por esta thread.
    thread_sensores.start()
    thread_camera.start()
    thread_irrigacao.start()

    print(f"[INFO] Modo de execução: {MODO_EXECUCAO} | ADS1115 real: {ADS_REAL}")
    print("[INFO] Threads de sensores, câmera e irrigação iniciadas.")

    app.run(
        host="0.0.0.0",
        port=5000,
        debug=False,
        use_reloader=False,
        threaded=True,
    )