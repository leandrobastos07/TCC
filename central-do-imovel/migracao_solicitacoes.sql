-- Atualizacao para vincular atendimentos a conta de visitante.
-- Execute apenas UMA vez no phpMyAdmin, apos fazer backup do banco.
-- Nao apaga nem altera os atendimentos ja existentes.
USE central_imovel;

ALTER TABLE atendimentos
  ADD COLUMN id_usuario INT NULL,
  ADD CONSTRAINT fk_atendimentos_usuarios
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
    ON DELETE SET NULL;
