from flask import Flask, Response, render_template_string, request, redirect, url_for, jsonify, send_from_directory
import threading
import atexit
import time
import os
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
DESLIGAR_TOMADAS_AO_INICIAR = True
DESATIVAR_IRRIGACAO_AO_INICIAR = True
DB_NAME = "farmview.db"
CONFIG_FILE = "config_farmview.json"

PINOS = {
    1: PIN_1,
    2: PIN_2,
    3: PIN_3,
    4: PIN_4,
}

V_MIN, V_MAX = 0.30, 2.50
# Faixa usada para identificar sensor conectado ao ADS1115.
# Ajuste após testar com o 10HS real e com o canal desconectado.
SENSOR_VOLTAGE_PRESENT_MIN = 0.10
SENSOR_VOLTAGE_PRESENT_MAX = 3.20

lock = threading.Lock()
gpio_lock = threading.Lock()
db_lock = threading.Lock()
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
        "irrigacao_automatica": False,
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


def desligar_todas_tomadas():
    """Garante que todas as saídas iniciem desligadas."""
    for pino in (PIN_1, PIN_2, PIN_3, PIN_4):
        desligar_pin(pino)
    time.sleep(0.2)


# Inicializa todos os relés desligados antes de carregar interface/sensores.
if DESLIGAR_TOMADAS_AO_INICIAR:
    desligar_todas_tomadas()

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


def ler_sensor_com_status(ch, n=5, delay=0.06):
    """
    Lê o canal do ADS1115 e indica se o sensor 10HS parece conectado.

    Observação: entrada analógica solta pode flutuar. Para maior confiabilidade,
    use resistor pull-down de alto valor no canal do ADS1115.
    """
    try:
        vals = []
        for _ in range(n):
            vals.append(ch.voltage)
            time.sleep(delay)

        v = sum(vals) / len(vals)
        conectado = SENSOR_VOLTAGE_PRESENT_MIN <= v <= SENSOR_VOLTAGE_PRESENT_MAX

        if not conectado:
            return False, None, None

        return True, v, v_to_pct(v)

    except Exception as exc:
        print(f"[ERRO SENSOR] Falha ao ler canal ADS1115: {exc}")
        return False, None, None

# ---------------- CÂMERA ----------------
camera = None
camera_on = False
gravando = False
video_writer = None


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

# ---------------- FLASK ----------------
app = Flask(__name__)




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
    bomba_ligada_auto = False

    while True:
        try:
            config = carregar_config()

            if not config.get("irrigacao_automatica", False):
                time.sleep(config.get("intervalo_verificacao_segundos", 10))
                continue

            sensor_idx = int(config.get("sensor_referencia", 1)) - 1
            tomada = int(config.get("tomada_bomba", 1))

            limite_min = float(config.get("limite_umidade_baixa", 35))
            limite_max = float(config.get("limite_umidade_alta", 60))

            sensor_conectado, v, umidade = ler_sensor_com_status(channels[sensor_idx])

            if not sensor_conectado:
                print(f"[AVISO IRRIGAÇÃO] Sensor 10HS {sensor_idx + 1} desconectado. Bomba não será acionada.")
                if bomba_ligada_auto:
                    desligar_pin(PINOS[tomada])
                    salvar_tomada(tomada, PINOS[tomada], "desligado")
                    bomba_ligada_auto = False
                time.sleep(config.get("intervalo_verificacao_segundos", 10))
                continue

            if umidade <= limite_min and not bomba_ligada_auto:
                ligar_pin(PINOS[tomada])
                salvar_tomada(tomada, PINOS[tomada], "ligado")
                bomba_ligada_auto = True

            elif umidade >= limite_max and bomba_ligada_auto:
                desligar_pin(PINOS[tomada])
                salvar_tomada(tomada, PINOS[tomada], "desligado")
                bomba_ligada_auto = False

            time.sleep(config.get("intervalo_verificacao_segundos", 10))

        except Exception as exc:
            print(f"[ERRO IRRIGAÇÃO AUTOMÁTICA] {exc}")
            time.sleep(10)



def resposta_rele(numero, acao, estado):
    """Retorna JSON para curl/API e redirect para botões da interface web."""
    aceita_json = (
        request.args.get("api") == "1"
        or request.is_json
        or "application/json" in request.headers.get("Accept", "")
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
                f"curl.exe -X POST http://192.168.1.16:5000/toggle_pin_{numero}?acao=on",
                f"curl.exe -X POST http://192.168.1.16:5000/toggle_pin_{numero}?acao=off",
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


# @app.route("/gravar", methods=["POST"])
# def gravar():
#     global gravando
#     with lock:
#         gravando = (not gravando) if camera_on else False
#     return redirect(url_for("index"))

@app.route('/gravar', methods=['POST'])
def gravar():
    global gravando

    if not camera_on:
        return redirect(url_for('index'))

    gravando = not gravando

    return redirect(url_for('index'))


@app.route("/gravacao.avi")
def baixar_gravacao():
    if os.path.exists("gravacao.avi"):
        return send_from_directory(os.getcwd(), "gravacao.avi", as_attachment=True)
    return "Ainda não há gravação", 404


# ---------------- SENSORES ----------------
@app.route("/api_sensores")
def api_sensores():
    global ultimo_registro_sensor

    vs, ps, conectados = [], [], []
    agora = time.time()
    gravar_leitura = (agora - ultimo_registro_sensor) >= SENSOR_SAVE_INTERVAL

    for i, ch in enumerate(channels):
        conectado, v, p = ler_sensor_com_status(ch)
        conectados.append(conectado)

        if not conectado:
            vs.append(None)
            ps.append(None)
            continue

        v = round(v, 4)
        p = round(p, 2)

        vs.append(v)
        ps.append(p)

        # Evita gravar 4 linhas no SQLite a cada 2 segundos apenas por atualização da tela.
        # Sensores desconectados não são registrados no banco.
        if gravar_leitura:
            salvar_sensor(i + 1, v, p)

    if gravar_leitura:
        ultimo_registro_sensor = agora

    return jsonify({
        "solo_v": vs,
        "solo_pct": ps,
        "conectados": conectados,
        "quantidade": len(channels),
        "ads_real": ADS_REAL,
        "modo": MODO_EXECUCAO
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
        gpio_cleanup()


atexit.register(_cleanup)

if __name__ == "__main__":
    # host="0.0.0.0" permite acesso local no Raspberry e também pela rede.
    # Local: http://127.0.0.1:5000
    # Rede:  http://IP_DO_RASPBERRY:5000
    # IMPORTANTE:
    # debug=False e use_reloader=False evitam que o Flask inicialize o GPIO duas vezes.
    # threaded=True permite usar câmera, sensores, interface web e curl ao mesmo tempo.
    init_db()

    config = carregar_config()
    config["ip_aplicacao"] = ip_local()

    # Evita que a irrigação automática ligue a Tomada 01 logo ao iniciar.
    # Depois, você pode ativar novamente pela tela Configurações.
    if DESATIVAR_IRRIGACAO_AO_INICIAR:
        config["irrigacao_automatica"] = False

    salvar_config(config)

    # Força todas as tomadas desligadas após carregar/salvar configuração.
    if DESLIGAR_TOMADAS_AO_INICIAR:
        desligar_todas_tomadas()

    thread_irrigacao = threading.Thread(
        target=controle_irrigacao_automatica,
        daemon=True
    )
    thread_irrigacao.start()

    print(f"[INFO] Modo de execução: {MODO_EXECUCAO} | GPIO real: {GPIO_REAL} | ADS1115 real: {ADS_REAL}")
    app.run(host="0.0.0.0", port=5000, debug=False, use_reloader=False, threaded=True)
