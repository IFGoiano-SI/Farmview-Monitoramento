import json
import time

from conftest import AUTH


def test_health_nao_exige_token(cliente):
    assert cliente.get("/health").status_code == 200


def test_rotas_exigem_token_fora_do_raspberry(cliente):
    assert cliente.get("/status").status_code == 401
    assert cliente.post("/toggle_pin_1?acao=on").status_code == 401
    assert cliente.get("/status", headers={"X-Api-Key": "errado"}).status_code == 401


def test_acesso_local_liberado(app_mod):
    c = app_mod.app.test_client()
    c.environ_base["REMOTE_ADDR"] = "127.0.0.1"
    assert c.get("/status").status_code == 200


def test_token_na_url_vira_cookie(cliente):
    r = cliente.get("/status?token=token-de-teste")
    assert r.status_code == 302
    assert cliente.get("/status").status_code == 200


def test_sem_cors_aberto(cliente):
    r = cliente.get("/status", headers=AUTH)
    assert "Access-Control-Allow-Origin" not in r.headers


def test_status_reflete_estado_real_das_tomadas(cliente):
    cliente.post("/toggle_pin_2?acao=on", headers=AUTH)
    dados = cliente.get("/status", headers=AUTH).get_json()
    assert dados["tomadas"]["2"] is True
    assert dados["tomadas"]["1"] is False
    assert {"camera_on", "gravando", "sensores", "config"} <= dados.keys()


def test_toggle_camera_e_idempotente(cliente, app_mod):
    for _ in range(2):
        r = cliente.post("/toggle_camera", data={"acao": "on", "api": 1}, headers=AUTH)
        assert r.get_json()["camera_on"] is True
    r = cliente.post("/toggle_camera", data={"acao": "off", "api": 1}, headers=AUTH)
    assert r.get_json()["camera_on"] is False


def test_desligar_tudo(cliente):
    cliente.post("/toggle_pin_1?acao=on", headers=AUTH)
    cliente.post("/toggle_camera", data={"acao": "on", "api": 1}, headers=AUTH)
    dados = cliente.post("/desligar_tudo", headers=AUTH).get_json()
    assert dados["ok"] is True
    assert not any(dados["tomadas"].values())
    assert dados["camera_on"] is False


def test_config_padrao_e_segura_e_gravacao_atomica(app_mod):
    config = app_mod.carregar_config()
    assert config["irrigacao_automatica"] is False

    with open(app_mod.CONFIG_FILE, "w") as f:
        f.write("{corrompido")
    assert app_mod.carregar_config()["irrigacao_automatica"] is False

    config["limite_umidade_baixa"] = 20
    app_mod.salvar_config(config)
    with open(app_mod.CONFIG_FILE) as f:
        assert json.load(f)["limite_umidade_baixa"] == 20


def _habilitar_irrigacao(app_mod, **extra):
    config = app_mod.carregar_config()
    config.update({"irrigacao_automatica": True, "tomada_bomba": 1, "sensor_referencia": 1}, **extra)
    app_mod.salvar_config(config)
    return app_mod.PINOS[1]


def test_irrigacao_liga_e_desliga_pela_umidade(app_mod):
    pin = _habilitar_irrigacao(app_mod)
    estado = {"ligada_em": None, "pausa_ate": 0}

    app_mod.dados_sensores.update(solo_pct=[10, 0, 0, 0], atualizado_em=time.time())
    app_mod.verificar_irrigacao(estado)
    assert app_mod.pin_ligado(pin)

    app_mod.dados_sensores.update(solo_pct=[80, 0, 0, 0], atualizado_em=time.time())
    app_mod.verificar_irrigacao(estado)
    assert not app_mod.pin_ligado(pin)


def test_bomba_desliga_com_leitura_desatualizada(app_mod):
    pin = _habilitar_irrigacao(app_mod)
    app_mod.ligar_pin(pin)
    app_mod.dados_sensores.update(solo_pct=[10, 0, 0, 0], atualizado_em=time.time() - 120)

    app_mod.verificar_irrigacao({"ligada_em": None, "pausa_ate": 0})
    assert not app_mod.pin_ligado(pin)


def test_bomba_respeita_tempo_maximo(app_mod):
    pin = _habilitar_irrigacao(app_mod, tempo_maximo_bomba_segundos=60, pausa_apos_limite_segundos=600)
    estado = {"ligada_em": None, "pausa_ate": 0}
    agora = time.time()

    app_mod.dados_sensores.update(solo_pct=[10, 0, 0, 0], atualizado_em=agora)
    app_mod.verificar_irrigacao(estado, agora=agora)
    assert app_mod.pin_ligado(pin)

    app_mod.dados_sensores.update(atualizado_em=agora + 61)
    app_mod.verificar_irrigacao(estado, agora=agora + 61)
    assert not app_mod.pin_ligado(pin)

    # Durante a pausa a bomba não religa, mesmo com o solo seco.
    app_mod.dados_sensores.update(atualizado_em=agora + 120)
    app_mod.verificar_irrigacao(estado, agora=agora + 120)
    assert not app_mod.pin_ligado(pin)


def test_configuracoes_rejeita_valores_invalidos(cliente):
    r = cliente.post("/configuracoes", data={"limite_umidade_baixa": 80, "limite_umidade_alta": 20}, headers=AUTH)
    assert r.status_code == 400
