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
        const resposta = await fetch(
            RASP_URL + '/api_sensores',
            { cache: 'no-store' }
        );

        if (!resposta.ok) {
            throw new Error('Erro HTTP: ' + resposta.status);
        }

        const dados = await resposta.json();

        // O Flask retorna { solo_v: [v0, v1, v2, v3], ... }
        const tensoes = dados.solo_v ?? [];

        for (let i = 0; i < 4; i++) {

            const voltagem    = Number(tensoes[i] ?? 0);
            const porcentagem = voltagemParaPorcentagem(voltagem);

            // VOLTAGEM
            document.getElementById(
                `sensorVolt${i}`
            ).textContent =
                voltagem.toFixed(3).replace('.', ',') + ' V';

            // PORCENTAGEM
            document.getElementById(
                `sensorPct${i}`
            ).textContent =
                porcentagem.toFixed(1).replace('.', ',') + '%';

            // BARRA
            document.getElementById(
                `sensorBar${i}`
            ).style.width =
                Math.max(0, Math.min(100, porcentagem)) + '%';

            // STATUS
            let status = '';

            if (porcentagem < 35) {
                status = 'Baixo';
            } else if (porcentagem < 70) {
                status = 'Ideal';
            } else {
                status = 'Alta';
            }

            document.getElementById(
                `sensorStatus${i}`
            ).textContent = status;
        }

    } catch (erro) {
        console.error(
            'Erro ao atualizar sensores:',
            erro
        );
    }
}

// Atualiza imediatamente
atualizarSensores();

// Depois atualiza automaticamente a cada 1 segundo
setInterval(atualizarSensores, 1000);




