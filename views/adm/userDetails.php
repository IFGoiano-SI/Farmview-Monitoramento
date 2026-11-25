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
                <h4><a href="<?= base_url('adm/listarUsuarios') ?>"><i class="bi bi-arrow-left" style="color: black"></i></a> &nbsp;Dados pessoais</h4>
            </div>

            <div class="container mt-3" style="background-color: #f0f0f07a; border-radius: 10px; padding: 15px 25px;">
                <h5 class="mt-3"> Detalhes de: <strong><?= esc($usuarios->usuarios_nome) ?></strong> - ID: #0<?= (int) $usuarios->usuarios_id ?></h5>

                <form action="<?= base_url('adm/editarUsuario') ?>" method="post" enctype="multipart/form-data">
<?= csrf_field() ?>

                    <div class="row">
                        <div class="col-md-6 mt-5">
                            <label for="usuarios_nome" style="margin-left: 5px;">Nome</label>
                            <input type="text" name="usuarios_nome" id="usuarios_nome" class="form-control" value="<?= esc($usuarios->usuarios_nome) ?>" maxlength="40">
                        </div>

                        <div class="col-md-6 mt-5">
                            <label for="usuarios_escolaridade">Nível de escolaridade</label>
                            <select name="usuarios_escolaridade"
                                class="form-control"
                                id="usuarios_escolaridade"
                                required>

                                <option value="" disabled>Selecione</option>

                                <option value="Ensino Médio"
                                    <?= $usuarios->usuarios_escolaridade === "Ensino Médio" ? 'selected' : '' ?>>
                                    Ensino Médio
                                </option>

                                <option value="Graduação"
                                    <?= $usuarios->usuarios_escolaridade === "Graduação" ? 'selected' : '' ?>>
                                    Graduação
                                </option>

                                <option value="Mestrado"
                                    <?= $usuarios->usuarios_escolaridade === "Mestrado" ? 'selected' : '' ?>>
                                    Mestrado
                                </option>

                                <option value="Doutorado"
                                    <?= $usuarios->usuarios_escolaridade === "Doutorado" ? 'selected' : '' ?>>
                                    Doutorado
                                </option>
                            </select>
                        </div>

                        <div class="col-md-6 mt-4">
                            <label for="usuarios_email" style="margin-left: 5px;">Email</label>
                            <input type="email" name="usuarios_email" id="usuarios_email" class="form-control" value="<?= esc($usuarios->usuarios_email) ?>">
                        </div>

                        <div class="col-md-6 mt-4">
                            <label for="usuarios_senha_hash" style="margin-left: 5px;">Senha</label>
                            <input type="password" name="nova_senha" id="usuarios_senha_hash" class="form-control" placeholder="Deixe em branco para manter" autocomplete="new-password" minlength="<?= \Services\Validacao::SENHA_MINIMA ?>">
                        </div>

                        <div class="col-md-6 mt-4">
                            <label for="senha" style="margin-left: 5px;">Confirmar senha</label>
                            <input type="password" name="confirmar_senha" id="senha" class="form-control" placeholder="Confirme a nova senha" autocomplete="new-password">
                        </div>

                        <div class="col-md-6 mt-4 ">
                            <label for="usuarios_imagem">Foto de perfil</label>
                            <input type="file" name="usuarios_imagem" id="usuarios_imagem" class="form-control" accept="image/png, image/jpeg">
                        </div>

                        <div class="col-md-6 mt-4">
                            <label for="usuarios_ativo">Status usuário</label>
                            <select name="usuarios_ativo"
                                class="form-control"
                                id="usuarios_ativo"
                                required>

                                <option value="" disabled>Selecione</option>

                                <option value="1"
                                    <?= (int) $usuarios->usuarios_ativo === 1 ? 'selected' : '' ?>>
                                    Ativo
                                </option>

                                <option value="0"
                                    <?= (int) $usuarios->usuarios_ativo === 0 ? 'selected' : '' ?>>
                                    Inativo
                                </option>
                                </option>
                            </select>
                        </div>

                        <div class="col-md-6 mt-4">
                            <label for="usuarios_nivel">Tipo de usuário</label>
                            <select name="usuarios_nivel"
                                class="form-control"
                                id="usuarios_nivel"
                                required>

                                <option value="" disabled>Selecione</option>

                                <option value="1"
                                    <?= (int) $usuarios->usuarios_nivel === 1 ? 'selected' : '' ?>>
                                    Normal
                                </option>

                                <option value="2"
                                    <?= (int) $usuarios->usuarios_nivel === 2 ? 'selected' : '' ?>>
                                    Administrador
                                </option>
                                </option>
                            </select>
                        </div>

                        <div class="col-md-6 mt-4 ">
                            <label for="usuarios_criado_em">Criado em:</label>
                            <input type="text" id="usuarios_criado_em" class="form-control" value="<?= data_br($usuarios->usuarios_criado_em) ?>" readonly>
                        </div>

                        <div class="col-md-6 mt-4 ">
                            <label for="usuarios_atualizado_em">Atualizado em:</label>
                            <input type="text" id="usuarios_atualizado_em" class="form-control" value="<?= data_br($usuarios->usuarios_atualizado_em) ?>" readonly>
                        </div>

                        <div class="col-md-6 mt-4 ">
                            <label for="usuarios_deletado_em">Deletado em:</label>
                            <input type="text" id="usuarios_deletado_em" class="form-control" value="<?= data_br($usuarios->usuarios_deletado_em) ?>" readonly>
                        </div>

                        <input type="hidden" name="usuarios_id" value="<?= (int) $usuarios->usuarios_id ?>">


                        <div class="userBotao mt-3" style="display: flex; gap: 10px; justify-content: flex-end;">
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalDeletarConta">Deletar usuário</button>
                            <button type="submit" class="btn btn-success"> Atualizar usuário</button>
                        </div>

                    </div>
                </form>
            </div>

            <div class="modal fade" id="modalDeletarConta" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered modal-md">
                    <div class="modal-content">

                        <div class="modal-header">
                            <h5 class="modal-title" style="text-align: center !important; font-size: x-large;"></h5>
                        </div>

                        <div class="modal-body">
                            <form action="<?= base_url('adm/deletarUsuario/' . id_url((int) $usuarios->usuarios_id)) ?>" method="post">
<?= csrf_field() ?>
                                <h4 class="text-center mt-4 mb-5">Deseja excluír usuário?</h4>
                                <p class="text-center">
                                    <button type="button" class="btn btn-secondary" style="width: 100px;" data-bs-dismiss="modal">Não</button>
                                    <button type="submit" class="btn btn-primary" style="width: 100px;" data-bs-dismiss="modal"
                                        data-bs-toggle="modal" data-bs-target="#modal2">Sim</button>
                                </p>
                            </form>
                        </div>

                        <div class="modal-footer"></div>

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