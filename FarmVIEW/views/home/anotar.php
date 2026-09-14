<?php redirectPages(); ?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anotação - CEBIO</title>
    <link rel="stylesheet" href="<?= base_url('Public/templates/Css/home.css') ?>">
    <link rel="shortcut icon" href="<?= base_url('Public/templates/ImageSistema/logo.ico') ?>" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css" rel="stylesheet">


    <style>
        
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
                        <li><a class="linksDashboard" href="<?= base_url('home/tela') ?>"><i class="bi bi-house-door-fill"></i> Dashboard</a></li>
                        <li><a class="linksDashboard" href="#"><i class="bi bi-pencil-square"></i> Anotação</a></li>
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

            <?php if (isset($_SESSION['usuarios_logado'])): ?>

                <div class="navBar">

                    <div class="navName">
                        <h4><?= esc($nome) ?></h4>
                        <p><?= esc($escolaridade) ?></p>
                    </div>

                    <div class="navLogout">
                        <a href="#">
                            <img id="imagePerson" src="<?= base_url($foto) ?>" alt="Foto de perfil">
                        </a>

                        <button type="button" class="btn-logout" data-bs-toggle="modal" data-bs-target="#meuModalSair">
                            Sair <i class="bi bi-box-arrow-right"></i>
                        </button>
                    </div>

                </div>

                <!-- Modal de logout -->
                <div class="modal fade" id="meuModalSair" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered modal-md">
                        <div class="modal-content">

                            <div class="modal-header">
                                <h5 class="modal-title" style="text-align: center !important; font-size: x-large;"></h5>
                            </div>

                            <div class="modal-body">
                                <form action="<?= base_url('login/logout') ?>" method="post">
                                    <h4 class="text-center mt-4 mb-5">Deseja sair da sua conta?</h4>
                                    <p class="text-center">
                                        <button type="button" class="btn-voltar" data-bs-dismiss="modal">Voltar</button>
                                        <button type="submit" class="btn-finalizar" data-bs-dismiss="modal"
                                            data-bs-toggle="modal" data-bs-target="#modal2">Sair</button>
                                    </p>
                                </form>
                            </div>

                            <div class="modal-footer"></div>

                        </div>
                    </div>
                </div>

            <?php endif; ?>

            <!-- ===================== ANOTAÇÃO ===================== -->
            <form action="<?= base_url('anotacao/salvar') ?>" method="post">
                <div class="telaAnotacao mt-5">
                    <input type="hidden" name="experimento_id" value="<?= $dadosAnotacao->experimentos_id ?>">
                    <input type="hidden" name="anotacao_id" value="<?= $anotacao->anotacoes_id ?? '' ?>">

                    <textarea id="my-text-area" class="anotacao" name="anotacoes_conteudo"><?= $anotacao->anotacoes_conteudo ?? '' ?></textarea>

                    <div class="text-center mt-3">
                        <button type="submit" class="btnAnotacao">Salvar anotação</button>
                    </div>
                </div>
            </form>

        </div>

    </div>

    <!-- ===================== FOOTER ===================== -->
    <div class="contentFooter">
        <div class="aboutFooter">
            <h5>Plataforma de monitoramento em ambientes controlados</h5>
        </div>

        <div class="linkFooter">
            <ul>
                <li><a href="#" target="_blank">Home</a></li>
                <li><a href="https://cebiobrasil.com.br/" target="_blank">CEBIO</a></li>
                <li><a href="#" target="_blank">IF Goiano Campus Ceres</a></li>
                <li><a href="#" target="_blank">FAPEG</a></li>
                <li><a href="#" target="_blank">LabTI</a></li>
            </ul>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>

    <script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>
    <script>
        const easymde = new EasyMDE({
            element: document.getElementById('my-text-area'),
        });

        document.querySelector('form').addEventListener('submit', function() {
            easymde.codemirror.save();
        });
    </script>

</body>

</html>