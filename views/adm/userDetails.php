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
    $url_rasp = $_SESSION['url_rasp'];
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
                <?php if (isset($_SESSION['msg'])): ?>
                    <?= msg($_SESSION['msg']['texto'], $_SESSION['msg']['color']); ?>
                    <?php unset($_SESSION['msg']); ?>
                <?php endif; ?>
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
                            <img id="imagePerson" src="<?= base_url($foto) ?>" alt="Foto de perfil">
                        </a>



                        <button type="button" class="btn-logout" data-bs-toggle="modal" data-bs-target="#meuModalSair">
                            Sair <i class="bi bi-box-arrow-right"></i>
                        </button>
                    </div>
                </div>


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


            <div class="titleBack mt-3" style="padding-left: 30px;">
                <h4><a href="<?= base_url('adm/listarUsuarios') ?>"><i class="bi bi-arrow-left" style="color: black"></i></a> &nbsp;Dados pessoais</h4>
            </div>

            <div class="container mt-3" style="background-color: #f0f0f07a; border-radius: 10px; padding: 15px 25px;">
                <h5 class="mt-3"> Detalhes de: <strong><?= $usuarios->usuarios_nome ?></strong> - ID: #0<?= $usuarios->usuarios_id ?></h5>

                <form action="<?= base_url('adm/editarUsuario') ?>" method="post" enctype="multipart/form-data">

                    <div class="row">
                        <div class="col-md-6 mt-5">
                            <label for="usuarios_nome" style="margin-left: 5px;">Nome</label>
                            <input type="text" name="usuarios_nome" id="usuarios_nome" class="form-control" value="<?= $usuarios->usuarios_nome ?>">
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
                            <input type="email" name="usuarios_email" id="usuarios_email" class="form-control" value="<?= $usuarios->usuarios_email ?>">
                        </div>

                        <div class="col-md-6 mt-4">
                            <label for="usuarios_senha_hash" style="margin-left: 5px;">Senha</label>
                            <input type="password" name="usuarios_senha_hash" id="usuarios_senha_hash" class="form-control" placeholder="Digite a senha">
                        </div>

                        <div class="col-md-6 mt-4">
                            <label for="senha" style="margin-left: 5px;">Confirmar senha</label>
                            <input type="password" name="senha" id="senha" class="form-control" placeholder="Confirme sua senha">
                        </div>

                        <div class="col-md-6 mt-4 ">
                            <label for="usuarios_imagem">Foto de perfil</label>
                            <input type="file" name="usuarios_imagem" id="usuarios_imagem" class="form-control" accept="image/*">
                        </div>

                        <div class="col-md-6 mt-4">
                            <label for="usuarios_ativo">Status usuário</label>
                            <select name="usuarios_ativo"
                                class="form-control"
                                id="usuarios_ativo"
                                required>

                                <option value="" disabled>Selecione</option>

                                <option value="1"
                                    <?= $usuarios->usuarios_ativo === 1 ? 'selected' : '' ?>>
                                    Ativo
                                </option>

                                <option value="0"
                                    <?= $usuarios->usurios_ativo === 0 ? 'selected' : '' ?>>
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
                                    <?= $usuarios->usuarios_nivel === 1 ? 'selected' : '' ?>>
                                    Normal
                                </option>

                                <option value="2"
                                    <?= $usuarios->usuarios_nivel === 0 ? 'selected' : '' ?>>
                                    Administrador
                                </option>
                                </option>
                            </select>
                        </div>

                        <div class="col-md-6 mt-4 ">
                            <label for="usuarios_criado_em">Criado em:</label>
                            <input type="text" name="usuarios_criado_em" id="usuarios_criado_em" class="form-control" value=<?= date('d/m/Y', strtotime($usuarios->usuarios_criado_em)) ?> readonly>
                        </div>

                        <div class="col-md-6 mt-4 ">
                            <label for="usuarios_atualizado_em">Atualizado em:</label>
                            <input type="text" name="usuarios_atualizado_em" id="usuarios_atualizado_em" class="form-control" value=<?= date('d/m/Y', strtotime($usuarios->usuarios_atualizado_em)) ?> readonly>
                        </div>

                        <div class="col-md-6 mt-4 ">
                            <label for="usuarios_deletado_em">Deletado em:</label>
                            <input type="text" name="usuarios_atualizado_em" id="usuarios_atualizado_em" class="form-control" value=<?= date('d/m/Y', strtotime($usuarios->usuarios_atualizado_em)) ?> readonly>
                        </div>

                        <input type="hidden" name="usuarios_id" value="<?= $usuarios->usuarios_id ?>">


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
                            <form action="<?= base_url('adm/deletarUsuario/' . rtrim(base64_encode((string) $usuarios->usuarios_id), '=')) ?>" method="post">
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

</body>


</html>