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
