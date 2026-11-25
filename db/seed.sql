-- Usuário administrador inicial (somente para instalação nova).
-- E-mail: admin@farmview.local  |  Senha: TroqueEstaSenha!
-- TROQUE A SENHA no primeiro acesso (Administração > Usuários).
INSERT INTO `usuarios` (`usuarios_nome`, `usuarios_escolaridade`, `usuarios_email`, `usuarios_senha`,
                        `usuarios_imagem`, `usuarios_ativo`, `usuarios_nivel`)
SELECT 'Administrador', 'Graduação', 'admin@farmview.local',
       '$2y$10$VXfFdF.RwmlZCvpcmdwUEeprNTup6Icipn1Mi6.FzNO5NiBpOrQDm', 'Public/uploadImages/Noimage.png', 1, 2
WHERE NOT EXISTS (SELECT 1 FROM `usuarios` WHERE `usuarios_nivel` = 2);
