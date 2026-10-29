<?php redirectPages(); ?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmView</title>

    <link rel="shortcut icon" href="<?= base_url('Public/templates/ImageSistema/LogoSistema.ico') ?>">
    <link rel="stylesheet" href="<?= base_url('Public/templates/Css/home.css') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css" rel="stylesheet">


    <style>
        .linkVideo,
        .download {
            text-decoration: none !important;
            color: black;
        }

        .headerTable {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            width: 90%;
        }

        .contentScreen {
            min-width: 0;
        }


        .btnLimparTabela {
            width: 150px;
            height: 40px;
            border: none;
            background-color: var(--corVermelha);
            border-radius: 5px;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s all ease;
        }

        .btnLimparTabela:hover {
            transform: translateY(-2px);
        }

        .tabelaVideos {
            width: 80%;
            margin: 0 auto;
            overflow-x: auto;
        }

        .tabelaVideos table {
            width: 100%;
            white-space: nowrap;
        }

        @media (max-width: 768px) {
            .tabelaVideos {
                width: 95%;
            }

            .headerTable{
                width: 100%;
            }

            .titulo {
                display: flex;
                justify-content: start;
            }

            .titulo h4 {
                margin-left: 2px !important;
                font-size: 14pt;
                margin-bottom: 0px !important;
                font-weight: bold;
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
                    <a href="<?= base_url('home/tela') ?>"><img src="<?= base_url('Public/templates/ImageSistema/LogoPreta.png') ?>" alt="Logo CEBIO"></a>
                </div>

                <div class="leftLinks">
                    <ul>
                        <li><a class="linksDashboard" href="<?= base_url('home/tela') ?>"><i class="bi bi-house-door-fill"></i> Dashboard</a></li>
                        <li><a class="linksDashboard" href="<?= base_url('anotacao/index') ?>"><i class="bi bi-pencil-square"></i> Anotação</a></li>
                        <li><a class="linksDashboard" href="<?= base_url('experimento/listarExperimentos') ?>"><i class="bi bi-clipboard2-pulse"></i> Plantio</a></li>
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



            <div class="gravacao">

                <div class="headerTable">
                    <div class="titulo">
                        <h4 style="margin-left: 70px;" class="mt-5 mb-3">Veja suas últimas gravações </h4>
                    </div>

                    <?php
                    $videosAtivos = array_values(array_filter(
                        $dadosVideo,
                        fn($video) => $video->videos_status === 'Ativo'
                    ));

                    $totalRegistros = count($videosAtivos);
                    $temVideoAtivo = $totalRegistros > 0;

                    $porPagina = 5;
                    $totalPaginas = max(1, (int) ceil($totalRegistros / $porPagina));

                    $pagina = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT);
                    $pagina = max(1, min($pagina ?: 1, $totalPaginas));

                    $inicio = ($pagina - 1) * $porPagina;

                    $videosPagina = array_slice($videosAtivos, $inicio, $porPagina);
                    ?>

                    <?php if ($temVideoAtivo): ?>
                        <div class="buttonLimparTabela">
                            <input type="hidden" name="deletado_em" value="<?= $_SESSION['usuarios_logado']->usuarios_id ?>">
                            <button type="submit" class="btnLimparTabela" data-bs-toggle="modal" data-bs-target="#limparRegistro"> Limpar registros</button>
                        </div>

                        <div class="modal fade" id="limparRegistro" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered modal-md">
                                <div class="modal-content">

                                    <div class="modal-header">
                                        <h5 class="modal-title" style="text-align: center !important; font-size: x-large;"></h5>
                                    </div>

                                    <div class="modal-body">
                                        <form action="<?= base_url('gravacao/deletarAll') ?>" method="post">
                                            <h4 class="text-center mt-2">Deseja deletar todos os vídeos da lista?</h4>
                                            <p class="text-center mb-3">Ao deletar os vídeos, você não terá mais acesso a eles, tem certeza?</p>
                                            <div style="display: flex; justify-content:center; gap: 10px;">
                                                <button
                                                    type="button" data-bs-dismiss="modal" class="btn btn-danger mt-3" style="width: 100px; "> Não</button>

                                                <button type="submit" class="btn btn-success mt-3" style="width: 100px;">Sim</button>
                                                <input type="hidden" name="usuarios_id" value="<?= $_SESSION['usuarios_logado']->usuarios_id ?>">
                                            </div>
                                        </form>
                                    </div>

                                    <div class="modal-footer"></div>

                                </div>
                            </div>
                        </div>

                    <?php endif; ?>




                </div>

                <?php if (!$temVideoAtivo): ?>
                    <p style="margin-left: 70px; margin-top:30px; font-size: 13pt;">Não há nenhum vídeo para ser listado.</p>


                <?php else: ?>

                    <div class="tabelaVideos mt-5">
                        <table class="table table-striped ">
                            <thead>
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Experimento</th>
                                    <th scope="col">Arquivo</th>
                                    <th scope="col">Duração</th>
                                    <th scope="col">Tamanho</th>
                                    <th scope="col">Download</th>
                                    <th scope="col">Gravado em:</th>
                                    <th scope="col">Deletar vídeo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($videosPagina as $video): ?>
                                    <tr>
                                        <th scope="row"> #0<?= esc($video->videos_id) ?></th>
                                        <td><?= esc(mb_strimwidth($video->experimentos_titulo, 0, '50', '...')) ?></td>
                                        <td><?= esc($video->path_video) ?></td>
                                        <td><?= esc($video->duracao_segundos) ?> segundo(s)</td>
                                        <td><?= number_format($video->tamanho_bytes / 1000000, 2, ',', '.') ?> MB </td>
                                        <td class="download text-start"><a style="text-decoration: none; color: black;" href="<?= base_url($video->path_video) ?>" download><i class="bi bi-download "></i> &nbsp; baixar</a></td>
                                        <td><?= date('d/m/Y - H:i:s', strtotime($video->criado_em)) ?></td>
                                        <td><button type="submit" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deletar-<?= $video->videos_id ?>">Deletar</button></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>



                    <?php foreach ($videosPagina as $video): ?>
                        <div
                            class="modal fade"
                            id="deletar-<?= esc($video->videos_id) ?>"
                            tabindex="-1"
                            aria-hidden="true">

                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Deletar vídeo</h5>

                                        <button
                                            type="button"
                                            class="btn-close"
                                            data-bs-dismiss="modal"
                                            aria-label="Fechar">
                                        </button>
                                    </div>

                                    <form action="<?= base_url('gravacao/deletarUnique') ?>" method="post">
                                        <input
                                            type="hidden"
                                            name="videos_id"
                                            value="<?= esc($video->videos_id) ?>">


                                            <input type="hidden" name="path_video" value="<?= esc($video->path_video) ?>">

                                        <div class="modal-body text-center">
                                            <h4>Deseja deletar o video <strong>#0<?= $video->videos_id ?></strong> da tabela?</h4>
                                            <p>Ao deletar, você não conseguirá fazer o download dele!</p>
                                        </div>

                                        <div class="modal-footer" style="display: flex; justify-content:center">
                                            <button
                                                type="button" data-bs-dismiss="modal" class="btn btn-danger" style="width: 100px;"> Não</button>

                                            <button type="submit" class="btn btn-success" style="width: 100px;">Sim</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                <?php endif; ?>

                <?php if ($temVideoAtivo && $totalPaginas > 1): ?>
                    <nav aria-label="Paginação dos vídeos">
                        <ul class="pagination justify-content-center mt-5">

                            <li class="page-item <?= $pagina === 1 ? 'disabled' : '' ?>">
                                <?php if ($pagina > 1): ?>
                                    <a class="page-link" href="?pagina=<?= $pagina - 1 ?>">
                                        Anterior
                                    </a>
                                <?php else: ?>
                                    <span class="page-link">Anterior</span>
                                <?php endif; ?>
                            </li>

                            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                                <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
                                    <?php if ($i === $pagina): ?>
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

                            <li class="page-item <?= $pagina === $totalPaginas ? 'disabled' : '' ?>">
                                <?php if ($pagina < $totalPaginas): ?>
                                    <a class="page-link" href="?pagina=<?= $pagina + 1 ?>">
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

    <script src="https://cdn.tiny.cloud/1/o6z5ag4bp5rs2fcu26yt81z9zyy2juzfw464xmplay588s0l/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>


</body>

</html>