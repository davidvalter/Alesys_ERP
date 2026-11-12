<?php
// CLI migration: creates server-side thumbnails and links existing image files.
// For legacy images above 40 MP (up to 70 MP), use a higher CLI memory limit
// after checking available server RAM, e.g. php -d memory_limit=512M database/migrar_miniaturas.php.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../configs/database.php';
require_once __DIR__ . '/../includes/ImageMediaStorage.php';

function caminhoLegadoLocal(string $webPath): ?string
{
    $webPath = trim($webPath);
    if ($webPath === '') {
        return null;
    }

    $parsed = parse_url($webPath, PHP_URL_PATH);
    if (is_string($parsed) && $parsed !== '') {
        $webPath = $parsed;
    }
    $webPath = '/' . ltrim(str_replace('\\', '/', $webPath), '/');

    if (!preg_match('~^/uploads/(pratos|produtos|cardapio)/[a-zA-Z0-9._/-]+$~', $webPath)) {
        return null;
    }
    if (strpos($webPath, '/uploads/../') !== false || strpos($webPath, '/../') !== false) {
        return null;
    }

    $publicRoot = realpath(__DIR__ . '/../public');
    if ($publicRoot === false) {
        return null;
    }
    $filePath = realpath($publicRoot . str_replace('/', DIRECTORY_SEPARATOR, $webPath));
    if ($filePath === false || !is_file($filePath)) {
        return null;
    }

    $prefix = rtrim($publicRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (strncmp($filePath, $prefix, strlen($prefix)) !== 0) {
        return null;
    }
    return $filePath;
}

function migrarImagem(PDO $pdo, string $sourcePath, string $owner, string $table, string $idColumn, int $recordId): bool
{
    $thumbnailPath = null;
    try {
        $thumbnailPath = ImageMediaStorage::createThumbnailFromExisting($sourcePath, $owner);
        $pdo->beginTransaction();
        $mediaId = ImageMediaStorage::register($pdo, $thumbnailPath);
        $stmt = $pdo->prepare("UPDATE {$table} SET ImagemId = ? WHERE {$idColumn} = ? AND ImagemId IS NULL");
        $stmt->execute([$mediaId, $recordId]);
        $associated = $stmt->rowCount() > 0;
        $pdo->commit();

        if (!$associated) {
            ImageMediaStorage::deleteIfUnused($pdo, $mediaId);
        }
        return $associated;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($thumbnailPath !== null) {
            ImageMediaStorage::deleteFiles($thumbnailPath);
        }
        fwrite(STDERR, "Falha ao migrar {$table} #{$recordId}: {$e->getMessage()}\n");
        return false;
    }
}

try {
    $pdo = DatabaseConfig::getConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $migradas = 0;
    $ignoradas = 0;

    $pratos = $pdo->query(
        "SELECT Id, Foto, ImagemId
         FROM Pratos
         WHERE Foto IS NOT NULL AND Foto <> ''
         ORDER BY Id"
    )->fetchAll(PDO::FETCH_ASSOC);

    foreach ($pratos as $prato) {
        if (!empty($prato['ImagemId'])) {
            $ignoradas++;
            continue;
        }
        $webPath = (string)$prato['Foto'];
        if (strpos($webPath, '/') !== 0) {
            $webPath = '/uploads/pratos/' . basename($webPath);
        }
        $sourcePath = caminhoLegadoLocal($webPath);
        if ($sourcePath === null) {
            fwrite(STDERR, "Imagem legada ausente/inválida para prato #{$prato['Id']}: {$webPath}\n");
            $ignoradas++;
            continue;
        }
        if (migrarImagem($pdo, $sourcePath, 'pratos', 'Pratos', 'Id', (int)$prato['Id'])) {
            $migradas++;
        } else {
            $ignoradas++;
        }
    }

    $cardapio = $pdo->query(
        "SELECT c.Id, c.TipoItem, c.ProdutoId, c.PratoId, c.Imagem,
                p.ImagemId AS ProdutoImagemId, pr.ImagemId AS PratoImagemId
         FROM Cardapio c
         LEFT JOIN Produtos p ON c.ProdutoId = p.Codigo
         LEFT JOIN Pratos pr ON c.PratoId = pr.Id
         WHERE c.Imagem IS NOT NULL AND c.Imagem <> ''
         ORDER BY c.Id"
    )->fetchAll(PDO::FETCH_ASSOC);

    $itensAssociados = [];
    foreach ($cardapio as $item) {
        if ($item['TipoItem'] === 'produto' && !empty($item['ProdutoId'])) {
            $owner = 'produtos';
            $table = 'Produtos';
            $idColumn = 'Codigo';
            $recordId = (int)$item['ProdutoId'];
            $alreadyHasImage = !empty($item['ProdutoImagemId']);
        } elseif ($item['TipoItem'] === 'prato' && !empty($item['PratoId'])) {
            $owner = 'pratos';
            $table = 'Pratos';
            $idColumn = 'Id';
            $recordId = (int)$item['PratoId'];
            $alreadyHasImage = !empty($item['PratoImagemId']);
        } else {
            $ignoradas++;
            continue;
        }

        $key = $table . ':' . $recordId;
        if ($alreadyHasImage || isset($itensAssociados[$key])) {
            $ignoradas++;
            continue;
        }
        $itensAssociados[$key] = true;

        $sourcePath = caminhoLegadoLocal((string)$item['Imagem']);
        if ($sourcePath === null) {
            fwrite(STDERR, "Imagem legada ausente/inválida para item de cardápio #{$item['Id']}: {$item['Imagem']}\n");
            $ignoradas++;
            continue;
        }
        if (migrarImagem($pdo, $sourcePath, $owner, $table, $idColumn, $recordId)) {
            $migradas++;
        } else {
            $ignoradas++;
        }
    }

    echo "Migração de miniaturas concluída. Associadas: {$migradas}; ignoradas/ausentes: {$ignoradas}.\n";
    echo "Os arquivos legados foram mantidos. Confira os caminhos reportados antes de removê-los.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Migração não concluída: {$e->getMessage()}\n");
    exit(1);
}
