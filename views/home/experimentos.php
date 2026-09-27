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
        /* Imagem do card na listagem (id, não class) */
        #imagemExperimentos {
            width: 200px;
            height: 200px;
            object-fit: cover;
            display: block;
            margin: 16px auto 0;
            /* centraliza horizontalmente */
            /* border-radius: 50%; */
            /* opcional, deixa redondo tipo avatar */
        }

        /* ===================== MODAL - INFORMAÇÕES DO EXPERIMENTO ===================== */

        .info-group {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
            padding: 20px 24px;
            background: #f8faf9;
            border-radius: 14px;
            flex-wrap: wrap;
        }

        /* Bloco: foto + dados (SEM width/height fixos aqui) */
        .info-imagem {
            display: flex;
            align-items: flex-start;
            gap: 18px;
            flex: 1 1 320px;
        }

        .info-imagem>img {
            width: 90px;
            height: 90px;
            min-width: 90px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #ffffff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.12);
        }

        .info-dados {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .info-dados>h5 {
            margin-bottom: 6px;
            color: #1e2b23;
        }

        .info-dados>p {
            margin: 0;
            line-height: 1.6;
            font-size: 14.5px;
            color: #3c4a42;
        }

        .info-dados>p strong {
            color: #1e2b23;
            font-weight: 600;
            margin-right: 4px;
        }

        .info-status {
            flex: 0 0 auto;
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding: 14px 20px;
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e6ebe8;
            min-width: 180px;
        }

        .info-status>p {
            margin: 0;
            line-height: 1.6;
            font-size: 14px;
            color: #3c4a42;
        }

        .info-status>p strong {
            color: #1e2b23;
        }

        /* Badge de status (Ativo/Finalizado) */
        .info-status>p:first-child span {
            display: inline-block;
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 13px;
            color: #fff !important;
        }

        .info-status>p:first-child span[style*="21BA7C"] {
            background-color: #21BA7C;
        }

        .info-status>p:first-child span[style*="C52222"] {
            background-color: #C52222;
        }

        /* ===================== ANOTAÇÕES DO EXPERIMENTO ===================== */

        .conteudo-modal {
            padding: 4px 4px 8px;
        }

        .conteudo-modal h5 {
            color: #1e2b23;
            font-weight: 600;
        }

        .conteudo-modal hr {
            border: none;
            border-top: 1px solid #e6ebe8;
            margin: 8px 0 16px;
        }

        .conteudo-modal p {
            font-size: 14.5px;
            line-height: 1.7;
            color: #3c4a42;
            text-align: justify;
        }

        .conteudo-modal h6 {
            color: #9aa39e;
            font-style: italic;
            font-size: 14px;
        }

        /* ===================== RESPONSIVO ===================== */

        @media (max-width: 576px) {
            .info-group {
                flex-direction: column;
                gap: 16px;
            }

            .info-status {
                width: 100%;
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
                    <img src="<?= base_url('Public/templates/ImageSistema/LogoPreta.png') ?>" alt="Logo CEBIO">
                </div>

                <div class="leftLinks">
                    <ul>
                        <li><a class="linksDashboard" href="<?= base_url('home/tela') ?>"><i class="bi bi-house-door-fill"></i> Dashboard</a></li>
                        <li><a class="linksDashboard" href="<?= base_url('anotacao/index') ?>"><i class="bi bi-pencil-square"></i> Anotação</a></li>
                        <li><a class="linksDashboard" href="#"><i class="bi bi-clipboard2-pulse"></i> Plantio</a></li>
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

            <!-- ===================== LISTA DE EXPERIMENTOS ===================== -->
            <div class="listExperimentos">

                <div class="textExperimentos">
                    <h4>Seus experimentos &nbsp; <i class="bi bi-leaf" style="color: var(--corPrincipal);"></i></h4>
                </div>

                <div class="containerExperimentos">

                    <div class="linksExperimentos">
                        <ul>
                            <li><a href="#" onclick="mostrar('ativos'); return false;">Ativos</a></li>
                            <li><a href="#" onclick="mostrar('finalizados'); return false;">Finalizados</a></li>
                        </ul>
                    </div>

                    <div class="experimentos-lista">

                        <!-- ATIVOS (visível por padrão) -->
                        <div id="form-ativos" class="cardsExperimentos ativo" style="display: flex; flex-wrap: wrap; gap: 1rem;">
                            <?php if (empty($experimentosAtivos)): ?>
                                <h5 class="text-center mt-4">Você não possui nenhum experimento ativo!</h5>

                            <?php else: ?>

                                <?php foreach ($experimentosAtivos as $experimentoAtivos): ?>

                                    <div class="card" style="width: 18rem;">
                                        <img id="imagemExperimentos" src="<?= base_url($experimentoAtivos->experimentos_imagem) ?>" class="card-img-top" alt="...">

                                        <div class="card-body">
                                            <h5 class="card-title"><strong><?= $experimentoAtivos->experimentos_titulo ?></strong></h5>
                                            <p class="card-text text-small"> <strong>Experimento de: </strong> <?= $experimentoAtivos->usuarios_nome . ', ' . ($experimentoAtivos->participantes_nome ? $experimentoAtivos->participantes_nome : '') ?></p>
                                            <p class="card-text text-small"> <strong>Orientador: </strong> <?= $experimentoAtivos->participantes_orientador ?></p>
                                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#meuModal<?= $experimentoAtivos->experimentos_id ?>">
                                                Ver mais <i class="bi bi-plus"></i>
                                            </button>
                                        </div>

                                        <div class="modal fade" id="meuModal<?= $experimentoAtivos->experimentos_id ?>">
                                            <div class="modal-dialog modal-dialog-centered modal-xl">

                                                <div class="modal-content">

                                                    <div class="modal-header">
                                                        <h5 class="modal-title" style="text-align: center !important; font-size: x-large;">
                                                            <?= $experimentoAtivos->experimentos_titulo ?></h5>
                                                        <button class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>

                                                    <div class="modal-body">

                                                        <div class="info-group">
                                                            <div class="info-imagem">
                                                                <img src="<?= base_url($experimentoAtivos->experimentos_imagem) ?>" alt="imagemPerfil">
                                                                <div class="info-dados">
                                                                    <h5 class="mt-1" style="font-size: 16pt ;"><strong><?= $experimentoAtivos->experimentos_titulo ?></strong></h5>
                                                                    <p><strong>Orientador: </strong> <?= ($experimentoAtivos->participantes_orientador ? $experimentoAtivos->participantes_orientador : 'Orientador não informado'); ?></p>

                                                                    <p><strong>Alunos: </strong> <?= $experimentoAtivos->usuarios_nome . ', ' . $experimentoAtivos->participantes_nome ?></p>
                                                                   
                                                                    <p><strong>Luzes do experimento: </strong> <?= ($experimentoAtivos->bancadas_luzes ? implode(', ', json_decode($experimentoAtivos->bancadas_luzes, true)) : "Não há luzes no experimento!")  ?></p>
                                                                    <p><strong>Bancada: </strong> <?= esc($experimentoAtivos->bancadas_tipo_plantacao) ?> </p>
                                                                    <p><strong>Nome Científico: </strong> <?= esc($experimentoAtivos->bancadas_nome_cientifico) ?> </p>
                                                                    <p><strong>Quantidade de plantação: </strong> <?= esc($experimentoAtivos->bancadas_quantidade) ?> </p>
                                                                    <p><strong>Sala do experimento: </strong> <?= esc($experimentoAtivos->experimentos_sala) ?> </p>

                                                                </div>
                                                            </div>
                                                            <div class="info-status mb-4">
                                                                <p><strong>Status:</strong> <span style="color: #21BA7C; font-weight:bold">Ativo</span></p>
                                                                <p><strong>Início:</strong> <?= date('d/m/Y', strtotime($experimentoAtivos->experimentos_inicio)) ?></p>
                                                                <p><strong>Final:</strong> <?= date('d/m/Y', strtotime($experimentoAtivos->experimentos_final)) ?></p>

                                                            </div>
                                                        </div>

                                                        <!-- <div class="conteudo-modal">
                                                            <h5 class="mt-2 mb-2">Anotações do experimento</h5>
                                                            <hr>

                                                            <//?php if (!empty($experimentoAtivos->anotacoes_conteudo)): ?>
                                                                <p> <//?= mb_substr($experimentoAtivos->anotacoes_conteudo, 0, 1200, 'UTF-8') ?></p>

                                                            <//?php else: ?>
                                                                <h6>Não há anotação referente a este experimento!</h6>

                                                            <//?php endif; ?>
                                                        </div> -->

                                                    </div>

                                                    <div class="modal-footer">

                                                        <div class="row">

                                                            <div class="col-md-6">
                                                                <button type="submit" class="btn-verAnotacao"
                                                                    onclick="window.location.href='<?= base_url('anotacao/index') ?>'"> Ver Anotações</button>
                                                            </div>

                                                        </div>

                                                    </div>

                                                </div>

                                            </div>
                                        </div>

                                    </div>

                                <?php endforeach; ?>

                            <?php endif; ?>
                        </div>

                        <!-- FINALIZADOS (oculto por padrão) -->
                        <div id="form-finalizados" class="cardsExperimentos" style="display: none; flex-wrap: wrap; gap: 1rem;">
                            <?php if (empty($experimentosFinalizados)): ?>

                                <h5 class="text-center mt-4">Você não possui nenhum experimento Finalizado!</h5>

                            <?php else: ?>

                                <?php foreach ($experimentosFinalizados as $experimentoFinalizado): ?>
                                    <div class="card" style="width: 18rem;">
                                        <img id="imagemExperimentos" src="<?= base_url($experimentoFinalizado->experimentos_imagem) ?>" class="card-img-top" alt="...">

                                        <div class="card-body">
                                            <h5 class="card-title"><strong><?= $experimentoFinalizado->experimentos_titulo ?></strong></h5>
                                            <p class="card-text text-small"> <strong>Experimento de: </strong> <?= $experimentoFinalizado->participantes_nome . ' , ' .  $experimentoFinalizado->participantes_orientador ?></p>
                                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#meuModal<?= $experimentoFinalizado->experimentos_id ?>">
                                                Ver mais <i class="bi bi-plus"></i>
                                            </button>
                                        </div>

                                        <div class="modal fade" id="meuModal<?= $experimentoFinalizado->experimentos_id ?>">
                                            <div class="modal-dialog modal-dialog-centered modal-xl">

                                                <div class="modal-content">

                                                    <div class="modal-header">
                                                        <h5 class="modal-title" style="text-align: center !important; font-size: x-large;">
                                                            <?= $experimentoFinalizado->experimentos_titulo ?></h5>
                                                        <button class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>

                                                    <div class="modal-body">

                                                        <div class="info-group">
                                                            <div class="info-imagem">
                                                                <img src="<?= base_url($experimentoFinalizado->experimentos_imagem) ?>" alt="imagemPerfil">
                                                                <div class="info-dados">
                                                                    <h5 class="mt-1" style="font-size: 16pt ;"><strong><?= $experimentoFinalizado->experimentos_titulo ?></strong></h5>
                                                                    <p><strong>Orientador: </strong> <?= ($experimentoFinalizado->participantes_orientador ? $experimentoFinalizado->participantes_orientador : 'Orientador não informado'); ?></p>

                                                                    <p><strong>Alunos: </strong> <?= $experimentoFinalizado->usuarios_nome . ', ' . $experimentoFinalizado->participantes_nome ?></p>
                                                                
                                                                    <p><strong>Luzes do experimento: </strong> <?= ($experimentoFinalizado->bancadas_luzes ? implode(', ', json_decode($experimentoFinalizado->bancadas_luzes, true)) : "Não houve luzes utilizadas!")  ?></p>
                                                                    <p><strong>Bancada: </strong> <?= esc($experimentoFinalizado->bancadas_tipo_plantacao) ?> </p>
                                                                    <p><strong>Nome Científico: </strong> <?= esc($experimentoFinalizado->bancadas_nome_cientifico) ?> </p>
                                                                    <p><strong>Quantidade de plantação: </strong> <?= esc($experimentoFinalizado->bancadas_quantidade) ?> </p>
                                                                    <p><strong>Sala do experimento: </strong> <?= esc($experimentoFinalizado->experimentos_sala) ?> </p>

                                                                </div>
                                                            </div>
                                                            <div class="info-status mb-4">
                                                                <p><strong>Status:</strong> <span style="color: #C52222; font-weight:bold">Finalizado</span></p>
                                                                <p><strong>Início:</strong> <?= date('d/m/Y', strtotime($experimentoFinalizado->experimentos_inicio)) ?></p>
                                                                <p><strong>Final:</strong> <?= date('d/m/Y', strtotime($experimentoFinalizado->experimentos_final)) ?></p>
                                                                <p><strong>Encerrado:</strong> <?= date('d/m/Y H:i:s', strtotime($experimentoFinalizado->experimentos_encerrado)) ?></p>

                                                            </div>
                                                        </div>

                                                        

                                                    </div>

                                                    <div class="modal-footer">

                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <button class="btn-voltar" data-bs-dismiss="modal">Sair</button>
                                                            </div>
                                                        </div>

                                                    </div>

                                                </div>

                                            </div>
                                        </div>

                                    </div>
                                <?php endforeach; ?>

                            <?php endif; ?>
                        </div>

                    </div>

                </div>

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

    <script src="<?= base_url('Public/Js/javascript.js') ?>"></script>

    <script>
        function mostrar(tipo) {
            // esconde todas as seções
            document.getElementById("form-ativos").style.display = 'none';
            document.getElementById("form-finalizados").style.display = 'none';

            // mostra apenas a selecionada
            var alvo = document.getElementById('form-' + tipo);
            alvo.style.display = 'flex';

            // mantém a classe 'ativo' sincronizada, caso o CSS use ela em outro lugar
            document.getElementById("form-ativos").classList.remove('ativo');
            document.getElementById("form-finalizados").classList.remove('ativo');
            alvo.classList.add('ativo');
        }
    </script>

</body>

</html>