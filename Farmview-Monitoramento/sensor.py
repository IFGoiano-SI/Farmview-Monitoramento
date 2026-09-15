import spidev
import time

# Inicializa SPI
spi = spidev.SpiDev()
spi.open(0, 0)  # Bus 0, Device CE0
spi.max_speed_hz = 1350000

# Função para ler o canal do MCP3008
def read_channel(channel):
    adc = spi.xfer2([1, (8 + channel) << 4, 0])
    data = ((adc[1] & 3) << 8) + adc[2]
    return data

# Converte a leitura para tensão (MCP3008 é 10 bits, 0-1023)
def convert_to_voltage(adc_value, vref=3.3):
    return (adc_value / 1023.0) * vref

# Converte a tensão para umidade volumétrica aproximada (calibração genérica)
def voltage_to_moisture(voltage):
    # Fórmula simplificada (ajuste com base na calibração do solo real)
    # Exemplo genérico baseado nos dados do fabricante
    # VWC = (voltage - 0.45) / 0.045
    return max(0.0, (voltage - 0.45) / 0.045)

try:
    while True:
        adc_value = read_channel(0)  # Canal 0
        voltage = convert_to_voltage(adc_value)
        moisture = voltage_to_moisture(voltage)

        print(f"ADC: {adc_value} | Tensão: {voltage:.3f} V | Umidade: {moisture:.2f} %")

        time.sleep(1)

except KeyboardInterrupt:
    spi.close()
    print("Programa finalizado.")
