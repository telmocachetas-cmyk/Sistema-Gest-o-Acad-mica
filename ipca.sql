-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 19-Mar-2026 às 19:58
-- Versão do servidor: 10.4.32-MariaDB
-- versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `ipca`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `cursos`
--

CREATE TABLE `cursos` (
  `ID` int(11) NOT NULL,
  `Nome` text NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `duracao_semestres` int(11) DEFAULT 6,
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `cursos`
--

INSERT INTO `cursos` (`ID`, `Nome`, `codigo`, `descricao`, `duracao_semestres`, `ativo`) VALUES
(1, 'Desenvolvimento Web e Multimédia', 'DWM', 'Curso de programação web e multimédia', 6, 1),
(2, 'Comércio Eletrónico', 'CE', 'Curso de comércio eletrónico e marketing digital', 6, 1),
(3, 'Redes de Computadores', 'RC', 'Curso de redes e infraestruturas', 6, 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `disciplinas`
--

CREATE TABLE `disciplinas` (
  `ID` int(11) NOT NULL,
  `Nome_disc` text NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `creditos` int(11) DEFAULT 6,
  `horas` int(11) DEFAULT 60
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `disciplinas`
--

INSERT INTO `disciplinas` (`ID`, `Nome_disc`, `codigo`, `creditos`, `horas`) VALUES
(1, 'Matemática', 'MAT', 6, 60),
(2, 'Programação WEB I', 'PW1', 6, 60),
(3, 'Linguagens de Programação', 'LP', 6, 60),
(5, 'Álgebra Linear', 'AL', 6, 60),
(7, 'Armazenamento e Acesso de Dados', 'AAD', 6, 60),
(8, 'Algoritmos e Estruturas de Dados', 'AED', 6, 60),
(9, 'Sistemas Operativos', 'SO', 6, 60),
(10, 'Engenharia de Software', 'ES', 6, 60),
(11, 'Base de Dados', 'BD', 6, 60),
(12, 'Segurança Informática', 'SI', 6, 60);

-- --------------------------------------------------------

--
-- Estrutura da tabela `fichas_aluno`
--

CREATE TABLE `fichas_aluno` (
  `id` int(11) NOT NULL,
  `aluno_id` varchar(20) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `nome_completo` varchar(200) NOT NULL,
  `data_nascimento` date DEFAULT NULL,
  `nif` varchar(20) DEFAULT NULL,
  `morada` text DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `foto_path` varchar(255) DEFAULT NULL,
  `estado` enum('rascunho','submetida','aprovada','rejeitada') DEFAULT 'rascunho',
  `observacoes` text DEFAULT NULL,
  `data_submissao` datetime DEFAULT NULL,
  `data_decisao` datetime DEFAULT NULL,
  `gestor_id` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `grupos`
--

CREATE TABLE `grupos` (
  `ID` int(11) NOT NULL,
  `GRUPO` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `grupos`
--

INSERT INTO `grupos` (`ID`, `GRUPO`) VALUES
(1, 'ADMIN'),
(2, 'ALUNO'),
(3, 'FUNCIONARIO');

-- --------------------------------------------------------

--
-- Estrutura da tabela `notas`
--

CREATE TABLE `notas` (
  `id` int(11) NOT NULL,
  `pauta_id` int(11) NOT NULL,
  `aluno_id` varchar(20) NOT NULL,
  `nota` decimal(4,1) DEFAULT NULL,
  `aprovado` tinyint(1) DEFAULT NULL,
  `data_registo` datetime DEFAULT NULL,
  `funcionario_id` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `pautas`
--

CREATE TABLE `pautas` (
  `id` int(11) NOT NULL,
  `uc_id` int(11) NOT NULL,
  `ano_letivo` varchar(20) NOT NULL,
  `epoca` varchar(50) NOT NULL,
  `data_criacao` datetime DEFAULT NULL,
  `funcionario_id` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `pautas`
--

INSERT INTO `pautas` (`id`, `uc_id`, `ano_letivo`, `epoca`, `data_criacao`, `funcionario_id`) VALUES
(2, 1, '2025/2026', 'Normal', '2026-03-16 14:52:01', 'func1');

-- --------------------------------------------------------

--
-- Estrutura da tabela `pedidos_matricula`
--

CREATE TABLE `pedidos_matricula` (
  `id` int(11) NOT NULL,
  `aluno_id` varchar(20) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `ano_letivo` varchar(20) NOT NULL,
  `estado` enum('pendente','aprovado','rejeitado') DEFAULT 'pendente',
  `observacoes` text DEFAULT NULL,
  `data_pedido` datetime DEFAULT NULL,
  `data_decisao` datetime DEFAULT NULL,
  `funcionario_id` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `plano_estudos`
--

CREATE TABLE `plano_estudos` (
  `id` int(11) NOT NULL,
  `CURSOS` int(11) NOT NULL,
  `DISCIPLINA` int(11) NOT NULL,
  `ano` int(11) DEFAULT 1,
  `semestre` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `plano_estudos`
--

INSERT INTO `plano_estudos` (`id`, `CURSOS`, `DISCIPLINA`, `ano`, `semestre`) VALUES
(147, 1, 8, 1, 1),
(159, 1, 3, 1, 1),
(160, 1, 12, 1, 1),
(161, 1, 7, 1, 2),
(162, 1, 11, 1, 2),
(163, 1, 5, 1, 2),
(164, 1, 10, 1, 2),
(165, 1, 9, 1, 2),
(166, 2, 1, 1, 1),
(167, 2, 8, 1, 1),
(168, 2, 2, 1, 1),
(169, 2, 3, 1, 1),
(170, 2, 12, 1, 1),
(171, 2, 7, 1, 2),
(172, 2, 11, 1, 2),
(175, 2, 9, 1, 2),
(176, 3, 1, 1, 1),
(177, 3, 8, 1, 1),
(178, 3, 2, 1, 1),
(179, 3, 3, 1, 1),
(180, 3, 12, 1, 1),
(181, 3, 7, 1, 2),
(182, 3, 11, 1, 2),
(183, 3, 5, 1, 2),
(184, 3, 10, 1, 2),
(185, 3, 9, 1, 2),
(186, 1, 2, 1, 1),
(187, 1, 1, 1, 1),
(190, 2, 5, 1, 2),
(191, 2, 10, 1, 2);

-- --------------------------------------------------------

--
-- Estrutura da tabela `users`
--

CREATE TABLE `users` (
  `login` varchar(20) NOT NULL,
  `pwd` varchar(250) NOT NULL,
  `grupo` int(11) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `nome_completo` varchar(200) DEFAULT NULL,
  `ultimo_acesso` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `users`
--

INSERT INTO `users` (`login`, `pwd`, `grupo`, `email`, `nome_completo`, `ultimo_acesso`) VALUES
('aluno1', '$2y$10$DNW1zUdDALWI/46Yr5J8Zu3uhlVi.42XrOJgGhyw5kmc/0fR5OXa.', 2, NULL, NULL, NULL),
('func1', '$2a$12$6/a6hLPADaiwgPs8TMTqn.I.MQTWhp.Y3xS9Usd4Co1XZFYtwgpkm', 3, 'func1@ipca.pt', 'Funcionário Teste', NULL),
('gestor1', '$2a$12$yBCTTXxO0AJ8dwZZk2H/3.bWX4246WJeMy3fip1RMSabND1.IY6QS', 1, 'gestor1@ipca.pt', 'Gestor Pedagógico', '2026-03-15 13:39:03');

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `cursos`
--
ALTER TABLE `cursos`
  ADD PRIMARY KEY (`ID`);

--
-- Índices para tabela `disciplinas`
--
ALTER TABLE `disciplinas`
  ADD PRIMARY KEY (`ID`);

--
-- Índices para tabela `fichas_aluno`
--
ALTER TABLE `fichas_aluno`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `curso_id` (`curso_id`),
  ADD KEY `gestor_id` (`gestor_id`);

--
-- Índices para tabela `grupos`
--
ALTER TABLE `grupos`
  ADD PRIMARY KEY (`ID`);

--
-- Índices para tabela `notas`
--
ALTER TABLE `notas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pauta_id` (`pauta_id`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `funcionario_id` (`funcionario_id`);

--
-- Índices para tabela `pautas`
--
ALTER TABLE `pautas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uc_id` (`uc_id`),
  ADD KEY `funcionario_id` (`funcionario_id`);

--
-- Índices para tabela `pedidos_matricula`
--
ALTER TABLE `pedidos_matricula`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `curso_id` (`curso_id`),
  ADD KEY `funcionario_id` (`funcionario_id`);

--
-- Índices para tabela `plano_estudos`
--
ALTER TABLE `plano_estudos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `CURSOS` (`CURSOS`),
  ADD KEY `DISCIPLINA` (`DISCIPLINA`);

--
-- Índices para tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`login`),
  ADD KEY `grupo` (`grupo`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `cursos`
--
ALTER TABLE `cursos`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `disciplinas`
--
ALTER TABLE `disciplinas`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de tabela `fichas_aluno`
--
ALTER TABLE `fichas_aluno`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `grupos`
--
ALTER TABLE `grupos`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `notas`
--
ALTER TABLE `notas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `pautas`
--
ALTER TABLE `pautas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `pedidos_matricula`
--
ALTER TABLE `pedidos_matricula`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `plano_estudos`
--
ALTER TABLE `plano_estudos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=193;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `fichas_aluno`
--
ALTER TABLE `fichas_aluno`
  ADD CONSTRAINT `fichas_aluno_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `users` (`login`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fichas_aluno_ibfk_2` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fichas_aluno_ibfk_3` FOREIGN KEY (`gestor_id`) REFERENCES `users` (`login`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limitadores para a tabela `notas`
--
ALTER TABLE `notas`
  ADD CONSTRAINT `notas_ibfk_1` FOREIGN KEY (`pauta_id`) REFERENCES `pautas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `notas_ibfk_2` FOREIGN KEY (`aluno_id`) REFERENCES `users` (`login`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `notas_ibfk_3` FOREIGN KEY (`funcionario_id`) REFERENCES `users` (`login`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `pautas`
--
ALTER TABLE `pautas`
  ADD CONSTRAINT `pautas_ibfk_1` FOREIGN KEY (`uc_id`) REFERENCES `disciplinas` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `pautas_ibfk_2` FOREIGN KEY (`funcionario_id`) REFERENCES `users` (`login`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `pedidos_matricula`
--
ALTER TABLE `pedidos_matricula`
  ADD CONSTRAINT `pedidos_matricula_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `users` (`login`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `pedidos_matricula_ibfk_2` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `pedidos_matricula_ibfk_3` FOREIGN KEY (`funcionario_id`) REFERENCES `users` (`login`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limitadores para a tabela `plano_estudos`
--
ALTER TABLE `plano_estudos`
  ADD CONSTRAINT `plano_estudos_ibfk_1` FOREIGN KEY (`CURSOS`) REFERENCES `cursos` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `plano_estudos_ibfk_2` FOREIGN KEY (`DISCIPLINA`) REFERENCES `disciplinas` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`grupo`) REFERENCES `grupos` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
