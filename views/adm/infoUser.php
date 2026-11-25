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
        .infoList {
            display: flex;
            gap: 80px;
            padding-left: 20px;
            margin-top: 30px;
        }

        .groupInfo {
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #0000002c;
            width: 300px;
            display: flex;
            flex-flow: column;
            box-shadow: 1px 1px 3px #00000010;
        }

        .infoTitle p {
            font-size: 12pt;
            color: #7c7c7c;
        }

        .infoQuant {
            display: flex;
            align-items: center;
        }

        .imgInfoQuant {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .textInfo {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .nomeInfo {
            margin-left: 8px;
            display: flex;
        }

        .totInfo {
            background-color: green;
            color: white;
            border-radius: 100%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding-top: 15px;

        }


        .totInfo p {
            font-size: 13pt;
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
                    <a href="<?= base_url('adm/index') ?>"><img src="<?= base_url('Public/templates/ImageSistema/LogoPreta.png') ?>" alt="Logo CEBIO"></a>
                </div>

                <div class="leftLinks">
                    <ul>
                        <li>
                            <a class="linksDashboard" href="<?= base_url('adm/index') ?>">
                                <i class="bi bi-house-door-fill"></i> Dashboard
                            </a>
                        </li>
                        <li>
                            <a class="linksDashboard" href="#">
                                <i class="bi bi-person-plus-fill"></i> Usuários
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
                <?php partial('alerts'); ?>
            </div>

            <!-- ===================== NAVBAR ===================== -->
            <?php if (isset($_SESSION['usuarios_logado'])): ?>

                <div class="navBar">
                    <div class="navName">
                        <h4>Olá, <?= esc($nome) ?>!</h4>
                        <p style="width: 150px; background-color: ligthgreen"> Administrador</p>
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

            <div class="titleBack mt-3" style="padding-left: 30px;">
                <h4><a href="<?= base_url('adm/index') ?>"><i class="bi bi-arrow-left" style="color: black"></i></a> &nbsp;Mais informações</h4>
            </div>

            <div class="cardInfo" style="padding-left: 30px; margin-top:40px;">
                <div class="card" style="width: 18rem; ">
                    <img id="imagemExperimentos" src="<?= esc(base_url($infoUser->experimentos_imagem)) ?>" class="card-img-top" alt="...">

                    <div class="card-body">
                        <h5 class="card-title"><strong><?= esc($infoUser->experimentos_titulo) ?></strong></h5>
                        <p class="card-text text-small"> <strong>Experimento de: </strong> <?= esc($infoUser->usuarios_nome) ?></p>

                        <div class="butaoCard" style="display: flex; gap: 10px">
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#listaExperimentos">
                                Ver mais <i class="bi bi-plus"></i>
                            </button>

                            <?php if ($infoUser->experimentos_status != 'Ativo'): ?>

                                <form action="<?= base_url('adm/ativarExperimentos') ?>" method="post">
<?= csrf_field() ?>

                                                                        <input type="hidden" name="experimentos_id" value="<?= (int) $infoUser->experimentos_id ?>">

                                    <button type="submit" class="btn btn-warning">Ativar</button>


                                </form>

                            <?php endif; ?>

                        </div>



                    </div>
                </div>
            </div>


            <div class="modal fade modal-xl" id="listaExperimentos" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered modal-xl">
                    <div class="modal-content">

                        <div class="modal-header">
                            <h5 class="modal-title mt-3" style="text-align: center !important; font-size: x-large;">Plantio de <strong><?= esc($infoUser->usuarios_nome) ?></strong></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <form action="<?= base_url('adm/editarExperimentos') ?>" method="post" enctype="multipart/form-data">
<?= csrf_field() ?>
                            <div class="modal-body">
                                <div class="row">

                                    <!-- Id do experimento -->
                                    <input type="hidden" name="experimentos_id" value="<?= (int) $infoUser->experimentos_id ?>">
                                    <h5><i class="bi bi-leaf"></i>&nbsp; Experimentos</h5>

                                    <div class="col-md-6 mt-4">
                                        <label for="experimentos_titulo">Título</label>
                                        <input type="text" class="form-control" name="experimentos_titulo" id="titulo" placeholder="Experimentos informado pelo usuário" value="<?= esc($infoUser->experimentos_titulo) ?>">
                                    </div>


                                    <div class="col-md-6 mt-4">
                                        <label for="experimentos_inicio">Início</label>
                                        <input type="date" class="form-control" name="experimentos_inicio" id="inicio" placeholder="Data informada pelo usuário" value="<?= esc($infoUser->experimentos_inicio) ?>">
                                    </div>

                                    <div class="col-md-6 mt-4">
                                        <label for="experimentos_final">Final</label>
                                        <input type="date" class="form-control" name="experimentos_final" id="final" placeholder="Data informada pelo usuário" value="<?= esc($infoUser->experimentos_final) ?>">
                                    </div>

                                    <div class="col-md-6 mt-4">
                                        <label for="experimentos_sala">Sala</label>
                                        <input type="text" class="form-control" name="experimentos_sala" id="sala" placeholder="Sala informada pelo usuário" value="<?= esc($infoUser->experimentos_sala) ?>">
                                    </div>

                                    <div class="col-md-6 mt-4">
                                        <label for="experimentos_status">Status</label>
                                        <input type="text" class="form-control" name="experimentos_status" id="sala" placeholder="Status do experimento" value="<?= esc($infoUser->experimentos_status) ?>" readonly>
                                    </div>


                                    <div class="col-md-6 mt-4">
                                        <label for="experimentos_encerrado">Data de encerramento</label>
                                        <input type="text" class="form-control" name="experimentos_encerrado" id="encerrado" placeholder="Data encerrada" value="<?= data_br($infoUser->experimentos_encerrado, 'd/m/Y H:i:s') ?>" readonly>
                                    </div>


                                    <h5 class="mt-5"><i class="bi bi-flask "></i> &nbsp;Bancadas</h5>

                                    <div class="col-md-6 mt-3">
                                        <label for="bancadas_tipo_plantacao">Plantação</label>
                                        <input type="text" class="form-control" name="bancadas_tipo_plantacao" id="tipo_plantacao" placeholder="Tipo de plantação" value="<?= esc($infoUser->bancadas_tipo_plantacao) ?>">
                                    </div>

                                    <div class="col-md-6 mt-4">
                                        <label for="bancadas_nome_cientifico">Nome científico</label>
                                        <input type="text" class="form-control" name="bancadas_nome_cientifico" id="nome_cientifico" placeholder="Nome científico" value="<?= esc($infoUser->bancadas_nome_cientifico) ?>">
                                    </div>


                                    <div class="col-md-6 mt-4">
                                        <label for="bancadas_quantidade">Quantidade</label>
                                        <input type="number" class="form-control" name="bancadas_quantidade" id="bancadas_quantidade" placeholder="Quantidade" value="<?= esc($infoUser->bancadas_quantidade) ?>">
                                    </div>


                                    <div class="col-md-6 mt-4">
                                        <label for="bancadas_luzes">Luzes</label>
                                        <input type="text" class="form-control" name="bancadas_luzes" id="bancadas_luzes" placeholder="Quantidade" value="<?= esc(luzes_texto($infoUser->bancadas_luzes, 'Nenhuma')) ?>" readonly>
                                    </div>




                                </div>



                            </div>
                            <div class="modal-footer">

                                <button type="submit" class="btn btn-success">Atualizar dados</button>
                            </div>

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

</body>


</html>