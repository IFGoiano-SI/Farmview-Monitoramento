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

const switchGravar = document.getElementById('gravarTela');
const labelGravar = document.getElementById('labelGravar');

switchGravar?.addEventListener('change', async function () {
    const iniciando = this.checked;
    const rota = iniciando ? 'home/iniciargravacao' : 'home/parargravacao';

    this.disabled = true;
    labelGravar.textContent = iniciando ? 'Iniciando...' : 'Salvando gravação...';

    try {
        const url = BASE_URL + rota;
        const r = await fetch(url, {
            method: 'POST',
            body: new FormData(document.getElementById('formGravacao'))
        });

        const texto = await r.text();
        console.log('URL:', url, '| Status:', r.status, '| Resposta:', texto);

        let json;
        try {
            json = JSON.parse(texto);
        } catch {
            throw new Error('Resposta não é JSON (status ' + r.status + '): ' + texto.substring(0, 300));
        }

        if (!r.ok) {
            alert(json.erro || 'Erro na gravação');
            this.checked = !iniciando;
            labelGravar.textContent = this.checked ? 'Gravando...' : 'Gravar vídeo';
        } else if (json.redirect) {
            window.location.href = json.redirect;
            return;
        } else {
            labelGravar.textContent = iniciando ? 'Gravando...' : 'Gravar vídeo';
        }
    } catch (e) {
        console.error(e);
        alert(e.message);
        this.checked = !iniciando;
        labelGravar.textContent = this.checked ? 'Gravando...' : 'Gravar vídeo';
    }

    this.disabled = false;
});



