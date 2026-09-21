-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 15/09/2026 às 22:30
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `CEBIOHUB`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `anotacoes`
--

CREATE TABLE `anotacoes` (
  `anotacoes_id` int(11) NOT NULL,
  `anotacoes_conteudo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `anotacoes_criado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `anotacoes_atualizado_em` timestamp NULL DEFAULT NULL,
  `anotacoes_deletado_em` timestamp NULL DEFAULT NULL,
  `anotacoes_experimentos_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `anotacoes`
--

INSERT INTO `anotacoes` (`anotacoes_id`, `anotacoes_conteudo`, `anotacoes_criado_em`, `anotacoes_atualizado_em`, `anotacoes_deletado_em`, `anotacoes_experimentos_id`) VALUES
(1, 'EXPERIMENTANDO AS ANOTAÇOES DE NOVO\r\n', '2026-05-27 00:30:20', '2026-05-27 00:30:20', NULL, 17),
(2, 'Testando as anotaçoes do sistema', '2026-05-27 22:32:30', '2026-05-27 22:32:30', NULL, 19),
(3, '![](https://) testando anotação', '2026-05-27 23:22:23', '2026-05-27 23:22:23', NULL, 20),
(4, 'Testando as anotações do sistema de monitoramento', '2026-05-27 23:30:20', '2026-05-27 23:30:20', NULL, 18),
(5, '- [FAZER O PLANTIO DE DOIS EM DOIS DIAS ] Feito\r\n- ', '2026-05-27 23:59:06', '2026-05-27 23:59:06', NULL, 21),
(6, 'Hoje estou finalizando meu experimento por conta de uns problemas de saude, entao quero agradecer a todos pela oportunidade de me ajudar e fortalecer na caminada, obrigado a todos\r\n', '2026-06-02 18:14:01', '2026-06-02 23:14:01', NULL, 32),
(7, 'OLA GALERA DO GRUPO', '2026-06-03 01:05:49', '2026-06-03 01:05:49', NULL, 33),
(8, 'Testando a paginação do cebio\r\n', '2026-08-26 17:06:39', '2026-08-26 22:06:39', NULL, 43),
(9, 'Quero testar essas novas funcionalidades, antes de aplicar uma melhoria no desenvolvimento de escrita semanticas para organizar\r\nPosso anotar do jeito que eu bem entender.', '2026-07-28 21:16:33', '2026-07-29 02:16:33', NULL, 44),
(10, 'Ola testando a funcionalidade', '2026-07-29 21:07:46', '2026-07-30 02:07:46', NULL, 45),
(11, '1. [ X ] Fazer a plantação de ervas daninhas\r\n- [ X ] Fazer o cultivo desta plantação\r\n*  Quero ver como vai fazer pra utilizar esse sistema aqui no dia a dia', '2026-08-31 18:58:17', '2026-08-31 23:58:17', NULL, 49),
(12, '**![](https://)*Vamos testar essas funcionando***', '2026-09-15 19:49:17', '2026-09-16 00:49:17', NULL, 50);

-- --------------------------------------------------------

--
-- Estrutura para tabela `bancadas`
--

CREATE TABLE `bancadas` (
  `bancadas_id` int(11) NOT NULL,
  `bancadas_tipo_plantacao` varchar(100) DEFAULT NULL,
  `bancadas_nome_cientifico` varchar(100) DEFAULT NULL,
  `bancadas_quantidade` int(11) DEFAULT NULL,
  `bancadas_luzes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`bancadas_luzes`)),
  `bancadas_dispositivos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`bancadas_dispositivos`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `bancadas`
--

INSERT INTO `bancadas` (`bancadas_id`, `bancadas_tipo_plantacao`, `bancadas_nome_cientifico`, `bancadas_quantidade`, `bancadas_luzes`, `bancadas_dispositivos`) VALUES
(15, 'Banana', 'banana milanesa', 18, '[\"RGW\",\"Branca\",\"Vermelha\",\"Azul\"]', '[\"camera\",\"ventoinha\",\"lampada\",\"umidade\"]'),
(16, 'pera', 'pera', 12, '[\"RGW\",\"Branca\",\"Vermelha\",\"Azul\"]', '[\"camera\",\"ventoinha\",\"lampada\",\"umidade\"]'),
(17, 'Melancia media', 'Melnacium Grandie', 15, '[\"RGW\",\"Branca\",\"Vermelha\",\"Azul\"]', '[\"camera\",\"ventoinha\",\"lampada\",\"umidade\"]'),
(18, 'Melancia', 'Melancia', 15, '[\"RGW\",\"Branca\",\"Vermelha\",\"Azul\"]', '[\"camera\",\"ventoinha\",\"lampada\",\"umidade\"]'),
(19, 'Morango', 'Morango', 14, '[\"RGW\",\"Branca\",\"Vermelha\",\"Azul\"]', '[\"camera\",\"ventoinha\",\"lampada\",\"umidade\"]'),
(20, 'Hidropônica', 'Lactuca sativa', 24, '[\"RGW\",\"Branca\",\"Vermelha\",\"Azul\"]', '[\"camera\",\"ventoinha\",\"lampada\",\"umidade\"]'),
(21, 'Abacaxi', 'AbacaxiMellon', 15, '[\"Azul\"]', '[\"lampada\"]'),
(22, 'Abacaxi', 'AbacaxiMellon', 15, '\"[]\"', '[\"umidade\"]'),
(23, 'Abacaxi pequeno', 'AbacaxiMellon', 15, '[\"RGW\",\"Branca\",\"Vermelha\",\"Azul\"]', '[\"camera\",\"ventoinha\",\"lampada\",\"umidade\"]'),
(24, 'baaaaaaaaa', 'açpllll~~~´]', 10, '\"[]\"', '[]'),
(25, 'baaaaaaaaa', 'açpllll~~~´]', 10, '\"[]\"', '[]'),
(26, 'Plantação de microVerde', 'macarroni', 19, '[\"RGW\",\"Branca\",\"Vermelha\",\"Azul\"]', '[\"camera\",\"ventoinha\",\"lampada\",\"umidade\"]'),
(27, 'Morangos', 'Morangos', 10, '[\"RGW\",\"Branca\"]', '[\"camera\",\"ventoinha\"]'),
(28, 'Morangos', 'Morangos', 10, '[\"RGW\",\"Branca\"]', '[\"camera\",\"ventoinha\"]'),
(29, 'Melancia', 'Melancia', 10, '[\"RGW\",\"Branca\",\"Vermelha\",\"Azul\"]', '[\"camera\",\"ventoinha\",\"lampada\",\"umidade\"]'),
(30, 'Melancia', 'Melancia', 10, '[\"RGW\",\"Branca\",\"Vermelha\",\"Azul\"]', '[\"camera\",\"ventoinha\",\"lampada\",\"umidade\"]'),
(31, 'Melancia doce', 'Melnacium Grandie', 15, '[\"RGW\",\"Branca\"]', '[\"ventoinha\",\"umidade\"]'),
(32, 'Melancia doce', 'Melnacium Grandie', 15, '[\"RGW\",\"Branca\"]', '[\"ventoinha\",\"umidade\"]'),
(33, 'Pera Grande', 'Pera a milanesa', 18, '[\"RGW\",\"Branca\",\"Vermelha\",\"Azul\"]', '[]'),
(34, 'Chocolates', 'Chocolates na vertical', 19, '[\"RGW\",\"Branca\",\"Vermelha\",\"Azul\"]', '[]'),
(35, 'Manjericao', 'Manjericao', 17, '[\"RGW\",\"Branca\",\"Vermelha\",\"Azul\"]', '[]'),
(36, 'Manjericao', 'Manjericao', 48, '[\"RGW\",\"Branca\",\"Vermelha\",\"Azul\"]', '[]'),
(37, 'Melancia', 'Melancia', 10, '[\"RGW\",\"Branca\",\"Vermelha\",\"Azul\"]', '[]'),
(38, 'Acabaxi', 'Abacaxi', 10, '[\"RGW\",\"Branca\",\"Vermelha\",\"Azul\"]', '[]'),
(39, 'Melancia', 'Melnacium Grandie', 10, '[\"RGW\",\"Branca\",\"Vermelha\",\"Azul\"]', '[]'),
(40, 'abcde', 'abcde', 1, '[\"RGW\",\"Branca\"]', '[]'),
(41, 'Melancia', 'Melancia', 10, '[\"RGW\",\"Branca\"]', '[]');

-- --------------------------------------------------------

--
-- Estrutura para tabela `bancadas_experimentos`
--

CREATE TABLE `bancadas_experimentos` (
  `be_id` int(11) NOT NULL,
  `be_bancadas_id` int(11) DEFAULT NULL,
  `be_experimentos_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `bancadas_experimentos`
--

INSERT INTO `bancadas_experimentos` (`be_id`, `be_bancadas_id`, `be_experimentos_id`) VALUES
(1, 15, 17),
(2, 16, 18),
(3, 17, 19),
(4, 18, 20),
(5, 19, 21),
(6, 20, 22),
(7, 21, 24),
(8, 22, 25),
(9, 23, 26),
(10, 24, 30),
(11, 25, 31),
(12, 26, 32),
(13, 27, 33),
(14, 28, 34),
(15, 29, 35),
(16, 30, 36),
(17, 31, 37),
(18, 32, 38),
(19, 33, 40),
(20, 34, 41),
(21, 35, 42),
(22, 36, 43),
(23, 37, 44),
(24, 38, 45),
(25, 39, 46),
(26, 40, 49),
(27, 41, 50);

-- --------------------------------------------------------

--
-- Estrutura para tabela `experimentos`
--

CREATE TABLE `experimentos` (
  `experimentos_id` int(11) NOT NULL,
  `experimentos_titulo` varchar(255) NOT NULL,
  `experimentos_inicio` date DEFAULT NULL,
  `experimentos_final` date DEFAULT NULL,
  `experimentos_sala` varchar(30) DEFAULT NULL,
  `experimentos_status` varchar(10) DEFAULT NULL,
  `experimentos_encerrado` timestamp NULL DEFAULT NULL,
  `experimentos_imagem` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `experimentos`
--

INSERT INTO `experimentos` (`experimentos_id`, `experimentos_titulo`, `experimentos_inicio`, `experimentos_final`, `experimentos_sala`, `experimentos_status`, `experimentos_encerrado`, `experimentos_imagem`) VALUES
(17, 'Experimento x', '2026-05-01', '2026-05-04', 'sala de experimento', 'Finalizado', '2026-05-27 00:30:32', 'Public/templates/Images/6a15f4baecdfc-melanciadoces.jpeg'),
(18, 'Experimento xZ', '2025-09-10', '2027-02-15', 'peraaqw', 'Finalizado', '2026-05-27 23:33:16', 'Public/templates/Images/6a15f63085ae9-melanciadoces.jpeg'),
(19, 'Teste da melancia', '2026-02-15', '2026-06-20', 'Sala vertical ', 'Ativo', NULL, 'Public/templates/Images/6a172a8146f70-melanciadoces.jpeg'),
(20, 'Melancias BioInsumos', '2026-05-27', '2026-05-30', 'Vertical', 'Ativo', NULL, 'Public/templates/Images/6a173628a2ba3-melanciadoces.jpeg'),
(21, 'Morangos', '2026-02-15', '2026-02-15', 'Morangos', 'Finalizado', '2026-05-28 00:26:28', 'Public/templates/Images/6a173d1072446-melanciadoces.jpeg'),
(22, 'Monitoramento Inteligente de Cultivo Hidropônico', '2025-05-10', '2026-08-30', 'Laboratório 03 – Bloco B', 'Finalizado', '2026-06-02 22:19:46', 'Public/templates/Images/6a174c80b6ad9-hidroponia-.jpg'),
(23, 'Avaliação do Crescimento de abacaxi em Ambiente Controlado', '2026-06-02', '2026-10-28', 'LabTI', 'Ativo', NULL, 'Public/templates/Images/6a1f1303af40f-abacaxi.jpg'),
(24, 'Avaliação do Crescimento de abacaxi em Ambiente Controlado', '2026-06-02', '2026-10-28', 'LabTI', 'Finalizado', '2026-06-02 17:30:28', 'Public/templates/Images/6a1f13135b40b-abacaxi.jpg'),
(25, 'Avaliação do Crescimento de abacaxi em Ambiente Controlado', '2026-06-02', '2026-10-28', 'LabTI', 'Finalizado', '2026-06-02 17:30:25', 'Public/templates/Images/6a1f1317ec8e6-macaco1.jpg'),
(26, 'Avaliação do Crescimento de abacaxi em Ambiente Controlado', '2026-06-02', '2027-05-15', 'LabTI', 'Finalizado', '2026-06-02 17:36:48', 'Public/templates/Images/6a1f13df742aa-abacaxi.jpg'),
(27, 'abcde', '2026-06-10', '2027-03-26', 'açlo', 'Ativo', NULL, 'Public/templates/Images/6a1f19b20ffca-abacaxi.jpg'),
(28, 'abcde', '2026-06-10', '2027-03-26', 'açlo', 'Ativo', NULL, 'Public/templates/Images/6a1f19c7c4386-abacaxi.jpg'),
(29, 'abcde', '2026-06-10', '2027-03-26', 'açlo', 'Ativo', NULL, 'Public/templates/Images/6a1f19ddd772b-abacaxi.jpg'),
(30, 'abcde', '2026-06-10', '2027-03-26', 'açlo', 'Ativo', NULL, 'Public/templates/Images/6a1f19e9c2324-abacaxi.jpg'),
(31, 'abcde', '2026-06-10', '2027-03-26', 'açlo', 'Finalizado', '2026-06-02 18:07:26', 'Public/templates/Images/6a1f19fccd0c2-abacaxi.jpg'),
(32, 'Avaliação do Crescimento de Alface em Ambiente Controlado', '2026-06-12', '2026-09-24', 'LabTI123456', 'Excluido', NULL, 'Public/templates/Images/6a1f1cf19fcb2-abacaxi.jpg'),
(33, 'Plantação de morango', '2026-06-06', '2027-06-12', 'MORANGO', 'Ativo', NULL, 'Public/templates/Images/6a1f3629ab35f-abacaxi.jpg'),
(34, 'Plantação de morango', '2026-06-06', '2027-06-12', 'MORANGO0000', 'Ativo', NULL, 'Public/templates/Images/6a1f363001a45-abacaxi.jpg'),
(35, 'Melancia do Cebio', '2026-06-15', '2027-05-19', 'Laboratorio Vertical Farming', 'Finalizado', '2026-06-16 19:29:44', 'Public/templates/Images/6a2700d584572-melanciadoces.jpeg'),
(36, 'Melancia do Cebio', '2026-06-15', '2027-05-19', 'Laboratorio Vertical Farming', 'Finalizado', '2026-06-16 19:29:26', 'Public/templates/Images/6a2700dbbfa54-melanciadoces.jpeg'),
(37, 'Melancia com bioInsumo', '2026-06-14', '2027-05-15', 'Laboratorio Vertical Farming', 'Ativo', NULL, 'Public/templates/Images/6a2705e53ceb1-melanciadoces.jpeg'),
(38, 'Melancia com bioInsumo', '2026-06-15', '2027-05-15', 'Laboratorio Vertical Farming', 'Excluido', NULL, 'Public/templates/Images/6a27065985aeb-melanciadoces.jpeg'),
(39, 'Pera com microbioticos', '2026-06-10', '2027-05-18', 'Vertical Farming', 'Ativo', NULL, 'Public/templates/Images/6a31a4a9171a3-hidroponia-.jpg'),
(40, 'Pera com microbioticos', '2026-06-10', '2027-05-18', 'Vertical Farming', 'Finalizado', '2026-06-17 19:56:13', 'Public/templates/Images/6a31a4fd302d1-hidroponia-.jpg'),
(41, 'Chocolantes na plantação', '2026-06-20', '2027-05-19', 'vertical farming', 'Ativo', NULL, 'Public/templates/Images/6a32fef10aa69-hidroponia-.jpg'),
(42, 'Melancia Filantropo', '2026-06-25', '2026-07-31', 'vertical farming', 'Ativo', NULL, 'Public/templates/Images/6a398c56cccd5-hidroponia-.jpg'),
(43, 'Manjericao com diferentes tipos de luzes', '2026-06-27', '2026-06-27', 'vertical farming', 'Finalizado', '2026-08-26 17:09:35', 'Public/templates/Images/6a3b0d7b76068-WhatsApp Image 2026-06-23 at 17.24.17.jpeg'),
(44, 'TestandoOexperimento', '2026-08-01', '2026-09-14', 'Sala vertical', 'Ativo', NULL, 'Public/templates/Images/6a690ce12607e-melanciadoces.jpeg'),
(45, 'Analise de manjericao sobre cuidados esteriotipados na vertical farming', '2026-07-29', '2027-07-20', 'Vertical Farming', 'Finalizado', '2026-07-29 21:08:07', 'Public/templates/Images/6a6a689570bb1-melanciadoces.jpeg'),
(46, 'Agua na melancia gaseficada de forma natural e artificial', '2026-08-31', '2026-09-25', 'Sala 152', 'Excluido', NULL, 'Public/templates/Images/6a8f1ea45c605-hidroponia-.jpg'),
(47, 'abcde', '2026-09-02', '2026-09-12', 'abcde', 'Ativo', NULL, 'Public/templates/Images/6a95cdc7ab57c-hidroponia-.jpg'),
(48, 'abcde', '2026-09-02', '2026-09-12', 'abcde', 'Ativo', NULL, 'Public/templates/Images/6a95cddb61394-hidroponia-.jpg'),
(49, 'abcde', '2026-09-09', '2026-09-12', 'abcde', 'Ativo', NULL, 'Public/templates/Images/6a95ce041bc74-hidroponia-.jpg'),
(50, 'Teste de melancia', '2026-09-02', '2026-10-19', 'Sala da vertical Farming', 'Ativo', NULL, 'Public/templates/Images/6a95cf801a065-imagem2Vertical.png');

-- --------------------------------------------------------

--
-- Estrutura para tabela `participantes`
--

CREATE TABLE `participantes` (
  `participantes_id` int(11) NOT NULL,
  `participantes_nome` varchar(255) DEFAULT NULL,
  `participantes_orientador` varchar(255) NOT NULL,
  `participantes_experimentos_id` int(11) DEFAULT NULL,
  `participantes_usuarios_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `participantes`
--

INSERT INTO `participantes` (`participantes_id`, `participantes_nome`, `participantes_orientador`, `participantes_experimentos_id`, `participantes_usuarios_id`) VALUES
(1, 'Felipe , Layza Lopes\r\n', 'Vilson Soares', 17, 16),
(2, 'Felipe , Ola mundo', 'Vilson Soares', 18, 16),
(3, ' , Janaina', 'Vilson Soares', 19, 17),
(4, ' , Felipe', 'Henrique', 20, 18),
(5, 'Janaina Batista de Lima , Felipe e Cassia', 'Henrique', 21, 19),
(6, 'Janaina Batista de Lima , Cássia e Henrique', 'Vilson Soares', 22, 16),
(7, 'Janaina, Cassia e Carlos', 'Vilson Soares', 24, 16),
(8, '', 'Vilson Soares', 25, 16),
(9, 'Janaína, Cassia e Pedro Sampaio', 'Vilson Soares', 26, 15),
(10, '', 'Vilson Soares', 31, 15),
(11, 'BonitorMar, FeioMar e Napoleao', 'Vilson Soares', 32, 21),
(12, '', 'Vilson Soares', 33, 21),
(13, '', 'Vilson Soares', 34, 21),
(14, '', 'Vilson Soares', 35, 15),
(15, '', 'Vilson Soares', 36, 15),
(16, 'Felipe Ferreira ', 'Henrique', 37, 22),
(17, 'Felipe Ferreira ', 'Henrique', 38, 22),
(18, '', 'Vilson Soares', 40, 15),
(19, 'Debora e Bianca', 'Flávia', 41, 24),
(20, 'Vincius e Ana', 'Henrique', 42, 16),
(21, 'Vincius, Felipe Ferreira e Vilson Soares', 'Henrique', 43, 25),
(22, 'Felipe Ferreira Gomes', 'Vilson Soares', 44, 27),
(23, 'Felipe Ferreira Gomes', 'Vilson Soares', 45, 28),
(24, 'Melancia e o abacaxi', 'Vilson Soares', 46, 25),
(25, 'Somente eu', 'Vilson Soares', 49, 15),
(26, 'Felipe, Janaina, Bianca, Arthur e Ana Julia', 'Vilson Soares', 50, 25);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `usuarios_id` int(11) NOT NULL,
  `usuarios_nome` varchar(255) DEFAULT NULL,
  `usuarios_escolaridade` varchar(255) DEFAULT NULL,
  `usuarios_email` varchar(255) DEFAULT NULL,
  `usuarios_senha` varchar(255) DEFAULT NULL,
  `usuarios_imagem` varchar(255) DEFAULT NULL,
  `usuarios_ativo` tinyint(1) DEFAULT NULL,
  `usuarios_nivel` int(11) NOT NULL,
  `usuarios_ativacao_hash` varchar(255) DEFAULT NULL,
  `usuarios_reset_hash` varchar(255) DEFAULT NULL,
  `usuarios_expira_em` varchar(255) DEFAULT NULL,
  `usuarios_criado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `usuarios_atualizado_em` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `usuarios_deletado_em` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`usuarios_id`, `usuarios_nome`, `usuarios_escolaridade`, `usuarios_email`, `usuarios_senha`, `usuarios_imagem`, `usuarios_ativo`, `usuarios_nivel`, `usuarios_ativacao_hash`, `usuarios_reset_hash`, `usuarios_expira_em`, `usuarios_criado_em`, `usuarios_atualizado_em`, `usuarios_deletado_em`) VALUES
(15, 'Felipe', 'Graduação', 'felipeferreiraag0@gmail.com', '$2y$10$wGsyQJi1OWt8zUhcTo24vuWWgpaHY7H3MldfeZJ5UDEBqEUgw8wSS', 'Public/uploadImages/6a15ef20641e1-FotoMInha.jpeg', 1, 1, NULL, NULL, NULL, '2026-09-15 18:14:40', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(16, 'Felipe Ferreira Gomes ', 'Graduação', 'drawaz03@gmail.com', '$2y$10$foXihwogkguIGBddm1y0wOtKosZ72qBIZhnWtBnLUBrUtM9wDIm9O', 'Public/uploadImages/6a15f421e79ed-FotoMInha.jpeg', 1, 1, NULL, NULL, NULL, '2026-05-27 18:29:21', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(17, 'Felipe Ferreira ', 'Graduação', 'felipe123@gmail.com', '$2y$10$VbmwkJik3jsJKSgJD0Ye8ODISaedLwBhgoWm8M/VjMooRhrT/4VfK', 'Public/uploadImages/6a172a2d50d3d-FotoMInha.jpeg', 1, 1, NULL, NULL, NULL, '2026-05-27 17:30:21', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(18, 'Janaina Batista de Lima', 'Mestrado', 'jana24bl@gmail.com', '$2y$10$id6FrlpjpJHr.giLMYJTs.Y.ngYh.G/ZT6pOpNAq6EYLUtaOq0wpm', 'Public/uploadImages/6a17357ee3874-cristiano.jpg', 1, 1, NULL, 'bf67ea1fb8846ed00babffccf2213440bd44c5a396c535fe61b8b76697833545', '2026-05-27 21:24:06', '2026-05-27 18:45:19', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(19, 'Janaina Batista de Lima', 'Mestrado', 'janaina123@gmail.com', '$2y$10$acxNegQfCCbNy9nJvD4HQueph6qPPqgAxCK/8rGCbnhr.nFxViKC6', 'Public/uploadImages/6a173caeef331-cristiano.jpg', 1, 1, NULL, NULL, NULL, '2026-05-27 18:49:19', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(20, 'Matusalem', 'Ensino Médio', 'matusalem@gmail.com', '$2y$10$Mk/V24XpW2JoDnf3SbqlSuao7KgSQ6dt8XDH3ARzg.ylXXUhi74te', 'Public/uploadImages/6a1dd900565f9-messiCareca.jpg', 1, 1, NULL, NULL, NULL, '2026-06-01 19:09:52', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(21, 'LindoMar', 'Ensino Médio', 'lindomar123@gmail.com', '$2y$10$GO9dGmhTDlDSjhNHR8TbvOCYaRNGyYwUt1X698mx14au4DKacdGtu', 'Public/uploadImages/6a1f1c5e0f1e3-messiCareca.jpg', 1, 1, NULL, NULL, NULL, '2026-06-02 18:09:34', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(22, 'Vilson Siqueira Soares', 'Doutorado', 'vilsonssoares@gmail.com', '$2y$10$gQH7iJoLLKiqyNYIAp.7XuiwLw8FeWwMb91hXNTrkFl/ZlPi9dXve', 'Public/uploadImages/6a270563e8281-messiCareca.jpg', 1, 1, NULL, NULL, NULL, '2026-06-08 18:09:40', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(23, 'Thomas Tuchel', 'Doutorado', 'tomastuchel@gmail.com', '$2y$10$qfKrV53OYazJkMxzyPNQie8YRNxD.RejNTqbeDyTNSf436t2ESdFy', 'Public/uploadImages/6a32fd5a7741d-messiCareca.jpg', 1, 1, NULL, NULL, NULL, '2026-06-17 20:02:34', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(24, 'Thomas Tuchel', 'Doutorado', 'tomasthucel@gmail.com', '$2y$10$YlZm/lhv5rCk0K1hwngDJulmf/ZgGTi9NibJ3avz/2vkLndMhleMa', 'Public/uploadImages/6a32fe18521c8-messiCareca.jpg', 1, 1, NULL, NULL, NULL, '2026-06-17 20:05:44', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(25, 'Administrador CEBIO', 'Doutorado', 'administradorcebio@gmail.com', '$2y$10$YJM5xEgQchd3q0LZoagp6.sf757WpL.gF4vdRz9Jj/WEhljiNapGC', 'Public/uploadImages/6a3b0cba4a8b5-CEBIO.jpeg', 1, 1, NULL, NULL, NULL, '2026-06-23 22:46:18', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(26, 'olaMundo', 'Doutorado', 'vaitomando@gmail.com', '$2y$10$Rpian/wxWs17pKMQAtqC9.6B1VkbXGbSrgKjw2NkumAdhUU5Be0oO', 'Public/uploadImages/6a45ba6a169b5-dheniel.png', 1, 1, NULL, NULL, NULL, '2026-07-02 01:10:02', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(27, 'CebioTeste', 'Doutorado', 'cebio@teste.com', '$2y$10$ffCzESiB//D19EFspQaKR.XYKFalv3TtjX2l27u2x3mJFqjkjGxOm', 'Public/uploadImages/6a68fe86e1d57-messiCareca.jpg', 1, 1, NULL, NULL, NULL, '2026-07-28 19:09:58', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(28, 'Oliver Tree', 'Mestrado', 'oliverTree@gmail.com', '$2y$10$wcEDoifDh/UlUxPnV4vVye3iYPRUXksVDYsWUPfsl1yUIcpk7UBJS', 'Public/uploadImages/6a6a67ca11f8a-messiCareca.jpg', 1, 1, NULL, NULL, NULL, '2026-07-29 20:51:22', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(29, 'Tiago', 'Graduação', 'tiago@gmail.com', '$2y$10$9hH6exMaYYQ.TqXC6bE7nu5zmARx2WJAGdpM9GnJxw9NdWpqpFlUC', 'Public/uploadImages/6a7bc800e2509-CEBIO.jpeg', 1, 1, NULL, NULL, NULL, '2026-08-12 01:10:24', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(30, 'Felipe Ferreira Gomes', 'Graduação', 'felipe.gomes1@estudante.ifgoiano.edu.br', '$2y$10$HJ25Vn/WJ6/pLTXFzwSKneCzJ8zxm0ImYzMqvNRcKN9WjzHmo8OWC', 'Public/uploadImages/6a8ddaaa69396-CEBIO.jpeg', 1, 1, NULL, NULL, NULL, '2026-08-25 18:10:50', '0000-00-00 00:00:00', '0000-00-00 00:00:00');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `anotacoes`
--
ALTER TABLE `anotacoes`
  ADD PRIMARY KEY (`anotacoes_id`),
  ADD KEY `FK_anotacoes_2` (`anotacoes_experimentos_id`);

--
-- Índices de tabela `bancadas`
--
ALTER TABLE `bancadas`
  ADD PRIMARY KEY (`bancadas_id`);

--
-- Índices de tabela `bancadas_experimentos`
--
ALTER TABLE `bancadas_experimentos`
  ADD PRIMARY KEY (`be_id`),
  ADD KEY `FK_bancadas_experimentos_1` (`be_bancadas_id`),
  ADD KEY `FK_bancadas_experimentos_2` (`be_experimentos_id`);

--
-- Índices de tabela `experimentos`
--
ALTER TABLE `experimentos`
  ADD PRIMARY KEY (`experimentos_id`);

--
-- Índices de tabela `participantes`
--
ALTER TABLE `participantes`
  ADD PRIMARY KEY (`participantes_id`),
  ADD KEY `FK_participantes_1` (`participantes_experimentos_id`),
  ADD KEY `FK_participantes_2` (`participantes_usuarios_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`usuarios_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `anotacoes`
--
ALTER TABLE `anotacoes`
  MODIFY `anotacoes_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `bancadas`
--
ALTER TABLE `bancadas`
  MODIFY `bancadas_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT de tabela `bancadas_experimentos`
--
ALTER TABLE `bancadas_experimentos`
  MODIFY `be_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de tabela `experimentos`
--
ALTER TABLE `experimentos`
  MODIFY `experimentos_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT de tabela `participantes`
--
ALTER TABLE `participantes`
  MODIFY `participantes_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `usuarios_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `anotacoes`
--
ALTER TABLE `anotacoes`
  ADD CONSTRAINT `FK_anotacoes_2` FOREIGN KEY (`anotacoes_experimentos_id`) REFERENCES `experimentos` (`experimentos_id`);

--
-- Restrições para tabelas `bancadas_experimentos`
--
ALTER TABLE `bancadas_experimentos`
  ADD CONSTRAINT `FK_bancadas_experimentos_1` FOREIGN KEY (`be_bancadas_id`) REFERENCES `bancadas` (`bancadas_id`),
  ADD CONSTRAINT `FK_bancadas_experimentos_2` FOREIGN KEY (`be_experimentos_id`) REFERENCES `experimentos` (`experimentos_id`);

--
-- Restrições para tabelas `participantes`
--
ALTER TABLE `participantes`
  ADD CONSTRAINT `FK_participantes_1` FOREIGN KEY (`participantes_experimentos_id`) REFERENCES `experimentos` (`experimentos_id`),
  ADD CONSTRAINT `FK_participantes_2` FOREIGN KEY (`participantes_usuarios_id`) REFERENCES `usuarios` (`usuarios_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
