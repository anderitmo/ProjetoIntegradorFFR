-- Schema do Banco de Dados inhosti_projint
-- Engine: InnoDB, Charset: UTF-8 (utf8mb4)

CREATE DATABASE IF NOT EXISTS `inhosti_projint` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `inhosti_projint`;

-- 1. Tabela de Professores
CREATE TABLE IF NOT EXISTS `professores` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario` VARCHAR(50) NOT NULL UNIQUE,
  `senha_hash` VARCHAR(255) NOT NULL,
  `nome` VARCHAR(100) NOT NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabela de Ciclos (Semestres)
CREATE TABLE IF NOT EXISTS `ciclos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome_semestre` VARCHAR(20) NOT NULL,
  `status_ativo` TINYINT(1) DEFAULT 1,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabela de Grupos de PI
CREATE TABLE IF NOT EXISTS `grupos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ciclo_id` INT NOT NULL,
  `codigo_acesso_unico` VARCHAR(20) NOT NULL UNIQUE,
  `tema` VARCHAR(255) NOT NULL,
  `nivel_pi` INT NOT NULL DEFAULT 1,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ciclo_id`) REFERENCES `ciclos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabela de Alunos
CREATE TABLE IF NOT EXISTS `alunos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(100) NOT NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabela de Relacionamento Grupo - Alunos (Histórico / Reprovações)
CREATE TABLE IF NOT EXISTS `grupo_alunos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `grupo_id` INT NOT NULL,
  `aluno_id` INT NOT NULL,
  `status_aluno` ENUM('ativo', 'aprovado', 'reprovado', 'desistente') DEFAULT 'ativo',
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`grupo_id`) REFERENCES `grupos`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`aluno_id`) REFERENCES `alunos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabela de Tarefas (Kanban)
CREATE TABLE IF NOT EXISTS `tarefas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `grupo_id` INT NOT NULL,
  `titulo` VARCHAR(255) NOT NULL,
  `descricao` TEXT NULL,
  `status_kanban` ENUM('a_fazer', 'em_andamento', 'revisao', 'concluido') DEFAULT 'a_fazer',
  `data_prazo` DATE NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`grupo_id`) REFERENCES `grupos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Tabela de Arquivos das Tarefas (Controle de Versão)
CREATE TABLE IF NOT EXISTS `tarefa_arquivos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tarefa_id` INT NOT NULL,
  `aluno_id` INT NOT NULL,
  `caminho_arquivo` VARCHAR(255) NOT NULL,
  `versao` INT NOT NULL DEFAULT 1,
  `data_envio` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`tarefa_id`) REFERENCES `tarefas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`aluno_id`) REFERENCES `alunos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Tabela da Agenda de Bancas
CREATE TABLE IF NOT EXISTS `agenda_bancas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `grupo_id` INT NOT NULL,
  `data_apresentacao` DATETIME NOT NULL,
  `link_convite_externo` VARCHAR(255) NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`grupo_id`) REFERENCES `grupos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserção de dados iniciais para testes
-- Senha do professor: admin123
INSERT INTO `professores` (`usuario`, `senha_hash`, `nome`) VALUES
('admin', '$2y$10$yeXQjv1AnPky5lsbPel.Y.zgWWlxSltsWIJ8SRW8h5g1f95d/Ok.G', 'Prof. Dr. Carlos Silva')
ON DUPLICATE KEY UPDATE `senha_hash`='$2y$10$yeXQjv1AnPky5lsbPel.Y.zgWWlxSltsWIJ8SRW8h5g1f95d/Ok.G', `id`=`id`;

INSERT INTO `ciclos` (`id`, `nome_semestre`, `status_ativo`) VALUES
(1, '2025.1', 1)
ON DUPLICATE KEY UPDATE `id`=`id`;

INSERT INTO `grupos` (`id`, `ciclo_id`, `codigo_acesso_unico`, `tema`, `nivel_pi`) VALUES
(1, 1, 'PI2025-G1', 'Sistema de Gestão Agrícola IoT', 1),
(2, 1, 'PI2025-G2', 'Aplicativo de Saúde Comunitária', 1)
ON DUPLICATE KEY UPDATE `id`=`id`;

INSERT INTO `alunos` (`id`, `nome`) VALUES
(1, 'Ana Souza'),
(2, 'Bruno Lima'),
(3, 'Carla Mendes'),
(4, 'Daniel Oliveira')
ON DUPLICATE KEY UPDATE `id`=`id`;

INSERT INTO `grupo_alunos` (`grupo_id`, `aluno_id`, `status_aluno`) VALUES
(1, 1, 'ativo'),
(1, 2, 'ativo'),
(2, 3, 'ativo'),
(2, 4, 'ativo')
ON DUPLICATE KEY UPDATE `id`=`id`;

INSERT INTO `tarefas` (`grupo_id`, `titulo`, `descricao`, `status_kanban`, `data_prazo`) VALUES
(1, 'Elaboração da Documentação Inicial', 'Levantamento de requisitos e diagrama de casos de uso.', 'a_fazer', '2025-05-10'),
(1, 'Protótipo de Tela', 'Desenvolvimento do mock-up navegável em HTML/CSS.', 'em_andamento', '2025-05-15'),
(1, 'Modelagem do Banco de Dados', 'Criação do DER e DDL em SQL puro.', 'revisao', '2025-05-20'),
(1, 'Apresentação Final', 'Preparar os slides para a banca.', 'concluido', '2025-06-01')
ON DUPLICATE KEY UPDATE `id`=`id`;
