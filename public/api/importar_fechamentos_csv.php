<?php

/* SCRIPT PARA IMPORTACAO MANUAL DE VENDAS DO CSV PARA O SISTEMA. USAR COM CUIDADO */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../configs/database.php';

$arquivo = __DIR__ . '/../uploads/Faturamento_2026.csv';

$pdo = getDB();

if (!file_exists($arquivo)) {
    die("Arquivo CSV não encontrado.");
}

$handle = fopen($arquivo, "r");

if (!$handle) {
    die("Erro ao abrir o CSV.");
}

// Lê cabeçalhos
$linhaOperadoras = fgetcsv($handle, 0, ";");
$linhaMeios = fgetcsv($handle, 0, ";");

if (!$linhaOperadoras || !$linhaMeios) {
    die("Cabeçalho inválido.");
}

// Remove primeira coluna (Data)
array_shift($linhaOperadoras);
array_shift($linhaMeios);

$pdo->beginTransaction();

try {

    while (($linha = fgetcsv($handle, 0, ";")) !== false) {

        if (empty($linha[0]))
            continue;

        $dataStr = trim($linha[0]);

        $data = DateTime::createFromFormat("d/m/Y", $dataStr);

        if (!$data) {
            throw new Exception("Formato de data inválido: $dataStr");
        }

        $dataSql = $data->format("Y-m-d");

        // Insere fechamento principal
        $stmt = $pdo->prepare("
            INSERT INTO FechamentosCaixa 
            (Data, ValorTotal, Status, ResponsavelId) 
            VALUES (?, 0, 'fechado', 1)
        ");
        $stmt->execute([$dataSql]);

        $fechamentoId = $pdo->lastInsertId();

        $totalDia = 0;

        for ($i = 1; $i < count($linha); $i++) {

            if (!isset($linha[$i]) || $linha[$i] === '')
                continue;

            $valor = str_replace(",", ".", $linha[$i]);
            $valor = floatval($valor);

            if ($valor <= 0)
                continue;

            $operadora = trim($linhaOperadoras[$i - 1]);
            $meio = strtolower(trim($linhaMeios[$i - 1]));

            // Normalização dos meios
            $mapa = [
                'debito' => 'debito',
                'credito' => 'credito',
                'voucher' => 'voucher',
                'pix' => 'pix',
                'dinheiro' => 'dinheiro',
                'ifood' => 'deliveryapp',
                'delivery' => 'deliveryapp'
            ];

            if (!isset($mapa[$meio]))
                continue;

            $meioBanco = $mapa[$meio];

            $stmtItem = $pdo->prepare("
                INSERT INTO FechamentosCaixaItens 
                (FechamentoId, Operadora, MeioPagamento, Valor)
                VALUES (?, ?, ?, ?)
            ");

            $stmtItem->execute([
                $fechamentoId,
                $operadora,
                $meioBanco,
                $valor
            ]);

            $totalDia += $valor;
        }

        // Atualiza total do fechamento
        $stmtUpd = $pdo->prepare("
            UPDATE FechamentosCaixa 
            SET ValorTotal = ?
            WHERE Id = ?
        ");

        $stmtUpd->execute([$totalDia, $fechamentoId]);
    }

    $pdo->commit();

    echo "Importação concluída com sucesso!";

} catch (Exception $e) {

    $pdo->rollBack();
    echo "Erro: " . $e->getMessage();
}

fclose($handle);