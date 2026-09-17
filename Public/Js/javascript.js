setTimeout(() => {

    const alert = document.querySelector('.alert');

    if (alert) {

        const bsAlert = new bootstrap.Alert(alert);

        bsAlert.close();
    }

}, 7000);



const textoObrigatorio = document.querySelectorAll('.textoObrigatorio');




// async function atualizarSensores() {
//     try {
//         const resposta = await fetch(
//             BASE_URL + 'home/sensores',
//             {
//                 cache: 'no-store'
//             }
//         );

//         // const resposta = await fetch(BASE_URL + 'home/sensores', { cache: 'no-store' });
//         // console.log('status:', resposta.status);
//         // const texto = await resposta.clone().text();
//         // console.log('resposta bruta:', texto);

//         if (!resposta.ok) {
//             throw new Error('Erro HTTP: ' + resposta.status);
//         }

//         const dados = await resposta.json();

//         if (!dados.success) {
//             console.error(dados.erro);
//             return;
//         }

//         for (let i = 0; i < 4; i++) {

//             const voltagem = Number(dados.v[i] ?? 0);
//             const porcentagem = Number(dados.pct[i] ?? 0);

//             // VOLTAGEM
//             document.getElementById(
//                 `sensorVolt${i}`
//             ).textContent =
//                 voltagem.toFixed(3).replace('.', ',') + ' V';

//             // PORCENTAGEM
//             document.getElementById(
//                 `sensorPct${i}`
//             ).textContent =
//                 porcentagem.toFixed(1).replace('.', ',') + '%';

//             // BARRA
//             document.getElementById(
//                 `sensorBar${i}`
//             ).style.width =
//                 Math.max(0, Math.min(100, porcentagem)) + '%';

//             // STATUS
//             let status = '';

//             if (porcentagem < 35) {
//                 status = 'Baixo';
//             } else if (porcentagem < 70) {
//                 status = 'Ideal';
//             } else {
//                 status = 'Alta';
//             }

//             document.getElementById(
//                 `sensorStatus${i}`
//             ).textContent = status;
//         }

//     } catch (erro) {
//         console.error(
//             'Erro ao atualizar sensores:',
//             erro
//         );
//     }
// }

// Atualiza imediatamente
// atualizarSensores();

// Depois atualiza automaticamente a cada 1 segundos
setInterval(atualizarSensores, 1000);




