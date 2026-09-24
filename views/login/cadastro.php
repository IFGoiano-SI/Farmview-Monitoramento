<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmView</title>

    <link rel="shortcut icon" href="<?= base_url('Public/templates/ImageSistema/LogoSistema.ico') ?>" type="image/x-icon">

    <link rel="stylesheet" href="<?= base_url('Public/templates/Css/registrar.css') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">


</head>

<body>
    <div class="alert-container">
        <?php if (isset($_SESSION['msg'])): ?>
            <?= msg($_SESSION['msg']['texto'], $_SESSION['msg']['color']); ?>
            <?php unset($_SESSION['msg']); ?>
        <?php endif; ?>
    </div>

    <img src="<?= base_url('Public/templates/ImageSistema/verticalFarming.png') ?>" class="imgFundo" alt="sem image">

    <main>

        <div class="cadastro">
            <form action="<?= base_url('usuario/cadastrar') ?>" method="post" enctype="multipart/form-data">
                <div class="boxCadastro">
                    <h4 class="mb-5">Realize seu cadastro</h4>

                    <div class="campos mb-3">
                        <label for="usuarios_nome">Nome</label>
                        <input type="text"
                            autocomplete="off"
                            name="usuarios_nome"
                            class="input"
                            id="usuarios_nome"
                            required
                            placeholder="Informe seu nome completo"
                            value="<?= $_POST['usuarios_nome'] ?? '' ?>">
                    </div>

                    <div class="campos mb-3">
                        <label for="usuarios_escolaridade">Nível de escolaridade</label>
                        <select name="usuarios_escolaridade"
                            class="select"
                            id="usuarios_escolaridade"
                            required>
                            <option value="" selected disabled>Selecione</option>
                            <option value="Ensino Médio">Ensino Médio</option>
                            <option value="Graduação">Graduação</option>
                            <option value="Mestrado">Mestrado</option>
                            <option value="Doutorado">Doutorado</option>
                        </select>
                    </div>

                    <div class="campos mb-3">
                        <label for="usuarios_email">Email</label>
                        <input type="email"
                            name="usuarios_email"
                            class="input"
                            id="usuarios_email"
                            required
                            placeholder="Informe seu email"
                            value="<?= $_POST['usuarios_email'] ?? '' ?>">
                    </div>

                    <div class="campos mb-3">
                        <label for="usuarios_senha">Senha</label>

                        <div class="campo-senha">
                            <input type="password"
                                name="usuarios_senha"
                                class="input"
                                id="senha"
                                required
                                placeholder="Informe sua senha">

                            <i class="bi bi-eye" id="toggleSenha"></i>
                        </div>
                    </div>

                    <div class="campos mb-3">
                        <label for="usuarios_imagem">Foto de perfil</label>
                        <input type="file"
                            name="usuarios_imagem"
                            id="usuarios_imagem"

                            accept="image/*">
                    </div>

                    <div class="caixaBotao mt-4">
                        <input type="submit"
                            class="btn-entrar"
                            style="width: 400px; margin-top: 10px;"
                            name="enviar"
                            value="Confirmar">

                        <input type="button"
                            class="btn-cadastrar"
                            style="width: 400px; margin-top: 10px;"
                            onclick="window.location.href='<?= base_url('login') ?>'"
                            value="Voltar">

                    </div>

                </div>
            </form>
        </div>
    </main>





    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= base_url('Public/Js/javascript.js') ?>"></script>


    <script>
        const toggle = document.getElementById("toggleSenha");
        const senha = document.getElementById("senha");

        toggle.addEventListener("click", () => {
            if (senha.type === "password") {
                senha.type = "text";
                toggle.classList.remove("bi-eye");
                toggle.classList.add("bi-eye-slash");
            } else {
                senha.type = "password";
                toggle.classList.remove("bi-eye-slash");
                toggle.classList.add("bi-eye");
            }
        });
    </script>

</body>

</html>