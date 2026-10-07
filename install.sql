-- Peças Prime - Marketplace de autopeças
-- Importe este arquivo no phpMyAdmin (XAMPP) e depois acesse http://localhost/pecas-prime

CREATE DATABASE IF NOT EXISTS pecas_prime CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE pecas_prime;

CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  description TEXT,
  specs TEXT,
  category_id INT,
  brand VARCHAR(80) DEFAULT '',
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  old_price DECIMAL(10,2) DEFAULT NULL,
  stock INT NOT NULL DEFAULT 0,
  sales INT NOT NULL DEFAULT 0,
  image VARCHAR(255) DEFAULT NULL,
  rating DECIMAL(2,1) NOT NULL DEFAULT 5,
  rating_count INT NOT NULL DEFAULT 0,
  free_shipping TINYINT(1) NOT NULL DEFAULT 0,
  featured TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(30) DEFAULT '',
  address VARCHAR(200) DEFAULT '',
  city VARCHAR(80) DEFAULT '',
  state VARCHAR(2) DEFAULT '',
  zip VARCHAR(10) DEFAULT '',
  is_admin TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  total DECIMAL(10,2) NOT NULL,
  payment VARCHAR(20) NOT NULL DEFAULT 'pix',
  status VARCHAR(20) NOT NULL DEFAULT 'aguardando pagamento',
  address VARCHAR(250) DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  qty INT NOT NULL DEFAULT 1,
  price DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

CREATE TABLE reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  user_id INT,
  stars TINYINT NOT NULL DEFAULT 5,
  comment TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  user_id INT,
  question TEXT NOT NULL,
  answer TEXT,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- Categorias
INSERT INTO categories (name) VALUES
('Motor'), ('Freios'), ('Suspensão'), ('Elétrica'), ('Filtros'), ('Óleos e Fluidos'), ('Acessórios');

-- Usuário admin (senha: admin123)
INSERT INTO users (name, email, password, is_admin) VALUES
('Administrador', 'admin@pecasprime.com', '$2y$12$KkL1QbRBv8zZ22RVLl7q4u/Ur/6B6Hcqw1vYlxPFtZAWSqm9iZVQG', 1);

-- Produtos
INSERT INTO products (name, description, specs, category_id, brand, price, old_price, stock, sales, rating, rating_count, free_shipping, featured) VALUES
('Pastilha de Freio Dianteira Cerâmica', 'Pastilha cerâmica de alta performance, frenagem silenciosa e menor desgaste do disco. Aprovada pelas principais montadoras.', 'Posição: dianteira|Material: cerâmica|Conteúdo: 4 pastilhas', 2, 'Bosch', 189.90, 249.90, 120, 843, 4.8, 215, 1, 1),
('Disco de Freio Ventilado', 'Disco ventilado com tratamento antiferrugem, excelente dissipação de calor para frenagens seguras e consistentes.', 'Diâmetro: 256mm|Tipo: ventilado|Par de discos', 2, 'Fremax', 289.00, 359.00, 60, 431, 4.7, 98, 1, 1),
('Amortecedor Dianteiro Pressurizado a Gás', 'Amortecedor pressurizado a gás, estabilidade e conforto em qualquer terreno. Garantia de 2 anos.', 'Posição: dianteiro|Tipo: pressurizado a gás|Par', 3, 'Cofap', 459.90, 599.90, 35, 612, 4.9, 187, 1, 1),
('Kit Embreagem Completo', 'Kit com disco, platô e rolamento. Engate suave e durabilidade estendida para uso urbano e rodoviário.', 'Conteúdo: disco, platô e rolamento|Montagem: completa', 1, 'Sachs', 799.00, NULL, 22, 198, 4.6, 64, 1, 1),
('Óleo Sintético 5W30 API SP 1L', 'Óleo sintético premium com proteção total do motor, troca recomendada a cada 10.000 km.', 'Viscosidade: 5W30|API: SP|Volume: 1 litro', 6, 'Mobil', 49.90, 64.90, 300, 1520, 4.9, 540, 1, 1),
('Óleo Hidráulico DOT4 500ml', 'Fluido de freio DOT4 de alta performance, ponto de ebulição elevado para frenagens seguras.', 'Especificação: DOT 4|Volume: 500ml', 6, 'Bosch', 24.90, NULL, 250, 890, 4.7, 310, 1, 0),
('Bateria Selada 60Ah 18 Meses', 'Bateria selada livre de manutenção, partida forte em qualquer clima. Garantia de 18 meses.', 'Capacidade: 60Ah|CCA: 480A|Garantia: 18 meses', 4, 'Moura', 399.00, 479.00, 45, 705, 4.8, 252, 0, 1),
('Alternador 90A Recondicionado Novo', 'Alternador de alta durabilidade com regulador de tensão integrado, carregamento estável da bateria.', 'Amperagem: 90A|Condição: novo|Com regulador', 4, 'Valeo', 549.00, 689.00, 18, 156, 4.5, 47, 0, 1),
('Vela de Ignição Iridium (Jogo 4 pcs)', 'Jogo de velas de iridium com centelha mais forte, motor mais econômico e partida instantânea.', 'Eletrodo: iridium|Conteúdo: 4 velas|Gap: 0,8mm', 1, 'NGK', 139.90, 179.90, 180, 978, 4.9, 388, 1, 1),
('Correia Dentada Reforçada', 'Correia dentada com borracha EPDM, resistente ao calor e à fadiga. Troca a cada 60.000 km.', 'Material: EPDM|Dentes: 137|Largura: 25mm', 1, 'Contitech', 129.90, NULL, 90, 534, 4.6, 122, 1, 0),
('Bomba de Água Completa', 'Bomba d''água com vedação premium e rolamento selado, sistema de arrefecimento sempre eficiente.', 'Vedação: mecânica|Inclui junta|Rotação: horária', 1, 'Valeo', 259.90, 319.90, 40, 287, 4.7, 81, 1, 0),
('Filtro de Ar Esportivo Lavável', 'Filtro esportivo lavável e reutilizável, mais fluxo de ar para seu motor render mais.', 'Tipo: esportivo lavável|Material: algodão|Vida útil: até 160.000 km', 5, 'K&N', 219.00, 279.00, 55, 234, 4.8, 76, 1, 1),
('Filtro de Óleo Premium', 'Filtro de óleo com válvula antirretorno e alto poder de retenção de impurezas.', 'Válvula: antirretorno|Micronagem: 25|Compatível: várias montadoras', 5, 'Mann', 39.90, 52.90, 400, 1675, 4.8, 592, 1, 1),
('Filtro de Combustível Injetado', 'Filtro de combustível de alta capacidade, protege bicos injetores e bomba de combustível.', 'Tipo: injetados|Pressão: até 6 bar', 5, 'Wega', 34.90, NULL, 320, 645, 4.5, 190, 1, 0),
('Farol LED 6000K Par', 'Kit farol LED 6000K luz branca pura, visão noturna cristalina e instalação plug and play.', 'Temperatura: 6000K|Fluxo: 8000lm|Par de faróis', 4, 'Hella', 389.90, 499.90, 30, 421, 4.7, 133, 1, 1),
('Central Multimídia Android 9"', 'Central multimí tela 9 polegadas com Android, GPS, câmera de ré e espelhamento de celular.', 'Tela: 9"|Sistema: Android|Wi-Fi, GPS e Bluetooth', 7, 'Pioneer', 1299.00, 1599.00, 12, 87, 4.6, 45, 0, 1),
('Jogo de Tapetes de Borracha', 'Jogo de tapetes universais de borracha de alta resistência, proteção total contra água e sujeira.', 'Material: borracha|Conteúdo: 4 peças|Universal', 7, 'Cromoplast', 79.90, 99.90, 150, 356, 4.4, 118, 1, 0),
('Kit de Calotas 14" (4 peças)', 'Kit de calotas resistentes em ABS com fixação por presilha, acabamento premium para seu carro.', 'Aro: 14"|Material: ABS|Conteúdo: 4 calotas', 7, 'Plauto', 69.90, NULL, 200, 298, 4.3, 92, 1, 0);

-- Perguntas e respostas de exemplo
INSERT INTO questions (product_id, user_id, question, answer) VALUES
(5, NULL, 'Serve no Honda Civic 2018?', 'Sim, o 5W30 API SP é recomendado para o Civic 2018. Qualquer dúvida, consulte o manual.'),
(7, NULL, 'Qual a garantia dessa bateria?', '18 meses de garantia contra defeitos de fabricação.');

-- Avaliações de exemplo
INSERT INTO reviews (product_id, user_id, stars, comment) VALUES
(5, NULL, 5, 'Óleo excelente, motor mais silencioso. Recomendo!'),
(5, NULL, 5, 'Melhor custo-benefício. Entrega rápida.'),
(1, NULL, 5, 'Frenagem perfeita e sem ruído nenhum.'),
(3, NULL, 4, 'Bom produto, melhorou muito a estabilidade.');
