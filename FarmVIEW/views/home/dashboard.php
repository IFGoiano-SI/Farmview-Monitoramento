<?php redirectPages(); ?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoramento - CEBIO</title>
    <link rel="stylesheet" href="<?= base_url('Public/templates/Css/home.css') ?>">
    <link rel="shortcut icon" href="<?= base_url('Public/templates/ImageSistema/logo.ico') ?>" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
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
                        <li><a class="linksDashboard" href="#"><i class="bi bi-house-door-fill"></i> Dashboard</a></li>
                        <li><a class="linksDashboard" href="<?= base_url('anotacao/index') ?>"><i class="bi bi-pencil-square"></i> Anotação</a></li>
                        <li><a class="linksDashboard" href="<?= base_url('experimento/listarExperimentos') ?>"><i class="bi bi-clipboard2-pulse"></i> Experimentos</a></li>
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

            <div class="alert-container">
                <?php if (isset($_SESSION['msg'])): ?>
                    <?= msg($_SESSION['msg']['texto'], $_SESSION['msg']['color']); ?>
                    <?php unset($_SESSION['msg']); ?>
                <?php endif; ?>
            </div>

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

            <div class="Finalizacao">
                <button type="button" class="btn-finalizarExperimento" data-bs-toggle="modal" data-bs-target="#meuModal">
                    Finalizar Experimentos <i class="bi bi-clipboard-check"></i>
                </button>
            </div>


            <!-- Modal de finalizar experimento -->
            <div class="modal fade" id="meuModal">
                <div class="modal-dialog modal-dialog-centered modal-xl">
                    <div class="modal-content">

                        <div class="modal-header">
                            <button class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <h4 class="text-center mb-1">Deseja finalizar o experimento?</h4>
                            <h6 class="text-center mb-5">Ao finalizar esse experimento ele ficará na aba de experimentos e não poderá ser alterado novamente</h6>

                            <div class="botoes-experimentos">
                                <button class="btn-voltar" data-bs-dismiss="modal">Sair</button>
                                <button type="submit" onclick="window.location.href='<?= base_url('experimento/finalizarExperimento/' . $usuarioExperimento->experimentos_id) ?>'" class="btn-finalizar">Finalizar</button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="mainContent">

                <!-- ===================== CÂMERA ===================== -->
                <div class="contentCam">

                    <h4><i class="bi bi-camera"></i> Câmera ao vivo</h4>

                    <form id="formCamera" action="<?= base_url('home/ligarcamera') ?>" method="post">
                        <div class="cam">
                            <img id="camera" src="http://192.168.0.127:5000/video_feed" alt="Câmera indisponível">
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

                <!-- ===================== TOMADAS ===================== -->
                <div class="contentSensores">

                    <!-- TOMADA 1 -->
                    <form class="groupSensores" action="<?= base_url('home/toggleDispositivo') ?>" method="post">
                        <input type="hidden" name="tomada" value="1">

                        <div class="infoSensores">
                            <div class="iconSensores">
                                <i class="bi bi-broadcast fs-4"></i>
                            </div>
                            <div class="textSensores mt-2">
                                <h4>Tomada 1</h4>
                                <p>Status: <?= ($_SESSION['tomada_1'] ?? '') === 'Ligado' ? 'Ligado' : 'Desligado' ?></p>
                            </div>
                        </div>

                        <div class="buttonSensores">
                            <button type="submit" name="acao" value="on" class="ligarSensores">Ligar</button>
                            <button type="submit" name="acao" value="off" class="desligarSensores">Desligar</button>
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
                                <p>Status: <?= ($_SESSION['tomada_2'] ?? '') === 'Ligado' ? 'Ligado' : 'Desligado' ?></p>
                            </div>
                        </div>

                        <div class="buttonSensores">
                            <button type="submit" name="acao" value="on" class="ligarSensores">Ligar</button>
                            <button type="submit" name="acao" value="off" class="desligarSensores">Desligar</button>
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
                                <p>Status: <?= ($_SESSION['tomada_3'] ?? '') === 'Ligado' ? 'Ligado' : 'Desligado' ?></p>
                            </div>
                        </div>

                        <div class="buttonSensores">
                            <button type="submit" name="acao" value="on" class="ligarSensores">Ligar</button>
                            <button type="submit" name="acao" value="off" class="desligarSensores">Desligar</button>
                        </div>
                    </form>

                    <!-- SENSOR (antiga tomada 4) -->
                    <div class="sensoresUmidade">
                        <div class="sensorPlanta">
                            <h4>Sensores h10s</h4>
                            <p>Umidade: 10%</p>
                        </div>

                        <div class="sensorPlanta">
                            <h4>Sensores h10s</h4>
                            <p>Umidade: 10%</p>
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- ===================== FOOTER ===================== -->
    <div class="contentFooter">
        <div class="aboutFooter">
            <h5>Plataforma de monitoramento em ambientes controlados</h5>
        </div>

        <div class="linkFooter">
            <ul>
                <li><a href="https://cebiobrasil.com.br/" target="_blank">CEBIO Brasil</a></li>
                <li><a href="https://www.ifgoiano.edu.br/home/index.php/ceres" target="_blank">IF Goiano</a></li>
                <li><a href="https://goias.gov.br/fapeg/" target="_blank">FAPEG</a></li>
            </ul>
        </div>
    </div>

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