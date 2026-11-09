<?php
/* /public/api/nfe_ocr_upload.php */

require_once "../../configs/database.php";
header("Content-Type: application/json; charset=utf-8");

function logErro($msg)
{
    $dir = __DIR__ . "/../logs/ocr/";
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    file_put_contents(
        $dir . "log_" . date("Y-m-d") . ".log",
        "[" . date("H:i:s") . "] " . $msg . PHP_EOL,
        FILE_APPEND
    );
}

function normalizarTexto($texto)
{
    $texto = mb_strtoupper($texto);
    $texto = str_replace(["\r"], "", $texto);
    $texto = preg_replace('/[ \t]+/', ' ', $texto);
    return $texto;
}

function normalizarProduto($str)
{
    $str = mb_strtolower($str);
    $str = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
    $str = preg_replace('/[^a-z0-9\s]/', '', $str);
    $str = preg_replace('/\s+/', ' ', $str);
    return trim($str);
}

function fazerMatchProdutos($pdo, $itens)
{
    $produtos = $pdo->query("
        SELECT Codigo, Nome
        FROM Produtos
        WHERE Ativo = 1
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($itens as &$item) {
        $nomeOCR = normalizarProduto($item["Descricao"]);
        $melhor = null;
        $melhorScore = 0;

        foreach ($produtos as $produto) {
            $nomeBanco = normalizarProduto($produto["Nome"]);
            similar_text($nomeOCR, $nomeBanco, $percent);

            if ($percent > $melhorScore) {
                $melhorScore = $percent;
                $melhor = $produto;
            }
        }

        if ($melhor && $melhorScore >= 55) {
            $item["fk_Produtos_Codigo"] = $melhor["Codigo"];
            $item["ProdutoNome"] = $melhor["Nome"];
            $item["MatchScore"] = round($melhorScore, 1);
        }
    }

    return $itens;
}

function extrairCampos($texto)
{
    $texto = normalizarTexto($texto);
    $linhas = explode("\n", $texto);

    $result = [
        "Numero" => null,
        "Serie" => null,
        "DataEmissao" => null,
        "DataRecebimento" => null,
        "MercadoNome" => null,
        "ChaveAcesso" => null,
        "NaturezaOperacao" => null,
        "ValorTotal" => null,
        "Itens" => []
    ];

    // ============================
    // NÚMERO NOTA (ROBUSTO)
    // ============================
    if (preg_match('/N[ºO°\s]*\s*(\d{6,})/i', $texto, $m)) {
        $result["Numero"] = $m[1];
    } elseif (preg_match('/\b(\d{8})\b/', $texto, $m)) {
        // fallback (número típico de NF)
        $result["Numero"] = $m[1];
    }

    // ============================
    // SÉRIE
    // ============================
    if (preg_match('/S[ÉE]RIE[\s:]*([0-9]{1,3})/i', $texto, $m)) {
        $result["Serie"] = trim($m[1]);
    }

    // ============================
    // CHAVE DE ACESSO (ROBUSTA)
    // ============================
    if (preg_match('/([0-9][\s]{0,2}){44,}/', $texto, $m)) {

        $chave = preg_replace('/\D/', '', $m[0]);

        if (strlen($chave) >= 44) {
            $result["ChaveAcesso"] = substr($chave, 0, 44);
        }
    }

    // ============================
    // DATA EMISSÃO (por label)
    // ============================
    if (preg_match('/DATA\s*DE\s*EMISS[ÃA]O.*?(\d{2}\/\d{2}\/\d{4})/i', $texto, $m)) {

        $data = DateTime::createFromFormat('d/m/Y', $m[1]);
        if ($data) {
            $result["DataEmissao"] = $data->format('Y-m-d');
        }
    }

    // ============================
    // DATA ENTRADA / RECEBIMENTO
    // ============================
    if (preg_match('/ENTRADA.*?(\d{2}\/\d{2}\/\d{4})/i', $texto, $m)) {

        $data = DateTime::createFromFormat('d/m/Y', $m[1]);
        if ($data) {
            $result["DataRecebimento"] = $data->format('Y-m-d');
        }
    }

    // fallback se não achar por label
    if (!$result["DataEmissao"]) {
        if (preg_match_all('/\d{2}\/\d{2}\/\d{4}/', $texto, $m)) {

            if (!empty($m[0][0])) {
                $data = DateTime::createFromFormat('d/m/Y', $m[0][0]);
                if ($data) {
                    $result["DataEmissao"] = $data->format('Y-m-d');
                }
            }

            if (!empty($m[0][1])) {
                $data = DateTime::createFromFormat('d/m/Y', $m[0][1]);
                if ($data) {
                    $result["DataRecebimento"] = $data->format('Y-m-d');
                }
            }
        }
    }

    // ============================
    // NATUREZA OPERAÇÃO
    // ============================
    if (preg_match('/VENDA.*?(?=\n)/i', $texto, $m)) {
        $result["NaturezaOperacao"] = trim($m[0]);
    }

    // ============================
    // MERCADO (emitente)
    // ============================
    foreach ($linhas as $linha) {

        // limpa ruídos comuns
        $linhaLimpa = preg_replace('/[^A-Z0-9\s\.]/', '', $linha);

        if (
            preg_match('/BRASAL|REFRIGERANTES|LTDA|S\.A|SA/', $linhaLimpa) &&
            strlen($linhaLimpa) > 10 &&
            !str_contains($linhaLimpa, 'DESTINATARIO') &&
            !str_contains($linhaLimpa, 'CLIENTE')
        ) {
            $result["MercadoNome"] = trim($linhaLimpa);
            break;
        }
    }

    // ============================
    // ITENS
    // ============================

    $itens = [];

    foreach ($linhas as $linha) {

        $linha = preg_replace('/\s+/', ' ', trim($linha));

        /**
         * Captura linhas tipo:
         * 1756 COCA COLA ZERO LT 6X310ML ... CX 2,0000 15,3150
         */
        if (
            preg_match(
                '/^(\d{2,6})\s+(.+?)\s+(?:CX|X)\s+(\d+,\d{3,4})\D+(\d+,\d{4})/i',
                $linha,
                $m
            )
        ) {

            $codigo = trim($m[1]);
            $descricao = trim($m[2]);
            $quantidade = (float) str_replace(',', '.', $m[3]);
            $valorUnit = (float) str_replace(',', '.', $m[4]);

            // limpa sujeiras comuns do OCR
            $descricao = preg_replace('/[\[\]\|]+/', '', $descricao);
            $descricao = preg_replace('/\b(10|010|5401|I0|IO)\b/', '', $descricao);
            $descricao = preg_replace('/\s+/', ' ', $descricao);
            $descricao = trim($descricao);

            $itens[] = [
                "Codigo" => $codigo,
                "Descricao" => $descricao,
                "Quantidade" => $quantidade,
                "ValorUnitario" => $valorUnit,
                "ValorTotal" => round($quantidade * $valorUnit, 2)
            ];
        }
    }

    $result["Itens"] = $itens;

    // ============================
    // VALOR TOTAL (DANFE padrão)
    // ============================
    if (preg_match('/VALOR\s*TOTAL\s*DA\s*NOTA.*?(\d+,\d{2})/i', $texto, $m)) {

        $valor = str_replace('.', '', $m[1]);
        $valor = str_replace(',', '.', $valor);

        $result["ValorTotal"] = (float) $valor;

    } elseif (preg_match('/\b(\d{1,3}\.\d{3},\d{2}|\d+,\d{2})\b/', $texto, $m)) {
        // fallback genérico
        $valor = str_replace('.', '', $m[1]);
        $valor = str_replace(',', '.', $valor);
        $result["ValorTotal"] = (float) $valor;
    }

    return $result;
}

try {
    if (empty($_POST['fileBase64'])) {
        throw new Exception("Arquivo não enviado");
    }

    $base64 = $_POST['fileBase64'];

    if (strpos($base64, "base64,") !== false) {
        $base64 = explode("base64,", $base64)[1];
    }

    $bin = base64_decode($base64);
    if (!$bin) {
        throw new Exception("Base64 inválido");
    }

    $dir = __DIR__ . "/../uploads/tmp/";
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    // 🔥 AGORA COM EXTENSÃO REAL
    $isPdf = substr($bin, 0, 4) === '%PDF';
    $ext = $isPdf ? '.pdf' : '.jpg';

    $tmpToken = uniqid("nf_", true);
    $fileTmp = $dir . $tmpToken . $ext;

    file_put_contents($fileTmp, $bin);

    $texto = "";

    if ($isPdf) {
        $txtNative = $fileTmp . "_native.txt";

        $cmdPdfText = "pdftotext -layout "
            . escapeshellarg($fileTmp)
            . " "
            . escapeshellarg($txtNative)
            . " 2>&1";

        exec($cmdPdfText, $outPdf, $codePdf);

        logErro("CMD PDFTOTEXT: " . $cmdPdfText);

        if (file_exists($txtNative)) {
            $texto = file_get_contents($txtNative);
            @unlink($txtNative);
        }

        if (strlen(trim($texto)) < 100) {
            logErro("Fallback OCR iniciado");

            $imgPath = $fileTmp . "_page1.png";

            $imagick = new Imagick();
            $imagick->setResolution(300, 300);
            $imagick->readImage($fileTmp . "[0]");
            $imagick->setImageFormat("png");
            $imagick->setImageColorspace(Imagick::COLORSPACE_GRAY);
            $imagick->contrastImage(1);
            $imagick->sharpenImage(0, 1);
            $imagick->writeImage($imgPath);
            $imagick->clear();

            $output = $imgPath . ".txt";

            $cmdOCR = "tesseract "
                . escapeshellarg($imgPath) . " "
                . escapeshellarg($imgPath)
                . " -l por --psm 6 2>&1";

            exec($cmdOCR);

            if (!file_exists($output)) {
                throw new Exception("Erro OCR fallback");
            }

            $texto = file_get_contents($output);

            @unlink($imgPath);
            @unlink($output);
        }
    } else {
        $output = $fileTmp . ".txt";

        $cmdOCR = "tesseract "
            . escapeshellarg($fileTmp) . " "
            . escapeshellarg($fileTmp)
            . " -l por --psm 6 2>&1";

        exec($cmdOCR);

        if (!file_exists($output)) {
            throw new Exception("Erro OCR imagem");
        }

        $texto = file_get_contents($output);
        @unlink($output);
    }

    logErro("TEXTO FINAL:\n" . $texto);

    $dados = extrairCampos($texto);

    $pdo = getDB();
    $dados["Itens"] = fazerMatchProdutos($pdo, $dados["Itens"]);

    echo json_encode([
        "success" => true,
        "data" => $dados,
        "tmp_file" => basename($fileTmp)
    ]);

} catch (Throwable $e) {
    logErro($e->getMessage());

    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Erro ao processar OCR"
    ]);
}