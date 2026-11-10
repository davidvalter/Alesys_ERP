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
