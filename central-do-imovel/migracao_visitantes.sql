-- Execute uma unica vez no phpMyAdmin, na aba Importar.
-- Preserva as contas atuais, os imoveis e a marca personalizada.
USE central_imovel;
ALTER TABLE usuarios
  MODIFY COLUMN tipo ENUM('administrador','corretor','visitante')
  NOT NULL DEFAULT 'corretor';
