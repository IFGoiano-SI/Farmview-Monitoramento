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

    <!-- <style>
        .cadastro {
            width: 100vw;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .boxCadastro {
            display: flex;
            width: 500px;
            height: 700px;
            flex-flow: column wrap;
            align-items: center;
            padding-top: 45px;
            box-shadow: 1px 1px 4px rgba(0, 0, 0, 0.281);
            border-radius: 15px;
        }

        .boxCadastro>h4 {
            font-weight: bold;
        }

        .campos {
            display: flex;
            flex-flow: column wrap;
            width: 80%;
        }

        .input {
            height: 40px;
            border-radius: 10px;
            border: 1px solid rgba(0, 0, 0, 0.137);
            padding: 10px;
            transition: transform 0.5s ease;

        }

        .input:focus {
            transform: translate(-2px, -4px);
            outline: none;
            /* remove a borda azul padrão */
            border-color: #00813652
        }

        .select {
            height: 40px;
            border-radius: 10px;
            border: 1px solid rgba(0, 0, 0, 0.137);
            padding: 5px;
            transition: transform 0.5s ease;
            background-color: white;

        }

        .caixaBotao {
            display: flex;
            justify-content: center;
            flex-flow: column wrap;
        }

        .select:focus {
            transform: translate(-2px, -4px);
            outline: none;
            /* remove a borda azul padrão */
            border-color: #00813652
        }

        .campo-senha {
            position: relative;
        }

        .campo-senha input {
            width: 100%;
            padding-right: 45px;
            /* espaço para o ícone */
        }

        .campo-senha i {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #777;
            font-size: 18px;
        }

        .campo-senha i:hover {
            color: #008135;
        }

        .btn-entrar {
            background-color: #008135;
            height: 50px;
            border-radius: 10px;
            border: 1px solid rgba(0, 0, 0, 0.137);
            padding: 5px;
            color: white;
            font-size: 15pt;
            font-weight: 500;
        }

        .btn-entrar:hover {
            transform: translate(0, -3px);
            transition: transform 0.2s ease;
            background-color: #35ad45e5;


        }

        .btn-cadastrar {
            height: 50px;
            border-radius: 10px;
            border: 1px solid rgba(0, 0, 0, 0.137);
            padding: 5px;
            color: rgb(5, 5, 5);
            font-size: 15pt;
            font-weight: 500;

        }

        .btn-cadastrar:hover {
            transform: translate(0, -3px);
            transition: transform 0.2s ease;

        }
    </style> -->

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


    <!-- <div class="cadastro">
        <form action="" method="post">
            <div class="boxCadastro">
                <h4 class="mb-5">Realize seu cadastro </h4>
                <div class="campos mb-3">
                    <label for="usuarios_nome">Nome Completo</label>
                    <input type="text" autocomplete="off" name="usuarios_nome" class="form-control" id="usuarios_nome" required
                        placeholder="Informe seu nome completo" value="<?= $_POST['usuarios_nome'] ?? '' ?>">
                </div>

                <div class="campos mb-3">
                    <label for="usuarios_sexo">Genêro</label>
                    <select class="select" name="usuarios_sexo" id="sexo">
                        <option value="" selected disabled>Selecione seu gênero</option>
                        <option value="Masculino">Masculino</option>
                        <option value="Feminio">Feminio</option>
                        <option value="Outro">Outro</option>
                    </select>
                </div>

                <div class="campos mb-3">
                    <label for="usuarios_telefone">Telefone/Celular</label>
                    <input type="tel" class="input" name="usuarios_telefome" id="telefone"
                        placeholder="(XX) XXXXX-XXXX">
                </div>

                <div class="campos mb-3">
                    <label for="usuarios_cpf">CPF</label>
                    <input type="text" class="input cpf" maxlength="14" name="usuarios_cpf" id="cpf" placeholder="000.000.000-00">
                </div>

                <div class="campos mb-3">
                    <label for="usuarios_email">Email</label>
                    <input type="email" class="input" name="usuarios_email" id="email" placeholder="Digite seu email">
                </div>

                <div class="campos mb-3">
                    <label for="usuarios_password_hash">Senha</label>

                    <div class="campo-senha">
                        <input type="password" class="input" name="usuarios_senha" id="senha"
                            placeholder="Digite sua senha">

                        <i class="bi bi-eye" id="toggleSenha"></i>
                    </div>
                </div>


                <div class="caixaBotao mt-3">
                    <button type="submit" class="btn-entrar" style="width: 400px; margin-top: 10px;">Criar
                        conta</button>
                </div>

            </div>

        </form>
    </div> -->



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