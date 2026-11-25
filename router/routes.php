<?php

/**
 * '/caminho' => [Controller, método, acesso, verbo]
 * acesso: publico | usuario | admin  — verbo: GET | POST | ANY
 */
$routes = [
    // Autenticação e cadastro
    '/login'                                    => ['LoginController', 'index', 'publico', 'GET'],
    '/login/autenticar'                         => ['LoginController', 'autenticar', 'publico', 'POST'],
    '/login/logout'                             => ['LoginController', 'logout', 'usuario', 'POST'],
    '/login/esqueciSenha'                       => ['LoginController', 'esqueciSenha', 'publico', 'GET'],
    '/login/enviarEmail'                        => ['LoginController', 'enviarEmail', 'publico', 'POST'],
    '/login/alterarSenha'                       => ['LoginController', 'alterarSenha', 'publico', 'GET'],
    '/login/salvarSenha'                        => ['LoginController', 'salvarSenha', 'publico', 'POST'],
    '/registrar'                                => ['UsuarioController', 'index', 'publico', 'GET'],
    '/usuario/cadastrar'                        => ['UsuarioController', 'cadastrar', 'publico', 'POST'],

    // Painel do usuário
    '/'                                         => ['HomeController', 'index', 'usuario', 'GET'],
    '/home'                                     => ['HomeController', 'index', 'usuario', 'GET'],
    '/home/dashboard'                           => ['HomeController', 'index', 'usuario', 'GET'],
    '/home/tela'                                => ['HomeController', 'index', 'usuario', 'GET'],
    '/home/sensores'                            => ['HomeController', 'sensores', 'usuario', 'GET'],
    '/home/status'                              => ['HomeController', 'status', 'usuario', 'GET'],
    '/home/toggleDispositivo'                   => ['HomeController', 'toggleDispositivo', 'usuario', 'POST'],
    '/home/ligarcamera'                         => ['HomeController', 'ligarCamera', 'usuario', 'POST'],
    '/home/iniciargravacao'                     => ['HomeController', 'iniciarGravacao', 'usuario', 'POST'],
    '/home/parargravacao'                       => ['HomeController', 'pararGravacao', 'usuario', 'POST'],
    '/camera/stream'                            => ['CameraController', 'stream', 'usuario', 'GET'],
    '/camera/autorizar'                         => ['CameraController', 'autorizar', 'publico', 'GET'],

    // Experimentos
    '/experimento'                              => ['ExperimentoController', 'listarExperimentos', 'usuario', 'GET'],
    '/experimento/listarExperimentos'           => ['ExperimentoController', 'listarExperimentos', 'usuario', 'GET'],
    '/experimento/pesquisar'                    => ['ExperimentoController', 'pesquisarExperimento', 'usuario', 'ANY'],
    '/experimento/cadastrar'                    => ['ExperimentoController', 'cadastrar', 'usuario', 'POST'],
    '/experimento/editar/:id'                   => ['ExperimentoController', 'editar', 'usuario', 'GET'],
    '/experimentos/editarExperimentosAtivo/:id' => ['ExperimentoController', 'editarExperimentosAtivo', 'usuario', 'POST'],
    '/experimentos/excluirExperimento/:id'      => ['ExperimentoController', 'excluirExperimento', 'usuario', 'POST'],
    '/experimento/finalizarExperimento/:id'     => ['ExperimentoController', 'finalizarExperimento', 'usuario', 'POST'],

    // Anotações
    '/anotacao/index'                           => ['AnotacaoController', 'index', 'usuario', 'GET'],
    '/anotacao/salvar'                          => ['AnotacaoController', 'salvar', 'usuario', 'POST'],
    '/anotacao/exportarPdf'                     => ['AnotacaoController', 'exportarPdf', 'usuario', 'POST'],

    // Gravações
    '/gravacao/index'                           => ['GravacaoController', 'index', 'usuario', 'GET'],
    '/gravacao/download/:id'                    => ['GravacaoController', 'download', 'usuario', 'GET'],
    '/gravacao/deletarUnique'                   => ['GravacaoController', 'deletarGravacao', 'usuario', 'POST'],
    '/gravacao/deletarAll'                      => ['GravacaoController', 'deletarTodasGravacao', 'usuario', 'POST'],

    // Administração
    '/adm/index'                                => ['AdminController', 'index', 'admin', 'GET'],
    '/adm/infoUser/:id'                         => ['AdminController', 'infoUser', 'admin', 'GET'],
    '/adm/ativarExperimentos'                   => ['AdminController', 'ativarExperimentos', 'admin', 'POST'],
    '/adm/editarExperimentos'                   => ['AdminController', 'editarExperimentos', 'admin', 'POST'],
    '/adm/listarUsuarios'                       => ['AdminController', 'listarUsuarios', 'admin', 'GET'],
    '/adm/usuarios'                             => ['AdminController', 'listarUsuarios', 'admin', 'GET'],
    '/adm/createUser'                           => ['AdminController', 'createUser', 'admin', 'POST'],
    '/adm/detailsUser/:id'                      => ['AdminController', 'detailsUser', 'admin', 'GET'],
    '/adm/editarUsuario'                        => ['AdminController', 'editarUsuario', 'admin', 'POST'],
    '/adm/deletarUsuario/:id'                   => ['AdminController', 'deletarUsuario', 'admin', 'POST'],
];
