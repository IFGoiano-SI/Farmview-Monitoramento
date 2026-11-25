        <!DOCTYPE html>
    <html lang="pt-br">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>FarmView</title>

        <link rel="shortcut icon" href="<?= base_url('Public/templates/ImageSistema/LogoSistema.ico') ?>" type="image/x-icon">

        <link rel="stylesheet" href="<?= base_url('Public/templates/Css/home.css') ?>">

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
            integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

        <style>
            .contentSensores {
                display: flex;
            }

            .sensor-progresso {
                width: 100%;
                height: 6px;
                background-color: #e5e7eb;
                border-radius: 10px;
                overflow: hidden;
            }

            .sensor-progresso-barra {
                height: 100%;
                background-color: #16a34a;
                border-radius: 10px;
            }

            .statusLigado {
                background-color: #d9f7df;
                color: #0f9e30;
                padding: 4px 10px;
                border-radius: 20px;
                font-weight: 600;
            }

            .statusDesligado {
                background-color: #fde2e2;
                color: #da1818;
                padding: 4px 10px;
                border-radius: 20px;
                font-weight: 600;
            }


            .buttonSensores .form-check-input {
                width: 3.5rem;
                height: 1.8rem;
                cursor: pointer;
            }

            .buttonSensores .form-check-input:checked {
                background-color: #0f9e30;
                border-color: #0f9e30;
            }

            .buttonSensores .form-check-input:focus {
                box-shadow: none;
                border-color: #aaa;
            }

            .gravacao {
                width: 170px;
                height: 40px;
                border-radius: 8px;
                border: none;
                background-color: #1893a8;
                color: white;
                font-size: 13pt;
                font-weight: 500;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                transition: all 0.3 ease;

            }

            .cam #camera {
                width: 100%;
                height: 100%;
                object-fit: contain;
                cursor: zoom-in;
            }

            #modalCamera {
                width: 95vw;
                max-width: 1200px;
                max-height: 95vh;
                padding: 0;
                border: none;
                border-radius: 12px;
                background: #111;
            }

            #modalCamera::backdrop {
                background: rgba(0, 0, 0, 0.85);
            }

            .cameraAmpliada {
                position: relative;
                padding: 45px 12px 12px;
            }

            #cameraAmpliadaConteudo #camera {
                display: block;
                width: 100%;
                height: auto;
                max-height: 80vh;
                object-fit: contain;
                cursor: default;
            }

            #fecharCamera {
                position: absolute;
                top: 5px;
                right: 12px;
                border: none;
                background: transparent;
                color: white;
                font-size: 30px;
                cursor: pointer;
            }
        </style>
    </head>


    <body>

        <?php
        $foto = $_SESSION['usuarios_logado']->usuarios_imagem;
        $nome = $_SESSION['usuarios_logado']->usuarios_nome;
        $escolaridade = $_SESSION['usuarios_logado']->usuarios_escolaridade;
        ?>

        <div class="fullScreen">

            <!-- ===================== SIDEBAR ===================== -->
            <div class="sideBar">

                <div class="leftScreenTop">
                    <div class="logo">
                        <a href="<?= base_url('home/tela') ?>"><img src="<?= base_url('Public/templates/ImageSistema/LogoPreta.png') ?>" alt="Logo CEBIO"></a>
                    </div>

                    <div class="leftLinks">
                        <ul>
                            <li>
                                <a class="linksDashboard" href="#">
                                    <i class="bi bi-house-door-fill"></i> Dashboard
                                </a>
                            </li>
                            <li>
                                <a class="linksDashboard" href="<?= base_url('anotacao/index') ?>">
                                    <i class="bi bi-pencil-square"></i> Anotação
                                </a>
                            </li>
                            <li>
                                <a class="linksDashboard" href="<?= base_url('experimento/listarExperimentos') ?>">
                                    <i class="bi bi-clipboard2-pulse"></i> Plantio
                                </a>
                            </li>

                            <li><a class="linksDashboard" href="<?= base_url('gravacao/index') ?>"><i class="bi bi-webcam"></i> Vídeos</a></li>
                        </ul>
                    </div>
                </div>

                <div class="leftScreenBottom">
                    <h5>&copy; 2026 CEBIO CERES</h5>
                    <p>Todos os direitos reservados</p>
                </div>

            </div>

            <!-- ===================== CONTEÚDO ===================== -->
            <div class="contentScreen">

                <div class="alert-container">
                    <?php partial('alerts'); ?>
                </div>

                <!-- ===================== NAVBAR ===================== -->
                <?php if (isset($_SESSION['usuarios_logado'])): ?>

                    <div class="navBar">
                        <div class="navName">
                            <h4><?= esc($nome) ?></h4>
                            <p><?= esc($escolaridade) ?></p>
                        </div>

                        <div class="navLogout">
                            <a href="<?= base_url('experimento/editar/' . $usuarioExperimento->experimentos_id) ?>">
                                <img id="imagePerson" src="<?= esc(base_url($foto)) ?>" alt="Foto de perfil">
                            </a>


                            <button type="button" class="btn-logout" data-bs-toggle="modal" data-bs-target="#meuModalSair">
                                Sair <i class="bi bi-box-arrow-right"></i>
                            </button>
                        </div>
                    </div>


                    <?php partial('logout_modal'); ?>

                <?php endif; ?>

                <div id="avisoBancada" class="alert alert-warning mx-4 mt-3 <?= $online ? 'd-none' : '' ?>" role="status" aria-live="polite">
                    <i class="bi bi-wifi-off" aria-hidden="true"></i>
                    <strong>Sem comunicação com a bancada.</strong>
                    <span id="avisoBancadaTexto">Os valores exibidos podem estar desatualizados.</span>
                </div>

                <!-- ===================== ÁREA SUPERIOR ===================== -->
                <div class="Finalizacao">

                    <div class="graficosSensores">

                        <!-- GRÁFICO TEMPERATURA DA TELA -->
                        <div class="cardGrafico">
                            <div class="topoGrafico">
                                <div class="tituloGrafico">
                                    <i class="bi bi-thermometer-half"></i>
                                    <h4>Temperatura da Tela</h4>
                                </div>

                                <select>
                                    <option>Últimas 24h</option>
                                    <option>Últimas 12h</option>
                                    <option>Últimas 6h</option>
                                </select>
                            </div>

                            <div class="corpoGrafico">
                                <div class="grafico">
                                    <canvas id="graficoTela"></canvas>
                                </div>

                                <div class="valorAtual">
                                    <span>Atual</span>
                                    <strong id="temperaturaTela" title="Sensor de temperatura ainda não integrado">--</strong>
                                </div>
                            </div>
                        </div>

                        <!-- GRÁFICO TEMPERATURA DO CULTIVO -->
                        <div class="cardGrafico">
                            <div class="topoGrafico">
                                <div class="tituloGrafico">
                                    <i class="bi bi-leaf"></i>
                                    <h4>Temperatura do Cultivo</h4>
                                </div>

                                <select>
                                    <option>Últimas 24h</option>
                                    <option>Últimas 12h</option>
                                    <option>Últimas 6h</option>
                                </select>
                            </div>

                            <div class="corpoGrafico">
                                Valor indisponível
                                <div class="grafico">
                                    <canvas id="graficoCultivo"></canvas>
                                </div>

                                <div class="valorAtual">
                                    <span>Atual</span>
                                    <strong id="temperaturaCultivo" title="Sensor de temperatura ainda não integrado">--</strong>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="campoBotao">
                        <button type="button" class="btn-finalizarExperimento" data-bs-toggle="modal" data-bs-target="#meuModal">
                            Finalizar Experimentos <i class="bi bi-clipboard-check"></i>
                        </button>
                    </div>

                </div>
                <!-- ===================== CONTEÚDO PRINCIPAL ===================== -->
                <div class="mainContent">

                    <!-- ===================== CÂMERA ===================== -->
                    <div class="contentCam">
                        <h4><i class="bi bi-camera"></i> Câmera ao vivo</h4>

                        <form id="formCamera" action="<?= base_url('home/ligarcamera') ?>" method="post">
<?= csrf_field() ?>
                            <div class="cam">
                                <img id="camera"
                                    src="<?= esc($videoUrl) ?>"
                                    alt="Imagem ao vivo da bancada (clique para ampliar)"
                                    title="Clique para ampliar"
                                    role="button"
                                    tabindex="0"
                                    onclick="abrirCamera()"
                                    onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); abrirCamera(); }">
                            </div>

                            <dialog id="modalCamera">
                                <div class="cameraAmpliada">
                                    <button type="button" id="fecharCamera" aria-label="Fechar câmera">
                                        &times;
                                    </button>

                                    <div id="cameraAmpliadaConteudo"></div>
                                </div>
                            </dialog>
                        </form>

                        <div class="buttonCam">
                            <form id="formGravacao">
                                <?= csrf_field() ?>
                                <input type="hidden" name="experimentos_id"
                                    value="<?= (int) $usuarioExperimento->experimentos_id ?>">

                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="gravarTela"
                                        <?= $gravando ? 'checked' : '' ?>
                                        <?= !$camera_on ? 'disabled' : '' ?>>
                                    <label class="form-check-label" for="gravarTela" id="labelGravar">
                                        <?= $gravando ? 'Gravando...' : 'Gravar vídeo' ?>
                                    </label>
                                </div>
                            </form>



                            <button type="submit" form="formCamera" name="acao" value="on" class="OnCam">
                                Ligar câmera <i class="bi bi-camera-video"></i>
                            </button>

                            <button type="submit" form="formCamera" name="acao" value="off" class="OffCam"
                                <?= $gravando ? 'disabled title="Pare a gravação antes de desligar a câmera"' : '' ?>>
                                Desligar câmera <i class="bi bi-camera-video-off"></i>
                            </button>
                        </div>
                    </div>

                    <!-- ===================== SENSORES / TOMADAS ===================== -->
                    <div class="contentSensores">

                        <!-- As tomadas ficam agrupadas como no HTML de referência -->
                        <div class="groupTomadas">

                            <!-- TOMADA 1 -->
                            <form class="groupSensores" action="<?= base_url('home/toggleDispositivo') ?>" method="post">
<?= csrf_field() ?>
                                <input type="hidden" name="tomada" value="1">

                                <div class="infoSensores">
                                    <div class="iconSensores">
                                        <i class="bi bi-broadcast fs-4"></i>
                                    </div>

                                    <div class="textSensores mt-2">
                                        <h4>Tomada 1</h4>
                                        <p>
                                            Status:
                                            <span id="statusTomada1" class="<?= ($tomadas[1] ?? null) === true ? 'statusLigado' : 'statusDesligado' ?>">
                                                <?= ($tomadas[1] ?? null) === null ? 'Indisponível' : (($tomadas[1]) ? 'Ligado' : 'Desligado') ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>

                                <div class="buttonSensores">
                                    <div class="form-check form-switch">

                                        <!-- <input type="hidden" name="acao" value="off"> -->

                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            role="switch"
                                            id="tomada1"
                                            name="acao"
                                            value="on"
                                            onchange="this.form.submit()"
                                            aria-label="Ligar ou desligar a tomada 1"
                                            <?= ($tomadas[1] ?? null) === true ? 'checked' : '' ?>
                                            <?= ($tomadas[1] ?? null) === null ? 'disabled' : '' ?>>


                                    </div>
                                </div>
                            </form>

                            <!-- TOMADA 2 -->
                            <form class="groupSensores" action="<?= base_url('home/toggleDispositivo') ?>" method="post">
<?= csrf_field() ?>
                                <input type="hidden" name="tomada" value="2">

                                <div class="infoSensores">
                                    <div class="iconSensores">
                                        <i class="bi bi-broadcast fs-4"></i>
                                    </div>

                                    <div class="textSensores mt-2">
                                        <h4>Tomada 2</h4>
                                        <p>
                                            Status:
                                            <span id="statusTomada2" class="<?= ($tomadas[2] ?? null) === true ? 'statusLigado' : 'statusDesligado' ?>">
                                                <?= ($tomadas[2] ?? null) === null ? 'Indisponível' : (($tomadas[2]) ? 'Ligado' : 'Desligado') ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>

                                <div class="buttonSensores">
                                    <div class="form-check form-switch">

                                        <!-- <input type="hidden" name="acao" value="off"> -->

                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            role="switch"
                                            id="tomada2"
                                            name="acao"
                                            value="on"
                                            onchange="this.form.submit()"
                                            aria-label="Ligar ou desligar a tomada 2"
                                            <?= ($tomadas[2] ?? null) === true ? 'checked' : '' ?>
                                            <?= ($tomadas[2] ?? null) === null ? 'disabled' : '' ?>>

                                    </div>
                                </div>
                            </form>

                            <!-- TOMADA 3 -->
                            <form class="groupSensores" action="<?= base_url('home/toggleDispositivo') ?>" method="post">
<?= csrf_field() ?>
                                <input type="hidden" name="tomada" value="3">

                                <div class="infoSensores">
                                    <div class="iconSensores">
                                        <i class="bi bi-broadcast fs-4"></i>
                                    </div>

                                    <div class="textSensores mt-2">
                                        <h4>Tomada 3</h4>
                                        <p>
                                            Status:
                                            <span id="statusTomada3" class="<?= ($tomadas[3] ?? null) === true ? 'statusLigado' : 'statusDesligado' ?>">
                                                <?= ($tomadas[3] ?? null) === null ? 'Indisponível' : (($tomadas[3]) ? 'Ligado' : 'Desligado') ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>

                                <div class="buttonSensores">
                                    <div class="form-check form-switch">
                                        <!-- <input type="hidden" name="acao" value="off"> -->
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            role="switch"
                                            id="tomada3"
                                            name="acao"
                                            value="on"
                                            onchange="this.form.submit()"
                                            aria-label="Ligar ou desligar a tomada 3"
                                            <?= ($tomadas[3] ?? null) === true ? 'checked' : '' ?>
                                            <?= ($tomadas[3] ?? null) === null ? 'disabled' : '' ?>>
                                    </div>
                                </div>
                            </form>

                            <!-- Tomada 4 -->

                            <form class="groupSensores" action="<?= base_url('home/toggleDispositivo') ?>" method="post">
<?= csrf_field() ?>
                                <input type="hidden" name="tomada" value="4">

                                <div class="infoSensores">
                                    <div class="iconSensores">
                                        <i class="bi bi-broadcast fs-4"></i>
                                    </div>

                                    <div class="textSensores mt-2">
                                        <h4>Tomada 4</h4>
                                        <p>
                                            Status:
                                            <span id="statusTomada4" class="<?= ($tomadas[4] ?? null) === true ? 'statusLigado' : 'statusDesligado' ?>">
                                                <?= ($tomadas[4] ?? null) === null ? 'Indisponível' : (($tomadas[4]) ? 'Ligado' : 'Desligado') ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>

                                <div class="buttonSensores">
                                    <div class="form-check form-switch">

                                        <!-- <input type="hidden" name="acao" value="off"> -->

                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            role="switch"
                                            id="tomada4"
                                            name="acao"
                                            value="on"
                                            onchange="this.form.submit()"
                                            aria-label="Ligar ou desligar a tomada 4"
                                            <?= ($tomadas[4] ?? null) === true ? 'checked' : '' ?>
                                            <?= ($tomadas[4] ?? null) === null ? 'disabled' : '' ?>>

                                    </div>
                                </div>
                            </form>

                        </div>

                        <!-- Sensores de umidade separados das tomadas -->
                        <div class="groupUmidade" aria-live="polite">

                            <?php
                            $percentuais = $sensores['pct'] ?? [];
                            $tensoes = $sensores['v'] ?? [];
                            ?>

                            <?php for ($i = 0; $i < 4; $i++): ?>

                                <?php
                                $pct = isset($percentuais[$i])
                                    ? (float) $percentuais[$i]
                                    : 0;

                                $voltagem = isset($tensoes[$i])
                                    ? (float) $tensoes[$i]
                                    : 0;

                                if ($pct < $limites['baixa']) {
                                    $statusSensor = 'Baixa';
                                } elseif ($pct < $limites['alta']) {
                                    $statusSensor = 'Ideal';
                                } else {
                                    $statusSensor = 'Alta';
                                }
                                ?>

                                <div class="sensoresUmidade">

                                    <div class="sensor-icon">
                                        <i class="bi bi-droplet-fill"></i>
                                    </div>

                                    <div class="sensorPlanta">

                                        <div class="sensor-topo">

                                            <h4>Sensor 10HS <?= $i + 1 ?></h4>

                                            <span
                                                class="sensor-status"
                                                id="sensorStatus<?= $i ?>">
                                                <?= $statusSensor ?>
                                            </span>

                                        </div>

                                        <div class="sensor-valor">

                                            <strong id="sensorPct<?= $i ?>">
                                                <?= number_format($pct, 1, ',', '.') ?>%
                                            </strong>

                                        </div>

                                        <div class="sensor-progresso">

                                            <div
                                                id="sensorBar<?= $i ?>"
                                                class="sensor-progresso-barra"
                                                style="width: <?= max(0, min(100, $pct)) ?>%;">
                                            </div>

                                        </div>

                                        <div class="sensor-rodape">

                                            <!-- <span>
                                                Umidade do solo
                                            </span> -->

                                            <span id="sensorVolt<?= $i ?>">
                                                <?= number_format($voltagem, 3, ',', '.') ?> V
                                            </span>

                                        </div>

                                    </div>

                                </div>

                            <?php endfor; ?>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!-- ===================== MODAL FINALIZAR EXPERIMENTO ===================== -->
        <div class="modal fade" id="meuModal">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">

                    <div class="modal-header">
                        <button class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <h4 class="text-center mb-1">Deseja finalizar o experimento?</h4>
                        <h6 class="text-center mb-5">
                            Ao finalizar esse experimento ele ficará na aba de experimentos e não poderá ser alterado novamente
                        </h6>

                        <div class="botoes-experimentos">
                            <button class="btn-voltar" data-bs-dismiss="modal">Sair</button>
                            <form action="<?= base_url('experimento/finalizarExperimento/' . (int) $usuarioExperimento->experimentos_id) ?>" method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn-finalizar">Finalizar</button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <?php partial('footer', ['estilo' => 'height: auto;']); ?>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
            crossorigin="anonymous"></script>

        <script>
            const BASE_URL = <?= json_encode(base_url()) ?>;
            window.CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;
            window.LIMITES_UMIDADE = <?= json_encode($limites) ?>;
        </script>

        <script>
            window.SENSORES_URL = <?= json_encode(base_url('home/sensores')) ?>;
        </script>
        <script src="<?= base_url('/Public/Js/javascript.js') ?>"></script>


        <script>
            document.getElementById('camera').onerror = function() {
                this.src = '<?= base_url('Public/templates/Images/Noimage.png') ?>';
            };
        </script>


        <script>
            const modalCamera = document.getElementById('modalCamera');
            const camera = document.getElementById('camera');
            const localOriginal = camera.parentElement;
            const conteudoAmpliado = document.getElementById('cameraAmpliadaConteudo');

            function abrirCamera() {
                if (modalCamera.open) return;

                modalCamera.showModal();
                conteudoAmpliado.appendChild(camera);
            }

            document.getElementById('fecharCamera').addEventListener('click', () => {
                modalCamera.close();
            });

            modalCamera.addEventListener('click', (event) => {
                if (event.target === modalCamera) {
                    modalCamera.close();
                }
            });

            modalCamera.addEventListener('close', () => {
                localOriginal.appendChild(camera);
            });
        </script>



    </body>


    </html>