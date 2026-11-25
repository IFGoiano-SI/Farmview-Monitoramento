-- =====================================================================
-- Migração 001 — para bancos criados a partir do dump antigo (CEBIOHUB.sql)
--
-- Faça BACKUP antes:  mysqldump -u root -p CEBIOHUB > backup.sql
-- Execute uma única vez: mysql -u root -p CEBIOHUB < db/migrations/001_seguranca_e_integridade.sql
--
-- Pode ser executada de novo se parar no meio (ex.: após corrigir um erro).
--
-- Verificação recomendada ANTES de rodar (deve retornar 0 linhas):
--   SELECT DISTINCT experimentos_status FROM experimentos
--    WHERE experimentos_status NOT IN ('Ativo','Finalizado','Excluido');
-- =====================================================================

-- Permite corrigir as datas '0000-00-00' herdadas do schema antigo.
SET SESSION sql_mode = '';

-- ---------- usuarios ----------
ALTER TABLE `usuarios`
  MODIFY `usuarios_atualizado_em` DATETIME NULL DEFAULT NULL,
  MODIFY `usuarios_deletado_em`   DATETIME NULL DEFAULT NULL,
  MODIFY `usuarios_criado_em`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  MODIFY `usuarios_expira_em`     DATETIME NULL DEFAULT NULL,
  MODIFY `usuarios_reset_hash`    CHAR(64) NULL DEFAULT NULL;

UPDATE `usuarios` SET `usuarios_atualizado_em` = NULL WHERE `usuarios_atualizado_em` = '0000-00-00 00:00:00';
UPDATE `usuarios` SET `usuarios_deletado_em`   = NULL WHERE `usuarios_deletado_em`   = '0000-00-00 00:00:00';

-- Tokens antigos eram guardados em texto puro; agora o banco guarda só o SHA-256.
UPDATE `usuarios` SET `usuarios_reset_hash` = NULL, `usuarios_expira_em` = NULL;

UPDATE `usuarios` SET `usuarios_ativo` = 1 WHERE `usuarios_ativo` IS NULL;

-- E-mails duplicados (o cadastro antigo permitia). Mantém o registro mais antigo,
-- que era o usado pelo login antigo; os demais são DESATIVADOS e têm o e-mail
-- renomeado para "duplicado-<id>-<email>". Nada é apagado: experimentos e vídeos
-- continuam ligados ao mesmo ID. Para conferir depois:
--   SELECT usuarios_id, usuarios_nome, usuarios_email FROM usuarios WHERE usuarios_email LIKE 'duplicado-%';
UPDATE `usuarios` u
INNER JOIN (
    SELECT `usuarios_email` AS email, MIN(`usuarios_id`) AS manter
    FROM `usuarios`
    GROUP BY `usuarios_email`
    HAVING COUNT(*) > 1
) d ON d.email = u.`usuarios_email` AND u.`usuarios_id` <> d.manter
SET u.`usuarios_email` = LEFT(CONCAT('duplicado-', u.`usuarios_id`, '-', u.`usuarios_email`), 255),
    u.`usuarios_ativo` = 0;

ALTER TABLE `usuarios`
  MODIFY `usuarios_ativo` TINYINT(1) NOT NULL DEFAULT 1,
  ADD UNIQUE KEY `uq_usuarios_email` (`usuarios_email`),
  ADD KEY `idx_usuarios_reset` (`usuarios_reset_hash`),
  ADD CONSTRAINT `chk_usuarios_nivel` CHECK (`usuarios_nivel` IN (0, 1, 2)),
  ADD CONSTRAINT `chk_usuarios_ativo` CHECK (`usuarios_ativo` IN (0, 1));

-- ---------- experimentos ----------
ALTER TABLE `experimentos` MODIFY `experimentos_encerrado` DATETIME NULL DEFAULT NULL;
UPDATE `experimentos` SET `experimentos_encerrado` = NULL WHERE `experimentos_encerrado` = '0000-00-00 00:00:00';

ALTER TABLE `experimentos`
  MODIFY `experimentos_status` ENUM('Ativo', 'Finalizado', 'Excluido') NOT NULL DEFAULT 'Ativo',
  ADD KEY `idx_experimentos_status` (`experimentos_status`);

-- ---------- bancadas ----------
-- Experimentos cadastrados sem luz gravavam a string JSON "[]" em vez de uma lista.
UPDATE `bancadas` SET `bancadas_luzes` = '[]' WHERE `bancadas_luzes` = '"[]"';
UPDATE `bancadas` SET `bancadas_dispositivos` = '[]' WHERE `bancadas_dispositivos` = '"[]"';

-- ---------- participantes ----------
ALTER TABLE `participantes`
  ADD KEY `idx_participantes_usuario_experimento` (`participantes_usuarios_id`, `participantes_experimentos_id`);

-- ---------- anotacoes ----------
-- A data de criação não deve mudar a cada atualização.
ALTER TABLE `anotacoes`
  MODIFY `anotacoes_criado_em`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  MODIFY `anotacoes_atualizado_em` DATETIME NULL DEFAULT NULL,
  MODIFY `anotacoes_deletado_em`   DATETIME NULL DEFAULT NULL;

-- ---------- videos_experimentos ----------
UPDATE `videos_experimentos` SET `videos_status` = 'Desativado' WHERE `videos_status` NOT IN ('Ativo', 'Desativado');

ALTER TABLE `videos_experimentos`
  MODIFY `videos_status` ENUM('Ativo', 'Desativado') NOT NULL DEFAULT 'Ativo',
  ADD CONSTRAINT `fk_videos_usuario` FOREIGN KEY (`usuarios_id`) REFERENCES `usuarios` (`usuarios_id`),
  ADD CONSTRAINT `fk_videos_experimento` FOREIGN KEY (`experimentos_id`) REFERENCES `experimentos` (`experimentos_id`);

-- ---------- login_tentativas (nova) ----------
CREATE TABLE IF NOT EXISTS `login_tentativas` (
  `id`           INT NOT NULL AUTO_INCREMENT,
  `ip`           VARCHAR(45) NOT NULL,
  `email`        VARCHAR(255) NOT NULL,
  `tentativa_em` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_login_tentativas` (`ip`, `email`, `tentativa_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Experimentos sem bancada (criados por falhas antigas no cadastro) podem ser
-- conferidos com:
--   SELECT e.* FROM experimentos e
--   LEFT JOIN bancadas_experimentos be ON be.be_experimentos_id = e.experimentos_id
--   WHERE be.be_id IS NULL;
