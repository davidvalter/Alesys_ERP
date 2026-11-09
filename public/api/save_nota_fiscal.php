<?php
/**
 * API - Salvar Nota Fiscal (Inserção / Edição)
 * Recalcula total a partir dos itens e aplica desconto no servidor (mais seguro).
 */

header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../../configs/database.php';
require_once __DIR__ . '/../conf/autenticacao.php';

$rawInput = file_get_contents("php://input");
error_log("DEBUG: JSON recebido -> " . substr($rawInput, 0, 1000)); // limitado

$data = json_decode($rawInput, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("ERRO: JSON inválido -> " . json_last_error_msg());
    echo json_encode(["success" => false, "message" => "JSON inválido enviado ao servidor"]);
    exit;
}

try {
    $pdo = getDB();
    $pdo->beginTransaction();
    error_log("DEBUG: Transação iniciada");

    // Extrai dados principais
    $notaId = $data['Id'] ?? null;
    $numero = $data['Numero'] ?? null;
    $serie = $data['Serie'] ?? null;
    $tipo = $data['Tipo'] ?? 0;
    $dataEmissao = $data['DataEmissao'] ?? null;
    $dataRecebimento = $data['DataRecebimento'] ?? null;
    $naturezaOperacao = $data['NaturezaOperacao'] ?? null;
    $chaveAcesso = $data['ChaveAcesso'] ?? null;
    // Aceita desconto por vários nomes possíveis enviados pelo frontend
    $desconto = 0;
    if (isset($data['Desconto']))
        $desconto = (float) $data['Desconto'];
    elseif (isset($data['ValorDesconto']))
        $desconto = (float) $data['ValorDesconto'];
    elseif (isset($data['valorDesconto']))
        $desconto = (float) $data['valorDesconto'];
    // for safety, normalize
    $desconto = (float) str_replace(',', '.', (string) $desconto);

    $mercado = $data['fk_Mercados_Codigo'] ?? null;
    $observacoes = $data['Observacoes'] ?? null;
    $itens = $data['itens'] ?? [];

    error_log("DEBUG: Dados extraídos -> Numero=$numero, Serie=$serie, Tipo=$tipo, DataEmissao=$dataEmissao, Mercado=$mercado, Itens=" . count($itens) . ", Desconto=" . $desconto);

    // Upload do PDF (mantive seu fluxo)
    $pdfPathDb = null;

    if (!empty($data['tmp_file'])) {
        $tmpFile = basename($data['tmp_file']);
        $tmpPath = __DIR__ . '/../uploads/tmp/' . $tmpFile;

        if (file_exists($tmpPath)) {
            $uploadDir = __DIR__ . '/../uploads/nf_compras/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $ext = pathinfo($tmpPath, PATHINFO_EXTENSION) ?: 'pdf';

            $safeNumero = preg_replace('/[^0-9]/', '', $numero);
            $safeMercado = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $mercado);
            $safeData = preg_replace('/[^0-9]/', '', (string) $dataEmissao);

            $pdfFileName = "nf_compra_{$safeData}_{$safeNumero}_{$safeMercado}.{$ext}";
            $filePath = $uploadDir . $pdfFileName;

            error_log("TMP ORIGEM: " . $tmpPath);
            error_log("DESTINO: " . $filePath);
            error_log("TMP EXISTE? " . (file_exists($tmpPath) ? 'SIM' : 'NAO'));

            if (!rename($tmpPath, $filePath)) {
                error_log("ERRO AO MOVER ARQUIVO");
            }

            $pdfPathDb = 'uploads/nf_compras/' . $pdfFileName;

            error_log("DEBUG: TMP movido para definitivo -> $filePath");
        }
    } elseif (!empty($data['pdfCaminhoExistente'])) {
        $pdfPathDb = $data['pdfCaminhoExistente'];
    }

    // Blindar Itens Vazios
    foreach ($itens as $idx => $item) {
        $produtoId = $item['fk_Produtos_Codigo'] ?? null;

        if (empty($produtoId)) {
            $nomeProduto = $item['ProdutoNome']
                ?? $item['DescricaoComplementar']
                ?? "Item " . ($idx + 1);

            throw new Exception("O produto '{$nomeProduto}' não foi cadastrado previamente. Ajuste a nota antes de salvar.");
        }
    }

    // ----- Recalcula o total bruto a partir dos itens no servidor -----
    $calculatedTotal = 0.0;

    foreach ($itens as $idx => $item) {
        // Aceita formatos com vírgula ou ponto, mas sem remover separadores de milhar
        $qRaw = trim((string) ($item['Quantidade'] ?? 0));
        $vRaw = trim((string) ($item['ValorUnitario'] ?? 0));

        // Substitui vírgula por ponto — ponto decimal é o único aceito em float
        $qNorm = str_replace(',', '.', $qRaw);
        $vNorm = str_replace(',', '.', $vRaw);

        // Converte para float com segurança
        $quantidade = is_numeric($qNorm) ? (float) $qNorm : 0;
        $valorUnitario = is_numeric($vNorm) ? (float) $vNorm : 0;

        // Cálculo item
        $subtotal = round($quantidade * $valorUnitario, 2);
        $calculatedTotal += $subtotal;

        // Atualiza array com valores corrigidos
        $itens[$idx]['Quantidade'] = $quantidade;
        $itens[$idx]['ValorUnitario'] = $valorUnitario;
    }

    // Total bruto
    $calculatedTotal = round($calculatedTotal, 2);

    // Aplica desconto e recalcula total líquido
    $valorLiquido = round($calculatedTotal - $desconto, 2);
    if ($valorLiquido < 0)
        $valorLiquido = 0.00;

    error_log("DEBUG: Total bruto={$calculatedTotal} | Desconto={$desconto} | Líquido={$valorLiquido}");

    // Opcional: se o cliente enviou um ValorTotal, logamos discrepância
    if (isset($data['ValorTotal'])) {
        $clientTotal = (float) str_replace(',', '.', (string) $data['ValorTotal']);
        if (abs($clientTotal - $valorLiquido) > 0.01) {
            error_log("WARN: Diferença entre total enviado pelo cliente ({$clientTotal}) e total calculado no servidor ({$valorLiquido}). Usando valor do servidor.");
        }
    }

    // ----- Inserção ou atualização -----
    if ($notaId) {
        error_log("DEBUG: Atualizando nota ID=$notaId");

        $stmt = $pdo->prepare("
            UPDATE NFe
            SET Numero = :Numero,
                Serie = :Serie,
                Tipo = :Tipo,
                DataEmissao = :DataEmissao,
                DataRecebimento = :DataRecebimento,
                NaturezaOperacao = :NaturezaOperacao,
                ChaveAcesso = :ChaveAcesso,
                ValorTotal = :ValorTotal,
                Desconto = :Desconto,
                fk_Mercados_Codigo = :Mercado,
                Observacoes = :Observacoes,
                pdf_path = :PdfPath
            WHERE Id = :Id
        ");
        $stmt->execute([
            ':Numero' => $numero,
            ':Serie' => $serie,
            ':Tipo' => $tipo,
            ':DataEmissao' => $dataEmissao,
            ':DataRecebimento' => $dataRecebimento,
            ':NaturezaOperacao' => $naturezaOperacao,
            ':ChaveAcesso' => $chaveAcesso,
            // GRAVA O VALOR LÍQUIDO calculado no servidor
            ':ValorTotal' => $valorLiquido,
            ':Desconto' => $desconto,
            ':Mercado' => $mercado,
            ':Observacoes' => $observacoes,
            ':PdfPath' => $pdfPathDb,
            ':Id' => $notaId
        ]);
        error_log("DEBUG: Nota ID=$notaId atualizada (valor salvo = {$valorLiquido})");

        // Remove itens antigos e insere novamente
        $pdo->prepare("DELETE FROM NFeItens WHERE fk_NFe_Id = :Id")->execute([':Id' => $notaId]);
        error_log("DEBUG: Itens antigos removidos");

        foreach ($itens as $item) {
            $stmtItem = $pdo->prepare("
                INSERT INTO NFeItens (fk_NFe_Id, fk_Produtos_Codigo, Quantidade, ValorUnitario, UnidadeMedida, DescricaoComplementar)
                VALUES (:NFeId, :ProdutoId, :Quantidade, :ValorUnitario, :Unidade, :Descricao)
            ");
            $stmtItem->execute([
                ':NFeId' => $notaId,
                ':ProdutoId' => $item['fk_Produtos_Codigo'],
                ':Quantidade' => $item['Quantidade'],
                ':ValorUnitario' => $item['ValorUnitario'],
                ':Unidade' => $item['UnidadeMedida'] ?? 'UN',
                ':Descricao' => $item['DescricaoComplementar'] ?? null
            ]);
        }
        error_log("DEBUG: " . count($itens) . " itens inseridos para a nota $notaId");

        $pdo->commit();

        // chama insert_estoque
        $_POST['nfeId'] = $notaId;
        ob_start();
        include __DIR__ . '/insert_estoque.php';
        ob_end_clean();

        echo json_encode(["success" => true, "message" => "Nota fiscal atualizada com sucesso", "notaId" => $notaId, "valor_salvo" => $valorLiquido]);

    } else {
        error_log("DEBUG: Inserindo nova nota fiscal");

        $stmt = $pdo->prepare("
            INSERT INTO NFe (Numero, Serie, Tipo, DataEmissao, DataRecebimento, NaturezaOperacao, ChaveAcesso, ValorTotal, Desconto, fk_Mercados_Codigo, Observacoes, pdf_path, Status)
            VALUES (:Numero, :Serie, :Tipo, :DataEmissao, :DataRecebimento, :NaturezaOperacao, :ChaveAcesso, :ValorTotal, :Desconto, :Mercado, :Observacoes, :PdfPath, '1')
        ");
        $stmt->execute([
            ':Numero' => $numero,
            ':Serie' => $serie,
            ':Tipo' => $tipo,
            ':DataEmissao' => $dataEmissao,
            ':DataRecebimento' => $dataRecebimento,
            ':NaturezaOperacao' => $naturezaOperacao,
            ':ChaveAcesso' => $chaveAcesso,
            ':ValorTotal' => $valorLiquido,
            ':Desconto' => $desconto,
            ':Mercado' => $mercado,
            ':Observacoes' => $observacoes,
            ':PdfPath' => $pdfPathDb
        ]);
        $newNotaId = $pdo->lastInsertId();
        error_log("DEBUG: Nota inserida com ID=$newNotaId (valor salvo = {$valorLiquido})");

        foreach ($itens as $item) {
            $stmtItem = $pdo->prepare("
                INSERT INTO NFeItens (fk_NFe_Id, fk_Produtos_Codigo, Quantidade, ValorUnitario, UnidadeMedida, DescricaoComplementar)
                VALUES (:NFeId, :ProdutoId, :Quantidade, :ValorUnitario, :Unidade, :Descricao)
            ");
            $stmtItem->execute([
                ':NFeId' => $newNotaId,
                ':ProdutoId' => $item['fk_Produtos_Codigo'],
                ':Quantidade' => $item['Quantidade'],
                ':ValorUnitario' => $item['ValorUnitario'],
                ':Unidade' => $item['UnidadeMedida'] ?? 'UN',
                ':Descricao' => $item['DescricaoComplementar'] ?? null
            ]);
        }
        error_log("DEBUG: " . count($itens) . " itens inseridos para a nota $newNotaId");

        $pdo->commit();

        // chama insert_estoque
        $_POST['nfeId'] = $newNotaId;
        ob_start();
        include __DIR__ . '/insert_estoque.php';
        ob_end_clean();

        echo json_encode(["success" => true, "message" => "Nota fiscal criada com sucesso", "notaId" => $newNotaId, "valor_salvo" => $valorLiquido]);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction())
        $pdo->rollBack();
    error_log("ERRO: Falha ao salvar nota fiscal -> " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Erro ao salvar nota fiscal. " . $e->getMessage(),
        "error" => $e->getMessage()
    ]);
}
