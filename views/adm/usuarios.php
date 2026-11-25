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
        .infoList {
            display: flex;
            gap: 80px;
            padding-left: 20px;
            margin-top: 30px;
        }

        .groupInfo {
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #0000002c;
            width: 300px;
            display: flex;
            flex-flow: column;
            box-shadow: 1px 1px 3px #00000010;
        }

        .infoTitle p {
            font-size: 12pt;
            color: #7c7c7c;
        }

        .infoQuant {
            display: flex;
            align-items: center;
        }

        .imgInfoQuant {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .textInfo {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .nomeInfo {
            margin-left: 8px;
            display: flex;
        }

        .totInfo {
            background-color: green;
            color: white;
            border-radius: 100%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding-top: 15px;

        }


        .totInfo p {
            font-size: 13pt;
        }

        .campoUser {
            display: flex;
            width: 80%;
            margin: 0 auto;
            gap: 40px;

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
                    <a href="<?= base_url('adm/index') ?>"><img src="<?= base_url('Public/templates/ImageSistema/LogoPreta.png') ?>" alt="Logo CEBIO"></a>
                </div>

                <div class="leftLinks">
                    <ul>
                        <li>
                            <a class="linksDashboard" href="<?= base_url('adm/index') ?>">
                                <i class="bi bi-house-door-fill"></i> Dashboard
                            </a>
                        </li>
                        <li>
                            <a class="linksDashboard" href="#">
                                <i class="bi bi-person-plus-fill"></i> Usuários
                            </a>
                        </li>
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

            <!-- ===================== NAVBAR ===================== -->
            <?php if (isset($_SESSION['usuarios_logado'])): ?>

                <div class="navBar">
                    <div class="navName">
                        <h4>Olá, <?= esc($nome) ?>!</h4>
                        <p style="width: 150px; background-color: ligthgreen"> Administrador</p>
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

            <div class="listTable">

                <div class="campoUser mt-5">
                    <input type="search" class="form-control" placeholder="Pesquise por um usuário" name="pesquisarUser" id="pesquisarUser" value="">
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createNewUser" style="width: 200px;"> <i class="bi bi-plus-lg"></i> Novo usuário </button>
                </div>


                <div class="modal fade modal-xl" id="createNewUser" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered modal-xl">
                        <div class="modal-content">

                            <div class="modal-header">
                                <h5 class="modal-title mt-3" style="text-align: center !important; font-size: x-large;"><strong>Cadastro de usuário</strong></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <form action="<?= base_url('adm/createUser') ?>" method="post" enctype="multipart/form-data">
<?= csrf_field() ?>
                                <div class="modal-body">
                                    <div class="row">

                                        <div class="col-md-6 mt-2">
                                            <label for="usuarios_nome">Nome</label>
                                            <input type="text" class="form-control" name="usuarios_nome" id="nome" placeholder="Informe o nome do usuário" maxlength="40" value="<?= esc(old('usuarios_nome')) ?>" required>
                                        </div>

                                        <div class="col-md-6 mt-2">
                                            <label for="usuarios_escolaridade">Nível de escolaridade</label>
                                            <select name="usuarios_escolaridade"
                                                class="form-control"
                                                id="usuarios_escolaridade"
                                                required>
                                                <option value="" <?= old('usuarios_escolaridade') === '' ? 'selected' : '' ?> disabled>Selecione</option>
                                                <?php foreach (\Services\Validacao::ESCOLARIDADES as $opcao): ?>
                                                    <option value="<?= esc($opcao) ?>" <?= old('usuarios_escolaridade') === $opcao ? 'selected' : '' ?>><?= esc($opcao) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-6 mt-4">
                                            <label for="usuarios_email">Email</label>
                                            <input type="email"
                                                name="usuarios_email"
                                                class="form-control"
                                                id="usuarios_email"
                                                required
                                                placeholder="Informe seu email"
                                                value="<?= esc(old('usuarios_email')) ?>">
                                        </div>

                                        <div class="col-md-6 mt-4">
                                            <label for="usuarios_senha">Senha</label>
                                            <input type="password"
                                                name="usuarios_senha"
                                                class="form-control"
                                                id="senha"
                                                required
                                                minlength="<?= \Services\Validacao::SENHA_MINIMA ?>"
                                                autocomplete="new-password"
                                                placeholder="Mínimo de <?= \Services\Validacao::SENHA_MINIMA ?> caracteres">


                                        </div>

                                        <div class="col-md-6 mt-4 mb-3">
                                            <label for="usuarios_imagem">Foto de perfil</label>
                                            <input type="file"
                                                name="usuarios_imagem"
                                                id="usuarios_imagem"
                                                class="form-control"
                                                required
                                                accept="image/png, image/jpeg">
                                        </div>

                                        <div class="col-md-6 mt-4 mb-3">
                                            <label for="usuarios_nivel">Nível do usuário</label>
                                            <select name="usuarios_nivel"
                                                class="form-control"
                                                id="usuarios_nivel"
                                                required>
                                                <option value="" <?= old('usuarios_nivel') === '' ? 'selected' : '' ?> disabled>Selecione</option>
                                                <option value="1" <?= old('usuarios_nivel') === '1' ? 'selected' : '' ?>>Normal</option>
                                                <option value="2" <?= old('usuarios_nivel') === '2' ? 'selected' : '' ?>>Administrador</option>
                                            </select>
                                        </div>

                                    </div>



                                </div>
                                <div class="modal-footer">

                                    <button type="submit" class="btn btn-success  mb-3">Cadastar usuário</button>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>

                <div class="tabelaVideos table-responsive mt-5" style="width: 80%; margin: 0 auto;">
                    <table class="table table-striped ">
                        <thead>
                            <tr>
                                <th scope="col">Perfil</th>
                                <th scope="col">ID</th>
                                <th scope="col">Nome</th>
                                <th scope="col">Escolaridade</th>
                                <th scope="col">Ativo</th>
                                <th scope="col">Nível</th>
                                <th scope="col">Detalhar</th>
                            </tr>
                        </thead>
                        <tbody id="tabelaUsuarios">
                            <?php foreach ($usuarios as $user): ?>
                                <tr data-nome="<?= esc($user->usuarios_nome) ?>">
                                    <th scope="row">
                                        <img
                                            src="<?= esc(base_url($user->usuarios_imagem)) ?>"
                                            alt="Foto do usuário"
                                            style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                                    </th>
                                    <td>#0<?= esc($user->usuarios_id) ?></td>
                                    <td class="nomeUsuario"><?= esc(mb_strimwidth($user->usuarios_nome, 0, '50', '...')) ?></td>
                                    <td><?= esc($user->usuarios_escolaridade) ?></td>
                                    <td> <?= (int) $user->usuarios_ativo === 1 ? 'Ativo' : 'Inativo' ?>
                                    </td>
                                    <td> <?= (int) $user->usuarios_nivel === 2 ? 'Adm' : 'Normal' ?>
                                    </td>

                                    <form action="<?= base_url('adm/detailsUser/' . id_url((int) $user->usuarios_id)) ?>" method="get">
                                        <td><button type="submit" class="btn btn-outline-primary">Ver mais</button></td>

                                    </form>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>



            </div>

            <!-- Paginação -->

            <nav id="paginacaoUsuarios" aria-label="Paginação dos usuários">
                <ul id="paginasUsuarios" class="pagination justify-content-center mt-5"></ul>
            </nav>

        </div>

    </div>


    <?php partial('footer', ['estilo' => 'height: auto;']); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>


    <script>
        const pesquisa = document.getElementById('pesquisarUser');
        const tabela = document.getElementById('tabelaUsuarios');
        const linhas = Array.from(tabela.querySelectorAll('tr'));
        const paginas = document.getElementById('paginasUsuarios');
        const navegacao = document.getElementById('paginacaoUsuarios');
        const limite = 8;
        let paginaAtual = 1;

        const semResultados = document.createElement('tr');
        const mensagem = document.createElement('td');
        mensagem.colSpan = 7;
        mensagem.className = 'text-center';
        mensagem.textContent = 'Nenhum usuário encontrado.';
        semResultados.appendChild(mensagem);
        tabela.appendChild(semResultados);

        function normalizar(texto) {
            return texto.normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase().trim();
        }

        function adicionarBotao(texto, destino, desabilitado = false, ativo = false) {
            const item = document.createElement('li');
            item.className = 'page-item';
            item.classList.toggle('disabled', desabilitado);
            item.classList.toggle('active', ativo);

            const botao = document.createElement('button');
            botao.type = 'button';
            botao.className = 'page-link';
            botao.textContent = texto;
            botao.disabled = desabilitado;
            if (ativo) botao.setAttribute('aria-current', 'page');
            botao.addEventListener('click', function () {
                paginaAtual = destino;
                atualizarTabela();
            });
            item.appendChild(botao);
            paginas.appendChild(item);
        }

        function atualizarTabela() {
            const termo = normalizar(pesquisa.value);
            const filtradas = linhas.filter(function (linha) {
                return normalizar(linha.dataset.nome).includes(termo);
            });
            const totalPaginas = Math.max(1, Math.ceil(filtradas.length / limite));
            paginaAtual = Math.min(paginaAtual, totalPaginas);
            const inicio = (paginaAtual - 1) * limite;

            linhas.forEach(linha => linha.hidden = true);
            filtradas.slice(inicio, inicio + limite)
                .forEach(linha => linha.hidden = false);
            semResultados.hidden = filtradas.length !== 0;

            paginas.replaceChildren();
            navegacao.hidden = totalPaginas <= 1;
            if (totalPaginas <= 1) return;

            adicionarBotao('Anterior', paginaAtual - 1, paginaAtual === 1);
            for (let pagina = 1; pagina <= totalPaginas; pagina++) {
                adicionarBotao(String(pagina), pagina, false, pagina === paginaAtual);
            }
            adicionarBotao('Próxima', paginaAtual + 1, paginaAtual === totalPaginas);
        }

        pesquisa.addEventListener('input', function () {
            paginaAtual = 1;
            atualizarTabela();
        });

        atualizarTabela();
    </script>

    <?php if (!empty($_SESSION['old'])): ?>
        <script>
            // Reabre o formulário preenchido após erro de validação.
            new bootstrap.Modal(document.getElementById('createNewUser')).show();
        </script>
    <?php endif; ?>


</body>


</html>