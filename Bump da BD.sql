-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307:3307
-- Tempo de geração: 07-Jun-2026 às 16:52
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
-- Banco de dados: `pcu`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `acesso`
--

CREATE TABLE `acesso` (
  `id` int(11) NOT NULL,
  `utilizador_id` int(11) NOT NULL,
  `tipo_recurso` enum('sala','laboratorio','equipamento') NOT NULL,
  `recurso_id` int(11) NOT NULL,
  `metodo` enum('cartao','movel','manual') NOT NULL,
  `resultado` enum('autorizado','negado') NOT NULL,
  `motivo_negacao` varchar(255) DEFAULT NULL,
  `registado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `acesso`
--

INSERT INTO `acesso` (`id`, `utilizador_id`, `tipo_recurso`, `recurso_id`, `metodo`, `resultado`, `motivo_negacao`, `registado_em`) VALUES
(6, 11, 'sala', 5, 'manual', 'negado', 'O utilizador chegou fora do horário da reserva.', '2026-06-07 01:18:10'),
(7, 11, 'sala', 5, 'manual', 'negado', 'O utilizador nao tem uma reserva valida para este espaco neste horario.', '2026-06-07 11:57:47'),
(8, 11, 'sala', 7, 'manual', 'negado', 'O utilizador nao tem uma reserva valida para este espaco neste horario.', '2026-06-07 11:57:53'),
(9, 11, 'sala', 8, 'manual', 'autorizado', NULL, '2026-06-07 11:57:54'),
(10, 1, 'sala', 7, 'manual', 'negado', 'O utilizador nao tem uma reserva valida para este espaco neste horario.', '2026-06-07 12:27:49');

-- --------------------------------------------------------

--
-- Estrutura da tabela `alerta`
--

CREATE TABLE `alerta` (
  `id` int(11) NOT NULL,
  `sensor_sustentabilidade_id` int(11) DEFAULT NULL,
  `tipo` enum('critico','aviso','informacao') NOT NULL,
  `mensagem` text NOT NULL,
  `valor_registado` decimal(10,2) DEFAULT NULL,
  `lido` tinyint(1) DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` enum('pendente','resolvido') DEFAULT 'pendente',
  `resolvido_em` timestamp NULL DEFAULT NULL,
  `resolvido_por` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `alerta`
--

INSERT INTO `alerta` (`id`, `sensor_sustentabilidade_id`, `tipo`, `mensagem`, `valor_registado`, `lido`, `criado_em`, `estado`, `resolvido_em`, `resolvido_por`) VALUES
(8, 5, 'critico', 'Temperatura acima do limite crítico', 27.50, 0, '2026-06-07 01:12:02', 'resolvido', '2026-06-07 01:12:24', 1),
(9, 5, 'critico', 'Temperatura acima do limite crítico', 23.10, 0, '2026-06-07 01:12:30', 'resolvido', '2026-06-07 12:57:59', 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `bicicleta`
--

CREATE TABLE `bicicleta` (
  `id` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `localidade_id` int(11) DEFAULT NULL,
  `estado` enum('disponivel','em_uso','manutencao') DEFAULT 'disponivel',
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `bicicleta`
--

INSERT INTO `bicicleta` (`id`, `codigo`, `localidade_id`, `estado`, `ativo`) VALUES
(9, 'BIKE-001', 1, 'disponivel', 1),
(10, 'BIKE-002', 1, 'disponivel', 1),
(11, 'BIKE-003', 1, 'disponivel', 1),
(12, 'BIKE-004', 1, 'disponivel', 1),
(13, 'BIKE-005', 1, 'disponivel', 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `equipamento`
--

CREATE TABLE `equipamento` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `tipo` varchar(100) DEFAULT NULL,
  `numero_serie` varchar(100) DEFAULT NULL,
  `estado` enum('disponivel','em_manutencao','indisponivel') DEFAULT 'disponivel',
  `ativo` tinyint(1) DEFAULT 1,
  `sala_id` int(11) DEFAULT NULL,
  `laboratorio_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `equipamento`
--

INSERT INTO `equipamento` (`id`, `nome`, `tipo`, `numero_serie`, `estado`, `ativo`, `sala_id`, `laboratorio_id`) VALUES
(4, 'Projetor 4K', 'Multimédia', '', 'disponivel', 1, 5, NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `laboratorio`
--

CREATE TABLE `laboratorio` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `edificio` varchar(50) DEFAULT NULL,
  `piso` int(11) DEFAULT NULL,
  `capacidade` int(11) DEFAULT NULL,
  `area_cientifica` varchar(100) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `laboratorio`
--

INSERT INTO `laboratorio` (`id`, `nome`, `edificio`, `piso`, `capacidade`, `area_cientifica`, `descricao`, `ativo`) VALUES
(3, 'Laboratório 1', 'Bloco B', 2, 19, 'Engenharia Química', '', 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `limites_alerta`
--

CREATE TABLE `limites_alerta` (
  `id` int(11) NOT NULL,
  `sensor_sustentabilidade_id` int(11) DEFAULT NULL,
  `limite_aviso` decimal(10,2) NOT NULL,
  `limite_critico` decimal(10,2) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `limites_alerta`
--

INSERT INTO `limites_alerta` (`id`, `sensor_sustentabilidade_id`, `limite_aviso`, `limite_critico`, `ativo`, `criado_em`, `atualizado_em`) VALUES
(5, 5, 30.00, 40.00, 1, '2026-06-07 01:11:58', '2026-06-07 12:57:16');

-- --------------------------------------------------------

--
-- Estrutura da tabela `localidade`
--

CREATE TABLE `localidade` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `localidade`
--

INSERT INTO `localidade` (`id`, `nome`) VALUES
(1, 'Porto - Campus Principal'),
(2, 'Polo Tecnológico'),
(3, 'Residência Universitária');

-- --------------------------------------------------------

--
-- Estrutura da tabela `lugar_estacionamento`
--

CREATE TABLE `lugar_estacionamento` (
  `id` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `localidade_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `lugar_estacionamento`
--

INSERT INTO `lugar_estacionamento` (`id`, `codigo`, `localidade_id`) VALUES
(11, 'P1-01', 2);

-- --------------------------------------------------------

--
-- Estrutura da tabela `mfa_codigo`
--

CREATE TABLE `mfa_codigo` (
  `id` int(11) NOT NULL,
  `utilizador_id` int(11) NOT NULL,
  `codigo` varchar(6) NOT NULL,
  `expira_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `usado` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `mfa_codigo`
--

INSERT INTO `mfa_codigo` (`id`, `utilizador_id`, `codigo`, `expira_em`, `usado`) VALUES
(18, 1, '208368', '2026-06-07 11:53:21', 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `mfa_dispositivo`
--

CREATE TABLE `mfa_dispositivo` (
  `id` int(11) NOT NULL,
  `utilizador_id` int(11) NOT NULL,
  `token` varchar(128) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `expira_em` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `mfa_dispositivo`
--

INSERT INTO `mfa_dispositivo` (`id`, `utilizador_id`, `token`, `criado_em`, `expira_em`) VALUES
(1, 1, '334c695eddfca2223a9551fc6ca7e8025ff7609e0bc5bb44947269907709e795', '2026-06-06 21:57:23', '2026-07-06 22:57:23'),
(2, 1, '5b4c5f51d3cdf7d73b10d5a56292fd07da0828141ff0cdd06143f3bca6efe700', '2026-06-07 11:53:21', '2026-07-07 12:53:21');

-- --------------------------------------------------------

--
-- Estrutura da tabela `permissao`
--

CREATE TABLE `permissao` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `permissao`
--

INSERT INTO `permissao` (`id`, `nome`, `descricao`) VALUES
(1, 'reservar_salas', 'Permite reservar salas de aula'),
(2, 'reservar_laboratorios', 'Permite reservar laboratórios'),
(3, 'reservar_equipamentos', 'Permite reservar equipamentos'),
(4, 'acesso_mobilidade', 'Permite usar bicicletas e trotinetes'),
(5, 'acesso_iot', 'Permite ver dashboard IoT'),
(6, 'exportar_relatorios', 'Permite exportar relatórios'),
(7, 'acesso_lss', 'Permite usar o terminal LSS'),
(8, 'gestao_espacos', 'Permite gerir salas, labs e equipamentos'),
(9, 'gestao_utilizadores', 'Permite gerir utilizadores');

-- --------------------------------------------------------

--
-- Estrutura da tabela `registo_mobilidade`
--

CREATE TABLE `registo_mobilidade` (
  `id` int(11) NOT NULL,
  `utilizador_id` int(11) NOT NULL,
  `tipo_recurso` enum('estacionamento','bicicleta','trotinete') NOT NULL,
  `recurso_id` int(11) DEFAULT NULL,
  `acao` enum('criar','editar','remover') NOT NULL,
  `detalhes` text DEFAULT NULL,
  `registado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `registo_mobilidade`
--

INSERT INTO `registo_mobilidade` (`id`, `utilizador_id`, `tipo_recurso`, `recurso_id`, `acao`, `detalhes`, `registado_em`) VALUES
(1, 1, 'estacionamento', 4, 'criar', 'Código: P1-05, Localidade: 3', '2026-06-06 21:58:13'),
(2, 1, 'estacionamento', 5, 'criar', 'Código: P1-06, Localidade: 1', '2026-06-06 22:10:16'),
(3, 1, 'estacionamento', 6, 'criar', 'Código: P1-03, Localidade: 2', '2026-06-06 22:10:34'),
(4, 1, 'estacionamento', 7, 'criar', 'Código: P1-50, Localidade: 2', '2026-06-06 22:11:27'),
(5, 1, 'estacionamento', 8, 'criar', 'Código: P1-60, Localidade: 1', '2026-06-06 22:15:01'),
(6, 1, 'estacionamento', 9, 'criar', 'Código: P1-51, Localidade: 1', '2026-06-06 22:50:49'),
(7, 1, 'estacionamento', 10, 'criar', 'Código: P1-55, Localidade: 1', '2026-06-06 23:55:11'),
(8, 1, 'bicicleta', 4, 'criar', 'Código: BIKE-004, Localidade: 1, Estado: disponivel', '2026-06-06 23:55:19'),
(9, 1, 'bicicleta', 5, 'criar', 'Código: BIKE-005, Localidade: 1, Estado: disponivel', '2026-06-06 23:55:19'),
(10, 1, 'bicicleta', 6, 'criar', 'Código: BIKE-006, Localidade: 1, Estado: disponivel', '2026-06-06 23:55:19'),
(11, 1, 'bicicleta', 7, 'criar', 'Código: BIKE-007, Localidade: 1, Estado: disponivel', '2026-06-06 23:55:19'),
(12, 1, 'bicicleta', 8, 'criar', 'Código: BIKE-008, Localidade: 1, Estado: disponivel', '2026-06-06 23:55:19'),
(13, 1, 'trotinete', 3, 'criar', 'Código: TROT-003, Localidade: 3, Estado: disponivel', '2026-06-06 23:55:29'),
(14, 1, 'trotinete', 4, 'criar', 'Código: TROT-004, Localidade: 3, Estado: disponivel', '2026-06-06 23:55:29'),
(15, 1, 'trotinete', 5, 'criar', 'Código: TROT-005, Localidade: 3, Estado: disponivel', '2026-06-06 23:55:29'),
(16, 1, 'trotinete', 6, 'criar', 'Código: TROT-006, Localidade: 3, Estado: disponivel', '2026-06-06 23:55:29'),
(17, 1, 'trotinete', 7, 'criar', 'Código: TROT-007, Localidade: 3, Estado: disponivel', '2026-06-06 23:55:29'),
(18, 1, 'trotinete', 8, 'criar', 'Código: TROT-008, Localidade: 3, Estado: disponivel', '2026-06-06 23:55:29'),
(19, 1, 'trotinete', 9, 'criar', 'Código: TROT-009, Localidade: 3, Estado: disponivel', '2026-06-06 23:55:29'),
(20, 1, 'trotinete', 10, 'criar', 'Código: TROT-010, Localidade: 3, Estado: disponivel', '2026-06-06 23:55:29'),
(21, 1, 'trotinete', 11, 'criar', 'Código: TROT-011, Localidade: 3, Estado: disponivel', '2026-06-06 23:55:29'),
(22, 1, 'trotinete', 12, 'criar', 'Código: TROT-012, Localidade: 3, Estado: disponivel', '2026-06-06 23:55:29'),
(23, 1, 'bicicleta', 9, 'criar', 'Código: BIKE-001, Localidade: 1, Estado: disponivel', '2026-06-07 01:10:54'),
(24, 1, 'bicicleta', 10, 'criar', 'Código: BIKE-002, Localidade: 1, Estado: disponivel', '2026-06-07 01:10:54'),
(25, 1, 'bicicleta', 11, 'criar', 'Código: BIKE-003, Localidade: 1, Estado: disponivel', '2026-06-07 01:10:54'),
(26, 1, 'bicicleta', 12, 'criar', 'Código: BIKE-004, Localidade: 1, Estado: disponivel', '2026-06-07 01:10:54'),
(27, 1, 'bicicleta', 13, 'criar', 'Código: BIKE-005, Localidade: 1, Estado: disponivel', '2026-06-07 01:10:54'),
(28, 1, 'estacionamento', 11, 'criar', 'Código: P1-01, Localidade: 2', '2026-06-07 01:11:04');

-- --------------------------------------------------------

--
-- Estrutura da tabela `reserva`
--

CREATE TABLE `reserva` (
  `id` int(11) NOT NULL,
  `utilizador_id` int(11) NOT NULL,
  `tipo_recurso` enum('sala','laboratorio','equipamento') NOT NULL,
  `recurso_id` int(11) NOT NULL,
  `data_inicio` datetime DEFAULT NULL,
  `data_fim` datetime DEFAULT NULL,
  `finalidade` text DEFAULT NULL,
  `estado` enum('confirmada','cancelada','expirada') DEFAULT 'confirmada'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `reserva`
--

INSERT INTO `reserva` (`id`, `utilizador_id`, `tipo_recurso`, `recurso_id`, `data_inicio`, `data_fim`, `finalidade`, `estado`) VALUES
(11, 1, 'sala', 5, '2026-06-07 12:55:00', '2026-06-07 12:56:00', 'Aula', 'expirada'),
(12, 11, 'sala', 8, '2026-06-07 12:57:00', '2026-06-07 12:58:00', '', 'expirada'),
(13, 11, 'sala', 5, '2026-06-07 13:22:00', '2026-06-07 13:23:00', '', 'expirada'),
(14, 1, 'sala', 5, '2026-06-07 13:24:00', '2026-06-07 13:25:00', '', 'expirada'),
(15, 1, 'sala', 5, '2026-06-07 13:28:00', '2026-06-07 13:29:00', '', 'cancelada');

-- --------------------------------------------------------

--
-- Estrutura da tabela `reserva_bicicleta`
--

CREATE TABLE `reserva_bicicleta` (
  `id` int(11) NOT NULL,
  `utilizador_id` int(11) NOT NULL,
  `bicicleta_id` int(11) NOT NULL,
  `inicio` timestamp NOT NULL DEFAULT current_timestamp(),
  `fim` timestamp NULL DEFAULT NULL,
  `estado` enum('ativa','cancelada','concluida') DEFAULT 'ativa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `reserva_bicicleta`
--

INSERT INTO `reserva_bicicleta` (`id`, `utilizador_id`, `bicicleta_id`, `inicio`, `fim`, `estado`) VALUES
(27, 1, 9, '2026-06-07 12:27:00', '2026-06-07 12:28:00', 'concluida'),
(28, 11, 9, '2026-06-07 13:03:00', '2026-06-07 13:04:00', 'concluida');

-- --------------------------------------------------------

--
-- Estrutura da tabela `reserva_trotinete`
--

CREATE TABLE `reserva_trotinete` (
  `id` int(11) NOT NULL,
  `utilizador_id` int(11) NOT NULL,
  `trotinete_id` int(11) NOT NULL,
  `inicio` timestamp NOT NULL DEFAULT current_timestamp(),
  `fim` timestamp NULL DEFAULT NULL,
  `estado` enum('ativa','cancelada','concluida') DEFAULT 'ativa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `sala`
--

CREATE TABLE `sala` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `edificio` varchar(50) DEFAULT NULL,
  `piso` int(11) DEFAULT NULL,
  `capacidade` int(11) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `sala`
--

INSERT INTO `sala` (`id`, `nome`, `edificio`, `piso`, `capacidade`, `descricao`, `ativo`) VALUES
(5, 'Sala 103', 'Bloco A', 1, 50, 'Auditório de LAM', 1),
(7, 'Sala 102', 'Bloco B', 1, 30, '', 1),
(9, 'Sala 107', 'Bloco C', 1, 45, '', 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `sensor_sustentabilidade`
--

CREATE TABLE `sensor_sustentabilidade` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `tipo` enum('temperatura','consumo_energetico','qualidade_ar','humidade') NOT NULL,
  `recurso_tipo` enum('sala','laboratorio') DEFAULT NULL,
  `recurso_id` int(11) DEFAULT NULL,
  `sala_id` int(11) DEFAULT NULL,
  `laboratorio_id` int(11) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `limite_alerta` decimal(10,2) DEFAULT NULL,
  `registado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `firmware` varchar(30) DEFAULT 'v2.3.1',
  `estado_tecnico` enum('ativo','inativo','manutencao') DEFAULT 'ativo',
  `intervalo_recolha` int(11) DEFAULT 30
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `sensor_sustentabilidade`
--

INSERT INTO `sensor_sustentabilidade` (`id`, `codigo`, `tipo`, `recurso_tipo`, `recurso_id`, `sala_id`, `laboratorio_id`, `ativo`, `limite_alerta`, `registado_em`, `firmware`, `estado_tecnico`, `intervalo_recolha`) VALUES
(5, 'S-A-101-T', 'temperatura', 'sala', 5, NULL, NULL, 1, NULL, '2026-06-07 01:10:32', 'v2.3.1', 'ativo', 30);

-- --------------------------------------------------------

--
-- Estrutura da tabela `trotinete`
--

CREATE TABLE `trotinete` (
  `id` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `localidade_id` int(11) DEFAULT NULL,
  `estado` enum('disponivel','em_uso','manutencao') DEFAULT 'disponivel',
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `utilizador`
--

CREATE TABLE `utilizador` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `login` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `perfil` enum('estudante','docente','funcionario_administrativo','administrador') NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `utilizador`
--

INSERT INTO `utilizador` (`id`, `nome`, `email`, `login`, `password`, `perfil`, `ativo`, `criado_em`) VALUES
(1, 'Administrador Sistema', 'gcostaribeiro2006@gmail.com', 'admin@upt.pt', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'administrador', 1, '2026-06-04 00:22:05'),
(11, 'Henrique Ricardo', 'gcostaribeiro20@gmail.com', 'ricardo@upt.pt', '$2y$10$H4wQSDv59Q3pdmTsKxDLNuCi8SfW96XZoOcJx3/5qb0hR6DTXIFqC', 'estudante', 1, '2026-06-07 01:08:45'),
(12, 'Alexandre Veiga', 'veiga1196@gmail.com', 'veiga@upt.pt', '$2y$10$zBzeXJdzHtH8UdbKNmVFV.Bam68kNgucowxvUhwN.DhKPGADpqxGu', 'docente', 1, '2026-06-07 01:09:13'),
-- --------------------------------------------------------

--
-- Estrutura da tabela `utilizador_permissao`
--

CREATE TABLE `utilizador_permissao` (
  `id` int(11) NOT NULL,
  `utilizador_id` int(11) NOT NULL,
  `permissao_id` int(11) NOT NULL,
  `atribuido_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atribuido_por` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `utilizador_permissao`
--

INSERT INTO `utilizador_permissao` (`id`, `utilizador_id`, `permissao_id`, `atribuido_em`, `atribuido_por`) VALUES
(28, 11, 4, '2026-06-07 01:08:45', 1),
(29, 11, 5, '2026-06-07 01:08:45', 1),
(30, 12, 1, '2026-06-07 01:09:13', 1),
(31, 12, 2, '2026-06-07 01:09:13', 1),
(32, 12, 3, '2026-06-07 01:09:13', 1),
(33, 12, 5, '2026-06-07 01:09:13', 1),
(34, 13, 6, '2026-06-07 01:09:58', 1),
(35, 13, 8, '2026-06-07 01:09:58', 1),
(36, 13, 9, '2026-06-07 01:09:58', 1),
(37, 11, 1, '2026-06-07 01:16:28', 1),
(38, 11, 2, '2026-06-07 01:16:32', 1),
(39, 11, 3, '2026-06-07 01:16:34', 1),
(42, 12, 7, '2026-06-07 13:07:42', 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `valor_consumo_energetico`
--

CREATE TABLE `valor_consumo_energetico` (
  `id` int(11) NOT NULL,
  `sensor_sustentabilidade_id` int(11) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `registado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `valor_humidade`
--

CREATE TABLE `valor_humidade` (
  `id` int(11) NOT NULL,
  `sensor_sustentabilidade_id` int(11) NOT NULL,
  `valor` decimal(5,2) NOT NULL,
  `registado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `valor_qualidade_ar`
--

CREATE TABLE `valor_qualidade_ar` (
  `id` int(11) NOT NULL,
  `sensor_sustentabilidade_id` int(11) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `registado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `valor_temperatura`
--

CREATE TABLE `valor_temperatura` (
  `id` int(11) NOT NULL,
  `sensor_sustentabilidade_id` int(11) NOT NULL,
  `valor` decimal(5,2) NOT NULL,
  `registado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `valor_temperatura`
--

INSERT INTO `valor_temperatura` (`id`, `sensor_sustentabilidade_id`, `valor`, `registado_em`) VALUES
(25, 5, 25.70, '2026-06-07 01:11:25'),
(26, 5, 20.00, '2026-06-07 01:11:33'),
(27, 5, 29.40, '2026-06-07 01:11:34'),
(28, 5, 27.50, '2026-06-07 01:12:02'),
(29, 5, 23.10, '2026-06-07 01:12:30'),
(30, 5, 23.50, '2026-06-07 12:25:53'),
(31, 5, 25.30, '2026-06-07 12:25:55'),
(32, 5, 27.90, '2026-06-07 12:57:20'),
(33, 5, 23.60, '2026-06-07 12:57:37'),
(34, 5, 18.70, '2026-06-07 12:57:38'),
(35, 5, 28.80, '2026-06-07 12:58:03'),
(36, 5, 20.20, '2026-06-07 12:58:05'),
(37, 5, 26.00, '2026-06-07 12:58:06');

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `acesso`
--
ALTER TABLE `acesso`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_acesso_utilizador` (`utilizador_id`);

--
-- Índices para tabela `alerta`
--
ALTER TABLE `alerta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_alerta_sensor_sust` (`sensor_sustentabilidade_id`),
  ADD KEY `fk_alerta_resolvido_por` (`resolvido_por`);

--
-- Índices para tabela `bicicleta`
--
ALTER TABLE `bicicleta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_bicicleta_localidade` (`localidade_id`);

--
-- Índices para tabela `equipamento`
--
ALTER TABLE `equipamento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_equipamento_sala` (`sala_id`),
  ADD KEY `fk_equipamento_laboratorio` (`laboratorio_id`);

--
-- Índices para tabela `laboratorio`
--
ALTER TABLE `laboratorio`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `limites_alerta`
--
ALTER TABLE `limites_alerta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_limites_alerta_sensor` (`sensor_sustentabilidade_id`);

--
-- Índices para tabela `localidade`
--
ALTER TABLE `localidade`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `lugar_estacionamento`
--
ALTER TABLE `lugar_estacionamento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_lugar_estacionamento_localidade` (`localidade_id`);

--
-- Índices para tabela `mfa_codigo`
--
ALTER TABLE `mfa_codigo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_mfa_codigo_utilizador` (`utilizador_id`);

--
-- Índices para tabela `mfa_dispositivo`
--
ALTER TABLE `mfa_dispositivo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_mfa_dispositivo_token` (`token`),
  ADD KEY `fk_mfa_dispositivo_utilizador` (`utilizador_id`);

--
-- Índices para tabela `permissao`
--
ALTER TABLE `permissao`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `registo_mobilidade`
--
ALTER TABLE `registo_mobilidade`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_registo_mobilidade_utilizador` (`utilizador_id`);

--
-- Índices para tabela `reserva`
--
ALTER TABLE `reserva`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_reserva_utilizador` (`utilizador_id`);

--
-- Índices para tabela `reserva_bicicleta`
--
ALTER TABLE `reserva_bicicleta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_res_bic_utilizador` (`utilizador_id`),
  ADD KEY `fk_res_bic_bicicleta` (`bicicleta_id`);

--
-- Índices para tabela `reserva_trotinete`
--
ALTER TABLE `reserva_trotinete`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_res_trot_utilizador` (`utilizador_id`),
  ADD KEY `fk_res_trot_trotinete` (`trotinete_id`);

--
-- Índices para tabela `sala`
--
ALTER TABLE `sala`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `sensor_sustentabilidade`
--
ALTER TABLE `sensor_sustentabilidade`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sensor_sustentabilidade_codigo` (`codigo`),
  ADD KEY `fk_sensor_sust_sala` (`sala_id`),
  ADD KEY `fk_sensor_sust_laboratorio` (`laboratorio_id`);

--
-- Índices para tabela `trotinete`
--
ALTER TABLE `trotinete`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_trotinete_localidade` (`localidade_id`);

--
-- Índices para tabela `utilizador`
--
ALTER TABLE `utilizador`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_email` (`email`),
  ADD UNIQUE KEY `uq_login` (`login`);

--
-- Índices para tabela `utilizador_permissao`
--
ALTER TABLE `utilizador_permissao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_up_utilizador` (`utilizador_id`),
  ADD KEY `fk_up_permissao` (`permissao_id`),
  ADD KEY `fk_up_atribuido_por` (`atribuido_por`);

--
-- Índices para tabela `valor_consumo_energetico`
--
ALTER TABLE `valor_consumo_energetico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_vconsumo_sensor` (`sensor_sustentabilidade_id`);

--
-- Índices para tabela `valor_humidade`
--
ALTER TABLE `valor_humidade`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_vhumidade_sensor` (`sensor_sustentabilidade_id`);

--
-- Índices para tabela `valor_qualidade_ar`
--
ALTER TABLE `valor_qualidade_ar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_vqar_sensor` (`sensor_sustentabilidade_id`);

--
-- Índices para tabela `valor_temperatura`
--
ALTER TABLE `valor_temperatura`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_vtemp_sensor` (`sensor_sustentabilidade_id`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `acesso`
--
ALTER TABLE `acesso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `alerta`
--
ALTER TABLE `alerta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `bicicleta`
--
ALTER TABLE `bicicleta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de tabela `equipamento`
--
ALTER TABLE `equipamento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `laboratorio`
--
ALTER TABLE `laboratorio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `limites_alerta`
--
ALTER TABLE `limites_alerta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `localidade`
--
ALTER TABLE `localidade`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `lugar_estacionamento`
--
ALTER TABLE `lugar_estacionamento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `mfa_codigo`
--
ALTER TABLE `mfa_codigo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `mfa_dispositivo`
--
ALTER TABLE `mfa_dispositivo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `permissao`
--
ALTER TABLE `permissao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `registo_mobilidade`
--
ALTER TABLE `registo_mobilidade`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de tabela `reserva`
--
ALTER TABLE `reserva`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de tabela `reserva_bicicleta`
--
ALTER TABLE `reserva_bicicleta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de tabela `reserva_trotinete`
--
ALTER TABLE `reserva_trotinete`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `sala`
--
ALTER TABLE `sala`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `sensor_sustentabilidade`
--
ALTER TABLE `sensor_sustentabilidade`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `trotinete`
--
ALTER TABLE `trotinete`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `utilizador`
--
ALTER TABLE `utilizador`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de tabela `utilizador_permissao`
--
ALTER TABLE `utilizador_permissao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT de tabela `valor_consumo_energetico`
--
ALTER TABLE `valor_consumo_energetico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de tabela `valor_humidade`
--
ALTER TABLE `valor_humidade`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de tabela `valor_qualidade_ar`
--
ALTER TABLE `valor_qualidade_ar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de tabela `valor_temperatura`
--
ALTER TABLE `valor_temperatura`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `acesso`
--
ALTER TABLE `acesso`
  ADD CONSTRAINT `fk_acesso_utilizador` FOREIGN KEY (`utilizador_id`) REFERENCES `utilizador` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `alerta`
--
ALTER TABLE `alerta`
  ADD CONSTRAINT `fk_alerta_resolvido_por` FOREIGN KEY (`resolvido_por`) REFERENCES `utilizador` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_alerta_sensor_sust` FOREIGN KEY (`sensor_sustentabilidade_id`) REFERENCES `sensor_sustentabilidade` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limitadores para a tabela `bicicleta`
--
ALTER TABLE `bicicleta`
  ADD CONSTRAINT `fk_bicicleta_localidade` FOREIGN KEY (`localidade_id`) REFERENCES `localidade` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limitadores para a tabela `equipamento`
--
ALTER TABLE `equipamento`
  ADD CONSTRAINT `fk_equipamento_laboratorio` FOREIGN KEY (`laboratorio_id`) REFERENCES `laboratorio` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_equipamento_sala` FOREIGN KEY (`sala_id`) REFERENCES `sala` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limitadores para a tabela `limites_alerta`
--
ALTER TABLE `limites_alerta`
  ADD CONSTRAINT `fk_limites_alerta_sensor` FOREIGN KEY (`sensor_sustentabilidade_id`) REFERENCES `sensor_sustentabilidade` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limitadores para a tabela `lugar_estacionamento`
--
ALTER TABLE `lugar_estacionamento`
  ADD CONSTRAINT `fk_lugar_estacionamento_localidade` FOREIGN KEY (`localidade_id`) REFERENCES `localidade` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limitadores para a tabela `mfa_codigo`
--
ALTER TABLE `mfa_codigo`
  ADD CONSTRAINT `fk_mfa_codigo_utilizador` FOREIGN KEY (`utilizador_id`) REFERENCES `utilizador` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `mfa_dispositivo`
--
ALTER TABLE `mfa_dispositivo`
  ADD CONSTRAINT `fk_mfa_dispositivo_utilizador` FOREIGN KEY (`utilizador_id`) REFERENCES `utilizador` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `reserva`
--
ALTER TABLE `reserva`
  ADD CONSTRAINT `fk_reserva_utilizador` FOREIGN KEY (`utilizador_id`) REFERENCES `utilizador` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `reserva_bicicleta`
--
ALTER TABLE `reserva_bicicleta`
  ADD CONSTRAINT `fk_res_bic_bicicleta` FOREIGN KEY (`bicicleta_id`) REFERENCES `bicicleta` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_res_bic_utilizador` FOREIGN KEY (`utilizador_id`) REFERENCES `utilizador` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `reserva_trotinete`
--
ALTER TABLE `reserva_trotinete`
  ADD CONSTRAINT `fk_res_trot_trotinete` FOREIGN KEY (`trotinete_id`) REFERENCES `trotinete` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_res_trot_utilizador` FOREIGN KEY (`utilizador_id`) REFERENCES `utilizador` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `sensor_sustentabilidade`
--
ALTER TABLE `sensor_sustentabilidade`
  ADD CONSTRAINT `fk_sensor_sust_laboratorio` FOREIGN KEY (`laboratorio_id`) REFERENCES `laboratorio` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sensor_sust_sala` FOREIGN KEY (`sala_id`) REFERENCES `sala` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limitadores para a tabela `trotinete`
--
ALTER TABLE `trotinete`
  ADD CONSTRAINT `fk_trotinete_localidade` FOREIGN KEY (`localidade_id`) REFERENCES `localidade` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limitadores para a tabela `utilizador_permissao`
--
ALTER TABLE `utilizador_permissao`
  ADD CONSTRAINT `fk_up_atribuido_por` FOREIGN KEY (`atribuido_por`) REFERENCES `utilizador` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_up_permissao` FOREIGN KEY (`permissao_id`) REFERENCES `permissao` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_up_utilizador` FOREIGN KEY (`utilizador_id`) REFERENCES `utilizador` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `valor_consumo_energetico`
--
ALTER TABLE `valor_consumo_energetico`
  ADD CONSTRAINT `fk_vconsumo_sensor` FOREIGN KEY (`sensor_sustentabilidade_id`) REFERENCES `sensor_sustentabilidade` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `valor_humidade`
--
ALTER TABLE `valor_humidade`
  ADD CONSTRAINT `fk_vhumidade_sensor` FOREIGN KEY (`sensor_sustentabilidade_id`) REFERENCES `sensor_sustentabilidade` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `valor_qualidade_ar`
--
ALTER TABLE `valor_qualidade_ar`
  ADD CONSTRAINT `fk_vqar_sensor` FOREIGN KEY (`sensor_sustentabilidade_id`) REFERENCES `sensor_sustentabilidade` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `valor_temperatura`
--
ALTER TABLE `valor_temperatura`
  ADD CONSTRAINT `fk_vtemp_sensor` FOREIGN KEY (`sensor_sustentabilidade_id`) REFERENCES `sensor_sustentabilidade` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
