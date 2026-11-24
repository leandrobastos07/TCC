-- ATUALIZAÇÃO DE FOTOS — Central do Imóvel
-- 1. No phpMyAdmin, clique no banco central_imovel.
-- 2. Importe este arquivo somente nesse banco.
-- Não apaga ou modifica as tabelas existentes.
CREATE TABLE IF NOT EXISTS fotos_imoveis (
    id_foto INT AUTO_INCREMENT PRIMARY KEY,
    id_imovel INT NOT NULL,
    arquivo VARCHAR(255) NOT NULL,
    principal TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (id_imovel) REFERENCES imoveis(id_imovel) ON DELETE CASCADE,
    INDEX idx_fotos_imovel (id_imovel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Associação das imagens de exemplo aos anúncios iniciais.
UPDATE imoveis SET imagem = 'uploads/06_casa_guaraituba.png' WHERE bairro = 'Guaraituba';
UPDATE imoveis SET imagem = 'uploads/05_apartamento_atuba.png' WHERE bairro = 'Atuba';
UPDATE imoveis SET imagem = 'uploads/04_terreno_campo_pequeno.png' WHERE bairro = 'Campo Pequeno';
UPDATE imoveis SET imagem = 'uploads/03_casa_terrea_centro.png' WHERE bairro = 'Centro';
UPDATE imoveis SET imagem = 'uploads/02_apartamento_roca_grande.png' WHERE bairro = 'Roça Grande';
UPDATE imoveis SET imagem = 'uploads/01_casa_sao_gabriel.png' WHERE bairro = 'São Gabriel';

INSERT INTO fotos_imoveis (id_imovel, arquivo, principal)
SELECT i.id_imovel, 'uploads/06_casa_guaraituba.png', 1
FROM imoveis i
WHERE i.bairro = 'Guaraituba'
  AND NOT EXISTS (SELECT 1 FROM fotos_imoveis f WHERE f.id_imovel = i.id_imovel);
INSERT INTO fotos_imoveis (id_imovel, arquivo, principal)
SELECT i.id_imovel, 'uploads/05_apartamento_atuba.png', 1
FROM imoveis i
WHERE i.bairro = 'Atuba'
  AND NOT EXISTS (SELECT 1 FROM fotos_imoveis f WHERE f.id_imovel = i.id_imovel);
INSERT INTO fotos_imoveis (id_imovel, arquivo, principal)
SELECT i.id_imovel, 'uploads/04_terreno_campo_pequeno.png', 1
FROM imoveis i
WHERE i.bairro = 'Campo Pequeno'
  AND NOT EXISTS (SELECT 1 FROM fotos_imoveis f WHERE f.id_imovel = i.id_imovel);
INSERT INTO fotos_imoveis (id_imovel, arquivo, principal)
SELECT i.id_imovel, 'uploads/03_casa_terrea_centro.png', 1
FROM imoveis i
WHERE i.bairro = 'Centro'
  AND NOT EXISTS (SELECT 1 FROM fotos_imoveis f WHERE f.id_imovel = i.id_imovel);
INSERT INTO fotos_imoveis (id_imovel, arquivo, principal)
SELECT i.id_imovel, 'uploads/02_apartamento_roca_grande.png', 1
FROM imoveis i
WHERE i.bairro = 'Roça Grande'
  AND NOT EXISTS (SELECT 1 FROM fotos_imoveis f WHERE f.id_imovel = i.id_imovel);
INSERT INTO fotos_imoveis (id_imovel, arquivo, principal)
SELECT i.id_imovel, 'uploads/01_casa_sao_gabriel.png', 1
FROM imoveis i
WHERE i.bairro = 'São Gabriel'
  AND NOT EXISTS (SELECT 1 FROM fotos_imoveis f WHERE f.id_imovel = i.id_imovel);
