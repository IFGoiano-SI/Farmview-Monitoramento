<?php

$routes = [
    '/' => ['HomeController', 'index'],
    '/home' => ['HomeController', 'index'], // Rota explícita para a home
    '/registrar' => ['UsuarioController', 'index'], // Rota para ver o formulário
    '/usuario/cadastrar' => ['UsuarioController', 'cadastrar'], // Rota que processa o formulário
    '/login' => ['LoginController', 'index'],
    '/login/autenticar' => ['LoginController', 'autenticar'],
    '/adm/index' => ['AdminController', 'index'],

    '/experimento/cadastrar' => ['ExperimentoController', 'cadastrar'],
    '/experimento' => ['ExperimentoController', 'index'],
    '/home/dashboard' => ['HomeController', 'index'],
    '/login/logout'    => ['LoginController', 'logout'],
    '/experimento/finalizarExperimento/:id' => ['ExperimentoController', 'finalizarExperimento'],

    '/login/esqueciSenha' => ['LoginController', 'esqueciSenha'],
    '/experimento/listarExperimentos' => ['ExperimentoController', 'listarExperimentos'],
    '/home/tela'  => ['HomeController', 'index'],
    '/anotacao/index' => ['AnotacaoController', 'index'],
    '/anotacao/criar' => ['AnotacaoController', 'criar'],

    '/login/enviarEmail' => ['LoginController', 'enviarEmail'],
    '/login/alterarSenha' => ['LoginController', 'alterarSenha'],
    '/login/salvarSenha' => ['LoginController', 'salvarSenha'],
    '/experimento/pesquisar' => ['ExperimentoController', 'pesquisarExperimento'],
    '/anotacao/salvar'       => ['AnotacaoController', 'salvar'],
    '/experimento/editar/:id' => ['ExperimentoController', 'editar'],
    '/experimentos/editarExperimentosAtivo/:id' => ['ExperimentoController', 'editarExperimentosAtivo'],
    '/experimentos/excluirExperimento/:id'      => ['ExperimentoController', 'excluirExperimento'],
    '/home/toggleDispositivo'                   => ['HomeController', 'toggleDispositivo'],
    '/home/ligarcamera'               => ['HomeController', 'ligarCamera'],
    '/home/sensores' => ['HomeController', 'sensores'],
    '/gravacao/index' => ['GravacaoController', 'index'],
    '/anotacao/exportarPdf' => ['AnotacaoController', 'exportarPdf'],
    '/home/gravarTela' => ['HomeController', 'gravarTela'],
    '/home/iniciargravacao'           => ['HomeController', 'iniciarGravacao'],
    '/home/parargravacao'             => ['HomeController', 'pararGravacao'],
    '/gravacao/deletarUnique'         => ['GravacaoController', 'deletarGravacao'],
    '/gravacao/deletarAll'            => ['GravacaoController', 'deletarTodasGravacao'],
    '/adm/infoUser/:id'               => ['AdminController', 'infoUser'],



];


?>