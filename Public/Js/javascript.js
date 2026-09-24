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

async function atualizarSensores() {
    try {
        console.log('Buscando sensores em:', RASP_URL);

        const resposta = await fetch(
            RASP_URL + '/api_sensores',
            { cache: 'no-store' }
        );

        console.log('HTTP:', resposta.status);

        if (!resposta.ok) {
            throw new Error('Erro HTTP: ' + resposta.status);
        }

        const dados = await resposta.json();

        console.log('DADOS RECEBIDOS:', dados);

        const tensoes = dados.solo_v ?? [];

        for (let i = 0; i < 4; i++) {
            const voltagem = Number(tensoes[i] ?? 0);
            const porcentagem = voltagemParaPorcentagem(voltagem);

            console.log(
                `Sensor ${i + 1}:`,
                voltagem,
                porcentagem
            );

            document.getElementById(`sensorVolt${i}`).textContent =
                voltagem.toFixed(3).replace('.', ',') + ' V';

            document.getElementById(`sensorPct${i}`).textContent =
                porcentagem.toFixed(1).replace('.', ',') + '%';

            document.getElementById(`sensorBar${i}`).style.width =
                Math.max(0, Math.min(100, porcentagem)) + '%';

            let status;

            if (porcentagem < 35) {
                status = 'Baixa';
            } else if (porcentagem < 70) {
                status = 'Ideal';
            } else {
                status = 'Alta';
            }

            document.getElementById(`sensorStatus${i}`).textContent =
                status;
        }

    } catch (erro) {
        console.error('ERRO SENSOR:', erro);
    }
}

atualizarSensores();

// NÃO coloque o setInterval ainda
// setInterval(atualizarSensores, 1000);



