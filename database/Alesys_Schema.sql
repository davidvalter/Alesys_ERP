CREATE DATABASE  IF NOT EXISTS `alesys` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;
USE `alesys`;
-- MySQL dump 10.13  Distrib 8.0.42, for Win64 (x86_64)
--
-- Host: localhost    Database: alesys
-- ------------------------------------------------------
-- Server version	5.5.5-10.11.10-MariaDB-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='-03:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `BaixasProducao`
--

DROP TABLE IF EXISTS `BaixasProducao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `BaixasProducao` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `DataHora` datetime NOT NULL DEFAULT current_timestamp(),
  `ProdutoId` int(11) NOT NULL COMMENT 'Ingrediente consumido',
  `Quantidade` decimal(10,3) NOT NULL,
  `UnidadeMedida` varchar(10) NOT NULL,
  `Motivo` enum('preparo','ajuste','perda') NOT NULL DEFAULT 'preparo',
  `Observacoes` varchar(255) DEFAULT NULL,
  `ResponsavelId` int(11) NOT NULL COMMENT 'Cozinheiro que realizou',
  `LoteProducao` varchar(50) DEFAULT NULL COMMENT 'Identificação do lote',
  `ItemPedidoId` int(11) DEFAULT NULL COMMENT 'Vínculo com pedido (opcional)',
  PRIMARY KEY (`Id`),
  KEY `FK_Baixa_Produto` (`ProdutoId`),
  KEY `FK_Baixa_Usuario` (`ResponsavelId`),
  KEY `FK_Baixa_ItemPedido` (`ItemPedidoId`),
  CONSTRAINT `FK_Baixa_ItemPedido` FOREIGN KEY (`ItemPedidoId`) REFERENCES `ItensPedido` (`Id`) ON DELETE SET NULL,
  CONSTRAINT `FK_Baixa_Produto` FOREIGN KEY (`ProdutoId`) REFERENCES `Produtos` (`Codigo`),
  CONSTRAINT `FK_Baixa_Usuario` FOREIGN KEY (`ResponsavelId`) REFERENCES `Usuarios` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Cardapio`
--

DROP TABLE IF EXISTS `Cardapio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Cardapio` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Nome` varchar(150) NOT NULL,
  `Descricao` text DEFAULT NULL,
  `TipoItem` enum('produto','prato') NOT NULL,
  `ProdutoId` int(11) DEFAULT NULL,
  `PratoId` int(11) DEFAULT NULL,
  `PrecoVenda` decimal(10,2) NOT NULL,
  `Categoria` varchar(100) DEFAULT NULL,
  `Imagem` varchar(255) DEFAULT NULL,
  `Ativo` tinyint(1) DEFAULT 1,
  `CriadoEm` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Id`),
  CONSTRAINT `chk_item` CHECK (`TipoItem` = 'produto' and `ProdutoId` is not null and `PratoId` is null or `TipoItem` = 'prato' and `PratoId` is not null and `ProdutoId` is null)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Clientes`
--

DROP TABLE IF EXISTS `Clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Clientes` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Nome` varchar(255) DEFAULT NULL,
  `CPF` varchar(20) DEFAULT NULL,
  `Email` varchar(255) DEFAULT NULL,
  `Telefone` varchar(20) DEFAULT NULL,
  `DataCadastro` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Configuracoes`
--

DROP TABLE IF EXISTS `Configuracoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Configuracoes` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Chave` varchar(100) NOT NULL,
  `Valor` text DEFAULT NULL,
  `Descricao` varchar(255) DEFAULT NULL,
  `AtualizadoEm` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Chave` (`Chave`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DreCategoriaMap`
--

DROP TABLE IF EXISTS `DreCategoriaMap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `DreCategoriaMap` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `CategoriaId` int(11) NOT NULL,
  `DreLinhaId` int(11) NOT NULL,
  `Observacao` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `uk_categoria` (`CategoriaId`),
  KEY `idx_drelinha` (`DreLinhaId`),
  CONSTRAINT `fk_dcm_cat` FOREIGN KEY (`CategoriaId`) REFERENCES `FinanceiroCategorias` (`Id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dcm_linha` FOREIGN KEY (`DreLinhaId`) REFERENCES `DreLinhas` (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DreLinhas`
--

DROP TABLE IF EXISTS `DreLinhas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `DreLinhas` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Codigo` varchar(30) NOT NULL,
  `Nome` varchar(120) NOT NULL,
  `Bloco` enum('receita','deducao','custo','despesa','extra','provisao') NOT NULL,
  `Operacao` enum('linha','subtotal','total') NOT NULL DEFAULT 'linha',
  `Ordem` int(11) NOT NULL DEFAULT 0,
  `Ativo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `uk_codigo` (`Codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Empresa`
--

DROP TABLE IF EXISTS `Empresa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Empresa` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `RazaoSocial` varchar(200) DEFAULT NULL,
  `NomeFantasia` varchar(200) DEFAULT NULL,
  `Cnpj` varchar(14) DEFAULT NULL,
  `InscricaoEstadual` varchar(20) DEFAULT NULL,
  `Logradouro` varchar(200) DEFAULT NULL,
  `Numero` varchar(20) DEFAULT NULL,
  `Bairro` varchar(100) DEFAULT NULL,
  `Cidade` varchar(100) DEFAULT NULL,
  `Uf` char(2) DEFAULT NULL,
  `Cep` varchar(8) DEFAULT NULL,
  `Telefone` varchar(20) DEFAULT NULL,
  `Email` varchar(150) DEFAULT NULL,
  `Ativo` tinyint(1) NOT NULL DEFAULT 1,
  `CriadoEm` datetime NOT NULL DEFAULT current_timestamp(),
  `AtualizadoEm` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `FechamentosCaixa`
--

DROP TABLE IF EXISTS `FechamentosCaixa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `FechamentosCaixa` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Data` date NOT NULL,
  `ValorTotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `Observacoes` text DEFAULT NULL,
  `Status` enum('aberto','fechado') DEFAULT 'fechado',
  `ResponsavelId` int(11) NOT NULL,
  `CriadoEm` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `uniq_data` (`Data`)
) ENGINE=InnoDB AUTO_INCREMENT=264 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `FechamentosCaixaItens`
--

DROP TABLE IF EXISTS `FechamentosCaixaItens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `FechamentosCaixaItens` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `FechamentoId` int(11) NOT NULL,
  `Operadora` varchar(50) NOT NULL,
  `MeioPagamento` enum('debito','credito','voucher','pix','deliveryapp','dinheiro') NOT NULL,
  `Valor` decimal(12,2) NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `FechamentoId` (`FechamentoId`),
  CONSTRAINT `FechamentosCaixaItens_ibfk_1` FOREIGN KEY (`FechamentoId`) REFERENCES `FechamentosCaixa` (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=2120 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `FinanceiroCategorias`
--

DROP TABLE IF EXISTS `FinanceiroCategorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `FinanceiroCategorias` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Nome` varchar(100) NOT NULL,
  `Tipo` enum('entrada','saida','nenhum') NOT NULL,
  `Grupo` varchar(100) DEFAULT NULL,
  `Ativo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `FinanceiroConciliacaoItens`
--

DROP TABLE IF EXISTS `FinanceiroConciliacaoItens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `FinanceiroConciliacaoItens` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `ConciliacaoId` int(11) NOT NULL,
  `LancamentoFinanceiroId` int(11) NOT NULL,
  `NFeId` int(11) NOT NULL,
  `ValorConciliado` decimal(12,2) NOT NULL,
  `Observacoes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UQ_ConcItem_Par` (`ConciliacaoId`,`LancamentoFinanceiroId`,`NFeId`),
  KEY `IX_ConcItem_Lancamento` (`LancamentoFinanceiroId`),
  KEY `IX_ConcItem_NFe` (`NFeId`),
  CONSTRAINT `FK_ConcItem_Conciliacao` FOREIGN KEY (`ConciliacaoId`) REFERENCES `FinanceiroConciliacoes` (`Id`),
  CONSTRAINT `FK_ConcItem_Lancamento` FOREIGN KEY (`LancamentoFinanceiroId`) REFERENCES `FinanceiroLancamentos` (`Id`),
  CONSTRAINT `FK_ConcItem_NFe` FOREIGN KEY (`NFeId`) REFERENCES `NFe` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `FinanceiroConciliacoes`
--

DROP TABLE IF EXISTS `FinanceiroConciliacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `FinanceiroConciliacoes` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `DataConciliacao` datetime NOT NULL DEFAULT current_timestamp(),
  `Status` enum('confirmada','estornada') NOT NULL DEFAULT 'confirmada',
  `UsuarioId` int(11) DEFAULT NULL,
  `Observacoes` text DEFAULT NULL,
  `EstornadoEm` datetime DEFAULT NULL,
  `EstornadoPorId` int(11) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `IX_Conciliacoes_Status_Data` (`Status`,`DataConciliacao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `FinanceiroContasBancarias`
--

DROP TABLE IF EXISTS `FinanceiroContasBancarias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `FinanceiroContasBancarias` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Nome` varchar(100) NOT NULL,
  `Tipo` enum('banco','caixa','maquininha','outros') NOT NULL,
  `Banco` varchar(100) DEFAULT NULL,
  `Agencia` varchar(20) DEFAULT NULL,
  `Conta` varchar(20) DEFAULT NULL,
  `SaldoInicial` decimal(12,2) DEFAULT 0.00,
  `SaldoAtual` decimal(12,2) DEFAULT 1.00,
  `Ativo` tinyint(1) DEFAULT 1,
  `CriadoEm` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `FinanceiroFormasPagamento`
--

DROP TABLE IF EXISTS `FinanceiroFormasPagamento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `FinanceiroFormasPagamento` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Nome` varchar(50) NOT NULL,
  `Tipo` enum('entrada','saida') NOT NULL DEFAULT 'entrada',
  `Ativo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `FinanceiroImportacaoOfx`
--

DROP TABLE IF EXISTS `FinanceiroImportacaoOfx`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `FinanceiroImportacaoOfx` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `NomeArquivo` varchar(255) NOT NULL,
  `HashArquivo` char(40) NOT NULL,
  `Banco` varchar(50) DEFAULT NULL,
  `Conta` varchar(50) DEFAULT NULL,
  `DataImportacao` datetime NOT NULL DEFAULT current_timestamp(),
  `UsuarioId` int(11) DEFAULT NULL,
  `Status` enum('importado','processado','erro') NOT NULL DEFAULT 'importado',
  `Observacoes` text DEFAULT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UK_HashArquivo` (`HashArquivo`),
  KEY `IDX_UsuarioId` (`UsuarioId`)
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `FinanceiroImportacaoOfxLancamento`
--

DROP TABLE IF EXISTS `FinanceiroImportacaoOfxLancamento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `FinanceiroImportacaoOfxLancamento` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `ImportacaoOfxId` int(11) NOT NULL,
  `FitId` varchar(100) NOT NULL,
  `DataLancamento` date NOT NULL,
  `Tipo` enum('entrada','saida') NOT NULL,
  `Valor` decimal(12,2) NOT NULL,
  `ValorOriginal` decimal(12,2) NOT NULL,
  `Descricao` varchar(255) DEFAULT NULL,
  `HashLinha` char(40) NOT NULL,
  `Status` enum('pendente','processado','ignorado','erro') NOT NULL DEFAULT 'pendente',
  `FinanceiroLancamentoId` int(11) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UK_FitId_Importacao` (`ImportacaoOfxId`,`FitId`),
  UNIQUE KEY `UK_HashLinha` (`HashLinha`),
  KEY `IDX_ImportacaoOfxId` (`ImportacaoOfxId`),
  KEY `IDX_Status` (`Status`),
  KEY `IDX_FinanceiroLancamentoId` (`FinanceiroLancamentoId`),
  CONSTRAINT `FK_ImportacaoOfx` FOREIGN KEY (`ImportacaoOfxId`) REFERENCES `FinanceiroImportacaoOfx` (`Id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20622 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `FinanceiroLancamentoNFe`
--

DROP TABLE IF EXISTS `FinanceiroLancamentoNFe`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `FinanceiroLancamentoNFe` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `FinanceiroLancamentoId` int(11) NOT NULL,
  `NFeId` int(11) NOT NULL,
  `UsuarioId` int(11) DEFAULT NULL,
  `CriadoEm` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UQ_LancNFe_Par` (`FinanceiroLancamentoId`,`NFeId`),
  KEY `IX_LancNFe_NFeId` (`NFeId`),
  CONSTRAINT `FK_LancNFe_Lancamento` FOREIGN KEY (`FinanceiroLancamentoId`) REFERENCES `FinanceiroLancamentos` (`Id`),
  CONSTRAINT `FK_LancNFe_NFe` FOREIGN KEY (`NFeId`) REFERENCES `NFe` (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `FinanceiroLancamentos`
--

DROP TABLE IF EXISTS `FinanceiroLancamentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `FinanceiroLancamentos` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `DataLancamento` datetime NOT NULL DEFAULT current_timestamp(),
  `DataPrevista` date DEFAULT NULL,
  `DataBaixa` date DEFAULT NULL,
  `Tipo` enum('entrada','saida') NOT NULL,
  `CategoriaId` int(11) DEFAULT NULL,
  `ContaId` int(11) DEFAULT NULL,
  `FormaPagamentoId` int(11) DEFAULT NULL,
  `ValorBruto` decimal(12,2) NOT NULL,
  `Taxas` decimal(12,2) DEFAULT 0.00,
  `ValorLiquido` decimal(12,2) GENERATED ALWAYS AS (`ValorBruto` - `Taxas`) STORED,
  `Descricao` varchar(255) DEFAULT NULL,
  `Origem` enum('manual','venda','compra','arquivoofx','transferencia','ajuste') DEFAULT 'manual',
  `OrigemId` int(11) DEFAULT NULL,
  `Status` enum('pendente','pago','cancelado') DEFAULT 'pendente',
  `ResponsavelId` int(11) DEFAULT NULL,
  `Observacoes` text DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `CategoriaId` (`CategoriaId`),
  KEY `ContaId` (`ContaId`),
  KEY `FormaPagamentoId` (`FormaPagamentoId`),
  KEY `ResponsavelId` (`ResponsavelId`),
  CONSTRAINT `FinanceiroLancamentos_ibfk_1` FOREIGN KEY (`CategoriaId`) REFERENCES `FinanceiroCategorias` (`Id`),
  CONSTRAINT `FinanceiroLancamentos_ibfk_2` FOREIGN KEY (`ContaId`) REFERENCES `FinanceiroContasBancarias` (`Id`),
  CONSTRAINT `FinanceiroLancamentos_ibfk_3` FOREIGN KEY (`FormaPagamentoId`) REFERENCES `FinanceiroFormasPagamento` (`Id`),
  CONSTRAINT `FinanceiroLancamentos_ibfk_4` FOREIGN KEY (`ResponsavelId`) REFERENCES `Usuarios` (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=17512 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `FinanceiroLogMovimentacoes`
--

DROP TABLE IF EXISTS `FinanceiroLogMovimentacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `FinanceiroLogMovimentacoes` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `LancamentoId` int(11) NOT NULL,
  `ContaId` int(11) NOT NULL,
  `Valor` decimal(12,2) NOT NULL,
  `Acao` enum('insercao','ajuste','remocao') NOT NULL DEFAULT 'insercao',
  `Observacoes` text DEFAULT NULL,
  `DataRegistro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Id`),
  KEY `idx_log_lancamento` (`LancamentoId`),
  KEY `idx_log_conta` (`ContaId`)
) ENGINE=InnoDB AUTO_INCREMENT=152 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ItensPedido`
--

DROP TABLE IF EXISTS `ItensPedido`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ItensPedido` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `PedidoId` int(11) NOT NULL,
  `PratoId` int(11) DEFAULT NULL,
  `Quantidade` decimal(10,3) NOT NULL,
  `ValorUnitario` decimal(10,2) NOT NULL,
  `ValorTotal` decimal(10,2) GENERATED ALWAYS AS (`Quantidade` * `ValorUnitario`) STORED,
  `Status` enum('pendente','preparando','entregue','cancelado') DEFAULT 'pendente',
  `CardapioId` int(11) DEFAULT NULL,
  `TipoItem` enum('produto','prato') DEFAULT NULL,
  `Observacoes` text DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FK_ItemPedido_Pedido` (`PedidoId`),
  KEY `FK_ItemPedido_Produto` (`PratoId`),
  CONSTRAINT `FK_ItemPedido_Pedido` FOREIGN KEY (`PedidoId`) REFERENCES `Pedidos` (`Id`) ON DELETE CASCADE,
  CONSTRAINT `FK_ItemPedido_Produto` FOREIGN KEY (`PratoId`) REFERENCES `Produtos` (`Codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Mercados`
--

DROP TABLE IF EXISTS `Mercados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Mercados` (
  `Codigo` int(11) NOT NULL AUTO_INCREMENT,
  `Nome` varchar(255) NOT NULL,
  `RazaoSocial` varchar(255) NOT NULL,
  `CNPJ` varchar(18) NOT NULL,
  `InscricaoEstadual` varchar(18) DEFAULT NULL,
  `Endereco` text DEFAULT NULL,
  `CEP` char(9) DEFAULT NULL,
  `Municipio` varchar(255) DEFAULT NULL,
  `Estado` char(2) DEFAULT NULL,
  `Pais` varchar(100) DEFAULT 'Brasil',
  `Email` varchar(255) DEFAULT NULL,
  `MatrizFilial` tinyint(1) DEFAULT 1 COMMENT '1=Matriz, 0=Filial',
  `Telefone` varchar(15) DEFAULT NULL,
  `DataCadastro` datetime DEFAULT current_timestamp(),
  `Ativo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`Codigo`),
  UNIQUE KEY `CNPJ` (`CNPJ`),
  KEY `idx_mercado_nome` (`Nome`),
  KEY `idx_mercado_cnpj` (`CNPJ`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Modulos`
--

DROP TABLE IF EXISTS `Modulos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Modulos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `identificador` varchar(50) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `tipo` varchar(45) NOT NULL DEFAULT 'submodulo',
  `url` varchar(100) NOT NULL,
  `icone` varchar(50) DEFAULT NULL,
  `menu` tinyint(1) DEFAULT 1,
  `ordem` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `identificador` (`identificador`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `MovimentacoesEstoque`
--

DROP TABLE IF EXISTS `MovimentacoesEstoque`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MovimentacoesEstoque` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `ProdutoId` int(11) NOT NULL,
  `Quantidade` decimal(10,3) NOT NULL,
  `Tipo` enum('entrada','saida','ajuste') NOT NULL,
  `Origem` enum('nfe','producao','venda','ajuste') NOT NULL,
  `OrigemId` int(11) DEFAULT NULL COMMENT 'ID da tabela de origem (ex: NFe_Id, Producao_Id)',
  `DataMovimento` datetime NOT NULL DEFAULT current_timestamp(),
  `ResponsavelId` int(11) NOT NULL COMMENT 'Usuário responsável',
  `Observacoes` text DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FK_Movimentacao_Produto` (`ProdutoId`),
  KEY `idx_movimentacao_tipo_data` (`Tipo`,`DataMovimento`),
  KEY `FK_Movimentacao_Usuario` (`ResponsavelId`),
  KEY `idx_movimentacoes_produto` (`ProdutoId`,`DataMovimento`),
  CONSTRAINT `FK_Movimentacao_Produto` FOREIGN KEY (`ProdutoId`) REFERENCES `Produtos` (`Codigo`),
  CONSTRAINT `FK_Movimentacao_Usuario` FOREIGN KEY (`ResponsavelId`) REFERENCES `Usuarios` (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `NFe`
--

DROP TABLE IF EXISTS `NFe`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `NFe` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Numero` varchar(20) NOT NULL,
  `Serie` varchar(5) DEFAULT NULL,
  `Tipo` tinyint(1) NOT NULL COMMENT 'Valor original do campo tpNF do XML; não classifica compra versus venda no Alesys',
  `DataEmissao` datetime NOT NULL,
  `DataRecebimento` datetime DEFAULT NULL,
  `NaturezaOperacao` varchar(255) NOT NULL,
  `ChaveAcesso` varchar(44) DEFAULT NULL,
  `ValorTotal` decimal(10,2) NOT NULL,
  `Desconto` decimal(12,2) DEFAULT 0.00,
  `Status` tinyint(1) DEFAULT 1 COMMENT '1=Ativa, 0=Cancelada',
  `Observacoes` text DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `fk_Mercados_Codigo` int(11) NOT NULL,
  `DataCadastro` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `ChaveAcesso` (`ChaveAcesso`),
  KEY `FK_NFe_Mercado` (`fk_Mercados_Codigo`),
  KEY `idx_nfe_numero` (`Numero`),
  KEY `idx_nfe_chave` (`ChaveAcesso`),
  KEY `idx_nfe_data` (`DataEmissao`),
  CONSTRAINT `FK_NFe_Mercado` FOREIGN KEY (`fk_Mercados_Codigo`) REFERENCES `Mercados` (`Codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=188 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `NFeItens`
--

DROP TABLE IF EXISTS `NFeItens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `NFeItens` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `fk_NFe_Id` int(11) NOT NULL,
  `fk_Produtos_Codigo` int(11) NOT NULL,
  `Quantidade` decimal(10,3) NOT NULL,
  `ValorUnitario` decimal(10,2) NOT NULL,
  `UnidadeMedida` varchar(10) DEFAULT 'UN',
  `ValorTotal` decimal(10,2) GENERATED ALWAYS AS (`Quantidade` * `ValorUnitario`) STORED,
  `DescricaoComplementar` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FK_NFeItem_NFe` (`fk_NFe_Id`),
  KEY `idx_nfeitem_produto` (`fk_Produtos_Codigo`),
  CONSTRAINT `FK_NFeItem_NFe` FOREIGN KEY (`fk_NFe_Id`) REFERENCES `NFe` (`Id`) ON DELETE CASCADE,
  CONSTRAINT `FK_NFeItem_Produto` FOREIGN KEY (`fk_Produtos_Codigo`) REFERENCES `Produtos` (`Codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=1108 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Pagamentos`
--

DROP TABLE IF EXISTS `Pagamentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Pagamentos` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `PedidoId` int(11) NOT NULL,
  `FormaPagamento` enum('dinheiro','cartao_credito','cartao_debito','pix','vale','outros') DEFAULT NULL,
  `Valor` decimal(10,2) NOT NULL,
  `Troco` decimal(10,2) DEFAULT 0.00,
  `DataHora` datetime NOT NULL DEFAULT current_timestamp(),
  `Status` enum('pendente','confirmado','cancelado') NOT NULL DEFAULT 'pendente',
  `DadosAdicionais` text DEFAULT NULL COMMENT 'Ex: número da transação',
  PRIMARY KEY (`Id`),
  KEY `FK_Pagamento_Pedido` (`PedidoId`),
  CONSTRAINT `FK_Pagamento_Pedido` FOREIGN KEY (`PedidoId`) REFERENCES `Pedidos` (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Parametros`
--

DROP TABLE IF EXISTS `Parametros`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Parametros` (
  `Chave` varchar(100) NOT NULL,
  `Valor` enum('0','1') NOT NULL DEFAULT '0',
  `Descricao` varchar(255) DEFAULT NULL,
  `Grupo` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`Chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Pedidos`
--

DROP TABLE IF EXISTS `Pedidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Pedidos` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `DataAbertura` datetime NOT NULL DEFAULT current_timestamp(),
  `DataFechamento` datetime DEFAULT NULL,
  `Status` enum('aberto','fechado','cancelado') NOT NULL DEFAULT 'aberto',
  `Tipo` enum('mesa','delivery','balcao','manual') NOT NULL DEFAULT 'mesa',
  `FormaPagamentoId` int(11) DEFAULT NULL,
  `Mesa` varchar(20) DEFAULT NULL,
  `Cliente` varchar(255) DEFAULT NULL,
  `ValorTotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Observacoes` text DEFAULT NULL,
  `ResponsavelId` int(11) NOT NULL COMMENT 'Garçom/Atendente',
  `StatusNFe` enum('pendente','emitindo','emitida','erro') DEFAULT 'pendente',
  `ChaveNFe` varchar(44) DEFAULT NULL,
  `ProtocoloNFe` varchar(50) DEFAULT NULL,
  `XMLNFe` longtext DEFAULT NULL,
  `DataEmissaoNFe` datetime DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FK_Pedido_Usuario` (`ResponsavelId`),
  KEY `idx_pedido_status_data` (`Status`,`DataAbertura`),
  KEY `idx_pedidos_data` (`DataAbertura`,`Status`),
  KEY `FK_Pedido_FormaPagamento` (`FormaPagamentoId`),
  CONSTRAINT `FK_Pedido_FormaPagamento` FOREIGN KEY (`FormaPagamentoId`) REFERENCES `FinanceiroFormasPagamento` (`Id`),
  CONSTRAINT `FK_Pedido_Usuario` FOREIGN KEY (`ResponsavelId`) REFERENCES `Usuarios` (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=111 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Perfis`
--

DROP TABLE IF EXISTS `Perfis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Perfis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  `descricao` text DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Permissoes`
--

DROP TABLE IF EXISTS `Permissoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Permissoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `perfil_id` int(11) NOT NULL,
  `modulo_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `perfil_id` (`perfil_id`,`modulo_id`),
  KEY `modulo_id` (`modulo_id`),
  CONSTRAINT `Permissoes_ibfk_1` FOREIGN KEY (`perfil_id`) REFERENCES `Perfis` (`id`) ON DELETE CASCADE,
  CONSTRAINT `Permissoes_ibfk_2` FOREIGN KEY (`modulo_id`) REFERENCES `Modulos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Pratos`
--

DROP TABLE IF EXISTS `Pratos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Pratos` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Nome` varchar(100) NOT NULL,
  `NCM` varchar(10) DEFAULT NULL,
  `CFOP` varchar(10) DEFAULT NULL,
  `CSOSN` varchar(5) DEFAULT NULL,
  `CEST` char(7) DEFAULT NULL,
  `UnidadeMedida` varchar(10) DEFAULT 'UN',
  `Categoria` enum('prato_principal','entrada','sobremesa','bebida') NOT NULL,
  `Preco` decimal(10,2) NOT NULL,
  `Foto` varchar(255) DEFAULT NULL,
  `Status` tinyint(1) DEFAULT 1,
  `ResponsavelId` int(11) NOT NULL,
  `CriadoEm` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Id`),
  KEY `ResponsavelId` (`ResponsavelId`),
  CONSTRAINT `Pratos_ibfk_1` FOREIGN KEY (`ResponsavelId`) REFERENCES `Usuarios` (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Producao`
--

DROP TABLE IF EXISTS `Producao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Producao` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `ItemPedidoId` int(11) NOT NULL,
  `Status` enum('pendente','em_producao','finalizado') NOT NULL DEFAULT 'pendente',
  `DataInicio` datetime DEFAULT NULL,
  `DataFim` datetime DEFAULT NULL,
  `ResponsavelId` int(11) DEFAULT NULL COMMENT 'Cozinheiro',
  PRIMARY KEY (`Id`),
  KEY `FK_Producao_ItemPedido` (`ItemPedidoId`),
  KEY `FK_Producao_Usuario` (`ResponsavelId`),
  CONSTRAINT `FK_Producao_ItemPedido` FOREIGN KEY (`ItemPedidoId`) REFERENCES `ItensPedido` (`Id`),
  CONSTRAINT `FK_Producao_Usuario` FOREIGN KEY (`ResponsavelId`) REFERENCES `Usuarios` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ProdutoPrecos`
--

DROP TABLE IF EXISTS `ProdutoPrecos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ProdutoPrecos` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `fk_Produtos_Codigo` int(11) NOT NULL,
  `fk_Mercados_Codigo` int(11) NOT NULL,
  `fk_NFe_Id` int(11) DEFAULT NULL,
  `DataReferencia` date NOT NULL,
  `ValorUnitario` decimal(10,2) NOT NULL,
  `UnidadeMedida` varchar(10) DEFAULT 'UN',
  `TipoPreco` tinyint(1) DEFAULT 1 COMMENT '1=Compra, 2=Venda',
  `Observacoes` text DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FK_Preco_NFe` (`fk_NFe_Id`),
  KEY `idx_preco_produto_data` (`fk_Produtos_Codigo`,`DataReferencia` DESC),
  KEY `idx_preco_mercado` (`fk_Mercados_Codigo`),
  CONSTRAINT `FK_Preco_Mercado` FOREIGN KEY (`fk_Mercados_Codigo`) REFERENCES `Mercados` (`Codigo`) ON DELETE CASCADE,
  CONSTRAINT `FK_Preco_NFe` FOREIGN KEY (`fk_NFe_Id`) REFERENCES `NFe` (`Id`) ON DELETE SET NULL,
  CONSTRAINT `FK_Preco_Produto` FOREIGN KEY (`fk_Produtos_Codigo`) REFERENCES `Produtos` (`Codigo`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Produtos`
--

DROP TABLE IF EXISTS `Produtos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Produtos` (
  `Codigo` int(11) NOT NULL AUTO_INCREMENT,
  `Nome` varchar(255) NOT NULL,
  `Descricao` text DEFAULT NULL,
  `CodigoBarras` varchar(50) DEFAULT NULL,
  `UnidadeMedida` varchar(10) DEFAULT 'UN',
  `Categoria` varchar(100) DEFAULT NULL,
  `DataCadastro` datetime DEFAULT current_timestamp(),
  `Ativo` tinyint(1) DEFAULT 1,
  `TipoProduto` enum('insumo','vendavel') NOT NULL DEFAULT 'insumo',
  `CategoriaVenda` enum('prato','bebida','sobremesa','outros') DEFAULT NULL,
  `NCM` varchar(10) DEFAULT NULL,
  `CFOP` varchar(10) DEFAULT NULL,
  `CSOSN` varchar(5) DEFAULT NULL,
  `CEST` char(7) DEFAULT NULL COMMENT 'Código Especificador da Substituição Tributária',
  PRIMARY KEY (`Codigo`),
  UNIQUE KEY `uk_produto_nome` (`Nome`),
  UNIQUE KEY `CodigoBarras` (`CodigoBarras`),
  KEY `idx_produto_nome` (`Nome`),
  KEY `idx_produto_categoria` (`Categoria`)
) ENGINE=InnoDB AUTO_INCREMENT=306 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ProdutosCEST`
--

DROP TABLE IF EXISTS `ProdutosCEST`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ProdutosCEST` (
  `Codigo` char(7) NOT NULL COMMENT 'Código CEST com 7 dígitos',
  `Descricao` text DEFAULT NULL,
  PRIMARY KEY (`Codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabela de referência CEST';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ProdutosCESTNCM`
--

DROP TABLE IF EXISTS `ProdutosCESTNCM`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ProdutosCESTNCM` (
  `CEST` char(7) NOT NULL,
  `NCM` char(8) NOT NULL,
  PRIMARY KEY (`CEST`,`NCM`),
  KEY `fk_cestncm_ncm` (`NCM`),
  CONSTRAINT `fk_cestncm_cest` FOREIGN KEY (`CEST`) REFERENCES `ProdutosCEST` (`Codigo`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cestncm_ncm` FOREIGN KEY (`NCM`) REFERENCES `ProdutosNCM` (`Codigo`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Vínculo entre CEST e NCM';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ProdutosNCM`
--

DROP TABLE IF EXISTS `ProdutosNCM`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ProdutosNCM` (
  `Codigo` char(8) NOT NULL COMMENT 'Código NCM com 8 dígitos, sem máscara',
  `Descricao` text DEFAULT NULL,
  `DataImportacao` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabela de referência NCM (fonte: Receita Federal / Siscomex)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Receitas`
--

DROP TABLE IF EXISTS `Receitas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Receitas` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `PratoId` int(11) NOT NULL COMMENT 'Produto final (tipo=prato)',
  `IngredienteId` int(11) NOT NULL COMMENT 'Produto usado (tipo=ingrediente)',
  `Quantidade` decimal(10,3) NOT NULL COMMENT 'Qtd necessária por unidade do prato',
  `UnidadeMedida` varchar(10) NOT NULL,
  `Ativo` tinyint(1) NOT NULL DEFAULT 1,
  `MetodoBaixa` enum('auto','manual') NOT NULL DEFAULT 'manual' COMMENT 'auto=Baixa automática pela receita, manual=Baixa manual pelo cozinheiro',
  PRIMARY KEY (`Id`),
  UNIQUE KEY `uk_receita_prato_ingrediente` (`PratoId`,`IngredienteId`),
  KEY `FK_Receita_Prato` (`PratoId`),
  KEY `FK_Receita_Ingrediente` (`IngredienteId`),
  CONSTRAINT `FK_Receita_Ingrediente` FOREIGN KEY (`IngredienteId`) REFERENCES `Produtos` (`Codigo`),
  CONSTRAINT `FK_Receita_Prato` FOREIGN KEY (`PratoId`) REFERENCES `Produtos` (`Codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Usuarios`
--

DROP TABLE IF EXISTS `Usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Usuarios` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Username` varchar(50) NOT NULL,
  `NomeCompleto` varchar(100) NOT NULL,
  `Senha` varchar(255) NOT NULL,
  `Salt` char(64) NOT NULL,
  `Ativo` tinyint(1) NOT NULL DEFAULT 1,
  `UltimoLogin` datetime DEFAULT NULL,
  `CriadoEm` timestamp NOT NULL DEFAULT current_timestamp(),
  `perfil_id` int(11) NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `username` (`Username`),
  KEY `perfil_id` (`perfil_id`),
  CONSTRAINT `Usuarios_ibfk_1` FOREIGN KEY (`perfil_id`) REFERENCES `Perfis` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `VendasNFCE`
--

DROP TABLE IF EXISTS `VendasNFCE`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `VendasNFCE` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `PedidoId` int(11) NOT NULL,
  `NfceId` int(11) DEFAULT NULL,
  `ChaveAcesso` varchar(44) DEFAULT NULL,
  `Protocolo` varchar(20) DEFAULT NULL,
  `Status` enum('PENDENTE','ENVIADA','AUTORIZADA','CONTINGENCIA','REJEITADA','CANCELADA','ERRO') NOT NULL DEFAULT 'PENDENTE',
  `MotivoRejeicao` text DEFAULT NULL,
  `XmlProc` mediumtext DEFAULT NULL,
  `XmlContingencia` mediumtext DEFAULT NULL,
  `DadosImpressao` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`DadosImpressao`)),
  `PayloadEnvio` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`PayloadEnvio`)),
  `Tentativas` int(11) NOT NULL DEFAULT 0,
  `CriadoEm` datetime NOT NULL DEFAULT current_timestamp(),
  `AtualizadoEm` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `uq_pedido` (`PedidoId`),
  KEY `idx_status` (`Status`),
  KEY `idx_chave` (`ChaveAcesso`),
  CONSTRAINT `fk_vendasnfce_pedido` FOREIGN KEY (`PedidoId`) REFERENCES `Pedidos` (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
-- Alesys + AleNFE: caixa de entrada de notas de compra e conciliação manual.
-- Executar uma única vez no banco alesys antes de habilitar a tela.
-- Esta migração não grava movimentações de estoque.

CREATE TABLE IF NOT EXISTS `AleNFEComprasImportacao` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `AleNFEId` varchar(100) NOT NULL,
  `ChaveAcesso` varchar(44) NOT NULL,
  `Numero` varchar(20) NOT NULL,
  `Serie` varchar(5) DEFAULT NULL,
  `Modelo` varchar(5) DEFAULT NULL,
  `FornecedorCNPJ` varchar(18) DEFAULT NULL,
  `FornecedorNome` varchar(255) DEFAULT NULL,
  `DataEmissao` datetime DEFAULT NULL,
  `ValorTotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `SituacaoAleNFE` varchar(40) DEFAULT NULL,
  `XMLCompleto` longtext DEFAULT NULL,
  `TipoXML` tinyint(1) DEFAULT NULL,
  `NaturezaOperacaoXML` varchar(255) DEFAULT NULL,
  `MercadoCodigo` int(11) DEFAULT NULL,
  `NFeId` int(11) DEFAULT NULL,
  `StatusImportacao` enum('pendente','importada','ignorada') NOT NULL DEFAULT 'pendente',
  `UltimaConsultaEm` datetime NOT NULL DEFAULT current_timestamp(),
  `CriadoEm` datetime NOT NULL DEFAULT current_timestamp(),
  `AtualizadoEm` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UQ_AleNFECompras_Chave` (`ChaveAcesso`),
  KEY `IX_AleNFECompras_Status_Data` (`StatusImportacao`,`DataEmissao`),
  KEY `IX_AleNFECompras_CNPJ` (`FornecedorCNPJ`),
  KEY `IX_AleNFECompras_AleNFEId` (`AleNFEId`),
  CONSTRAINT `FK_AleNFECompras_Mercado` FOREIGN KEY (`MercadoCodigo`) REFERENCES `Mercados` (`Codigo`) ON DELETE SET NULL,
  CONSTRAINT `FK_AleNFECompras_NFe` FOREIGN KEY (`NFeId`) REFERENCES `NFe` (`Id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `AleNFEComprasImportacaoItens` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `ImportacaoId` int(11) NOT NULL,
  `NumeroItem` int(11) NOT NULL,
  `CodigoFornecedor` varchar(100) DEFAULT NULL,
  `Descricao` varchar(255) NOT NULL,
  `NCM` varchar(10) DEFAULT NULL,
  `CFOP` varchar(10) DEFAULT NULL,
  `UnidadeMedida` varchar(10) DEFAULT NULL,
  `Quantidade` decimal(12,3) NOT NULL DEFAULT 0.000,
  `ValorUnitario` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `ValorTotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `ProdutoCodigo` int(11) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UQ_AleNFEComprasItem_Numero` (`ImportacaoId`,`NumeroItem`),
  KEY `IX_AleNFEComprasItem_Produto` (`ProdutoCodigo`),
  CONSTRAINT `FK_AleNFEComprasItem_Importacao` FOREIGN KEY (`ImportacaoId`) REFERENCES `AleNFEComprasImportacao` (`Id`) ON DELETE CASCADE,
  CONSTRAINT `FK_AleNFEComprasItem_Produto` FOREIGN KEY (`ProdutoCodigo`) REFERENCES `Produtos` (`Codigo`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `AleNFEComprasProdutoMapeamento` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `FornecedorCNPJ` varchar(18) NOT NULL,
  `CodigoFornecedor` varchar(100) NOT NULL,
  `NCM` varchar(10) NOT NULL DEFAULT '',
  `ProdutoCodigo` int(11) NOT NULL,
  `CriadoEm` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UQ_AleNFEProdutoMapeamento` (`FornecedorCNPJ`,`CodigoFornecedor`,`NCM`),
  KEY `IX_AleNFEProdutoMapeamento_Produto` (`ProdutoCodigo`),
  CONSTRAINT `FK_AleNFEProdutoMapeamento_Produto` FOREIGN KEY (`ProdutoCodigo`) REFERENCES `Produtos` (`Codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `Parametros` (`Chave`,`Valor`,`Descricao`,`Grupo`)
SELECT 'ALENFE_COMPRA_ATUALIZAR_ESTOQUE_AUTOMATICAMENTE','0',
       'Permite futuramente atualizar o estoque automaticamente ao importar uma nota de compra AleNFE. Desativado por padrão.',
       'Integrações'
WHERE NOT EXISTS (
  SELECT 1 FROM `Parametros` WHERE `Chave`='ALENFE_COMPRA_ATUALIZAR_ESTOQUE_AUTOMATICAMENTE'
);

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


