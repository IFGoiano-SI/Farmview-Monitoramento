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

                <h4 style="margin-left: 70px;" class="mt-5 mb-3">Veja suas últimas gravações </h4>


                <?php if (!empty($dadosVideo)): ?>
                    <p style="margin-left: 70px; margin-top:30px; font-size: 13pt;">Nenhum vídeo gravado ainda.</p>
                <?php else: ?>


                    <table class="table table-striped mt-5" style="width: 80%; margin: auto;">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Experimento</th>
                                <th scope="col">Arquivo</th>
                                <th scope="col">Download</th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php foreach ($dadosVideo as $video): ?>
                                <tr>
                                    <th scope="row"><?= esc($video->videos_id) ?></th>
                                    <td><?= esc($video->experimentos_nome) ?></td>
                                    <td><?= esc($video->path_videos) ?></td>
                                    <td><a href="<?= base_url('Public/uploads/videos/' . $video->videos_experimentos_arquivo) ?>" download><i class="bi bi-download"></i> Baixar</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

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

    <script>
        tinymce.init({
            selector: '#my-text-area',
            height: 600,

            plugins: [
                'advlist',
                'autolink',
                'lists',
                'link',
                'image',
                'table',
                'media',
                'fullscreen',
                'wordcount'
            ],

            toolbar: 'undo redo | bold italic underline | ' +
                'alignleft aligncenter alignright alignjustify | ' +
                'bullist numlist | link image table | fullscreen',

            menubar: 'edit insert format tools table'
        });
    </script>


    <script>
        document.getElementById('formExportar').addEventListener('submit', function() {
            const editor = tinymce.get('my-text-area');

            document.getElementById('conteudo-exportar').value = editor ?
                editor.getContent() :
                document.getElementById('my-text-area').value;
        });
    </script>

</body>

</html>