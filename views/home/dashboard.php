<?php redirectPages(); ?>
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
            background-color: #212121;
            border-color: #212121;
        }

        .buttonSensores .form-check-input:focus {
            box-shadow: none;
            border-color: #aaa;
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
                    <img src="<?= base_url('Public/templates/ImageSistema/LogoPreta.png') ?>" alt="Logo CEBIO">
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
                <?php if (isset($_SESSION['msg'])): ?>
                    <?= msg($_SESSION['msg']['texto'], $_SESSION['msg']['color']); ?>
                    <?php unset($_SESSION['msg']); ?>
                <?php endif; ?>
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
                            <img id="imagePerson" src="<?= base_url($foto) ?>" alt="Foto de perfil">
                        </a>

                        <button type="button" class="btn-logout" data-bs-toggle="modal" data-bs-target="#meuModalLogout">
                            Sair <i class="bi bi-box-arrow-right"></i>
                        </button>
                    </div>
                </div>

            <?php endif; ?>

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
                                <strong id="temperaturaTela">28,3°C</strong>
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
                            <div class="grafico">
                                <canvas id="graficoCultivo"></canvas>
                            </div>

                            <div class="valorAtual">
                                <span>Atual</span>
                                <strong id="temperaturaCultivo">25,7°C</strong>
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
                        <div class="cam">
                            <img id="camera"
                                style="width: 100%; height: 100%;"
                                src="http://10.20.138.38:5000/video_feed"
                                alt="Câmera indisponível">
                        </div>
                    </form>

                    <div class="buttonCam">
                        <button type="submit" form="formCamera" name="acao" value="on" class="OnCam">
                            Ligar câmera <i class="bi bi-camera-video"></i>
                        </button>

                        <button type="submit" form="formCamera" name="acao" value="off" class="OffCam">
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
                            <input type="hidden" name="tomada" value="1">

                            <div class="infoSensores">
                                <div class="iconSensores">
                                    <i class="bi bi-broadcast fs-4"></i>
                                </div>

                                <div class="textSensores mt-2">
                                    <h4>Tomada 1</h4>
                                    <p>
                                        Status:
                                        <span class="<?= ($_SESSION['tomada_1'] ?? '') === 'Ligado' ? 'statusLigado' : 'statusDesligado' ?>">
                                            <?= ($_SESSION['tomada_1'] ?? '') === 'Ligado' ? 'Ligado' : 'Desligado' ?>
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <div class="buttonSensores">
                                <div class="form-check form-switch">

                                    <input type="hidden" name="acao" value="off">

                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        role="switch"
                                        id="tomada1"
                                        name="acao"
                                        value="on"
                                        onchange="this.form.submit()"
                                        <?= ($_SESSION['tomada_1'] ?? '') === 'Ligado' ? 'checked' : '' ?>>

                                </div>
                            </div>
                        </form>

                        <!-- TOMADA 2 -->
                        <form class="groupSensores" action="<?= base_url('home/toggleDispositivo') ?>" method="post">
                            <input type="hidden" name="rota" value="api/bomba">
                            <input type="hidden" name="tomada" value="2">

                            <div class="infoSensores">
                                <div class="iconSensores">
                                    <i class="bi bi-broadcast fs-4"></i>
                                </div>

                                <div class="textSensores mt-2">
                                    <h4>Tomada 2</h4>
                                    <p>
                                        Status:
                                        <span class="<?= ($_SESSION['tomada_2'] ?? '') === 'Ligado' ? 'statusLigado' : 'statusDesligado' ?>">
                                            <?= ($_SESSION['tomada_2'] ?? '') === 'Ligado' ? 'Ligado' : 'Desligado' ?>
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <div class="buttonSensores">
                                <div class="form-check form-switch">

                                    <input type="hidden" name="acao" value="off">

                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        role="switch"
                                        id="tomada2"
                                        name="acao"
                                        value="on"
                                        onchange="this.form.submit()"
                                        <?= ($_SESSION['tomada_2'] ?? '') === 'Ligado' ? 'checked' : '' ?>>

                                </div>
                            </div>
                        </form>

                        <!-- TOMADA 3 -->
                        <form class="groupSensores" action="<?= base_url('home/toggleDispositivo') ?>" method="post">
                            <input type="hidden" name="rota" value="api/lampada">
                            <input type="hidden" name="tomada" value="3">

                            <div class="infoSensores">
                                <div class="iconSensores">
                                    <i class="bi bi-broadcast fs-4"></i>
                                </div>

                                <div class="textSensores mt-2">
                                    <h4>Tomada 3</h4>
                                    <p>
                                        Status:
                                        <span class="<?= ($_SESSION['tomada_3'] ?? '') === 'Ligado' ? 'statusLigado' : 'statusDesligado' ?>">
                                            <?= ($_SESSION['tomada_3'] ?? '') === 'Ligado' ? 'Ligado' : 'Desligado' ?>
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <div class="buttonSensores">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="acao" value="off">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        role="switch"
                                        id="tomada3"
                                        name="acao"
                                        value="on"
                                        onchange="this.form.submit()"
                                        <?= ($_SESSION['tomada_3'] ?? '') === 'Ligado' ? 'checked' : '' ?>>
                                </div>
                            </div>
                        </form>

                        <!-- Tomada 4 -->

                        <form class="groupSensores" action="<?= base_url('home/toggleDispositivo') ?>" method="post">
                            <input type="hidden" name="rota" value="api/lampada">
                            <input type="hidden" name="tomada" value="4">

                            <div class="infoSensores">
                                <div class="iconSensores">
                                    <i class="bi bi-broadcast fs-4"></i>
                                </div>

                                <div class="textSensores mt-2">
                                    <h4>Tomada 4</h4>
                                    <p>
                                        Status:
                                        <span class="<?= ($_SESSION['tomada_4'] ?? '') === 'Ligado' ? 'statusLigado' : 'statusDesligado' ?>">
                                            <?= ($_SESSION['tomada_4'] ?? '') === 'Ligado' ? 'Ligado' : 'Desligado' ?>
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <div class="buttonSensores">
                                <div class="form-check form-switch">

                                    <input type="hidden" name="acao" value="off">

                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        role="switch"
                                        id="tomada4"
                                        name="acao"
                                        value="on"
                                        onchange="this.form.submit()"
                                        <?= ($_SESSION['tomada_4'] ?? '') === 'Ligado' ? 'checked' : '' ?>>

                                </div>
                            </div>
                        </form>

                    </div>

                    <!-- Sensores de umidade separados das tomadas -->
                    <div class="groupUmidade">

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

                            if ($pct < 35) {
                                $statusSensor = 'Baixa';
                            } elseif ($pct < 70) {
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

                                        <span class="sensor-status">
                                            <?= $statusSensor ?>
                                        </span>

                                    </div>

                                    <div class="sensor-valor">

                                        <strong>
                                            <?= number_format($pct, 1, ',', '.') ?>%
                                        </strong>

                                    </div>

                                    <div class="sensor-progresso">

                                        <div
                                            class="sensor-progresso-barra"
                                            style="width: <?= max(0, min(100, $pct)) ?>%; margin-botom: 2px;">
                                        </div>

                                    </div>

                                    <div class="sensor-rodape">

                                        <!-- <span>
                                            Umidade do solo
                                        </span> -->

                                        <span>
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
                        <button type="submit"
                            onclick="window.location.href='<?= base_url('experimento/finalizarExperimento/' . $usuarioExperimento->experimentos_id) ?>'"
                            class="btn-finalizar">
                            Finalizar
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ===================== FOOTER ===================== -->
    <footer class="contentFooter" style="height: auto;">

        <div class="footerMain">
            <div class="aboutFooter">
                <h4>FarmView</h4>
                <p>
                    Plataforma de monitoramento em ambientes controlados
                </p>
            </div>
            <div class="linkFooter">
                <h5>Parceiros</h5>

                <ul>
                    <li><a href="https://cebiobrasil.com.br/" target="_blank">CEBIO</a></li>
                    <li>
                        <a href="https://www.ifgoiano.edu.br/home/index.php/ceres" target="_blank">
                            IF Goiano Campus Ceres
                        </a>
                    </li>
                    <li><a href="https://goias.gov.br/fapeg/" target="_blank">FAPEG</a></li>
                    <li><a href="https://lattes.cnpq.br/" target="_blank">CNPq</a></li>
                    <li><a href="#">LabTI</a></li>
                </ul>
            </div>

        </div>

        <div class="footerBottom">
            <p>© 2026 FarmView</p>
            <p>IF Goiano — Campus Ceres</p>
        </div>

    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>

    <script src="<?= base_url('Public/Js/javascript.js') ?>"></script>

    <script>
        document.getElementById('camera').onerror = function() {
            this.src = '<?= base_url('Public/templates/Images/Noimage.png') ?>';
        };
    </script>

</body>

</html>