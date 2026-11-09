<?php
/**
 * importar_cest.php
 * Importa os códigos CEST e o vínculo CEST <-> NCM.
 *
 * Fonte oficial: Convênio ICMS 92/15 (e atualizações, ex. 142/18) - CONFAZ
 * https://www.confaz.fazenda.gov.br/legislacao/convenios/2015/CV092_15
 *
 * Uso:
 *   php importar_cest.php                          -> importa apenas o seed manual
 *   php importar_cest.php caminho/arquivo.csv      -> importa o CSV + seed manual
 *
 * Formato do CSV (separador ; , sem cabeçalho):
 *   CEST;Descricao;NCM
 *   Ex.: 0100100;Agua mineral;22011000
 *
 * OBS: o NCM referenciado precisa existir na tabela ProdutosNCM.
 */
require_once __DIR__ . '/../../configs/database.php';

// Seed manual: CESTs mais comuns de bebidas (exemplo). Adicione os seus aqui.
$seedManual = [
    ['0100100', 'Água mineral e gelo', '22011000'],
    ['0100100', 'Água mineral e gelo', '22019000'],
    ['0100200', 'Refrigerantes', '22021000'],
    ['0100300', 'Cervejas', '22030000'],
    ['0100400', 'Vinhos', '22042100'],
    ['0100500', 'Destilados', '22083010'],
];

$pdo = getDB();

// 1) Coleta os vínculos (seed + CSV, se informado)
$vinculos = $seedManual;

if (isset($argv[1]) && is_file($argv[1])) {
    $linhas = file($argv[1], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($linhas as $linha) {
        $partes = array_map('trim', explode(';', $linha));
        if (count($partes) < 3)
            continue;
        [$cest, $descricao, $ncm] = $partes;
        // Normaliza
        $cest = preg_replace('/\D/', '', $cest);
        $ncm = preg_replace('/\D/', '', $ncm);
        if (strlen($cest) === 7 && strlen($ncm) === 8) {
            $vinculos[] = [$cest, $descricao, $ncm];
        }
    }
    echo "CSV processado. Total de vínculos (seed + CSV): " . count($vinculos) . "\n";
} else {
    echo "Nenhum CSV informado. Importando apenas o seed manual (" . count($vinculos) . " vínculos).\n";
}

// 2) Insere CESTs (REPLACE atualiza descrições em reimportações)
$stmtCest = $pdo->prepare("REPLACE INTO ProdutosCEST (Codigo, Descricao) VALUES (?, ?)");

// 3) Insere vínculos (IGNORE evita duplicar pares já existentes)
$stmtVinculo = $pdo->prepare(
    "INSERT IGNORE INTO ProdutosCESTNCM (CEST, NCM) VALUES (?, ?)"
);

$pdo->beginTransaction();
$cestImportados = 0;
$vinculoImportados = 0;
$ncmInexistente = 0;

// Cache de NCMs existentes para validar o vínculo
$ncmExistentes = $pdo->query("SELECT Codigo FROM ProdutosNCM")->fetchAll(PDO::FETCH_COLUMN);
$ncmExistentes = array_flip($ncmExistentes);

foreach ($vinculos as $v) {
    [$cest, $descricao, $ncm] = $v;

    // Só cria o vínculo se o NCM existir na tabela de referência
    if (!isset($ncmExistentes[$ncm])) {
        $ncmInexistente++;
        continue;
    }

    $stmtCest->execute([$cest, $descricao]);
    $cestImportados++;

    $stmtVinculo->execute([$cest, $ncm]);
    $vinculoImportados++;
}
$pdo->commit();

echo "Concluído!\n";
echo "  CESTs inseridos/atualizados: $cestImportados\n";
echo "  Vínculos CEST-NCM criados:   $vinculoImportados\n";
if ($ncmInexistente > 0) {
    echo "  Vínculos ignorados (NCM não existe em ProdutosNCM): $ncmInexistente\n";
}