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
- **Banco de dados:** MySQL, acessado por PDO; o exemplo de configuração define também o uso de `utf8mb4` e consultas preparadas nativas.
- **Interface:** HTML, CSS e JavaScript.
- **Bibliotecas de interface versionadas no repositório:** Bootstrap, jQuery, Chart.js, DataTables, jsPDF e Exif.js.
- **Integrações auxiliares:** ALENFE para emissão/consulta de NFC-e; script Bash com rclone para sincronização de arquivos de notas de compras com OneDrive.

Não há manifesto Composer ou npm no repositório. As bibliotecas de interface mencionadas estão incluídas em `public/js/libs/`; a presença de arquivos de configuração de exemplo não informa, por si só, todas as versões mínimas do ambiente.

## Estrutura do projeto

```text
.
├── configs/                    # Exemplos e arquivos privados de configuração
│   ├── database-example.php    # Exemplo de conexão MySQL/PDO
│   ├── segredos-example.php    # Exemplo de chave secreta da aplicação
│   └── alenfe-example.php      # Exemplo de configuração da integração ALENFE
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

As instruções abaixo descrevem a configuração inicial sugerida pelos arquivos do repositório. O projeto não inclui schema SQL, migrações nem um roteiro completo de provisionamento do banco; portanto, é necessário obter ou preparar o banco compatível com o código antes de executar a aplicação.

1. **Obtenha o código:**

   ```bash
   git clone https://github.com/davidvalter/Alesys_ERP.git
   cd Alesys_ERP
   ```

2. **Configure o PHP e o servidor web.** Disponibilize um servidor com PHP e suporte a PDO para MySQL. Configure o document root do Apache, Nginx ou servidor equivalente para apontar a `public/`, para que `configs/` permaneça fora da raiz pública. A versão mínima do PHP e a configuração detalhada do servidor não estão especificadas no repositório.

3. **Prepare as configurações privadas** na raiz, copiando os exemplos e ajustando seus valores:

   ```text
   configs/database-example.php  -> configs/database.php
   configs/segredos-example.php  -> configs/segredos.php
   configs/alenfe-example.php    -> configs/alenfe.php
   ```

   Preencha os dados de acesso ao MySQL em `database.php`. Defina uma chave secreta própria e forte em `segredos.php`. Ajuste em `alenfe.php` a URL e as credenciais da integração de NFC-e conforme o ambiente utilizado. Não publique esses arquivos nem reutilize credenciais de exemplo em produção.

4. **Prepare e configure o banco de dados.** O exemplo de conexão espera MySQL e fornece a função `getDB()`. O repositório não contém scripts SQL ou migrações para criar as tabelas exigidas pela aplicação; obtenha o schema correspondente antes de iniciar.

5. **Inicie o servidor web** com `public/` como raiz do site e acesse a URL local ou do ambiente configurado. A página inicial redireciona para o login ou para o dashboard conforme a sessão.

A integração de sincronização em `extras/sync_nfcompras_onedrive.sh` é opcional e requer configuração própria do rclone e do destino OneDrive; ela não é necessária para iniciar as páginas da aplicação.

## Observações

- Os arquivos de configuração reais são excluídos do versionamento pelo `.gitignore`; crie-os localmente a partir dos exemplos.
- Mantenha `configs/` fora da raiz pública do servidor e restrinja o acesso aos arquivos de credenciais.
- O repositório não documenta a criação do schema, usuários iniciais nem requisitos de versão detalhados. A instalação completa depende dessas informações.
- Integrações fiscais e de armazenamento externo exigem serviços e credenciais próprios. O código disponível não substitui a configuração ou validação desses serviços.
