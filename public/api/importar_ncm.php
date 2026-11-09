<?php
/**
 * importar_ncm.php
 * Importa a tabela NCM oficial da Receita Federal (Siscomex) para a tabela `ncm`.
 *
 * Uso:
 *   php importar_ncm.php                          -> baixa o JSON oficial e importa
 *   php importar_ncm.php /caminho/do/arquivo.json -> importa de um arquivo local
 */
require_once __DIR__ . '/../../configs/database.php';

$urlOficial = 'https://portalunico.siscomex.gov.br/classif/api/publico/nomenclatura/download/json';

// 1) Obtém o JSON (arquivo local ou download oficial)
if (isset($argv[1]) && is_file($argv[1])) {
    $json = file_get_contents($argv[1]);
} else {
    echo "Baixando NCM oficial da Receita Federal...\n";
    $json = file_get_contents($urlOficial);
    if ($json === false) {
        fwrite(STDERR, "Falha ao baixar. Baixe manualmente na página da Receita e use: php importar_ncm.php caminho/arquivo.json\n");
        exit(1);
    }
}

$dados = json_decode($json, true);
if (!is_array($dados)) {
    fwrite(STDERR, "JSON inválido ou formato inesperado.\n");
    exit(1);
}

// 2) Achata a estrutura (o JSON pode ter itens aninhados)
$codigos = [];
function coletarNcm($itens, &$codigos)
{
    if (!is_array($itens))
        return;
    foreach ($itens as $item) {
        if (!is_array($item))
            continue;
        if (isset($item['Codigo'])) {
            $cod = preg_replace('/\D/', '', $item['Codigo']); // remove pontos/espaços
            $cod = str_pad($cod, 8, '0', STR_PAD_LEFT);
            if (strlen($cod) === 8) { // só códigos NCM completos
                $codigos[$cod] = $item['Descricao'] ?? '';
            }
        }
        foreach (['Nomenclaturas', 'Filhos', 'filhos', 'children'] as $chave) {
            if (isset($item[$chave]))
                coletarNcm($item[$chave], $codigos);
        }
    }
}
coletarNcm($dados['Nomenclaturas'] ?? $dados, $codigos);
echo "Total de NCMs encontrados: " . count($codigos) . "\n";

// 3) Insere em lote (REPLACE atualiza descrições em reimportações)
$pdo = getDB();
$stmt = $pdo->prepare("REPLACE INTO alesys.ProdutosNCM (Codigo, Descricao, DataImportacao) VALUES (?, ?, NOW())");
$pdo->beginTransaction();
$contador = 0;
foreach ($codigos as $cod => $desc) {
    $stmt->execute([$cod, $desc]);
    $contador++;
    if ($contador % 2000 === 0)
        echo "  ...$contador importados\n";
}
$pdo->commit();
echo "Concluído! $contador NCMs importados/atualizados.\n";