setTimeout(() => {

    const alert = document.querySelector('.alert');

    if (alert) {

        const bsAlert = new bootstrap.Alert(alert);

        bsAlert.close();
    }

}, 7000);



const textoObrigatorio = document.querySelectorAll('.textoObrigatorio');




// Converte voltagem em porcentagem de umidade (mesma lógica do FlaskService.php)
function voltagemParaPorcentagem(voltagem) {
    const vMin = 0.30;
    const vMax = 2.50;
    const porcentagem = ((voltagem - vMin) / (vMax - vMin)) * 100;
    return Math.round(Math.max(0, Math.min(100, porcentagem)) * 100) / 100;
}

let atualizacaoAtiva = true;

async function atualizarSensores() {
    const controlador = new AbortController();
    const limite = setTimeout(() => controlador.abort(), 3000);

    try {
        const resposta = await fetch(window.SENSORES_URL, {
            cache: 'no-store',
            signal: controlador.signal

        });

        if (!resposta.ok) {
            throw new Error(`HTTP ${resposta.status}`);
        }

        const dados = await resposta.json();

        if (!dados.success || !Array.isArray(dados.v) || dados.v.length < 4) {
            throw new Error('Leituras dos sensores ausentes');
        }

        for (let i = 0; i < 4; i++) {
            const voltagem = Number(dados.v[i]);
            const porcentagem = Number(dados.pct[i]);

            if (!Number.isFinite(voltagem) || !Number.isFinite(porcentagem)) {
                continue;
            }

            document.getElementById(`sensorVolt${i}`).textContent =
                voltagem.toFixed(3).replace('.', ',') + ' V';

            document.getElementById(`sensorPct${i}`).textContent =
                porcentagem.toFixed(1).replace('.', ',') + '%';

            document.getElementById(`sensorBar${i}`).style.width =
                Math.max(0, Math.min(100, porcentagem)) + '%';

            document.getElementById(`sensorStatus${i}`).textContent =
                porcentagem < 35 ? 'Baixa' :
                    porcentagem < 70 ? 'Ideal' : 'Alta';
        }
    } catch (erro) {
        console.error('Falha na leitura dos sensores:', erro);
    } finally {
        clearTimeout(limite);

        if (atualizacaoAtiva) {
            setTimeout(atualizarSensores, 3000);
        }
    }
}


atualizarSensores();

// NÃO coloque o setInterval ainda
// setInterval(atualizarSensores, 1000);



