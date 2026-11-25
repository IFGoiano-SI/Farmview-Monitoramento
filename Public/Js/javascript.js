// Fecha automaticamente os alertas após alguns segundos.
setTimeout(() => {
    const alerta = document.querySelector('.alert:not(#avisoBancada)');

    if (alerta && window.bootstrap) {
        bootstrap.Alert.getOrCreateInstance(alerta).close();
    }
}, 7000);

/** Mostra uma mensagem no mesmo padrão visual dos alertas do sistema. */
function mostrarAlerta(texto, tipo = 'danger') {
    const container = document.querySelector('.alert-container');

    if (!container) {
        return;
    }

    const div = document.createElement('div');
    div.className = `alert alert-${tipo} alert-dismissible fade show`;
    div.setAttribute('role', 'alert');

    const strong = document.createElement('strong');
    strong.textContent = texto;

    const fechar = document.createElement('button');
    fechar.type = 'button';
    fechar.className = 'btn-close';
    fechar.setAttribute('data-bs-dismiss', 'alert');
    fechar.setAttribute('aria-label', 'Fechar');

    div.append(strong, fechar);
    container.replaceChildren(div);
}

// ===================== SENSORES / ESTADO DA BANCADA =====================
// Só roda nas páginas que exibem os sensores (dashboard).

const LIMITES = window.LIMITES_UMIDADE || { baixa: 35, alta: 60 };

function statusUmidade(porcentagem) {
    if (porcentagem < LIMITES.baixa) return 'Baixa';
    if (porcentagem < LIMITES.alta) return 'Ideal';
    return 'Alta';
}

function atualizarAvisoBancada(online, idadeSegundos) {
    const aviso = document.getElementById('avisoBancada');
    const texto = document.getElementById('avisoBancadaTexto');

    if (!aviso) return;

    const desatualizado = online && idadeSegundos !== null && idadeSegundos > 30;
    aviso.classList.toggle('d-none', online && !desatualizado);

    if (texto) {
        texto.textContent = desatualizado
            ? `Última leitura dos sensores há ${Math.round(idadeSegundos)} s.`
            : 'Os valores exibidos podem estar desatualizados.';
    }
}

function atualizarTomadas(tomadas) {
    if (!tomadas) return;

    Object.entries(tomadas).forEach(([numero, ligada]) => {
        const status = document.getElementById(`statusTomada${numero}`);
        const chave = document.getElementById(`tomada${numero}`);

        if (status) {
            status.textContent = ligada === null ? 'Indisponível' : (ligada ? 'Ligado' : 'Desligado');
            status.className = ligada === true ? 'statusLigado' : 'statusDesligado';
        }

        if (chave) {
            chave.disabled = ligada === null;
            chave.checked = ligada === true;
        }
    });
}

async function atualizarSensores() {
    const controlador = new AbortController();
    const limite = setTimeout(() => controlador.abort(), 4000);

    try {
        const resposta = await fetch(window.SENSORES_URL, {
            cache: 'no-store',
            signal: controlador.signal,
            headers: { 'X-Requested-With': 'fetch', 'Accept': 'application/json' },
        });

        if (resposta.status === 401) {
            window.location.reload();
            return;
        }

        const dados = await resposta.json();

        atualizarAvisoBancada(Boolean(dados.online), dados.idade_segundos ?? null);
        atualizarTomadas(dados.tomadas);

        if (!dados.online || !Array.isArray(dados.v)) {
            return;
        }

        for (let i = 0; i < 4; i++) {
            const voltagem = Number(dados.v[i]);
            const porcentagem = Number(dados.pct[i]);

            if (!Number.isFinite(voltagem) || !Number.isFinite(porcentagem)) {
                continue;
            }

            const campoVolt = document.getElementById(`sensorVolt${i}`);
            const campoPct = document.getElementById(`sensorPct${i}`);
            const barra = document.getElementById(`sensorBar${i}`);
            const status = document.getElementById(`sensorStatus${i}`);

            if (campoVolt) campoVolt.textContent = voltagem.toFixed(3).replace('.', ',') + ' V';
            if (campoPct) campoPct.textContent = porcentagem.toFixed(1).replace('.', ',') + '%';
            if (barra) barra.style.width = Math.max(0, Math.min(100, porcentagem)) + '%';
            if (status) status.textContent = statusUmidade(porcentagem);
        }
    } catch (erro) {
        atualizarAvisoBancada(false, null);
    } finally {
        clearTimeout(limite);
        setTimeout(atualizarSensores, 3000);
    }
}

if (window.SENSORES_URL && document.getElementById('sensorPct0')) {
    atualizarSensores();
}

// ===================== GRAVAÇÃO =====================

const switchGravar = document.getElementById('gravarTela');
const labelGravar = document.getElementById('labelGravar');

switchGravar?.addEventListener('change', async function () {
    const iniciando = this.checked;
    const rota = iniciando ? 'home/iniciargravacao' : 'home/parargravacao';

    this.disabled = true;
    labelGravar.textContent = iniciando ? 'Iniciando...' : 'Salvando gravação...';

    try {
        const resposta = await fetch(BASE_URL + rota, {
            method: 'POST',
            body: new FormData(document.getElementById('formGravacao')),
            headers: {
                'X-Requested-With': 'fetch',
                'Accept': 'application/json',
                'X-CSRF-Token': window.CSRF_TOKEN || '',
            },
        });

        let json = {};
        try {
            json = await resposta.json();
        } catch {
            throw new Error('Resposta inesperada do servidor. Tente novamente.');
        }

        if (!resposta.ok) {
            throw new Error(json.erro || 'Erro na gravação');
        }

        labelGravar.textContent = iniciando ? 'Gravando...' : 'Gravar vídeo';

        if (!iniciando) {
            mostrarAlerta('Vídeo salvo com sucesso! Ele já está disponível em "Vídeos".', 'success');
        }
    } catch (e) {
        mostrarAlerta(e.message, 'danger');
        this.checked = !iniciando;
        labelGravar.textContent = this.checked ? 'Gravando...' : 'Gravar vídeo';
    }

    this.disabled = false;
});
