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

        .campoUser {
            display: flex;
            width: 80%;
            margin: 0 auto;
            gap: 40px;

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
                    <a href="<?= base_url('home/tela') ?>"><img src="<?= base_url('Public/templates/ImageSistema/LogoPreta.png') ?>" alt="Logo CEBIO"></a>
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
                        <li>
                            <a class="linksDashboard" href="#">
                                <i class="bi bi-clipboard2-pulse"></i> Plantios
                            </a>
                        </li>

                        <li><a class="linksDashboard" href="#"><i class="bi bi-webcam"></i> Vídeos</a></li>
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
                        <a href="<?= base_url('experimento/editar/' . $usuarioExperimento->experimentos_id) ?>">
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

            <?php
            $limite = 8;
            $totalRegistros = count($usuarios);
            $totalPaginas = max(1, (int) ceil($totalRegistros / $limite));

            $paginaAtual = max(1, (int) ($_GET['pagina'] ?? 1));
            $paginaAtual = min($paginaAtual, $totalPaginas);

            $offset = ($paginaAtual - 1) * $limite;

            $usuariosPaginados = array_slice($usuarios, $offset, $limite);
            ?>

            <div class="listTable">

                <div class="campoUser mt-5">
                    <input type="search" class="form-control" placeholder="Pesquise por um usuário" name="pesquisarUser" id="pesquisarUser">
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createNewUser" style="width: 200px;"> <i class="bi bi-plus-lg"></i> Novo usuário </button>
                </div>


                <div class="modal fade modal-xl" id="createNewUser" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered modal-xl">
                        <div class="modal-content">

                            <div class="modal-header">
                                <h5 class="modal-title mt-3" style="text-align: center !important; font-size: x-large;"><strong>Cadastro de usuário</strong></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <form action="<?= base_url('adm/createUser') ?>" method="post" enctype="multipart/form-data">
                                <div class="modal-body">
                                    <div class="row">

                                        <div class="col-md-6 mt-4">
                                            <label for="usuarios_nome">Nome</label>
                                            <input type="text" class="form-control" name="usuarios_nome" id="nome" placeholder="Informe o nome do usuário" required>
                                        </div>

                                        <div class="col-md-6 mt-4">
                                            <label for="usuarios_escolaridade">Nível de escolaridade</label>
                                            <select name="usuarios_escolaridade"
                                                class="form-control"
                                                id="usuarios_escolaridade"
                                                required>
                                                <option value="" selected disabled>Selecione</option>
                                                <option value="Ensino Médio">Ensino Médio</option>
                                                <option value="Graduação">Graduação</option>
                                                <option value="Mestrado">Mestrado</option>
                                                <option value="Doutorado">Doutorado</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6 mt-4">
                                            <label for="usuarios_email">Email</label>
                                            <input type="email"
                                                name="usuarios_email"
                                                class="form-control"
                                                id="usuarios_email"
                                                required
                                                placeholder="Informe seu email"
                                                value="<?= $_POST['usuarios_email'] ?? '' ?>">
                                        </div>

                                        <div class="col-md-6 mt-4">
                                            <label for="usuarios_senha">Senha</label>
                                            <input type="password"
                                                name="usuarios_senha"
                                                class="form-control"
                                                id="senha"
                                                required
                                                placeholder="Informe sua senha">


                                        </div>

                                        <div class="col-md-6 mt-4 mb-3">
                                            <label for="usuarios_imagem">Foto de perfil</label>
                                            <input type="file"
                                                name="usuarios_imagem"
                                                id="usuarios_imagem"
                                                class="form-control"

                                                accept="image/*">
                                        </div>

                                         <div class="col-md-6 mt-4 mb-3">
                                            <label for="usuarios_nivel">Nível do usuário</label>
                                            <select name="usuarios_nivel"
                                                class="form-control"
                                                id="usuarios_nivel"
                                                required>
                                                <option value="" selected disabled>Selecione</option>
                                                <option value="1">Normal</option>
                                                <option value="2">Administrador</option>
                                            </select>
                                        </div>

                                    </div>



                                </div>
                                <div class="modal-footer">

                                    <button type="submit" class="btn btn-success  mb-3">Cadastar usuário</button>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>

                <div class="tabelaVideos mt-5" style="width: 80%; margin: 0 auto;">
                    <table class="table table-striped ">
                        <thead>
                            <tr>
                                <th scope="col">Perfil</th>
                                <th scope="col">ID</th>
                                <th scope="col">Nome</th>
                                <th scope="col">Escolaridade</th>
                                <th scope="col">Ativo</th>
                                <th scope="col">Nível</th>
                                <th scope="col">Detalhar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuariosPaginados as $user): ?>
                                <tr>
                                    <th scope="row">
                                        <img
                                            src="<?= esc(base_url($user->usuarios_imagem)) ?>"
                                            alt="Foto do usuário"
                                            style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                                    </th>
                                    <td>#0<?= esc($user->usuarios_id) ?></td>
                                    <td><?= esc(mb_strimwidth($user->usuarios_nome, 0, '50', '...')) ?></td>
                                    <td><?= esc($user->usuarios_escolaridade) ?></td>
                                    <td> <?= $user->usuarios_ativo == '1' ? 'Ativo' : 'Inativo' ?>
                                    </td>
                                    <td> <?= $user->usuarios_nivel == '1' ? 'Normal' : 'Adm' ?>
                                    </td>

                                    <form action="<?= base_url('adm/detailsUser/' . rtrim(base64_encode((string) $user->usuarios_id), '=')) ?>" method="get">
                                        <td><button type="submit" class="btn btn-outline-primary">Ver mais</button></td>

                                    </form>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>



            </div>

            <!-- Paginação -->

            <?php if ($totalPaginas > 1): ?>
                <nav aria-label="Paginação dos experimentos">
                    <ul class="pagination justify-content-center mt-5">

                        <!-- Anterior -->
                        <li class="page-item <?= $paginaAtual === 1 ? 'disabled' : '' ?>">
                            <?php if ($paginaAtual > 1): ?>
                                <a class="page-link" href="?pagina=<?= $paginaAtual - 1 ?>">
                                    Anterior
                                </a>
                            <?php else: ?>
                                <span class="page-link">Anterior</span>
                            <?php endif; ?>
                        </li>

                        <!-- Números das páginas -->
                        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                            <li class="page-item <?= $i === $paginaAtual ? 'active' : '' ?>">

                                <?php if ($i === $paginaAtual): ?>
                                    <span class="page-link" aria-current="page">
                                        <?= $i ?>
                                    </span>
                                <?php else: ?>
                                    <a class="page-link" href="?pagina=<?= $i ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endif; ?>

                            </li>
                        <?php endfor; ?>

                        <!-- Próxima -->
                        <li class="page-item <?= $paginaAtual === $totalPaginas ? 'disabled' : '' ?>">
                            <?php if ($paginaAtual < $totalPaginas): ?>
                                <a class="page-link" href="?pagina=<?= $paginaAtual + 1 ?>">
                                    Próxima
                                </a>
                            <?php else: ?>
                                <span class="page-link">Próxima</span>
                            <?php endif; ?>
                        </li>

                    </ul>
                </nav>
            <?php endif; ?>

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