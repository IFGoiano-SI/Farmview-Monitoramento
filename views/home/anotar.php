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
        .telaAnotacao {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-flow: column wrap;
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
                        <li><a class="linksDashboard" href="#"><i class="bi bi-pencil-square"></i> Anotação</a></li>
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

                <div class="navBar">

                    <div class="navName">
                        <h4><?= esc($nome) ?></h4>
                        <p><?= esc($escolaridade) ?></p>
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

            <form action="<?= base_url('anotacao/exportarPdf') ?>" method="post" id="formExportar">
<?= csrf_field() ?>
                <div class="exportar mt-3">
                    <input type="hidden" name="anotacoes_conteudo" id="conteudo-exportar">
                    <button type="submit" class="btn-exportar"><i class="bi bi-file-earmark-pdf-fill"></i> Exportar</button>

                </div>
            </form>

            <!-- ===================== ANOTAÇÃO ===================== -->
            <form action="<?= base_url('anotacao/salvar') ?>" method="post">
<?= csrf_field() ?>
                <div class="telaAnotacao mt-2">

                    <input type="hidden" name="experimento_id" value="<?= (int) $dadosAnotacao->experimentos_id ?>">

                    <textarea style="width: 80%;" id="my-text-area" class="anotacao" name="anotacoes_conteudo" aria-label="Anotação do experimento"><?= esc($anotacao->anotacoes_conteudo ?? '') ?></textarea>

                    <div class="botaoSalvar mt-4" style="display: flex; justify-content: flex-end; width: 79%">
                        <button type="submit" class="btnAnotacao">Salvar anotação</button>
                    </div>
            </form>
        </div>
    </div>


    </div>

    </div>

    <?php partial('footer'); ?>

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