-- =====================================================================
-- FarmView - schema do banco (instalação nova)
-- Compatível com MySQL 8 e MariaDB 10.5+. Não contém dados pessoais.
-- Para atualizar um banco existente, use db/migrations/.
-- =====================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `usuarios` (
  `usuarios_id`            INT NOT NULL AUTO_INCREMENT,
  `usuarios_nome`          VARCHAR(255) NOT NULL,
  `usuarios_escolaridade`  VARCHAR(255) DEFAULT NULL,
  `usuarios_email`         VARCHAR(255) NOT NULL,
  `usuarios_senha`         VARCHAR(255) NOT NULL,
  `usuarios_imagem`        VARCHAR(255) DEFAULT NULL,
  `usuarios_ativo`         TINYINT(1) NOT NULL DEFAULT 1,
  `usuarios_nivel`         INT NOT NULL DEFAULT 1,
  `usuarios_ativacao_hash` VARCHAR(255) DEFAULT NULL,
  `usuarios_reset_hash`    CHAR(64) DEFAULT NULL COMMENT 'SHA-256 do token de redefinição',
  `usuarios_expira_em`     DATETIME DEFAULT NULL,
  `usuarios_criado_em`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usuarios_atualizado_em` DATETIME DEFAULT NULL,
  `usuarios_deletado_em`   DATETIME DEFAULT NULL,
  PRIMARY KEY (`usuarios_id`),
  UNIQUE KEY `uq_usuarios_email` (`usuarios_email`),
  KEY `idx_usuarios_reset` (`usuarios_reset_hash`),
  CONSTRAINT `chk_usuarios_nivel` CHECK (`usuarios_nivel` IN (0, 1, 2)),
  CONSTRAINT `chk_usuarios_ativo` CHECK (`usuarios_ativo` IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `experimentos` (
  `experimentos_id`        INT NOT NULL AUTO_INCREMENT,
  `experimentos_titulo`    VARCHAR(255) NOT NULL,
  `experimentos_inicio`    DATE DEFAULT NULL,
  `experimentos_final`     DATE DEFAULT NULL,
  `experimentos_sala`      VARCHAR(30) DEFAULT NULL,
  `experimentos_status`    ENUM('Ativo', 'Finalizado', 'Excluido') NOT NULL DEFAULT 'Ativo',
  `experimentos_encerrado` DATETIME DEFAULT NULL,
  `experimentos_imagem`    VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`experimentos_id`),
  KEY `idx_experimentos_status` (`experimentos_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `bancadas` (
  `bancadas_id`              INT NOT NULL AUTO_INCREMENT,
  `bancadas_tipo_plantacao`  VARCHAR(100) DEFAULT NULL,
  `bancadas_nome_cientifico` VARCHAR(100) DEFAULT NULL,
  `bancadas_quantidade`      INT DEFAULT NULL,
  `bancadas_luzes`           LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`bancadas_luzes`)),
  `bancadas_dispositivos`    LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`bancadas_dispositivos`)),
  PRIMARY KEY (`bancadas_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `bancadas_experimentos` (
  `be_id`              INT NOT NULL AUTO_INCREMENT,
  `be_bancadas_id`     INT NOT NULL,
  `be_experimentos_id` INT NOT NULL,
  PRIMARY KEY (`be_id`),
  KEY `FK_bancadas_experimentos_1` (`be_bancadas_id`),
  KEY `FK_bancadas_experimentos_2` (`be_experimentos_id`),
  CONSTRAINT `FK_bancadas_experimentos_1` FOREIGN KEY (`be_bancadas_id`) REFERENCES `bancadas` (`bancadas_id`),
  CONSTRAINT `FK_bancadas_experimentos_2` FOREIGN KEY (`be_experimentos_id`) REFERENCES `experimentos` (`experimentos_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `participantes` (
  `participantes_id`              INT NOT NULL AUTO_INCREMENT,
  `participantes_nome`            VARCHAR(255) DEFAULT NULL,
  `participantes_orientador`      VARCHAR(255) NOT NULL,
  `participantes_experimentos_id` INT DEFAULT NULL,
  `participantes_usuarios_id`     INT DEFAULT NULL,
  PRIMARY KEY (`participantes_id`),
  KEY `FK_participantes_1` (`participantes_experimentos_id`),
  KEY `idx_participantes_usuario_experimento` (`participantes_usuarios_id`, `participantes_experimentos_id`),
  CONSTRAINT `FK_participantes_1` FOREIGN KEY (`participantes_experimentos_id`) REFERENCES `experimentos` (`experimentos_id`),
  CONSTRAINT `FK_participantes_2` FOREIGN KEY (`participantes_usuarios_id`) REFERENCES `usuarios` (`usuarios_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `anotacoes` (
  `anotacoes_id`              INT NOT NULL AUTO_INCREMENT,
  `anotacoes_conteudo`        TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `anotacoes_criado_em`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `anotacoes_atualizado_em`   DATETIME DEFAULT NULL,
  `anotacoes_deletado_em`     DATETIME DEFAULT NULL,
  `anotacoes_experimentos_id` INT DEFAULT NULL,
  PRIMARY KEY (`anotacoes_id`),
  KEY `FK_anotacoes_2` (`anotacoes_experimentos_id`),
  CONSTRAINT `FK_anotacoes_2` FOREIGN KEY (`anotacoes_experimentos_id`) REFERENCES `experimentos` (`experimentos_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `videos_experimentos` (
  `videos_id`        INT NOT NULL AUTO_INCREMENT,
  `path_video`       VARCHAR(255) NOT NULL,
  `usuarios_id`      INT NOT NULL,
  `experimentos_id`  INT NOT NULL,
  `duracao_segundos` INT NOT NULL DEFAULT 0,
  `tamanho_bytes`    BIGINT NOT NULL DEFAULT 0,
  `videos_status`    ENUM('Ativo', 'Desativado') NOT NULL DEFAULT 'Ativo',
  `criado_em`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deletado_em`      DATETIME DEFAULT NULL,
  PRIMARY KEY (`videos_id`),
  KEY `idx_videos_usuario` (`usuarios_id`, `videos_status`),
  KEY `idx_videos_experimento` (`experimentos_id`),
  CONSTRAINT `fk_videos_usuario` FOREIGN KEY (`usuarios_id`) REFERENCES `usuarios` (`usuarios_id`),
  CONSTRAINT `fk_videos_experimento` FOREIGN KEY (`experimentos_id`) REFERENCES `experimentos` (`experimentos_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `login_tentativas` (
  `id`           INT NOT NULL AUTO_INCREMENT,
  `ip`           VARCHAR(45) NOT NULL,
  `email`        VARCHAR(255) NOT NULL,
  `tentativa_em` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_login_tentativas` (`ip`, `email`, `tentativa_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
