from flask import Flask, Response, render_template_string, request, redirect, url_for, jsonify, send_from_directory
import threading
import atexit
import time
import os

import cv2
import RPi.GPIO as GPIO
import board
import busio
import adafruit_ads1x15.ads1115 as ADS
from adafruit_ads1x15.analog_in import AnalogIn
import threading

gpio_lock = threading.Lock()

# ---------------- GPIO / RELÉS ----------------
PIN_1 = 27
PIN_2 = 10
PIN_3 = 17
PIN_4 = 22
ACTIVE_LOW = True

GPIO.setmode(GPIO.BCM)
GPIO.setup(PIN_1, GPIO.OUT)
GPIO.setup(PIN_2, GPIO.OUT)
GPIO.setup(PIN_3, GPIO.OUT)
GPIO.setup(PIN_4, GPIO.OUT)


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
        return GPIO.input(pin) == GPIO.LOW if ACTIVE_LOW else GPIO.input(pin) == GPIO.HIGH


# Inicializa todos os relés desligados
for pino in (PIN_1, PIN_2, PIN_3, PIN_4):
    desligar_pin(pino)

PINOS = {
    1: PIN_1,
    2: PIN_2,
    3: PIN_3,
    4: PIN_4,
}

# ---------------- ADS1115 (4x 10HS) ----------------
i2c = busio.I2C(board.SCL, board.SDA)
ads = ADS.ADS1115(i2c, address=0x48)
ads.gain = 1

# ADS1115 possui 4 entradas analógicas: A0/P0, A1/P1, A2/P2 e A3/P3
channels = [
    AnalogIn(ads, ADS.P0),
    AnalogIn(ads, ADS.P1),
    AnalogIn(ads, ADS.P2),
    AnalogIn(ads, ADS.P3),
]

V_MIN, V_MAX = 0.30, 2.50


def v_to_pct(v):
    return round(max(0, min(100, (v - V_MIN) / (V_MAX - V_MIN) * 100)), 2)


def ler_sensor_medio(ch, n=5, delay=0.06):
    vals = []
    for _ in range(n):
        vals.append(ch.voltage)
        time.sleep(delay)
    v = sum(vals) / len(vals)
    return v, v_to_pct(v)

# ---------------- CÂMERA ----------------
camera = None
camera_on = False
gravando = False
video_writer = None
lock = threading.Lock()
gpio_lock = threading.Lock()


def abrir_camera():
    for idx in (0, 1):
        cap = cv2.VideoCapture(idx)
        if cap.isOpened():
            for _ in range(3):
                cap.read()
                time.sleep(0.05)
            return cap
        try:
            cap.release()
        except Exception:
            pass
    return None

# ---------------- FLASK ----------------
app = Flask(__name__)

HTML = """<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Vertical Farming — Monitoramento</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link href="{{ url_for('static', filename='bootstrap-5.3.8/css/bootstrap.min.css') }}" rel="stylesheet">
<style>
:root{--radius:1rem;}
body{background:#f6f8fb;font-family:system-ui;}
.container-narrow{max-width:72rem;margin:0 auto;padding:1rem;}
.brandbar{background:linear-gradient(90deg,#0b7d5e,#19a974,#2eb875);color:#fff;border-radius:var(--radius);padding:.9rem 1.2rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;}
.logo-left img,.logo-right img{max-height:6rem;height:auto;width:auto;object-fit:contain;}
.card{border:0;border-radius:var(--radius);box-shadow:0 .6rem 1.2rem rgba(0,0,0,.08);margin-top:1rem;}
.pill{padding:.35rem .7rem;border-radius:999px;font-size:.95rem;}
.muted{color:#6b7280;}
.video-wrap{display:flex;justify-content:center;margin-top:1.5rem;}
.video-wrap img{width:70%;border-radius:var(--radius);box-shadow:0 .7rem 1.4rem rgba(0,0,0,.12);background:#000;}
</style>
<script>
const TOTAL_SENSORES = 4;
async function refreshSensores(){
  try{
    const r = await fetch("{{ url_for('api_sensores') }}");
    const d = await r.json();
    for(let i = 0; i < TOTAL_SENSORES; i++){
      document.getElementById("solo" + i).textContent = d.solo_pct[i] + " %";
      document.getElementById("soloV" + i).textContent = Number(d.solo_v[i]).toFixed(3) + " V";
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
<div class="container-fluid px-3 py-4">
    <div class="brandbar">
        <div class="logo-left"><img src="/static/images/labti.jpeg" alt="LabTI"></div>
        <div class="text-center flex-grow-1">
            <div class="fw-semibold fs-5">Vertical Farming — Monitoramento</div>
            <div class="text-white-50 small">Câmera • Tomadas 1 - 4 • 4×10HS (ADS1115)</div>
        </div>
        <div class="logo-right"><img src="/static/images/cebio.png" alt="CEBIO"></div>
    </div>

    <div class="row">
        <div class="col-7">
            <!-- CÂMERA -->
            <div class="mt-3 d-flex gap-2">
                <form method="post" action="{{ url_for('toggle_camera') }}">
                    <button class="btn {{ 'btn-danger' if camera_on else 'btn-primary' }}" type="submit">
                        {{ 'Desativar Câmera' if camera_on else 'Ativar Câmera' }}
                    </button>
                </form>
            </div>
            <div class="video-wrap">
                {% if camera_on %}
                <img src="{{ url_for('video_feed') }}" alt="Câmera ao vivo">
                {% else %}
                <div class="p-4 text-center text-muted">Câmera desligada. Clique em "Ativar Câmera".</div>
                {% endif %}
            </div>
            <div class="d-flex gap-2 mt-3 align-items-center flex-wrap">
                <form method="post" action="{{ url_for('gravar') }}">
                    <button class="btn {{ 'btn-danger' if gravando else 'btn-primary' }}" type="submit" {{ '' if camera_on else 'disabled' }}>
                        {{ 'Parar Gravação' if gravando else 'Iniciar Gravação' }}
                    </button>
                </form>
                <a class="btn btn-outline-secondary" href="/gravacao.avi" download>Baixar último vídeo</a>
                <span class="ms-auto pill bg-light border text-muted d-none d-md-inline">{{ year }}</span>
            </div>
        </div>

        <div class="col-5">
            <!-- RELÉS -->
            {% for device, ligado, route_on in tomadas %}
            <div class="card">
                <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <div class="fw-semibold">{{ device }}</div>
                        <div class="mt-1">Status:
                            <span class="pill {{ 'bg-success-subtle border border-success text-success' if ligado else 'bg-danger-subtle border border-danger text-danger' }}">
                                {{ 'LIGADO' if ligado else 'DESLIGADO' }}
                            </span>
                        </div>
                    </div>
                    <form method="post" action="{{ url_for(route_on) }}" class="d-flex gap-2">
                        <button type="submit" name="acao" value="on" class="btn btn-success">Ligar</button>
                        <button type="submit" name="acao" value="off" class="btn btn-outline-danger">Desligar</button>
                    </form>
                </div>
            </div>
            {% endfor %}

            <!-- SENSORES -->
            <div class="card">
                <div class="card-body">
                    <h2 class="h6 m-0 mb-2">Sensores de Umidade do Solo — 10HS (ADS1115)</h2>
                    {% for i in range(4) %}
                    <div class="border-bottom py-2">
                        <div class="d-flex justify-content-between"><strong>10HS {{ i + 1 }}</strong><span id="solo{{ i }}">-- %</span></div>
                        <div class="text-muted small">Leitura: <span id="soloV{{ i }}">-- V</span></div>
                    </div>
                    {% endfor %}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="text-center text-muted small mt-4">© {{ year }} • CEBIO & LabTI</div>
<script src="{{ url_for('static', filename='bootstrap-5.3.8/js/bootstrap.bundle.min.js') }}"></script>
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

    return resposta_rele(numero, acao, estado)


@app.route("/health")
def health():
    return jsonify({"status": "ok"})


# ---------------- ROTAS RELÉS ----------------
@app.route("/")
def index():
    tomadas = [
        ("Tomada 01", pin_ligado(PIN_1), "toggle_pin_1"),
        ("Tomada 02", pin_ligado(PIN_2), "toggle_pin_2"),
        ("Tomada 03", pin_ligado(PIN_3), "toggle_pin_3"),
        ("Tomada 04", pin_ligado(PIN_4), "toggle_pin_4"),
    ]
    return render_template_string(
        HTML,
        tomadas=tomadas,
        camera_on=camera_on,
        gravando=gravando,
        year=time.strftime("%Y"),
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


# ---------------- ROTAS CÂMERA ----------------
@app.route("/toggle_camera", methods=["POST"])
def toggle_camera():
    global camera, camera_on
    if not camera_on:
        camera = abrir_camera()
        camera_on = camera is not None
    else:
        try:
            camera.release()
        except Exception:
            pass
        camera = None
        camera_on = False
    return redirect(url_for("index"))


@app.route("/video_feed")
def video_feed():
    if not camera_on or not camera:
        return Response("Câmera desligada", status=503, mimetype="text/plain; charset=utf-8")

    def gerar_frames():
        global video_writer
        fourcc = cv2.VideoWriter_fourcc(*"XVID")
        fps = 20.0
        while camera_on and camera:
            ok, frame = camera.read()
            if not ok:
                break
            with lock:
                if gravando:
                    if video_writer is None:
                        h, w = frame.shape[:2]
                        video_writer = cv2.VideoWriter("gravacao.avi", fourcc, fps, (w, h))
                    video_writer.write(frame)
                elif video_writer:
                    video_writer.release()
                    video_writer = None
            ret, buf = cv2.imencode(".jpg", frame)
            if not ret:
                continue
            yield b"--frame\r\nContent-Type: image/jpeg\r\n\r\n" + buf.tobytes() + b"\r\n"
            time.sleep(0.03)

    return Response(gerar_frames(), mimetype="multipart/x-mixed-replace; boundary=frame")


@app.route("/gravar", methods=["POST"])
def gravar():
    global gravando
    with lock:
        gravando = (not gravando) if camera_on else False
    return redirect(url_for("index"))


@app.route("/gravacao.avi")
def baixar_gravacao():
    if os.path.exists("gravacao.avi"):
        return send_from_directory(os.getcwd(), "gravacao.avi", as_attachment=True)
    return "Ainda não há gravação", 404


# ---------------- SENSORES ----------------
@app.route("/api_sensores")
def api_sensores():
    vs, ps = [], []
    for ch in channels:
        v, p = ler_sensor_medio(ch)
        vs.append(round(v, 4))
        ps.append(p)
    return jsonify({"solo_v": vs, "solo_pct": ps, "quantidade": len(channels)})


# ---------------- CLEANUP ----------------
def _cleanup():
    global video_writer, camera, camera_on
    try:
        with lock:
            if video_writer:
                video_writer.release()
                video_writer = None
        if camera:
            camera.release()
            camera = None
            camera_on = False
    finally:
        for pino in (PIN_1, PIN_2, PIN_3, PIN_4):
            desligar_pin(pino)
        GPIO.cleanup()


atexit.register(_cleanup)

if __name__ == "__main__":
    # host="0.0.0.0" permite acesso local no Raspberry e também pela rede.
    # Local: http://127.0.0.1:5000
    # Rede:  http://IP_DO_RASPBERRY:5000
    # IMPORTANTE:
    # debug=False e use_reloader=False evitam que o Flask inicialize o GPIO duas vezes.
    # threaded=True permite usar câmera, sensores, interface web e curl ao mesmo tempo.
    app.run(host="0.0.0.0", port=5000, debug=False, use_reloader=False, threaded=True)
