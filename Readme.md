# Alesys ERP

Sistema web de gestão empresarial desenvolvido principalmente em PHP, com foco em apoiar rotinas administrativas e operacionais de um restaurante. A aplicação concentra o código acessível pelo navegador em `public/`; configurações sensíveis, como conexão ao banco e segredos, ficam na raiz do projeto, fora do diretório público.

## Visão geral e propósito

O Alesys ERP reúne em uma aplicação os fluxos de compras, estoque, vendas e financeiro. Pelos módulos presentes no código, o sistema busca centralizar cadastros e movimentações, acompanhar resultados e reduzir controles dispersos entre ferramentas e planilhas.

A interface inclui páginas PHP renderizadas no servidor e funcionalidades de navegador implementadas em JavaScript. A pasta `public/api/` contém endpoints PHP usados pelas telas para consultar e atualizar dados.

## Principais funcionalidades

- **Vendas e PDV:** ponto de venda, registro de vendas, fechamento de caixa, cadastro de pratos e receitas, cardápio e emissão de NFC-e.
- **Compras:** cadastro de produtos e fornecedores/mercados, consulta de notas fiscais, importação de documentos e comparação de preços.
- **Estoque:** consulta de produtos e quantidades, movimentações e baixa manual.
- **Financeiro:** lançamentos, contas bancárias, categorias e formas de pagamento, importação e conferência de extratos OFX e conciliação.
- **Relatórios e indicadores:** dashboard, balanço, DRE, análises de compras e relatórios financeiros.
- **Administração e acesso:** usuários, perfis, permissões, empresas e parâmetros do sistema.

A disponibilidade de alguns fluxos depende das configurações do banco de dados e de serviços externos. A emissão de NFC-e, por exemplo, integra-se ao serviço ALENFE configurado fora de `public/`.

## Tecnologias

- **Servidor:** PHP.
- **Banco de dados:** MySQL/MariaDB, acessado por PDO; o exemplo de configuração define `utf8mb4` e consultas preparadas nativas. O dump incluído registra um servidor MariaDB 10.11.10.
- **Interface:** HTML, CSS e JavaScript.
- **Bibliotecas de interface versionadas no repositório:** Bootstrap, jQuery, Chart.js, DataTables, jsPDF e Exif.js.
- **Integrações auxiliares:** ALENFE para emissão/consulta de NFC-e; script Bash com rclone para sincronização de arquivos de notas de compras com OneDrive.

Não há manifesto Composer ou npm no repositório. As bibliotecas de interface mencionadas estão incluídas em `public/js/libs/`; a presença de arquivos de configuração de exemplo não informa, por si só, todas as versões mínimas do ambiente.

## Banco de dados

O arquivo [`database/Alesys_Schema.sql`](database/Alesys_Schema.sql) contém o dump do schema do Alesys, com 43 tabelas e relacionamentos por chaves estrangeiras. Ele descreve estruturas para:

- usuários, perfis, módulos, permissões, empresas e configurações;
- produtos, mercados/fornecedores, notas fiscais e classificações NCM/CEST;
- pratos, receitas, produção, cardápio, pedidos, pagamentos, vendas NFC-e e estoque;
- lançamentos financeiros, contas bancárias, importações OFX, conciliações, categorias e linhas da DRE.

O arquivo contém estrutura, sem comandos `INSERT` ou dados de negócio. Ele começa com `CREATE DATABASE IF NOT EXISTS alesys` e `USE alesys`; em seguida, executa `DROP TABLE IF EXISTS` e recria as tabelas.

> **Atenção:** importar esse arquivo sobre um banco `alesys` já existente apaga e recria as tabelas nele descritas, causando perda dos dados armazenados nessas tabelas. Use-o para criar um banco vazio ou faça e confira um backup antes de qualquer importação. O dump não cria usuários de aplicação nem carrega dados iniciais.

Para importar o schema em um ambiente local com cliente MySQL/MariaDB instalado:

```bash
mysql -u SEU_USUARIO -p < database/Alesys_Schema.sql
```

O usuário do banco precisa ter permissão para criar o banco `alesys` e suas tabelas. Depois, configure `configs/database.php` com as credenciais e o nome do banco usados pelo ambiente.

### Processo para futuras alterações de banco

Quando uma mudança no sistema exigir alteração do banco:

1. Atualize `database/Alesys_Schema.sql` para refletir a estrutura completa resultante.
2. Inclua o SQL de atualização incremental em `database/migrations/`, com nome ordenável, por exemplo `YYYYMMDD_descricao.sql`. Esse script deve orientar a atualização de uma instalação existente sem restaurar o dump completo.
3. Documente em texto claro no README o objetivo da alteração, a ordem em que aplicar o script e eventuais passos manuais ou impactos. Não dependa da importação do schema completo para atualizar uma instalação que já contém dados.

As atualizações do banco são manuais; o Codex não tem acesso direto ao banco de dados do ambiente.

### Importação manual de notas de compra do AleNFE

A tela `public/compras/importar_alenfe.php` consulta as notas do AleNFE e as coloca em uma caixa de entrada para revisão. A pessoa usuária confirma manualmente o fornecedor de `Mercados` e associa cada item a um produto de `Produtos`. O fornecedor é sugerido pelo CNPJ; os produtos podem ser sugeridos por mapeamentos já confirmados, código do item do fornecedor, NCM e descrição.

A importação exige o XML completo da nota. Os campos `Tipo` (valor original de `ide/tpNF`) e `NaturezaOperacao` (valor de `ide/natOp`) são lidos do XML; não são presumidos. No Alesys, as tabelas `NFe` e `NFeItens` representam notas de compra. `Tipo` é informação fiscal da nota do fornecedor e não deve ser usado para classificar uma nota como compra ou venda.

A importação grava a nota e seus itens em uma transação, impede duplicidade pela chave de acesso e memoriza os vínculos de produtos por CNPJ do fornecedor, código do item e NCM. **O fluxo atual não movimenta o estoque.** O parâmetro `ALENFE_COMPRA_ATUALIZAR_ESTOQUE_AUTOMATICAMENTE` é criado com valor `0` e aparece em Administração → Parâmetros → Estoque. Por enquanto, o controle é preparatório: mesmo que seja ativado, não altera o estoque até que essa lógica seja implementada.

Para aplicar a estrutura a uma instalação que já existe:

1. Faça backup do banco.
2. Execute uma vez `database/migrations/20261110_alenfe_importacao_compras.sql` no banco `alesys`. Não é necessário importar o dump completo.
3. Configure a integração ALENFE já usada pelo Alesys em `Configuracoes` (`ALENFE_URL` e `ALENFE_API_KEY`). A empresa é identificada pela API Key, portanto não é necessário enviar `empresaId`. Se o formato de consulta do ambiente for diferente, configure `ALENFE_COMPRAS_LIST_PATH` com o caminho completo relativo, incluindo os parâmetros.
4. O Alesys consulta `GET /api/integracoes/alesys/notas-compras?page=1&limit=100` e obtém XML em `GET /api/integracoes/alesys/notas-compras/{id}/xml`. As duas rotas usam o header `x-api-key`; a chave é enviada apenas pelo servidor Alesys e não fica exposta no navegador.

Depois da migração, acesse **Compras → Importar notas do AleNFE**. Notas sem XML completo ficam sem confirmação de importação até que o XML esteja disponível. A primeira consulta busca até 100 notas por página conforme a rota configurada.

## Estrutura do projeto

```text
.
├── configs/                    # Exemplos e arquivos privados de configuração
│   ├── database-example.php    # Exemplo de conexão MySQL/PDO
│   ├── segredos-example.php    # Exemplo de chave secreta da aplicação
│   └── alenfe-example.php      # Exemplo de configuração da integração ALENFE
├── database/
│   └── Alesys_Schema.sql       # Dump do schema do banco (estrutura, sem dados)
├── extras/                     # Scripts auxiliares de operação
├── public/                     # Diretório a ser publicado pelo servidor web
│   ├── admin/                  # Usuários, perfis, permissões e parâmetros
│   ├── api/                    # Endpoints PHP consumidos pelas telas
│   ├── components/             # Componentes compartilhados e indicadores
│   ├── compras/                # Compras, produtos, fornecedores e notas fiscais
│   ├── conf/                   # Autenticação, login e logging da aplicação web
│   ├── estoque/                # Consulta e controle de estoque
│   ├── financeiro/             # Lançamentos, contas, OFX e conciliação
│   ├── js/                     # JavaScript da aplicação e bibliotecas locais
│   ├── relatorios/             # Relatórios financeiros e operacionais
│   ├── repositories/           # Acesso a dados por repositórios PHP
│   ├── services/               # Serviços PHP da aplicação
│   ├── styles/                 # Folhas de estilo
│   ├── templates/              # Estruturas compartilhadas das páginas
│   ├── utils/                  # Utilitários, incluindo parser OFX
│   ├── vendas/                 # PDV, cardápio, pratos e fechamento de caixa
│   └── widgets/                # Blocos do dashboard
└── Readme.md
```

Os arquivos efetivamente usados para conexão e segredos são `configs/database.php`, `configs/segredos.php` e `configs/alenfe.php`. Eles são ignorados pelo Git; o repositório fornece versões de exemplo com o sufixo `-example.php`.

## Instalação e execução

1. **Obtenha o código:**

   ```bash
   git clone https://github.com/davidvalter/Alesys_ERP.git
   cd Alesys_ERP
   ```

2. **Configure o PHP e o servidor web.** Disponibilize um servidor com PHP e suporte a PDO para MySQL/MariaDB. Configure o document root do Apache, Nginx ou servidor equivalente para apontar para `public/`, mantendo `configs/` e `database/` fora da raiz pública. A versão mínima do PHP e a configuração detalhada do servidor não estão especificadas no repositório.

3. **Crie o banco de dados** importando `database/Alesys_Schema.sql`, conforme as instruções e o alerta da seção [Banco de dados](#banco-de-dados). Não reimporte o dump sobre um banco com dados que devam ser preservados.

4. **Prepare as configurações privadas** na raiz, copiando os exemplos e ajustando seus valores:

   ```text
   configs/database-example.php  -> configs/database.php
   configs/segredos-example.php  -> configs/segredos.php
   configs/alenfe-example.php    -> configs/alenfe.php
   ```

   Preencha os dados de acesso ao MySQL em `database.php`. Defina uma chave secreta própria e forte em `segredos.php`. Ajuste em `alenfe.php` a URL e as credenciais da integração de NFC-e conforme o ambiente utilizado. Não publique esses arquivos nem reutilize credenciais de exemplo em produção.

5. **Inicie o servidor web** com `public/` como raiz do site e acesse a URL local ou do ambiente configurado. A página inicial redireciona para o login ou para o dashboard conforme a sessão.

A integração de sincronização em `extras/sync_nfcompras_onedrive.sh` é opcional e requer configuração própria do rclone e do destino OneDrive; ela não é necessária para iniciar as páginas da aplicação.

## Observações

- Os arquivos de configuração reais são excluídos do versionamento pelo `.gitignore`; crie-os localmente a partir dos exemplos.
- Mantenha `configs/` e `database/` fora da raiz pública do servidor e restrinja o acesso aos arquivos de credenciais.
- O dump representa a estrutura do banco, mas não inclui dados iniciais, credenciais ou usuários de aplicação.
- Integrações fiscais e de armazenamento externo exigem serviços e credenciais próprios. O código disponível não substitui a configuração ou validação desses serviços.
