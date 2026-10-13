<?php redirectPages(); ?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmView</title>

    <link rel="shortcut icon" href="<?= base_url('Public/templates/ImageSistema/LogoSistema.ico') ?>" type="image/x-icon">

    <link rel="stylesheet" href="<?= base_url('Public/templates/Css/home.css') ?>">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <style>

    </style>
</head>

<body>



    <?php if (isset($_SESSION['usuarios_logado'])):
        $foto = $_SESSION['usuarios_logado']->usuarios_imagem;
        $nome = $_SESSION['usuarios_logado']->usuarios_nome;
        $escolaridade = $_SESSION['usuarios_logado']->usuarios_escolaridade;
    ?>

        <div class="fullScreen">

            <!-- SIDEBAR -->
            <div class="sideBar">

                <div class="leftScreenTop">
                    <div class="logo">
                        <a href="#"><img src="<?= base_url('Public/templates/ImageSistema/LogoPreta.png') ?>" alt="Logo CEBIO"></a>
                    </div>

                    <div class="leftLinks">
                        <ul>
                            <li><a class="linksDashboard" href="#"><i class="bi bi-house-door-fill"></i> Dashboard</a></li>
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

            <!-- CONTEÚDO -->
            <div class="contentScreen">

                <div class="alert-container" style="z-index: 9999999999999999999999;">
                    <?php if (isset($_SESSION['msg'])): ?>
                        <?= msg($_SESSION['msg']['texto'], $_SESSION['msg']['color']); ?>
                        <?php unset($_SESSION['msg']); ?>
                    <?php endif; ?>
                </div>

                <div class="navBar">

                    <div class="navName">
                        <h4><?= esc($nome) ?></h4>
                        <p><?= esc($escolaridade) ?></p>
                    </div>

                    <div class="navLogout">
                        <a href="#"><img id="imagePerson" src="<?= base_url($foto) ?>" alt="Foto de perfil"></a>

                        <button type="button" class="btn-logout" data-bs-toggle="modal" data-bs-target="#meuModalSair">
                            Sair <i class="bi bi-box-arrow-right"></i>
                        </button>

                        <!-- MODAL DE LOGOUT -->
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
                                                <button type="button" class="btn-voltar" data-bs-dismiss="modal">
                                                    Voltar
                                                </button>

                                                <button type="submit" class="btn-finalizar">
                                                    Sair
                                                </button>
                                            </p>
                                        </form>
                                    </div>

                                    <div class="modal-footer"></div>

                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <form action="<?= base_url('experimento/cadastrar') ?>" method="post" enctype="multipart/form-data">

                    <div class="mainTela">
                        <button type="button" class="newExperimento" data-bs-toggle="modal" data-bs-target="#meuModal">
                            <i class="bi bi-plus"></i> Cadastrar experimentos
                        </button>
                    </div>

                    <!-- MODAL 1 -->
                    <div class="modal fade" id="meuModal" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered modal-xl">
                            <div class="modal-content">

                                <div class="modal-header">
                                    <h5 class="modal-title" style="text-align: center !important; font-size: x-large;">
                                        Registre seu experimento
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>

                                <div class="modal-body">
                                    <div class="row">

                                        <div class="col-md-6 mb-4">
                                            <label for="titulo">Título do experimento</label>
                                            <input type="text" name="experimentos_titulo" class="form-control" id="titulo"
                                                placeholder="Informe o nome do experimento"
                                                value="<?= $_POST['experimentos_titulo'] ?? '' ?>" required>
                                            <span class="alertSpan" id="textoObrigatorio"></span>
                                        </div>

                                        <div class="col-md-6 mb-4">
                                            <label for="sala">Sala do experimento</label>
                                            <input type="text" name="experimentos_sala" class="form-control" id="sala"
                                                placeholder="Informe a sala do experimento"
                                                value="<?= $_POST['experimentos_sala'] ?? '' ?>" required>
                                            <span class="alertSpan" id="textoObrigatorio"></span>
                                        </div>

                                        <div class="col-md-6 mb-4">
                                            <label for="inicio">Data de início</label>
                                            <input type="date" name="experimentos_inicio" class="form-control" id="inicio"
                                                value="<?= $_POST['experimentos_inicio'] ?? '' ?>" required>
                                            <span class="alertSpan" id="textoObrigatorio"></span>
                                        </div>

                                        <div class="col-md-6 mb-4">
                                            <label for="final">Data final</label>
                                            <input type="date" name="experimentos_final" class="form-control" id="final"
                                                value="<?= $_POST['experimentos_final'] ?? '' ?>" required>
                                            <span class="alertSpan" id="textoObrigatorio"></span>
                                        </div>

                                        <div class="col-md-12 mb-4">
                                            <label for="participantes_externos">Estudantes participantes</label>
                                            <textarea name="participantes_externos" id="participantes_externos" class="form-control"
                                                placeholder="Informe o nome dos participantes" rows="5"><?= $_POST['participantes_externos'] ?? '' ?></textarea>
                                        </div>

                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn-voltar" data-bs-dismiss="modal">Sair</button>
                                    <button type="button" class="btn-finalizar" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modal2">Próximo</button>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- MODAL 2 -->
                    <div class="modal fade" id="modal2" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered modal-xl">
                            <div class="modal-content">

                                <div class="modal-header">
                                    <h5 class="modal-title" style="text-align: center !important; font-size: x-large;">
                                        Registre sua bancada
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>

                                <div class="modal-body">
                                    <div class="row">

                                        <div class="col-md-6 mb-4">
                                            <label for="tipoPlantacao">Tipo de plantação</label>
                                            <input type="text" name="bancadas_tipo_plantacao" class="form-control" id="tipoPlantacao"
                                                placeholder="Informe o tipo de plantação"
                                                value="<?= $_POST['bancadas_tipo_plantacao'] ?? '' ?>" required>
                                            <span class="alertSpan" id="textoObrigatorio"></span>
                                        </div>

                                        <div class="col-md-6 mb-4">
                                            <label for="nomeCientifico">Nome científico</label>
                                            <input type="text" name="bancadas_nome_cientifico" class="form-control" id="nomeCientifico"
                                                placeholder="Nome científico da plantação"
                                                value="<?= $_POST['bancadas_nome_cientifico'] ?? '' ?>" required>
                                            <span class="alertSpan" id="textoObrigatorio"></span>
                                        </div>

                                        <div class="col-md-6 mb-4">
                                            <label for="quantidade">Quantidade</label>
                                            <input type="number" name="bancadas_quantidade" class="form-control" id="quantidade"
                                                placeholder="Informe a quantidade de planta"
                                                value="<?= $_POST['bancadas_quantidade'] ?? '' ?>" required>
                                            <span class="alertSpan" id="textoObrigatorio"></span>
                                        </div>

                                        <div class="col-md-6 mb-4">
                                            <label for="nome">Nome da bancada</label>
                                            <input type="text" name="bancadas_nome" class="form-control" id="nome"
                                                placeholder="Informe o nome da bancada"
                                                value="<?= $_POST['bancadas_nome'] ?? '' ?>" required>
                                            <span class="alertSpan" id="textoObrigatorio"></span>
                                        </div>

                                        <div class="col-md-6 mb-4">
                                            <label for="experimentos_imagem">Anexe uma imagem para o experimento</label>
                                            <input type="file" name="experimentos_imagem" class="form-control" required id="experimentos_imagem">
                                            <span class="alertSpan" id="textoObrigatorio"></span>
                                        </div>

                                        <div class="col-md-6 mb-4">
                                            <label for="participantes_orientador">Orientador dos participante(s)</label>
                                            <select name="participantes_orientador" required class="form-control" id="participantes_orientador">
                                                <option value="" selected disabled>Selecione um orientador</option>
                                                <option value="Vilson Soares">Vilson Soares</option>
                                                <option value="Henrique">Henrique</option>
                                                <option value="Flávia">Flávia</option>
                                                <option value="Ana Paula">Ana Paula</option>
                                            </select>
                                            <span class="alertSpan" id="textoObrigatorio"></span>
                                        </div>

                                        <div class="col-md-6 mt-2">
                                            <label class="mb-2">Luzes da bancada</label>

                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" name="bancadas_luzes[]" value="RGW" id="rgw">
                                                <label class="form-check-label" for="rgw">Luzes RGW</label>
                                            </div>

                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" name="bancadas_luzes[]" value="Branca" id="white">
                                                <label class="form-check-label" for="white">Luzes White</label>
                                            </div>

                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" name="bancadas_luzes[]" value="Vermelha" id="red">
                                                <label class="form-check-label" for="red">Luzes Red</label>
                                            </div>

                                            <div class="form-check mb-5">
                                                <input type="checkbox" class="form-check-input" name="bancadas_luzes[]" value="Azul" id="blue">
                                                <label class="form-check-label" for="blue">Luzes Blue</label>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn-voltar" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#meuModal">Voltar</button>
                                    <button type="submit" class="btn-finalizar">Finalizar</button>
                                </div>

                            </div>
                        </div>
                    </div>

                </form>

            </div>

        </div>
    <?php endif; ?>

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
        const campos = document.querySelectorAll("input[required]");

        campos.forEach(campo => {
            campo.addEventListener("blur", () => {
                const span = campo.nextElementSibling;

                if (campo.value.trim() === "") {
                    span.innerText = "Campo obrigatório";
                    span.style.display = "block";
                } else {
                    span.innerText = "";
                    span.style.display = "none";
                }
            });
        });
    </script>

</body>

</html>