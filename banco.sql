-- CENTRAL DO IMÓVEL — exemplo didático para o TCC
-- Execute este arquivo no phpMyAdmin (Importar).
CREATE DATABASE IF NOT EXISTS central_imovel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE central_imovel;

CREATE TABLE IF NOT EXISTS usuarios (
  id_usuario INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL,
  tipo ENUM('administrador','corretor') NOT NULL DEFAULT 'corretor'
);

CREATE TABLE IF NOT EXISTS corretores (
  id_corretor INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  telefone VARCHAR(30),
  email VARCHAR(150)
);

CREATE TABLE IF NOT EXISTS clientes (
  id_cliente INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(150) NOT NULL,
  telefone VARCHAR(30)
);

CREATE TABLE IF NOT EXISTS imoveis (
  id_imovel INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(180) NOT NULL,
  tipo VARCHAR(40) NOT NULL,
  finalidade ENUM('Venda','Aluguel') NOT NULL,
  endereco VARCHAR(180) NOT NULL,
  bairro VARCHAR(100) NOT NULL,
  cidade VARCHAR(100) NOT NULL DEFAULT 'Colombo',
  preco DECIMAL(12,2) NOT NULL,
  descricao TEXT,
  quartos INT NOT NULL DEFAULT 0,
  banheiros INT NOT NULL DEFAULT 0,
  vagas INT NOT NULL DEFAULT 0,
  area INT NOT NULL DEFAULT 0,
  imagem VARCHAR(255),
  id_corretor INT NULL,
  FOREIGN KEY (id_corretor) REFERENCES corretores(id_corretor) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS atendimentos (
  id_atendimento INT AUTO_INCREMENT PRIMARY KEY,
  id_cliente INT NOT NULL,
  id_imovel INT NOT NULL,
  data DATE NOT NULL,
  mensagem TEXT NOT NULL,
  status ENUM('Novo','Respondido') NOT NULL DEFAULT 'Novo',
  FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente),
  FOREIGN KEY (id_imovel) REFERENCES imoveis(id_imovel)
);

-- Anúncios FICTÍCIOS para testar o sistema; substitua por seus próprios dados.
INSERT INTO corretores (id_corretor, nome, telefone, email) VALUES
  (1, 'Corretor Exemplo', '(41) 99999-0000', 'exemplo@central.local');
INSERT INTO imoveis
 (titulo,tipo,finalidade,endereco,bairro,cidade,preco,descricao,quartos,banheiros,vagas,area,imagem,id_corretor) VALUES
 ('Casa com quintal em Colombo','Casa','Venda','Endereço sob consulta','Guaraituba','Colombo',360000.00,'Casa ilustrativa com quintal, sala, cozinha e boa iluminação. Anúncio criado apenas para testar o projeto.',3,2,2,92,'uploads/06_casa_guaraituba.png',1),
 ('Apartamento prático no Atuba','Apartamento','Aluguel','Endereço sob consulta','Atuba','Colombo',1450.00,'Apartamento ilustrativo com dois quartos e fácil acesso à região de Curitiba.',2,1,1,55,'uploads/05_apartamento_atuba.png',1),
 ('Terreno em região residencial','Terreno','Venda','Endereço sob consulta','Campo Pequeno','Colombo',215000.00,'Terreno ilustrativo para construção residencial.',0,0,0,300,'uploads/04_terreno_campo_pequeno.png',1),
 ('Casa térrea perto do Centro','Casa','Venda','Endereço sob consulta','Centro','Colombo',425000.00,'Imóvel ilustrativo com três dormitórios e vaga coberta.',3,2,1,105,'uploads/03_casa_terrea_centro.png',1),
 ('Apartamento compacto em Roça Grande','Apartamento','Venda','Endereço sob consulta','Roça Grande','Colombo',239000.00,'Apartamento ilustrativo ideal para quem procura praticidade.',2,1,1,48,'uploads/02_apartamento_roca_grande.png',1),
 ('Casa para alugar no São Gabriel','Casa','Aluguel','Endereço sob consulta','São Gabriel','Colombo',1700.00,'Casa ilustrativa com dois dormitórios, cozinha e área de serviço.',2,1,1,70,'uploads/01_casa_sao_gabriel.png',1);
