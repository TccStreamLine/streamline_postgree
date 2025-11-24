-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 11/11/2025 às 22:40
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
-- Banco de dados: `streamline`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `categorias`
--

INSERT INTO `categorias` (`id`, `nome`) VALUES
(10, 'Alimentos'),
(11, 'Eletrônicos'),
(12, 'Limpeza'),
(9, 'Movéis');

-- --------------------------------------------------------

--
-- Estrutura para tabela `eventos`
--

CREATE TABLE `eventos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL COMMENT 'O título ou nome do evento.',
  `inicio` datetime NOT NULL COMMENT 'Data e hora de início do evento.',
  `horario` time DEFAULT NULL,
  `fim` datetime DEFAULT NULL COMMENT 'Data e hora de término do evento (opcional).',
  `usuario_id` int(11) NOT NULL COMMENT 'Chave estrangeira que liga o evento ao usuário que o criou.',
  `descricao` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `eventos`
--

INSERT INTO `eventos` (`id`, `titulo`, `inicio`, `horario`, `fim`, `usuario_id`, `descricao`) VALUES
(33, 'Apresentação TCC', '2025-09-29 10:20:00', '10:20:00', NULL, 22, 'Apresentação parcial do TCC com Paulo Rogério <3'),
(36, 'Apresentação TCC', '2025-09-29 11:00:00', '11:00:00', NULL, 22, 'Apresentação de TCC com o Paulo Rogério <3');

-- --------------------------------------------------------

--
-- Estrutura para tabela `fornecedores`
--

CREATE TABLE `fornecedores` (
  `id` int(11) NOT NULL,
  `razao_social` varchar(255) NOT NULL,
  `cnpj` varchar(20) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `senha` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expire` datetime DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'ativo',
  `profile_pic` varchar(255) DEFAULT NULL COMMENT 'Caminho para a imagem de perfil do fornecedor'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `fornecedores`
--

INSERT INTO `fornecedores` (`id`, `razao_social`, `cnpj`, `email`, `telefone`, `senha`, `reset_token`, `reset_token_expire`, `status`, `profile_pic`) VALUES
(16, 'Pichau', '12345678901234', 'lastzrr@gmail.com', '11947010600', '$2y$10$2GBEtxnW4053hdbDl.sZQOEU7evt09mb/9jKVwXuHg5shExGsdFQG', NULL, NULL, 'ativo', NULL),
(17, 'Extra', '49447734000102', 'leligmascarenhas@gmail.com', '11111111111', NULL, 'd342a18714f0f1d1a2dc531461b795618956871cac5d9bcf42d28a4d92cd74b879dd83a12d669a2a8d9cfd95e7349d17e632', '2025-09-29 18:48:01', 'ativo', NULL),
(18, 'Americanas', '48451255876001', 'iarafontes@usp.br', '11947766995', NULL, '687995f76389fe1377a1a139229b72c28f1c751a95cfadf7d890f2d466c93d7e4edbc1bceaf82e33e1677535d5cf14c74e7f', '2025-09-29 19:50:19', 'ativo', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `historico_entregas`
--

CREATE TABLE `historico_entregas` (
  `id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `fornecedor_id` int(11) NOT NULL,
  `quantidade_entregue` int(11) NOT NULL,
  `data_entrega` datetime NOT NULL,
  `valor_compra_unitario` decimal(10,2) NOT NULL,
  `nota_fiscal_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pedidos_fornecedor`
--

CREATE TABLE `pedidos_fornecedor` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fornecedor_id` int(11) NOT NULL,
  `data_pedido` datetime NOT NULL DEFAULT current_timestamp(),
  `status_pedido` varchar(50) NOT NULL DEFAULT 'Pendente',
  `valor_total_pedido` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pedido_fornecedor_itens`
--

CREATE TABLE `pedido_fornecedor_itens` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade_pedida` int(11) NOT NULL,
  `valor_unitario_pago` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` int(11) NOT NULL,
  `codigo_barras` varchar(255) DEFAULT NULL,
  `nome` varchar(255) NOT NULL,
  `especificacao` text DEFAULT NULL,
  `quantidade_estoque` int(11) NOT NULL DEFAULT 0,
  `quantidade_minima` int(11) NOT NULL DEFAULT 5,
  `valor_compra` decimal(10,2) NOT NULL,
  `valor_venda` decimal(10,2) NOT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `fornecedor_id` int(11) DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) NOT NULL DEFAULT 'ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `produtos`
--

INSERT INTO `produtos` (`id`, `codigo_barras`, `nome`, `especificacao`, `quantidade_estoque`, `quantidade_minima`, `valor_compra`, `valor_venda`, `categoria_id`, `fornecedor_id`, `data_cadastro`, `status`) VALUES
(14, '1', 'Mouse gamer', 'Mouse gamer preto', 5, 5, 60.00, 100.00, 11, 16, '2025-09-28 21:49:41', 'ativo'),
(15, '2', 'Teclado gamer', 'Teclado gamer branco', 49, 5, 40.00, 100.00, 11, 16, '2025-09-28 21:50:19', 'ativo'),
(16, '3', 'Mousepad gamer', 'Mousepad gamer preto e vermelho', 59, 5, 15.00, 50.00, 11, 16, '2025-09-28 21:50:58', 'ativo'),
(17, '4', 'Cadeira', 'Cadeira de madeira', 45, 5, 75.00, 150.00, 9, 17, '2025-09-28 21:53:04', 'ativo'),
(18, '5', 'Mesa', 'Mesa de madeira', 59, 5, 100.00, 200.00, 9, 17, '2025-09-28 21:53:56', 'ativo'),
(19, '6', 'Leite Piracanjuba', 'Leite de 1L Piracanjuba', 86, 5, 2.50, 10.00, 10, 17, '2025-09-28 21:55:13', 'ativo'),
(20, '7', 'Picanha', '1KG de Picanha da Swift', 98, 5, 40.00, 80.00, 10, 17, '2025-09-28 21:55:48', 'ativo'),
(21, '8', 'Filé de frango', '1KG de Filé de frango da Sadia', 94, 5, 20.00, 45.00, 10, 17, '2025-09-28 21:56:40', 'ativo'),
(22, '9', 'Vassoura', 'Vassoura de cerdas macias', 137, 5, 2.50, 10.00, 12, 17, '2025-09-28 21:58:11', 'ativo'),
(23, '0', 'Veja', 'Veja limpeza pesada original 500ml', 188, 5, 4.50, 15.00, 12, 17, '2025-09-28 21:59:35', 'ativo');

-- --------------------------------------------------------

--
-- Estrutura para tabela `servicos_prestados`
--

CREATE TABLE `servicos_prestados` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nome_servico` varchar(255) NOT NULL,
  `especificacao` text DEFAULT NULL,
  `horas_gastas` decimal(10,2) DEFAULT NULL,
  `data_prestacao` datetime DEFAULT NULL,
  `gastos` decimal(10,2) DEFAULT NULL,
  `valor_venda` decimal(10,2) NOT NULL,
  `produtos_usados` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `servicos_prestados`
--

INSERT INTO `servicos_prestados` (`id`, `usuario_id`, `nome_servico`, `especificacao`, `horas_gastas`, `data_prestacao`, `gastos`, `valor_venda`, `produtos_usados`, `status`) VALUES
(5, 22, 'Corte de cabelo', 'Corte padrão', 1.00, '2025-09-28 21:05:00', 2.50, 40.00, '1 gilette e 1 gola alta', 'ativo'),
(6, 22, 'Corte de cabelo', 'Corte padrão', 1.00, '2025-10-25 18:01:00', 5.00, 45.00, '1 gilette e 1 gola alta', 'inativo');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome_empresa` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefone` varchar(20) NOT NULL,
  `ramo_atuacao` varchar(100) NOT NULL,
  `quantidade_funcionarios` varchar(20) NOT NULL,
  `natureza_juridica` varchar(100) NOT NULL,
  `cnpj` varchar(18) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `senha_funcionarios` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expire` datetime DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL COMMENT 'Caminho para a imagem de perfil do usuário/empresa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome_empresa`, `email`, `telefone`, `ramo_atuacao`, `quantidade_funcionarios`, `natureza_juridica`, `cnpj`, `senha`, `senha_funcionarios`, `reset_token`, `reset_token_expire`, `profile_pic`) VALUES
(22, 'Adati', 'lastzrr@gmail.com', '11947010600', 'Atacado/Varejo', '20', 'LTDA', '12345678901234', '$2y$10$7734Nf939zA9I8OlEFSwEOBwAg0kDWC9XGNXLWbJn8S88PKOZoGoK', '$2y$10$hqf393iKS2FqDBIGIigWeuNHSdYopuas61UyVMU7seDfggNJtZj0y', 'a2dbdcc51ab9359257caa7bf189d6a46aa183c2e34cd363d572407c7639b88003d324326d2f6bdaec931e58354c0452c3205', '2025-09-28 15:46:46', 'uploads/profile_pics/empresa_22_68fd56749c3fa7.41449558.jpg');

-- --------------------------------------------------------

--
-- Estrutura para tabela `vendas`
--

CREATE TABLE `vendas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `descricao` text DEFAULT NULL,
  `data_venda` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) NOT NULL DEFAULT 'finalizada'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `vendas`
--

INSERT INTO `vendas` (`id`, `usuario_id`, `valor_total`, `descricao`, `data_venda`, `status`) VALUES
(38, 22, 160.00, NULL, '2025-09-28 22:00:28', 'finalizada'),
(39, 22, 130.00, '', '2025-09-28 22:00:00', 'finalizada'),
(40, 22, 300.00, '', '2025-09-27 22:01:00', 'finalizada'),
(41, 22, 310.00, '', '2025-09-26 22:01:00', 'finalizada'),
(42, 22, 350.00, '', '2025-09-25 22:02:00', 'finalizada'),
(43, 22, 300.00, '', '2025-09-24 22:02:00', 'finalizada'),
(44, 22, 15.00, '', '2025-09-24 22:03:00', 'finalizada'),
(45, 22, 180.00, '', '2025-09-29 22:03:00', 'finalizada'),
(46, 22, 80.00, '', '2025-09-29 22:03:00', 'finalizada'),
(47, 22, 290.00, '', '2025-09-23 22:07:00', 'finalizada'),
(48, 22, 200.00, '', '2025-09-22 22:09:00', 'finalizada'),
(49, 22, 45.00, '', '2025-09-22 22:10:00', 'finalizada'),
(50, 22, 5.00, 'desconto de 50%', '2025-09-29 00:02:00', 'finalizada'),
(51, 22, 10.00, NULL, '2025-09-29 00:03:42', 'finalizada'),
(52, 22, 40.00, NULL, '2025-10-25 20:59:36', 'finalizada'),
(53, 22, 40.00, '', '2025-10-25 21:23:00', 'finalizada');

-- --------------------------------------------------------

--
-- Estrutura para tabela `venda_itens`
--

CREATE TABLE `venda_itens` (
  `id` int(11) NOT NULL,
  `venda_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `valor_unitario` decimal(10,2) NOT NULL,
  `valor_total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `venda_itens`
--

INSERT INTO `venda_itens` (`id`, `venda_id`, `produto_id`, `quantidade`, `valor_unitario`, `valor_total`) VALUES
(45, 38, 14, 1, 100.00, 100.00),
(46, 38, 23, 1, 15.00, 15.00),
(47, 38, 21, 1, 45.00, 45.00),
(48, 39, 20, 1, 80.00, 80.00),
(49, 39, 22, 5, 10.00, 50.00),
(50, 40, 16, 1, 50.00, 50.00),
(51, 40, 14, 1, 100.00, 100.00),
(52, 40, 17, 1, 150.00, 150.00),
(53, 41, 17, 1, 150.00, 150.00),
(54, 41, 15, 1, 100.00, 100.00),
(55, 41, 22, 6, 10.00, 60.00),
(56, 42, 23, 10, 15.00, 150.00),
(57, 42, 18, 1, 200.00, 200.00),
(58, 43, 17, 2, 150.00, 300.00),
(59, 44, 23, 1, 15.00, 15.00),
(60, 45, 21, 4, 45.00, 180.00),
(61, 46, 20, 1, 80.00, 80.00),
(62, 47, 17, 1, 150.00, 150.00),
(63, 47, 19, 14, 10.00, 140.00),
(64, 48, 14, 2, 100.00, 200.00),
(65, 49, 21, 1, 45.00, 45.00),
(66, 50, 22, 1, 5.00, 5.00),
(67, 51, 22, 1, 10.00, 10.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `venda_servicos`
--

CREATE TABLE `venda_servicos` (
  `id` int(11) NOT NULL,
  `venda_id` int(11) NOT NULL,
  `servico_id` int(11) NOT NULL,
  `valor` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `venda_servicos`
--

INSERT INTO `venda_servicos` (`id`, `venda_id`, `servico_id`, `valor`) VALUES
(3, 52, 5, 40.00),
(4, 53, 5, 40.00);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome_unico` (`nome`);

--
-- Índices de tabela `eventos`
--
ALTER TABLE `eventos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `fornecedores`
--
ALTER TABLE `fornecedores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cnpj_unico` (`cnpj`),
  ADD UNIQUE KEY `email_unico` (`email`);

--
-- Índices de tabela `historico_entregas`
--
ALTER TABLE `historico_entregas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_entrega_produto` (`produto_id`),
  ADD KEY `fk_entrega_fornecedor` (`fornecedor_id`);

--
-- Índices de tabela `pedidos_fornecedor`
--
ALTER TABLE `pedidos_fornecedor`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pedido_usuario` (`usuario_id`),
  ADD KEY `fk_pedido_fornecedor` (`fornecedor_id`);

--
-- Índices de tabela `pedido_fornecedor_itens`
--
ALTER TABLE `pedido_fornecedor_itens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_item_pedido` (`pedido_id`),
  ADD KEY `fk_item_produto` (`produto_id`);

--
-- Índices de tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_barras` (`codigo_barras`);

--
-- Índices de tabela `servicos_prestados`
--
ALTER TABLE `servicos_prestados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `vendas`
--
ALTER TABLE `vendas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_vendas_usuarios` (`usuario_id`);

--
-- Índices de tabela `venda_itens`
--
ALTER TABLE `venda_itens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_venda_itens_vendas` (`venda_id`),
  ADD KEY `fk_venda_itens_produtos` (`produto_id`);

--
-- Índices de tabela `venda_servicos`
--
ALTER TABLE `venda_servicos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venda_id` (`venda_id`),
  ADD KEY `servico_id` (`servico_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `eventos`
--
ALTER TABLE `eventos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT de tabela `fornecedores`
--
ALTER TABLE `fornecedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `historico_entregas`
--
ALTER TABLE `historico_entregas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pedidos_fornecedor`
--
ALTER TABLE `pedidos_fornecedor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `pedido_fornecedor_itens`
--
ALTER TABLE `pedido_fornecedor_itens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de tabela `servicos_prestados`
--
ALTER TABLE `servicos_prestados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de tabela `vendas`
--
ALTER TABLE `vendas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT de tabela `venda_itens`
--
ALTER TABLE `venda_itens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT de tabela `venda_servicos`
--
ALTER TABLE `venda_servicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `historico_entregas`
--
ALTER TABLE `historico_entregas`
  ADD CONSTRAINT `fk_entrega_fornecedor` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores` (`id`),
  ADD CONSTRAINT `fk_entrega_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`);

--
-- Restrições para tabelas `pedidos_fornecedor`
--
ALTER TABLE `pedidos_fornecedor`
  ADD CONSTRAINT `fk_pedido_fornecedor` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores` (`id`),
  ADD CONSTRAINT `fk_pedido_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `pedido_fornecedor_itens`
--
ALTER TABLE `pedido_fornecedor_itens`
  ADD CONSTRAINT `fk_item_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos_fornecedor` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_item_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`);

--
-- Restrições para tabelas `servicos_prestados`
--
ALTER TABLE `servicos_prestados`
  ADD CONSTRAINT `servicos_prestados_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `vendas`
--
ALTER TABLE `vendas`
  ADD CONSTRAINT `fk_vendas_usuarios` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `venda_itens`
--
ALTER TABLE `venda_itens`
  ADD CONSTRAINT `fk_venda_itens_produtos` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`),
  ADD CONSTRAINT `fk_venda_itens_vendas` FOREIGN KEY (`venda_id`) REFERENCES `vendas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `venda_servicos`
--
ALTER TABLE `venda_servicos`
  ADD CONSTRAINT `venda_servicos_ibfk_1` FOREIGN KEY (`venda_id`) REFERENCES `vendas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `venda_servicos_ibfk_2` FOREIGN KEY (`servico_id`) REFERENCES `servicos_prestados` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
