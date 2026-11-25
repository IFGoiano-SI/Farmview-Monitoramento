"""
Simula o hardware do Raspberry (GPIO, ADS1115, câmera) para testar o
serviço Flask em qualquer computador.
"""
import os
import sys
import types

import pytest

RAIZ = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
sys.path.insert(0, os.path.join(RAIZ, "raspberry"))


def _instalar_stubs():
    estado_gpio = {}

    gpio = types.ModuleType("RPi.GPIO")
    gpio.BCM, gpio.OUT, gpio.HIGH, gpio.LOW = "BCM", "OUT", 1, 0
    gpio.setwarnings = lambda *a: None
    gpio.setmode = lambda *a: None
    gpio.setup = lambda pin, modo, initial=1: estado_gpio.__setitem__(pin, initial)
    gpio.output = lambda pin, valor: estado_gpio.__setitem__(pin, valor)
    gpio.input = lambda pin: estado_gpio.get(pin, 1)
    gpio.cleanup = lambda *a: None
    rpi = types.ModuleType("RPi")
    rpi.GPIO = gpio

    board = types.ModuleType("board")
    board.SCL, board.SDA = "SCL", "SDA"
    busio = types.ModuleType("busio")
    busio.I2C = lambda *a, **k: object()

    ads_pkg = types.ModuleType("adafruit_ads1x15")
    ads1115 = types.ModuleType("adafruit_ads1x15.ads1115")

    class ADS1115:
        def __init__(self, *a, **k):
            self.gain = 1

    ads1115.ADS1115 = ADS1115
    ads1115.P0, ads1115.P1, ads1115.P2, ads1115.P3 = 0, 1, 2, 3
    analog_in = types.ModuleType("adafruit_ads1x15.analog_in")

    class AnalogIn:
        def __init__(self, ads, canal):
            self.voltage = 1.0

    analog_in.AnalogIn = AnalogIn

    cv2 = types.ModuleType("cv2")

    class VideoCapture:
        def __init__(self, idx):
            pass

        def isOpened(self):
            return True

        def read(self):
            return True, None

        def release(self):
            pass

    cv2.VideoCapture = VideoCapture
    cv2.VideoWriter_fourcc = lambda *a: 0
    cv2.IMWRITE_JPEG_QUALITY = 1

    sys.modules.update({
        "RPi": rpi, "RPi.GPIO": gpio, "board": board, "busio": busio,
        "adafruit_ads1x15": ads_pkg, "adafruit_ads1x15.ads1115": ads1115,
        "adafruit_ads1x15.analog_in": analog_in, "cv2": cv2,
    })


_instalar_stubs()
os.environ.setdefault("FARMVIEW_TOKEN", "token-de-teste")

import app as farmview  # noqa: E402


@pytest.fixture
def app_mod(tmp_path, monkeypatch):
    monkeypatch.setattr(farmview, "DB_NAME", str(tmp_path / "farmview.db"))
    monkeypatch.setattr(farmview, "CONFIG_FILE", str(tmp_path / "config.json"))
    monkeypatch.setattr(farmview, "FARMVIEW_TOKEN", "token-de-teste")
    farmview.init_db()
    for pin in farmview.PINOS.values():
        farmview.desligar_pin(pin)
    farmview.camera_on = False
    farmview.camera = None
    return farmview


@pytest.fixture
def cliente(app_mod):
    app_mod.app.config["TESTING"] = True
    c = app_mod.app.test_client()
    c.environ_base["REMOTE_ADDR"] = "192.168.0.50"
    return c


AUTH = {"X-Api-Key": "token-de-teste"}
