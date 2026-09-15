<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmView</title>

    <link rel="shortcut icon" href="<?= base_url('Public/templates/ImageSistema/LogoSistema.ico') ?>" type="image/x-icon">
    <link rel="stylesheet" href="<?= base_url('Public/templates/Css/login.css') ?>">
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

    <div class="loginCadastro">
        <form action="<?= base_url('login/salvarSenha') ?>" method="post">
            <div class="caixaLogin">
                <div class="boaVinda">
                    <h3><Strong>Alteração de senha</Strong></h3>
                    <p>Informe sua nova senha</p>
                </div>

                <div class="campos mb-3" style="margin-top: 50px;">
                    <label for="usuarios_senha">Senha</label>
                    <input type="password"
                        name="usuarios_senha"
                        class="form-control"
                        id="usuarios_senha"
                        required
                        placeholder="Informe sua senha"
                        style="padding: 1rem;">
                </div>

                <div class="campos">
                    <label for="confirmarSenha">Confirme sua senha</label>
                    <input type="password"
                        name="confirmarSenha"
                        class="form-control"
                        id="confirmarSenha"
                        required
                        placeholder="Confirme sua senha"
                        style="padding: 1rem;">
                </div>

                <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">

                <div class="caixaBotao" style="margin-top: 50px;">
                    <button type="submit" class="btn-entrar"> Alterar senha</button>
                </div>

            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
    <script src="<?= base_url('Public/Js/javascript.js') ?>"></script>


</body>

</html>