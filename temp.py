import time
import board
import adafruit_dht

# ============================================================
# CONFIGURAÇÃO
# DHT11 conectado ao GPIO 26 (BCM 26)
# GPIO 26 = pino físico 37 do Raspberry Pi 4
# ============================================================

sensor = adafruit_dht.DHT11(
    board.D26,
    use_pulseio=False
)

print("=" * 45)
print("          SENSOR DHT11")
print("     GPIO 26 - Pino físico 37")
print("=" * 45)
print("Iniciando leitura...\n")

# Tempo para estabilização do sensor
time.sleep(2)

try:

    while True:

        try:

            temperatura = sensor.temperature
            umidade = sensor.humidity

            if temperatura is not None and umidade is not None:

                print(f"Temperatura : {temperatura:.1f} °C")
                print(f"Umidade     : {umidade:.1f} %")
                print("-" * 45)

            else:

                print("Leitura inválida. Tentando novamente...")

        except RuntimeError as erro:

            # DHT11 pode apresentar falhas ocasionais de comunicação
            print(f"Falha na leitura: {erro}")

        # Não consultar o sensor excessivamente rápido
        time.sleep(2)

except KeyboardInterrupt:

    print("\nLeitura encerrada pelo usuário.")

finally:

    sensor.exit()
    print("Sensor finalizado.")
