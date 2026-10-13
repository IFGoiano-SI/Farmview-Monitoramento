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
        .container {
            border-radius: 5px;
            padding: 10px;
            border-left: 3px solid var(--corPrincipal);
            box-shadow: 1px 1px 4px rgba(0, 0, 0, 0.13);
            padding-bottom: 25px;
        }

        .botoes {
            display: flex;
            justify-content: flex-end;
            gap: 30px;
        }

        .btn-deletar {
            background-color: var(--corVermelha);
            color: white;
            width: 200px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            height: 35px;
        }

        .btn-deletar:hover {
            transform: translate(2px, -4px);
        }

        .btn-modal {
            background-color: var(--corPrincipal);
            color: white;
            width: 200px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            height: 35px;
        }

        .btn-modal:hover {
            transform: translate(2px, -4px);
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
                        <li><a class="linksDashboard" href="<?= base_url('home/tela') ?>"><i class="bi bi-house-door-fill"></i> Dashboard</a></li>
                        <li><a class="linksDashboard" href="<?= base_url('anotacao/index') ?>"><i class="bi bi-pencil-square"></i> Anotação</a></li>
                        <li><a class="linksDashboard" href="<?= base_url('experimento/listarExperimentos') ?>"><i class="bi bi-clipboard2-pulse"></i> Plantio</a></li>
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
                <?php if (isset($_SESSION['msg'])): ?>
                    <?= msg($_SESSION['msg']['texto'], $_SESSION['msg']['color']); ?>
                    <?php unset($_SESSION['msg']); ?>
                <?php endif; ?>
            </div>

            <?php if (isset($_SESSION['usuarios_logado'])): ?>
                <?php
                $foto = $_SESSION['usuarios_logado']->usuarios_imagem;
                $nome = $_SESSION['usuarios_logado']->usuarios_nome;
                $escolaridade = $_SESSION['usuarios_logado']->usuarios_escolaridade;
                ?>

                <div class="navBar">

                    <div class="navName">
                        <div class="navName">
                            <h4><?= esc($nome) ?></h4>
                            <p><?= esc($escolaridade) ?></p>
                        </div>
                      
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
                                        <button type="submit" class="btn-modal" data-bs-dismiss="modal"
                                            data-bs-toggle="modal" data-bs-target="#modal2">Sair</button>
                                    </p>
                                </form>
                            </div>

                            <div class="modal-footer"></div>

                        </div>
                    </div>
                </div>

            <?php endif; ?>

            <!-- ===================== FORMULÁRIO DE EDIÇÃO ===================== -->
            <div class="container mt-5">
                <h5>Alterar o experimento: <strong><?= $editarExperimentos->experimentos_titulo ?></strong></h5>

                <form action="<?= base_url('experimentos/editarExperimentosAtivo/' . $editarExperimentos->experimentos_id) ?>" method="post">

                    <div class="row">

                        <div class="col-md-6 mt-5">
                            <label for="experimentos_titulo">Título do experimento</label>
                            <input type="text" name="experimentos_titulo" class="form-control" id="titulo"
                                placeholder="Informe o nome do experimento" value="<?= $editarExperimentos->experimentos_titulo ?>">
                        </div>

                        <div class="col-md-6 mt-5">
                            <label for="experimentos_inicio">Data de início</label>
                            <input type="date" name="experimentos_inicio" class="form-control" id="inicio"
                                value="<?= $editarExperimentos->experimentos_inicio ?>">
                        </div>

                        <div class="col-md-6 mt-5">
                            <label for="experimentos_final">Data final</label>
                            <input type="date" name="experimentos_final" class="form-control" id="final"
                                value="<?= $editarExperimentos->experimentos_final ?>">
                        </div>

                        <div class="col-md-6 mt-5">
                            <label for="experimentos_sala">Sala experimento</label>
                            <input type="text" name="experimentos_sala" class="form-control" id="sala"
                                placeholder="Informe a sala do experimento" value="<?= $editarExperimentos->experimentos_sala ?>">
                        </div>

                        <div class="botoes mt-5">
                            <button type="button" onclick="window.location.href='<?= base_url('experimentos/excluirExperimento/' . $editarExperimentos->experimentos_id) ?>'" id="deletar" class="btn-deletar">Deletar experimento</button>
                            <button type="submit" id="editar" class="btn-modal">Editar experimento</button>
                        </div>

                    </div>

                </form>
            </div>

        </div>

    </div>

    <!-- ===================== FOOTER ===================== -->
    <footer class="contentFooter">

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


    <script>
        // BtnDeletar = document.getElementById('deletar');
        // BtnEditar = document.getElementById('editar');

        // BtnDeletar.addEventListener('click', () => {
        //     BtnDeletar.disabled = true;
        //     BtnEditar.disabled = true;
        // });

        // BtnEditar.addEventListener('click', () => {
        //     BtnDeletar.disabled = true;
        //     BtnEditar.disabled = true;
        // });
    </script>

</body>

</html>