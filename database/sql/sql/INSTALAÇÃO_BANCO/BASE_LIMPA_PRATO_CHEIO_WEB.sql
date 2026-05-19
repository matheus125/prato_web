CREATE DATABASE  IF NOT EXISTS `prato_cheio` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `prato_cheio`;
-- MySQL dump 10.13  Distrib 8.0.44, for Win64 (x86_64)
--
-- Host: localhost    Database: prato_cheio
-- ------------------------------------------------------
-- Server version	8.0.44

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `tb_access_denied`
--

DROP TABLE IF EXISTS `tb_access_denied`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tb_access_denied` (
  `id_access_denied` int NOT NULL AUTO_INCREMENT,
  `perfil` varchar(20) NOT NULL,
  `rota` varchar(255) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_access_denied`),
  KEY `idx_perfil` (`perfil`),
  KEY `idx_rota` (`rota`),
  KEY `idx_ip` (`ip`)
) ENGINE=InnoDB AUTO_INCREMENT=107 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_access_denied`
--

LOCK TABLES `tb_access_denied` WRITE;
/*!40000 ALTER TABLE `tb_access_denied` DISABLE KEYS */;
/*!40000 ALTER TABLE `tb_access_denied` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_dependentes`
--

DROP TABLE IF EXISTS `tb_dependentes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tb_dependentes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_titular` int DEFAULT NULL,
  `id_familia` int DEFAULT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rg` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cpf` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `idade` int DEFAULT NULL,
  `genero` enum('M','F','Outro') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dependencia_cliente` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registration_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `registration_date_update` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_dependente_titular` (`id_titular`),
  KEY `fk_dependente_familia` (`id_familia`),
  CONSTRAINT `fk_dependente_familia` FOREIGN KEY (`id_familia`) REFERENCES `tb_familia` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_dependente_titular` FOREIGN KEY (`id_titular`) REFERENCES `tb_titular` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_dependentes`
--

LOCK TABLES `tb_dependentes` WRITE;
/*!40000 ALTER TABLE `tb_dependentes` DISABLE KEYS */;
/*!40000 ALTER TABLE `tb_dependentes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_endereco`
--

DROP TABLE IF EXISTS `tb_endereco`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tb_endereco` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cep` varchar(20) DEFAULT NULL,
  `bairro` varchar(50) DEFAULT NULL,
  `rua` varchar(100) DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `nacionalidade` varchar(50) DEFAULT NULL,
  `naturalidade` varchar(50) DEFAULT NULL,
  `cidade` varchar(50) DEFAULT NULL,
  `tempo_moradia_anos` varchar(50) DEFAULT NULL,
  `registration_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `registration_date_update` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=663 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_endereco`
--

LOCK TABLES `tb_endereco` WRITE;
/*!40000 ALTER TABLE `tb_endereco` DISABLE KEYS */;
/*!40000 ALTER TABLE `tb_endereco` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_familia`
--

DROP TABLE IF EXISTS `tb_familia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tb_familia` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome_familia` varchar(100) NOT NULL,
  `registration_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `registration_date_update` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=821 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_familia`
--

LOCK TABLES `tb_familia` WRITE;
/*!40000 ALTER TABLE `tb_familia` DISABLE KEYS */;
/*!40000 ALTER TABLE `tb_familia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_fechamento_dia`
--

DROP TABLE IF EXISTS `tb_fechamento_dia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tb_fechamento_dia` (
  `data_refeicao` varchar(10) NOT NULL,
  `limite` int NOT NULL DEFAULT '0',
  `total` int NOT NULL DEFAULT '0',
  `fechado` tinyint(1) NOT NULL DEFAULT '0',
  `fechado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`data_refeicao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_fechamento_dia`
--

LOCK TABLES `tb_fechamento_dia` WRITE;
/*!40000 ALTER TABLE `tb_fechamento_dia` DISABLE KEYS */;
/*!40000 ALTER TABLE `tb_fechamento_dia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_frequencia_diaria`
--

DROP TABLE IF EXISTS `tb_frequencia_diaria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tb_frequencia_diaria` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_id` int DEFAULT NULL,
  `nome` varchar(100) NOT NULL,
  `data` date DEFAULT NULL,
  `status` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cliente_id` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_frequencia_diaria`
--

LOCK TABLES `tb_frequencia_diaria` WRITE;
/*!40000 ALTER TABLE `tb_frequencia_diaria` DISABLE KEYS */;
/*!40000 ALTER TABLE `tb_frequencia_diaria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_frequencia_geral`
--

DROP TABLE IF EXISTS `tb_frequencia_geral`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tb_frequencia_geral` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_id` int DEFAULT NULL,
  `nome` varchar(100) NOT NULL,
  `data` date DEFAULT NULL,
  `status` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cliente_id` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_frequencia_geral`
--

LOCK TABLES `tb_frequencia_geral` WRITE;
/*!40000 ALTER TABLE `tb_frequencia_geral` DISABLE KEYS */;
/*!40000 ALTER TABLE `tb_frequencia_geral` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_funcionario`
--

DROP TABLE IF EXISTS `tb_funcionario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tb_funcionario` (
  `id_pessoa` int NOT NULL AUTO_INCREMENT,
  `nome_funcionario` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nrphone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dtregister` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ativo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id_pessoa`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `nrphone` (`nrphone`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_funcionario`
--

LOCK TABLES `tb_funcionario` WRITE;
/*!40000 ALTER TABLE `tb_funcionario` DISABLE KEYS */;
INSERT INTO `tb_funcionario` VALUES (1,'Matheus Mota da Silva','mmota350@gmail.com','(92) 98167-8846','2026-01-19 18:55:53',1),(2,'Raufe Souza','raufe@souza.com.br','(90) 99009-0911','2026-01-19 19:19:34',0),(3,'Valmir Cardene de Souza','valmir@gmail.com','(92) 99232-8678','2024-01-05 08:38:26',1),(4,'Liliane Santos','liliane@gmail.com','(92) 99235-0041','2024-01-11 04:06:14',0),(5,'Elianderson Mota da Silva adasasdasd','elianderson@gmail.com','(92) 98401-1109','2024-04-25 22:17:08',1),(6,'Fernando Baleia','FERNANDO@MATADOR.COM','(92) 98856-0782','2026-03-10 19:18:27',0),(7,'Fernanda Morhy Silva','santos@gmail.com','92993402694','2026-03-11 20:10:46',1),(8,'Raufe Souza','raufe.n.souza@gmail.com','92988451212','2026-03-18 14:17:04',1),(9,'ASDASDASD','mmotaasd350@gmail.com','12312312312','2026-04-14 12:32:00',0),(10,'CÉLIA ÇALVES','teste@teste.com','99999-9999','2026-04-14 12:44:37',1),(11,'GONÇALVES','asdasda@gmail.com','99292929299','2026-04-14 13:02:44',1);
/*!40000 ALTER TABLE `tb_funcionario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_permissions`
--

DROP TABLE IF EXISTS `tb_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tb_permissions` (
  `id_permission` int NOT NULL AUTO_INCREMENT,
  `permission_key` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  `module_name` varchar(60) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_permission`),
  UNIQUE KEY `uq_permission_key` (`permission_key`)
) ENGINE=InnoDB AUTO_INCREMENT=2845 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_permissions`
--

LOCK TABLES `tb_permissions` WRITE;
/*!40000 ALTER TABLE `tb_permissions` DISABLE KEYS */;
INSERT INTO `tb_permissions` VALUES (1,'DASHBOARD_VIEW','Visualizar dashboard','DASHBOARD','2026-03-12 14:56:34'),(2,'FUNCIONARIOS_VIEW','Visualizar funcionários','FUNCIONARIOS','2026-03-12 14:56:34'),(3,'FUNCIONARIOS_CREATE','Cadastrar funcionários','FUNCIONARIOS','2026-03-12 14:56:34'),(4,'FUNCIONARIOS_UPDATE','Editar funcionários','FUNCIONARIOS','2026-03-12 14:56:34'),(5,'FUNCIONARIOS_DELETE','Excluir/inativar funcionários','FUNCIONARIOS','2026-03-12 14:56:34'),(6,'FUNCIONARIOS_PASSWORD','Alterar senha de funcionários','FUNCIONARIOS','2026-03-12 14:56:34'),(7,'CLIENTES_VIEW','Visualizar clientes','CLIENTES','2026-03-12 14:56:34'),(8,'CLIENTES_CREATE','Cadastrar clientes','CLIENTES','2026-03-12 14:56:34'),(9,'CLIENTES_UPDATE','Editar clientes','CLIENTES','2026-03-12 14:56:34'),(10,'CLIENTES_DELETE','Excluir clientes','CLIENTES','2026-03-12 14:56:34'),(11,'VENDAS_VIEW','Visualizar vendas','VENDAS','2026-03-12 14:56:34'),(12,'RELATORIOS_VIEW','Visualizar relatórios','RELATORIOS','2026-03-12 14:56:34'),(13,'BACKUP_RUN','Executar backup','BACKUP','2026-03-12 14:56:34'),(14,'NOTIFICACOES_CLEAR','Limpar notificações','NOTIFICACOES','2026-03-12 14:56:34'),(15,'ACL_PROFILES_MANAGE','Gerenciar permissões por perfil','SEGURANCA','2026-03-12 14:56:34'),(16,'ACL_DENIED_VIEW','Visualizar acessos negados','SEGURANCA','2026-03-12 14:56:34'),(17,'USUARIOS_SECURITY_MANAGE','Gerenciar status/bloqueio de usuários','SEGURANCA','2026-03-12 14:56:34'),(28,'DEPENDENTES_VIEW','Visualizar dependentes','DEPENDENTES','2026-03-13 16:08:31'),(29,'DEPENDENTES_CREATE','Cadastrar dependentes','DEPENDENTES','2026-03-13 16:08:31'),(30,'DEPENDENTES_UPDATE','Editar dependentes','DEPENDENTES','2026-03-13 16:08:31'),(34,'NOTIFICACOES_VIEW','Visualizar notificações','NOTIFICACOES','2026-03-13 16:08:31'),(36,'NOTIFICACAO_TESTE_CREATE','Gerar notificação de teste','NOTIFICACOES','2026-03-13 16:08:31'),(40,'SISTEMA_DEBUG','Acessar rotas de debug','SEGURANCA','2026-03-13 16:08:31'),(1384,'AUDITORIA_VIEW','Visualizar logs de auditoria','SEGURANCA','2026-03-26 18:39:28');
/*!40000 ALTER TABLE `tb_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_profile_permissions`
--

DROP TABLE IF EXISTS `tb_profile_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tb_profile_permissions` (
  `id_profile_permission` int NOT NULL AUTO_INCREMENT,
  `perfil` enum('ADMIN','SUPERVISOR','ASSESSOR') NOT NULL,
  `id_permission` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_profile_permission`),
  UNIQUE KEY `uq_profile_permission` (`perfil`,`id_permission`),
  KEY `fk_profile_permission_permission` (`id_permission`),
  CONSTRAINT `fk_profile_permission_permission` FOREIGN KEY (`id_permission`) REFERENCES `tb_permissions` (`id_permission`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5783 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_profile_permissions`
--

LOCK TABLES `tb_profile_permissions` WRITE;
/*!40000 ALTER TABLE `tb_profile_permissions` DISABLE KEYS */;
INSERT INTO `tb_profile_permissions` VALUES (4738,'ADMIN',13,'2026-04-01 01:01:51'),(4739,'ADMIN',8,'2026-04-01 01:01:51'),(4740,'ADMIN',9,'2026-04-01 01:01:51'),(4741,'ADMIN',10,'2026-04-01 01:01:51'),(4742,'ADMIN',7,'2026-04-01 01:01:51'),(4743,'ADMIN',1,'2026-04-01 01:01:51'),(4744,'ADMIN',29,'2026-04-01 01:01:51'),(4745,'ADMIN',30,'2026-04-01 01:01:51'),(4746,'ADMIN',28,'2026-04-01 01:01:51'),(4747,'ADMIN',6,'2026-04-01 01:01:51'),(4748,'ADMIN',3,'2026-04-01 01:01:51'),(4749,'ADMIN',4,'2026-04-01 01:01:51'),(4750,'ADMIN',5,'2026-04-01 01:01:51'),(4751,'ADMIN',2,'2026-04-01 01:01:51'),(4752,'ADMIN',36,'2026-04-01 01:01:51'),(4753,'ADMIN',14,'2026-04-01 01:01:51'),(4754,'ADMIN',34,'2026-04-01 01:01:51'),(4755,'ADMIN',12,'2026-04-01 01:01:51'),(4756,'ADMIN',40,'2026-04-01 01:01:51'),(4757,'ADMIN',15,'2026-04-01 01:01:51'),(4758,'ADMIN',17,'2026-04-01 01:01:51'),(4759,'ADMIN',16,'2026-04-01 01:01:51'),(4760,'ADMIN',1384,'2026-04-01 01:01:51'),(4761,'ADMIN',11,'2026-04-01 01:01:51'),(4807,'SUPERVISOR',13,'2026-04-01 01:01:52'),(4808,'SUPERVISOR',8,'2026-04-01 01:01:52'),(4809,'SUPERVISOR',9,'2026-04-01 01:01:52'),(4810,'SUPERVISOR',10,'2026-04-01 01:01:52'),(4811,'SUPERVISOR',7,'2026-04-01 01:01:52'),(4812,'SUPERVISOR',1,'2026-04-01 01:01:52'),(4813,'SUPERVISOR',29,'2026-04-01 01:01:52'),(4814,'SUPERVISOR',30,'2026-04-01 01:01:52'),(4815,'SUPERVISOR',28,'2026-04-01 01:01:52'),(4816,'SUPERVISOR',6,'2026-04-01 01:01:52'),(4817,'SUPERVISOR',3,'2026-04-01 01:01:52'),(4818,'SUPERVISOR',4,'2026-04-01 01:01:52'),(4819,'SUPERVISOR',2,'2026-04-01 01:01:52'),(4820,'SUPERVISOR',36,'2026-04-01 01:01:52'),(4821,'SUPERVISOR',14,'2026-04-01 01:01:52'),(4822,'SUPERVISOR',34,'2026-04-01 01:01:52'),(4823,'SUPERVISOR',12,'2026-04-01 01:01:52'),(4824,'SUPERVISOR',11,'2026-04-01 01:01:52'),(4870,'ASSESSOR',13,'2026-04-01 01:01:52'),(4871,'ASSESSOR',8,'2026-04-01 01:01:52'),(4872,'ASSESSOR',9,'2026-04-01 01:01:52'),(4873,'ASSESSOR',7,'2026-04-01 01:01:52'),(4874,'ASSESSOR',1,'2026-04-01 01:01:52'),(4875,'ASSESSOR',29,'2026-04-01 01:01:52'),(4876,'ASSESSOR',28,'2026-04-01 01:01:52'),(4877,'ASSESSOR',2,'2026-04-01 01:01:52'),(4878,'ASSESSOR',36,'2026-04-01 01:01:52'),(4879,'ASSESSOR',14,'2026-04-01 01:01:52'),(4880,'ASSESSOR',34,'2026-04-01 01:01:52'),(4881,'ASSESSOR',12,'2026-04-01 01:01:52'),(4882,'ASSESSOR',11,'2026-04-01 01:01:52');
/*!40000 ALTER TABLE `tb_profile_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_refeicoes_vendidas`
--

DROP TABLE IF EXISTS `tb_refeicoes_vendidas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tb_refeicoes_vendidas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `total_servido` int NOT NULL,
  `data` varchar(20) DEFAULT NULL,
  `registration_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `registration_date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_refeicoes_vendidas`
--

LOCK TABLES `tb_refeicoes_vendidas` WRITE;
/*!40000 ALTER TABLE `tb_refeicoes_vendidas` DISABLE KEYS */;
/*!40000 ALTER TABLE `tb_refeicoes_vendidas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_relatorios`
--

DROP TABLE IF EXISTS `tb_relatorios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tb_relatorios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Idade_3a17Masculino` int NOT NULL,
  `Idade_3a17Masculino_PCD` int NOT NULL,
  `Idade_3a17Feminino` int NOT NULL,
  `Idade_3a17Feminino_PCD` int NOT NULL,
  `Idade_18a59Masculino` int NOT NULL,
  `Idade_18a59Masculino_PCD` int NOT NULL,
  `Idade_17a59Feminino` int NOT NULL,
  `Idade_17a59Feminino_PCD` int NOT NULL,
  `Idade_60Masculino` int NOT NULL,
  `Idade_60Masculino_PCD` int NOT NULL,
  `Idade_60Feminino` int NOT NULL,
  `Idade_60Feminino_PCD` int NOT NULL,
  `Situacao_risco_masculino` int NOT NULL,
  `Situacao_risco_Feminino` int NOT NULL,
  `Deficientes` int NOT NULL,
  `senhas_genericas` int NOT NULL,
  `Total_pessoas_atendidas` int NOT NULL,
  `qtd_refeicoes_servidas` int DEFAULT NULL,
  `ocorrencias` text,
  `cardapio` text,
  `nome_banco` varchar(100) DEFAULT NULL,
  `refeicoes_ofertadas` int DEFAULT '0',
  `sobra_refeicoes` int DEFAULT '0',
  `sobra_senhas` int DEFAULT '0',
  `data` date NOT NULL,
  `fechado` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `data` (`data`),
  UNIQUE KEY `uk_data_banco` (`data`,`nome_banco`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_relatorios`
--

LOCK TABLES `tb_relatorios` WRITE;
/*!40000 ALTER TABLE `tb_relatorios` DISABLE KEYS */;
/*!40000 ALTER TABLE `tb_relatorios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_relatorios_pdf`
--

DROP TABLE IF EXISTS `tb_relatorios_pdf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tb_relatorios_pdf` (
  `id` int NOT NULL AUTO_INCREMENT,
  `data_relatorio` date NOT NULL,
  `nome_arquivo` varchar(255) NOT NULL,
  `url_publica` varchar(500) DEFAULT NULL,
  `caminho_remoto` varchar(255) DEFAULT NULL,
  `status_upload` enum('SUCESSO','ERRO') NOT NULL DEFAULT 'SUCESSO',
  `mensagem_erro` text,
  `responsavel` varchar(255) DEFAULT NULL,
  `cpf_responsavel` varchar(20) DEFAULT NULL,
  `data_geracao` datetime NOT NULL,
  `data_upload` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_data_relatorio` (`data_relatorio`),
  KEY `idx_status_upload` (`status_upload`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_relatorios_pdf`
--

LOCK TABLES `tb_relatorios_pdf` WRITE;
/*!40000 ALTER TABLE `tb_relatorios_pdf` DISABLE KEYS */;
/*!40000 ALTER TABLE `tb_relatorios_pdf` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_resumodia`
--

DROP TABLE IF EXISTS `tb_resumodia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tb_resumodia` (
  `id` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_resumodia`
--

LOCK TABLES `tb_resumodia` WRITE;
/*!40000 ALTER TABLE `tb_resumodia` DISABLE KEYS */;
/*!40000 ALTER TABLE `tb_resumodia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_senhas`
--

DROP TABLE IF EXISTS `tb_senhas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tb_senhas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente` varchar(100) DEFAULT NULL,
  `cpf` varchar(45) DEFAULT NULL,
  `Idade` varchar(20) DEFAULT NULL,
  `Genero` varchar(45) DEFAULT NULL,
  `Deficiente` varchar(45) DEFAULT NULL,
  `tipoSenha` varchar(10) DEFAULT NULL,
  `status_cliente` varchar(45) DEFAULT NULL,
  `data_refeicao` varchar(10) DEFAULT NULL,
  `registration_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `registration_date_update` timestamp NULL DEFAULT NULL,
  `id_titular` int DEFAULT NULL,
  `id_dependente` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_senhas_data_cpf` (`data_refeicao`,`cpf`),
  KEY `idx_senhas_data_dep` (`data_refeicao`,`id_dependente`)
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_senhas`
--

LOCK TABLES `tb_senhas` WRITE;
/*!40000 ALTER TABLE `tb_senhas` DISABLE KEYS */;
/*!40000 ALTER TABLE `tb_senhas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_socio_economico`
--

DROP TABLE IF EXISTS `tb_socio_economico`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tb_socio_economico` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_titular` int DEFAULT NULL,
  `escolariedade` varchar(50) DEFAULT NULL,
  `renda_mensal_familia` varchar(50) DEFAULT NULL,
  `programas_sociais` varchar(50) DEFAULT NULL,
  `composicao_familiar` varchar(50) DEFAULT NULL,
  `moradia` varchar(50) DEFAULT NULL,
  `estrutura_Moradia` varchar(50) DEFAULT NULL,
  `qtdPessoas_Trabalhando` int DEFAULT NULL,
  `emprego` varchar(50) DEFAULT NULL,
  `profissao_Responsavel` varchar(50) DEFAULT NULL,
  `AB_Agua` varchar(3) DEFAULT NULL,
  `SN_basico` varchar(3) DEFAULT NULL,
  `Energia_eletrica` varchar(3) DEFAULT NULL,
  `Lixo_Domiciliar` varchar(15) DEFAULT NULL,
  `frequenta_Centro` varchar(5) DEFAULT NULL,
  `registration_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `registration_date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tb_socio_economico_ibfk_1` (`id_titular`),
  CONSTRAINT `tb_socio_economico_ibfk_1` FOREIGN KEY (`id_titular`) REFERENCES `tb_titular` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_socio_economico`
--

LOCK TABLES `tb_socio_economico` WRITE;
/*!40000 ALTER TABLE `tb_socio_economico` DISABLE KEYS */;
/*!40000 ALTER TABLE `tb_socio_economico` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_socio_economico_saude`
--

DROP TABLE IF EXISTS `tb_socio_economico_saude`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tb_socio_economico_saude` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_titular` int DEFAULT NULL,
  `id_dependente` int DEFAULT NULL,
  `doenca` varchar(100) DEFAULT NULL,
  `outras_Doencas` varchar(100) DEFAULT NULL,
  `deficiencia` varchar(3) DEFAULT NULL,
  `justificativa_Deficiencia` varchar(100) DEFAULT NULL,
  `laudo` varchar(100) DEFAULT NULL,
  `observacao` varchar(100) DEFAULT NULL,
  `registration_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `registration_date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tb_socio_economico_saude_ibfk_1` (`id_titular`),
  KEY `tb_socio_economico_saude_ibfk_2_idx` (`id_dependente`),
  CONSTRAINT `tb_socio_economico_saude_ibfk_1` FOREIGN KEY (`id_titular`) REFERENCES `tb_titular` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tb_socio_economico_saude_ibfk_2` FOREIGN KEY (`id_dependente`) REFERENCES `tb_dependentes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_socio_economico_saude`
--

LOCK TABLES `tb_socio_economico_saude` WRITE;
/*!40000 ALTER TABLE `tb_socio_economico_saude` DISABLE KEYS */;
/*!40000 ALTER TABLE `tb_socio_economico_saude` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_titular`
--

DROP TABLE IF EXISTS `tb_titular`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tb_titular` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_endereco` int DEFAULT NULL,
  `id_familia` int DEFAULT NULL,
  `nome_completo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nome_social` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cor_cliente` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nome_mae` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `idade` int DEFAULT NULL,
  `genero` enum('M','F','Outro') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado_civil` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rg` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cpf` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nis` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_cliente` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registration_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `registration_date_update` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_titular_endereco` (`id_endereco`),
  KEY `fk_titular_familia` (`id_familia`),
  CONSTRAINT `fk_titular_endereco` FOREIGN KEY (`id_endereco`) REFERENCES `tb_endereco` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_titular_familia` FOREIGN KEY (`id_familia`) REFERENCES `tb_familia` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=821 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_titular`
--

LOCK TABLES `tb_titular` WRITE;
/*!40000 ALTER TABLE `tb_titular` DISABLE KEYS */;
/*!40000 ALTER TABLE `tb_titular` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_userlogs`
--

DROP TABLE IF EXISTS `tb_userlogs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tb_userlogs` (
  `id_log` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `cpf_usuario` varchar(14) NOT NULL,
  `nome_funcionario` varchar(255) NOT NULL,
  `acao` varchar(50) NOT NULL,
  `modulo` varchar(50) DEFAULT NULL,
  `referencia_id` int DEFAULT NULL,
  `detalhes` text,
  `ip` varchar(45) NOT NULL,
  `user_agent` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`),
  KEY `idx_userlogs_usuario` (`id_usuario`),
  KEY `idx_userlogs_cpf` (`cpf_usuario`),
  KEY `idx_userlogs_acao` (`acao`),
  CONSTRAINT `fk_userlogs_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuario` (`id_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=898 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_userlogs`
--

LOCK TABLES `tb_userlogs` WRITE;
/*!40000 ALTER TABLE `tb_userlogs` DISABLE KEYS */;
/*!40000 ALTER TABLE `tb_userlogs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_usuario`
--

DROP TABLE IF EXISTS `tb_usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tb_usuario` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `id_pessoa` int NOT NULL,
  `cpf` varchar(14) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `senha` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inadmin` tinyint(1) NOT NULL DEFAULT '0',
  `dtregister` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `perfil` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `uq_usuario_pessoa` (`id_pessoa`),
  UNIQUE KEY `cpf` (`cpf`),
  UNIQUE KEY `uq_usuario_cpf` (`cpf`),
  CONSTRAINT `fk_usuario_pessoa` FOREIGN KEY (`id_pessoa`) REFERENCES `tb_funcionario` (`id_pessoa`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_usuario`
--

LOCK TABLES `tb_usuario` WRITE;
/*!40000 ALTER TABLE `tb_usuario` DISABLE KEYS */;
INSERT INTO `tb_usuario` VALUES (1,1,'022.903.862-00','$2y$12$jUqqeDP4n4Td0Jp.nud.BuhKpCe.QeIm6R.tKB8HP2drMh6eQGeuu',1,'2026-01-19 18:56:43','ADMIN',1),(2,2,'335.140.702-53','$2y$12$N5S5Qz/qDOUhEoCql.ECceVWXz2JxATV82hZ9cmnttA3twegSDg26',0,'2026-01-19 19:19:34','SUPERVISOR',0),(3,3,'660.918.882-34','$2y$12$ivPI8Ut4LMrYVRw5dlso0uZdA/AaHXH0AFq2/ibS.FqM7dEKPLQrq',1,'2026-01-27 16:48:13','ADMIN',1),(4,4,'602.124.622-53','$2y$12$vFFOVLRUJqBka9Fbc3cBp.zP3QSCIlzcvfUr6B5QxIli8v/CAAmsq',0,'2026-01-27 16:48:13','SUPERVISOR',1),(5,5,'774.153.292-87','$2y$12$HcBGA1Ho7LTeRPR1iBJID.VGKVLOtSpmH4zMVyZhc69k3JLCK2bHi',0,'2026-01-27 16:48:13','SUPERVISOR',1),(6,6,'836.203.382-72','$2y$12$YgctPbS3xeYJwIvgiX5jdu4lf1FHmwJhnvejZEB65.RvxkrD1/vzu',1,'2026-03-10 19:18:27','ADMIN',1),(7,7,'02543853200','$2y$12$7Df5AxDZk3c1p8Lau7uPQ.bZwYeKJksBocQbkDsBqbT.eSj/zSikO',0,'2026-03-11 20:10:46','ASSESSOR',1),(8,8,'02134952288','$2y$12$T/kWPUO9hEhjQNCkfZNLaO8WQ5XidcnV.pDsiCryF2tMctp4aMwrW',0,'2026-03-18 14:17:04','ASSESSOR',1),(9,9,'33514070253','$2y$12$hBo/y07FC1.isz7cNPaQMufZTIA3SNKKpNn/G2c0Li2TmGz3ElzjW',0,'2026-04-14 12:32:00','SUPERVISOR',0),(10,11,'04983172262','$2y$12$jD6L3h/MWwQMAcs7KmAoSOlUZxeOuUsJOL25/ki0p6RdBzHI685sG',0,'2026-04-14 13:02:44','ASSESSOR',1);
/*!40000 ALTER TABLE `tb_usuario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_usuarios_passwords_recoveries`
--

DROP TABLE IF EXISTS `tb_usuarios_passwords_recoveries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tb_usuarios_passwords_recoveries` (
  `id_recovery` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `desip` varchar(45) NOT NULL,
  `desrecovery` varchar(255) NOT NULL,
  `dtregister` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dtrecovery` datetime DEFAULT NULL,
  PRIMARY KEY (`id_recovery`),
  KEY `fk_password_recovery_usuario` (`id_usuario`),
  CONSTRAINT `fk_password_recovery_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_usuarios_passwords_recoveries`
--

LOCK TABLES `tb_usuarios_passwords_recoveries` WRITE;
/*!40000 ALTER TABLE `tb_usuarios_passwords_recoveries` DISABLE KEYS */;
/*!40000 ALTER TABLE `tb_usuarios_passwords_recoveries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'prato_cheio'
--

--
-- Dumping routines for database 'prato_cheio'
--
/*!50003 DROP PROCEDURE IF EXISTS `sp_atualizar_titular_familia_endereco` */;
ALTER DATABASE `prato_cheio` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_atualizar_titular_familia_endereco`(
    IN p_id_titular  INT,
    IN p_id_familia  INT,
    IN p_id_endereco INT,

    IN p_cep VARCHAR(20),
    IN p_bairro VARCHAR(50),
    IN p_rua VARCHAR(100),
    IN p_numero VARCHAR(20),
    IN p_referencia VARCHAR(100),
    IN p_nacionalidade VARCHAR(50),
    IN p_naturalidade VARCHAR(50),
    IN p_cidade VARCHAR(50),
    IN p_tempo_moradia_anos VARCHAR(50),

    IN p_nome_familia VARCHAR(100),

    IN p_nome_completo VARCHAR(100),
    IN p_nome_social VARCHAR(100),
    IN p_cor_cliente VARCHAR(50),
    IN p_nome_mae VARCHAR(100),
    IN p_telefone VARCHAR(20),
    IN p_data_nascimento DATE,
    IN p_genero VARCHAR(10),
    IN p_estado_civil VARCHAR(50),
    IN p_rg VARCHAR(20),
    IN p_cpf VARCHAR(15),
    IN p_nis VARCHAR(30),
    IN p_status_cliente VARCHAR(30)
)
BEGIN
    DECLARE v_idade INT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Erro ao atualizar titular, familia e endereco';
    END;

    START TRANSACTION;

    IF p_id_endereco IS NULL OR p_id_endereco <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ID do endereço inválido';
    END IF;

    IF p_id_familia IS NULL OR p_id_familia <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ID da família inválido';
    END IF;

    IF p_id_titular IS NULL OR p_id_titular <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ID do titular inválido';
    END IF;

    IF NOT EXISTS (SELECT 1 FROM tb_endereco WHERE id = p_id_endereco) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Endereço não encontrado para atualização';
    END IF;

    IF NOT EXISTS (SELECT 1 FROM tb_familia WHERE id = p_id_familia) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Família não encontrada para atualização';
    END IF;

    IF NOT EXISTS (SELECT 1 FROM tb_titular WHERE id = p_id_titular) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Titular não encontrado para atualização';
    END IF;

    SET v_idade = TIMESTAMPDIFF(YEAR, p_data_nascimento, CURDATE());

    UPDATE tb_endereco
       SET cep                = p_cep,
           bairro             = p_bairro,
           rua                = p_rua,
           numero             = p_numero,
           referencia         = p_referencia,
           nacionalidade      = p_nacionalidade,
           naturalidade       = p_naturalidade,
           cidade             = p_cidade,
           tempo_moradia_anos = p_tempo_moradia_anos
     WHERE id = p_id_endereco;

    UPDATE tb_familia
       SET nome_familia = p_nome_familia
     WHERE id = p_id_familia;

    UPDATE tb_titular
       SET id_endereco     = p_id_endereco,
           id_familia      = p_id_familia,
           nome_completo   = p_nome_completo,
           nome_social     = p_nome_social,
           cor_cliente     = p_cor_cliente,
           nome_mae        = p_nome_mae,
           telefone        = p_telefone,
           data_nascimento = p_data_nascimento,
           idade           = v_idade,
           genero          = p_genero,
           estado_civil    = p_estado_civil,
           rg              = p_rg,
           cpf             = p_cpf,
           nis             = p_nis,
           status_cliente  = p_status_cliente
     WHERE id = p_id_titular;

    COMMIT;

    SELECT
        p_id_titular  AS id_titular,
        p_id_familia  AS id_familia,
        p_id_endereco AS id_endereco,
        'ATUALIZAÇÃO REALIZADA COM SUCESSO' AS status;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `prato_cheio` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_cadastrar_dependente` */;
ALTER DATABASE `prato_cheio` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_cadastrar_dependente`(
    IN p_id_titular INT,
    IN p_nome VARCHAR(100),
    IN p_rg VARCHAR(20),
    IN p_cpf VARCHAR(15),
    IN p_data_nascimento DATE,
    IN p_genero ENUM('M','F','Outro'),
    IN p_dependencia_cliente VARCHAR(50)
)
BEGIN
    DECLARE v_id_familia INT;
    DECLARE v_idade INT;
    DECLARE v_id_dependente INT;

    -- Tratamento de erro
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Erro ao cadastrar dependente';
    END;

    START TRANSACTION;

    -- Busca a família vinculada ao titular
    SELECT id_familia
      INTO v_id_familia
      FROM tb_titular
     WHERE id = p_id_titular
     LIMIT 1;

    -- Validação
    IF v_id_familia IS NULL THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Titular nao encontrado';
    END IF;

    -- Calcula idade
    SET v_idade = TIMESTAMPDIFF(YEAR, p_data_nascimento, CURDATE());

    -- Insere dependente
    INSERT INTO tb_dependentes (
        id_titular,
        id_familia,
        nome,
        rg,
        cpf,
        data_nascimento,
        idade,
        genero,
        dependencia_cliente,
        registration_date
    ) VALUES (
        p_id_titular,
        v_id_familia,
        p_nome,
        p_rg,
        p_cpf,
        p_data_nascimento,
        v_idade,
        p_genero,
        p_dependencia_cliente,
        NOW()
    );

    SET v_id_dependente = LAST_INSERT_ID();

    COMMIT;

    -- Retorno para o PHP
    SELECT
        v_id_dependente AS id_dependente,
        p_id_titular    AS id_titular,
        v_id_familia    AS id_familia,
        'DEPENDENTE CADASTRADO COM SUCESSO' AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `prato_cheio` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_cadastrar_titular_familia_endereco` */;
ALTER DATABASE `prato_cheio` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_cadastrar_titular_familia_endereco`(
    -- ENDEREÇO
    IN p_cep VARCHAR(20),
    IN p_bairro VARCHAR(50),
    IN p_rua VARCHAR(100),
    IN p_numero VARCHAR(20),
    IN p_referencia VARCHAR(100),
    IN p_nacionalidade VARCHAR(50),
    IN p_naturalidade VARCHAR(50),
    IN p_cidade VARCHAR(50),
    IN p_tempo_moradia_anos VARCHAR(50),

    -- FAMÍLIA
    IN p_nome_familia VARCHAR(100),

    -- TITULAR
    IN p_nome_completo VARCHAR(100),
    IN p_nome_social VARCHAR(100),
    IN p_cor_cliente VARCHAR(50),
    IN p_nome_mae VARCHAR(100),
    IN p_telefone VARCHAR(20),
    IN p_data_nascimento DATE,
    IN p_genero ENUM('M','F','Outro'),
    IN p_estado_civil VARCHAR(50),
    IN p_rg VARCHAR(20),
    IN p_cpf VARCHAR(15),
    IN p_nis VARCHAR(30),
    IN p_status_cliente VARCHAR(30)
)
BEGIN
    DECLARE v_id_endereco INT;
    DECLARE v_id_familia INT;
    DECLARE v_id_titular INT;
    DECLARE v_idade INT;

    -- Tratamento de erro
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Erro ao cadastrar titular, familia e endereco';
    END;

    START TRANSACTION;

    -- ======================
    -- ENDEREÇO
    -- ======================
    INSERT INTO tb_endereco (
        cep,
        bairro,
        rua,
        numero,
        referencia,
        nacionalidade,
        naturalidade,
        cidade,
        tempo_moradia_anos,
        registration_date
    ) VALUES (
        p_cep,
        p_bairro,
        p_rua,
        p_numero,
        p_referencia,
        p_nacionalidade,
        p_naturalidade,
        p_cidade,
        p_tempo_moradia_anos,
        NOW()
    );

    SET v_id_endereco = LAST_INSERT_ID();

    -- ======================
    -- FAMÍLIA
    -- ======================
    INSERT INTO tb_familia (
        nome_familia,
        registration_date
    ) VALUES (
        p_nome_familia,
        NOW()
    );

    SET v_id_familia = LAST_INSERT_ID();

    -- ======================
    -- IDADE
    -- ======================
    SET v_idade = TIMESTAMPDIFF(YEAR, p_data_nascimento, CURDATE());

    -- ======================
    -- TITULAR
    -- ======================
    INSERT INTO tb_titular (
        id_endereco,
        id_familia,
        nome_completo,
        nome_social,
        cor_cliente,
        nome_mae,
        telefone,
        data_nascimento,
        idade,
        genero,
        estado_civil,
        rg,
        cpf,
        nis,
        status_cliente,
        registration_date
    ) VALUES (
        v_id_endereco,
        v_id_familia,
        p_nome_completo,
        p_nome_social,
        p_cor_cliente,
        p_nome_mae,
        p_telefone,
        p_data_nascimento,
        v_idade,
        p_genero,
        p_estado_civil,
        p_rg,
        p_cpf,
        p_nis,
        p_status_cliente,
        NOW()
    );

    SET v_id_titular = LAST_INSERT_ID();

    COMMIT;

    -- Retorno para o PHP
    SELECT
        v_id_titular  AS id_titular,
        v_id_familia  AS id_familia,
        v_id_endereco AS id_endereco,
        'CADASTRO REALIZADO COM SUCESSO' AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `prato_cheio` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_excluir_titular_dependentes_endereco` */;
ALTER DATABASE `prato_cheio` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_excluir_titular_dependentes_endereco`(
    IN p_id_titular INT
)
BEGIN
    DECLARE v_id_endereco INT;
    DECLARE v_msg TEXT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_msg = MESSAGE_TEXT;
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = v_msg;
    END;

    START TRANSACTION;

    IF p_id_titular IS NULL OR p_id_titular <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ID do titular inválido';
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM tb_titular
        WHERE id = p_id_titular
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Titular não encontrado';
    END IF;

    SELECT id_endereco
      INTO v_id_endereco
      FROM tb_titular
     WHERE id = p_id_titular
     LIMIT 1;

    DELETE FROM tb_dependentes
     WHERE id_titular = p_id_titular;

    DELETE FROM tb_titular
     WHERE id = p_id_titular;

    IF v_id_endereco IS NOT NULL AND v_id_endereco > 0 THEN
        DELETE FROM tb_endereco
         WHERE id = v_id_endereco;
    END IF;

    COMMIT;

    SELECT
        p_id_titular AS id_titular,
        v_id_endereco AS id_endereco,
        'CLIENTE EXCLUÍDO COM SUCESSO' AS status;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `prato_cheio` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_fechamento_atualizar` */;
ALTER DATABASE `prato_cheio` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_fechamento_atualizar`(
  IN p_data VARCHAR(10),
  IN p_limite INT
)
BEGIN
  DECLARE v_total INT DEFAULT 0;

  SELECT COUNT(*) INTO v_total
  FROM tb_senhas
  WHERE data_refeicao = p_data;

  INSERT INTO tb_fechamento_dia (data_refeicao, limite, total, fechado, fechado_em)
  VALUES (p_data, p_limite, v_total, IF(v_total >= p_limite, 1, 0), IF(v_total >= p_limite, NOW(), NULL))
  ON DUPLICATE KEY UPDATE
    limite = VALUES(limite),
    total = VALUES(total),
    fechado = IF(VALUES(total) >= VALUES(limite), 1, 0),
    fechado_em = IF(VALUES(total) >= VALUES(limite), COALESCE(fechado_em, NOW()), NULL);
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `prato_cheio` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_funcionario_usuario_delete` */;
ALTER DATABASE `prato_cheio` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_funcionario_usuario_delete`(
    IN p_id_usuario INT
)
BEGIN
    -- DECLARES DEVEM VIR PRIMEIRO
    DECLARE v_id_pessoa INT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Usuario possui vinculos e nao pode ser excluido';
    END;

    START TRANSACTION;

    -- Busca id_pessoa
    SELECT id_pessoa
      INTO v_id_pessoa
      FROM tb_usuario
     WHERE id_usuario = p_id_usuario
     LIMIT 1;

    -- Usuario nao encontrado
    IF v_id_pessoa IS NULL THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Usuario nao encontrado';
    END IF;

    -- Remove usuario
    DELETE FROM tb_usuario
     WHERE id_usuario = p_id_usuario;

    -- Remove funcionario
    DELETE FROM tb_funcionario
     WHERE id_pessoa = v_id_pessoa;

    COMMIT;

    -- Retorno
    SELECT 
        p_id_usuario AS id_usuario,
        v_id_pessoa  AS id_pessoa,
        'EXCLUIDO COM SUCESSO' AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `prato_cheio` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_funcionario_usuario_save` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_funcionario_usuario_save`(
    IN p_nome_funcionario VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    IN p_email            VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    IN p_nrphone          VARCHAR(20)  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    IN p_cpf              VARCHAR(14)  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    IN p_senha            VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    IN p_perfil           VARCHAR(20)  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
)
BEGIN
    DECLARE v_id_pessoa INT;
    DECLARE v_inadmin TINYINT(1);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Erro ao salvar funcionário e usuário';
    END;

    SET v_inadmin = IF(UPPER(p_perfil) = 'ADMIN', 1, 0);

    START TRANSACTION;

    INSERT INTO tb_funcionario (
        nome_funcionario,
        email,
        nrphone,
        dtregister
    ) VALUES (
        p_nome_funcionario,
        p_email,
        p_nrphone,
        NOW()
    );

    SET v_id_pessoa = LAST_INSERT_ID();

    INSERT INTO tb_usuario (
        id_pessoa,
        cpf,
        senha,
        inadmin,
        dtregister,
        perfil
    ) VALUES (
        v_id_pessoa,
        p_cpf,
        p_senha,
        v_inadmin,
        NOW(),
        p_perfil
    );

    COMMIT;

    SELECT 
        f.id_pessoa,
        f.nome_funcionario,
        f.email,
        f.nrphone,
        u.id_usuario,
        u.cpf,
        u.perfil,
        u.inadmin
    FROM tb_funcionario f
    INNER JOIN tb_usuario u 
        ON u.id_pessoa = f.id_pessoa
    WHERE f.id_pessoa = v_id_pessoa;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_funcionario_usuario_soft_delete` */;
ALTER DATABASE `prato_cheio` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_funcionario_usuario_soft_delete`(
    IN p_id_usuario INT
)
BEGIN
    DECLARE v_id_pessoa INT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Erro ao desativar usuario';
    END;

    START TRANSACTION;

    SELECT id_pessoa
      INTO v_id_pessoa
      FROM tb_usuario
     WHERE id_usuario = p_id_usuario
     LIMIT 1;

    IF v_id_pessoa IS NULL THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Usuario nao encontrado';
    END IF;

    UPDATE tb_usuario
       SET ativo = 0
     WHERE id_usuario = p_id_usuario;

    UPDATE tb_funcionario
       SET ativo = 0
     WHERE id_pessoa = v_id_pessoa;

    COMMIT;

    SELECT 'USUARIO DESATIVADO COM SUCESSO' AS status;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `prato_cheio` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_funcionario_usuario_update` */;
ALTER DATABASE `prato_cheio` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_funcionario_usuario_update`(
    IN p_id_usuario       INT,
    IN p_nome_funcionario VARCHAR(255),
    IN p_email            VARCHAR(150),
    IN p_nrphone          VARCHAR(20),
    IN p_cpf              VARCHAR(14),
    IN p_senha            VARCHAR(255),
    IN p_perfil           VARCHAR(20)
)
BEGIN
    -- DECLARES sempre no início
    DECLARE v_id_pessoa INT;
    DECLARE v_inadmin TINYINT(1);

    -- Handler de erro
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Erro ao atualizar funcionário e usuário';
    END;

    -- Define se é admin
    SET v_inadmin = IF(p_perfil = 'ADMIN', 1, 0);

    START TRANSACTION;

    -- Descobre o id_pessoa pelo id_usuario
    SELECT id_pessoa
      INTO v_id_pessoa
      FROM tb_usuario
     WHERE id_usuario = p_id_usuario
     LIMIT 1;

    -- Atualiza funcionário
    UPDATE tb_funcionario
       SET nome_funcionario = p_nome_funcionario,
           email            = p_email,
           nrphone          = p_nrphone
     WHERE id_pessoa = v_id_pessoa;

    -- Atualiza usuário (senha opcional)
    UPDATE tb_usuario
       SET cpf      = p_cpf,
           perfil   = p_perfil,
           inadmin  = v_inadmin,
           senha    = IF(p_senha IS NULL OR p_senha = '', senha, p_senha)
     WHERE id_usuario = p_id_usuario;

    COMMIT;

    -- Retorno final
    SELECT 
        f.id_pessoa,
        f.nome_funcionario,
        f.email,
        f.nrphone,
        u.id_usuario,
        u.cpf,
        u.perfil,
        u.inadmin
    FROM tb_funcionario f
    INNER JOIN tb_usuario u 
        ON u.id_pessoa = f.id_pessoa
    WHERE u.id_usuario = p_id_usuario;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `prato_cheio` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_gerar_relatorio_dia` */;
ALTER DATABASE `prato_cheio` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_gerar_relatorio_dia`(IN p_data VARCHAR(20))
BEGIN
    /*
      Gera/atualiza (UPSERT) o resumo do dia em tb_relatorios.
      p_data deve bater com tb_senhas.data_refeicao (ex: '2026-03-02').
    */

    INSERT INTO tb_relatorios (
        Idade_3a17Masculino,
        Idade_3a17Masculino_PCD,
        Idade_3a17Feminino,
        Idade_3a17Feminino_PCD,

        Idade_18a59Masculino,
        Idade_18a59Masculino_PCD,
        Idade_17a59Feminino,
        Idade_17a59Feminino_PCD,

        Idade_60Masculino,
        Idade_60Masculino_PCD,
        Idade_60Feminino,
        Idade_60Feminino_PCD,

        Situacao_risco_masculino,
        Situacao_risco_Feminino,

        Deficientes,
        senhas_genericas,
        Total_pessoas_atendidas,
        data,
        fechado
    )
    SELECT
        /* 3 a 17 */
        SUM(CASE WHEN sexo='M' AND pcd=0 AND idade BETWEEN 3 AND 17 THEN 1 ELSE 0 END) AS Idade_3a17Masculino,
        SUM(CASE WHEN sexo='M' AND pcd=1 AND idade BETWEEN 3 AND 17 THEN 1 ELSE 0 END) AS Idade_3a17Masculino_PCD,
        SUM(CASE WHEN sexo='F' AND pcd=0 AND idade BETWEEN 3 AND 17 THEN 1 ELSE 0 END) AS Idade_3a17Feminino,
        SUM(CASE WHEN sexo='F' AND pcd=1 AND idade BETWEEN 3 AND 17 THEN 1 ELSE 0 END) AS Idade_3a17Feminino_PCD,

        /* 18 a 59 */
        SUM(CASE WHEN sexo='M' AND pcd=0 AND idade BETWEEN 18 AND 59 THEN 1 ELSE 0 END) AS Idade_18a59Masculino,
        SUM(CASE WHEN sexo='M' AND pcd=1 AND idade BETWEEN 18 AND 59 THEN 1 ELSE 0 END) AS Idade_18a59Masculino_PCD,
        SUM(CASE WHEN sexo='F' AND pcd=0 AND idade BETWEEN 18 AND 59 THEN 1 ELSE 0 END) AS Idade_17a59Feminino,
        SUM(CASE WHEN sexo='F' AND pcd=1 AND idade BETWEEN 18 AND 59 THEN 1 ELSE 0 END) AS Idade_17a59Feminino_PCD,

        /* 60+ */
        SUM(CASE WHEN sexo='M' AND pcd=0 AND idade >= 60 THEN 1 ELSE 0 END) AS Idade_60Masculino,
        SUM(CASE WHEN sexo='M' AND pcd=1 AND idade >= 60 THEN 1 ELSE 0 END) AS Idade_60Masculino_PCD,
        SUM(CASE WHEN sexo='F' AND pcd=0 AND idade >= 60 THEN 1 ELSE 0 END) AS Idade_60Feminino,
        SUM(CASE WHEN sexo='F' AND pcd=1 AND idade >= 60 THEN 1 ELSE 0 END) AS Idade_60Feminino_PCD,

        /* Você não tem esse campo em tb_senhas -> deixa 0 por enquanto */
        0 AS Situacao_risco_masculino,
        0 AS Situacao_risco_Feminino,

        /* Total PCD (deficientes) */
        SUM(CASE WHEN pcd=1 THEN 1 ELSE 0 END) AS Deficientes,

        /* Senhas genéricas (linhas em tb_senhas do tipo GENÉRICA) */
        SUM(CASE WHEN UPPER(tipoSenha)='GENERICA' THEN 1 ELSE 0 END) AS senhas_genericas,

        /* Total de pessoas atendidas = total de linhas do dia */
        COUNT(*) AS Total_pessoas_atendidas,

        p_data AS data,
        1 AS fechado
    FROM (
        SELECT
            /* idade extraída com segurança */
            CAST(NULLIF(REGEXP_SUBSTR(IFNULL(Idade,''), '[0-9]+'), '') AS UNSIGNED) AS idade,

            /* sexo normalizado */
            CASE
                WHEN UPPER(TRIM(IFNULL(Genero,''))) IN ('M','MASCULINO') THEN 'M'
                WHEN UPPER(TRIM(IFNULL(Genero,''))) IN ('F','FEMININO') THEN 'F'
                ELSE 'N'
            END AS sexo,

            /* pcd normalizado */
            CASE
                WHEN UPPER(TRIM(IFNULL(Deficiente,''))) IN ('SIM','S','1','TRUE','PCD','DEFICIENTE') THEN 1
                ELSE 0
            END AS pcd,

            IFNULL(tipoSenha,'') AS tipoSenha
        FROM tb_senhas
        WHERE data_refeicao = p_data
    ) X
    ON DUPLICATE KEY UPDATE
        Idade_3a17Masculino       = VALUES(Idade_3a17Masculino),
        Idade_3a17Masculino_PCD   = VALUES(Idade_3a17Masculino_PCD),
        Idade_3a17Feminino        = VALUES(Idade_3a17Feminino),
        Idade_3a17Feminino_PCD    = VALUES(Idade_3a17Feminino_PCD),

        Idade_18a59Masculino      = VALUES(Idade_18a59Masculino),
        Idade_18a59Masculino_PCD  = VALUES(Idade_18a59Masculino_PCD),
        Idade_17a59Feminino       = VALUES(Idade_17a59Feminino),
        Idade_17a59Feminino_PCD   = VALUES(Idade_17a59Feminino_PCD),

        Idade_60Masculino         = VALUES(Idade_60Masculino),
        Idade_60Masculino_PCD     = VALUES(Idade_60Masculino_PCD),
        Idade_60Feminino          = VALUES(Idade_60Feminino),
        Idade_60Feminino_PCD      = VALUES(Idade_60Feminino_PCD),

        Situacao_risco_masculino  = VALUES(Situacao_risco_masculino),
        Situacao_risco_Feminino   = VALUES(Situacao_risco_Feminino),

        Deficientes               = VALUES(Deficientes),
        senhas_genericas          = VALUES(senhas_genericas),
        Total_pessoas_atendidas   = VALUES(Total_pessoas_atendidas),
        fechado                   = 1;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `prato_cheio` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_tb_senhas_count_by_date` */;
ALTER DATABASE `prato_cheio` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_tb_senhas_count_by_date`(
    IN p_data_refeicao VARCHAR(10)
)
BEGIN
    SELECT COUNT(*) AS total
    FROM tb_senhas
    WHERE data_refeicao = p_data_refeicao;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `prato_cheio` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_tb_senhas_save` */;
ALTER DATABASE `prato_cheio` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_tb_senhas_save`(
    IN p_cliente              VARCHAR(100),
    IN p_cpf                  VARCHAR(45),
    IN p_idade                VARCHAR(20),
    IN p_genero               VARCHAR(45),
    IN p_deficiente           VARCHAR(45),
    IN p_tipoSenha            VARCHAR(10),
    IN p_status_cliente       VARCHAR(45),
    IN p_data_refeicao        VARCHAR(10)
)
BEGIN
    INSERT INTO tb_senhas
    (
        cliente,
        cpf,
        Idade,
        Genero,
        Deficiente,
        tipoSenha,
        status_cliente,
        data_refeicao,
        registration_date,
        registration_date_update
    )
    VALUES
    (
        p_cliente,
        p_cpf,
        p_idade,
        p_genero,
        p_deficiente,
        p_tipoSenha,
        p_status_cliente,
        p_data_refeicao,
        NOW(),
        NOW()
    );

    -- retorna o id inserido
    SELECT LAST_INSERT_ID() AS id;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `prato_cheio` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-14 16:11:03
