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
            gap: 8px;
        }

        .imgInfo {
            flex-shrink: 0;
        }

        .imgInfoQuant {
            display: block;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .textInfo {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            flex: 1;
            min-width: 0;
        }

        .nomeInfo {
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .nomeInfo p {
            margin: 0;
            font-size: 11pt;
        }

        .totInfo {
            background-color: green;
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .totInfo p {
            margin: 0;
            font-size: 13pt;
        }

        .infoList {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .infoList .groupInfo {
            box-sizing: border-box;
            flex: 1 1 260px;
            min-width: 0;
            max-width: 350px;
        }

        .nomeInfo {
            min-width: 0;
            overflow-wrap: break-word;
        }

        @media (max-width: 600px) {
            .infoList {
                flex-direction: column;
                gap: 16px;
            }

            .infoList .groupInfo {
                flex: auto;
                width: 100%;
                max-width: none;
                margin: 0;
            }
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
                            <a class="linksDashboard" href="#">
                                <i class="bi bi-house-door-fill"></i> Dashboard
                            </a>
                        </li>
                        <li>
                            <a class="linksDashboard" href="<?= base_url('adm/listarUsuarios') ?>">
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
                        <a href="">
                            <img id="imagePerson" src="<?= esc(base_url($foto)) ?>" alt="Foto de perfil">
                        </a>


                        <button type="button" class="btn-logout" data-bs-toggle="modal" data-bs-target="#meuModalSair">
                            Sair <i class="bi bi-box-arrow-right"></i>
                        </button>
                    </div>
                </div>


                <?php partial('logout_modal'); ?>

            <?php endif; ?>

            <div class="infoList">
                <div class="groupInfo">
                    <div class="infoTitle">
                        <h5>Experimentos</h5>
                        <p><strong>Total cadastrado: <?= (int) $quantidadeExperimentos ?></strong></p>

                    </div>

                    <div class="infoQuant">
                        <div class="imgInfo">
                            <img class="imgInfoQuant" src="<?= esc(base_url($topExperimentos->imagem ?? 'Public/uploadImages/Noimage.png')) ?>" alt="Foto de perfil">

                        </div>
                        <div class="textInfo">
                            <div class="nomeInfo">

                                <p style="font-size: 11pt;"><?= esc($topExperimentos->nome ?? '-') ?></p>
                            </div>
                            <div class="totInfo">
                                <p><?= (int) ($topExperimentos->total_experimentos ?? 0) ?></p>

                            </div>

                        </div>
                    </div>
                </div>

                <div class="groupInfo">
                    <div class="infoTitle">
                        <h5>Total usuários: </h5>
                        <p><strong>Total cadastrado <?= (int) $totalUsuarios ?></strong></p>

                    </div>


                </div>

            </div>

            

            <div class="listTable">
                <h4 class="mt-5" style="margin-left: 20px;">Lista de experimentos</h4>

                <div class="tabelaVideos table-responsive mt-5" style="width: 80%; margin: 0 auto;">
                    <table class="table table-striped ">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Experimento</th>
                                <th scope="col">Usuário</th>
                                <th scope="col">Data de ínico</th>
                                <th scope="col">Sala</th>
                                <th scope="col">Status</th>
                                <th scope="col">Detalhar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($experimentosPaginados as $user): ?>
                                <tr>
                                    <th scope="row"> #0<?= esc($user->experimentos_id) ?></th>
                                    <td><?= esc(mb_strimwidth($user->experimentos_titulo, 0, '50', '...')) ?></td>
                                    <td><?= esc($user->usuarios_nome) ?></td>
                                    <td><?= date('d-m-Y', strtotime($user->experimentos_inicio)) ?></td>
                                    <td><?= esc($user->experimentos_sala) ?></td>
                                    <td style="color: <?= $user->experimentos_status == 'Ativo' ? 'green' : 'red' ?>;">
                                        <?= htmlspecialchars($user->experimentos_status) ?>
                                    </td>

                                    <form action="<?= base_url('adm/infoUser/' . id_url((int) $user->experimentos_id)) ?>" method="get">
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


    <?php partial('footer', ['estilo' => 'height: auto;']); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>

    <script>
        const BASE_URL = <?= json_encode(base_url()) ?>;
    </script>

    <script>
        window.SENSORES_URL = <?= json_encode(base_url('home/sensores')) ?>;
    </script>
    <script src="<?= base_url('/Public/Js/javascript.js') ?>"></script>

</body>


</html>