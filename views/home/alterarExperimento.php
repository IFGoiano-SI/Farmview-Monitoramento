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
                <?php partial('alerts'); ?>
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
                            <img id="imagePerson" src="<?= esc(base_url($foto)) ?>" alt="Foto de perfil">
                        </a>

                        <button type="button" class="btn-logout" data-bs-toggle="modal" data-bs-target="#meuModalSair">
                            Sair <i class="bi bi-box-arrow-right"></i>
                        </button>
                    </div>

                </div>

                <?php partial('logout_modal'); ?>

            <?php endif; ?>

            <!-- ===================== FORMULÁRIO DE EDIÇÃO ===================== -->
            <div class="container mt-5">
                <h5>Alterar o experimento: <strong><?= esc($editarExperimentos->experimentos_titulo) ?></strong></h5>

                <form action="<?= base_url('experimentos/editarExperimentosAtivo/' . (int) $editarExperimentos->experimentos_id) ?>" method="post">
<?= csrf_field() ?>

                    <div class="row">

                        <div class="col-md-6 mt-5">
                            <label for="experimentos_titulo">Título do experimento</label>
                            <input type="text" name="experimentos_titulo" class="form-control" id="titulo"
                                placeholder="Informe o nome do experimento" maxlength="80" value="<?= esc($editarExperimentos->experimentos_titulo) ?>">
                        </div>

                        <div class="col-md-6 mt-5">
                            <label for="experimentos_inicio">Data de início</label>
                            <input type="date" name="experimentos_inicio" class="form-control" id="inicio"
                                value="<?= esc($editarExperimentos->experimentos_inicio) ?>">
                        </div>

                        <div class="col-md-6 mt-5">
                            <label for="experimentos_final">Data final</label>
                            <input type="date" name="experimentos_final" class="form-control" id="final"
                                value="<?= esc($editarExperimentos->experimentos_final) ?>">
                        </div>

                        <div class="col-md-6 mt-5">
                            <label for="experimentos_sala">Sala experimento</label>
                            <input type="text" name="experimentos_sala" class="form-control" id="sala"
                                placeholder="Informe a sala do experimento" maxlength="30" value="<?= esc($editarExperimentos->experimentos_sala) ?>">
                        </div>

                        <div class="botoes mt-5">
                            <button type="button" id="deletar" class="btn-deletar" data-bs-toggle="modal" data-bs-target="#modalExcluirExperimento">Deletar experimento</button>
                            <button type="submit" id="editar" class="btn-modal">Editar experimento</button>
                        </div>

                    </div>

                </form>

                <!-- Confirmação de exclusão (POST + CSRF) -->
                <div class="modal fade" id="modalExcluirExperimento" tabindex="-1" aria-labelledby="tituloExcluirExperimento" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <form action="<?= base_url('experimentos/excluirExperimento/' . (int) $editarExperimentos->experimentos_id) ?>" method="post">
                                <?= csrf_field() ?>
                                <div class="modal-body text-center">
                                    <h4 id="tituloExcluirExperimento" class="mt-3">Deseja excluir este experimento?</h4>
                                    <p>Os dispositivos da bancada serão desligados e o experimento sairá da sua lista.</p>
                                </div>
                                <div class="modal-footer justify-content-center">
                                    <button type="button" class="btn-voltar" data-bs-dismiss="modal">Voltar</button>
                                    <button type="submit" class="btn-deletar">Excluir</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <?php partial('footer'); ?>

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