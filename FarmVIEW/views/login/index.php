<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoramento - CEBIO</title>
    <link rel="stylesheet" href="<?= base_url('Public/templates/Css/login.css') ?>">
    <link rel="shortcut icon" href="<?= base_url('Public/templates/ImageSistema/logo.ico') ?>" type="image/x-icon">
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

    <form action="<?= base_url('login/autenticar') ?>" method="post">
        <div class="loginCadastro">
            <div class="caixaLogin">
                <div class="boaVinda">
                    <h3><Strong>Bem-vindo</Strong></h3>
                    <p>Acesse sua conta para continuar</p>
                </div>

                <div class="campos">
                    <input type="email" name="usuarios_email" class="form-control" style="padding: 1rem;"
                        placeholder="Informe seu email" id="usuarios_email" value="<?= $_SESSION['old']['usuarios_email'] ?? '' ?>">
                    <!-- <span class="textoObrigatorio"> Teste</span> -->
                </div>

                <div class="campos">
                    <input type="password" name="usuarios_senha" class="form-control" style="padding: 1rem;"
                        placeholder="Informe sua senha" id="usuarios_senha">
                    <!-- <span class="textoObrigatorio"> Teste</span> -->

                </div>

                <div class="campoSenha mt-2 mb-5">
                    <p class="text-end"> <a href="<?= base_url('login/esqueciSenha') ?>">Esqueci minha senha</a></p>
                </div>

                <div class="caixaBotao">
                    <button type="submit" id="btnEntrar" class="btn-entrar">Entrar</button>
                    <button type="button" onclick="window.location.href='<?= base_url('registrar') ?>'" class="btn-cadastrar">Cadastrar</button>
                </div>

            </div>
        </div>

        </div>
        </div>

        <?php unset($_SESSION['old']); ?>



        <script>
            const btnEntrar = document.getElementById('btnEntrar');

            function validarCampo(elemento, mensagem) {
                elemento.innerText = mensagem;
                elemento.style.display = "block";

            }



            function logar() {
                let mensagem = document.querySelectorAll('.textoObrigatorio');

                mensagem.forEach(msg => {
                    msg.style.display = 'none'
                });
            }
        </script>




        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
            crossorigin="anonymous"></script>


        <script src="<?= base_url('Public/Js/javascript.js') ?>"></script>
</body>

</html>