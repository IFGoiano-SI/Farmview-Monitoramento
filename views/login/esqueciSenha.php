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
        <form action="<?= base_url('login/enviarEmail') ?>" method="post">
            <div class="caixaLogin">
                <div class="boaVinda">
                    <h3><Strong>Recuperação de conta</Strong></h3>
                    <p>Informe o email e verifique sua caixa de email</p>
                </div>

                <div class="campos mb-3" style="margin-top: 80px;">
                    <label for="usuarios_email">Email</label>
                    <input type="email"
                        name="usuarios_email"
                        class="form-control"
                        id="usuarios_email"
                        required
                        placeholder="Informe seu email"
                        style="padding: 1rem;"
                        value="<?= $_POST['usuarios_email'] ?? '' ?>">
                </div>

                <div class="caixaBotao" style="margin-top: 90px;">
                    <button type="submit" class="btn-entrar">Enviar</button>
                    <button type="button" onclick="window.location.href='<?= base_url('login') ?>'" class="btn-cadastrar"> Voltar </button>
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