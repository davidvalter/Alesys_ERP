<?php
/**
 * Armazenamento de imagens no filesystem.
 *
 * A base guarda somente Midias.CaminhoMiniatura. O original e a miniatura
 * ficam no servidor em public/uploads/{pratos|produtos}/.
 */
final class ImageMediaStorage
{
    const MAX_UPLOAD_BYTES = 10485760;
    const MAX_IMAGE_PIXELS = 40000000;
    const MAX_LEGACY_IMAGE_PIXELS = 70000000;
    const THUMBNAIL_SIZE = 640;

    public static function storeUploaded(array $file, string $owner): string
    {
        self::validateOwner($owner);

        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Não foi possível receber o arquivo de imagem.');
        }
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Upload de imagem inválido.');
        }
        if ((int)($file['size'] ?? 0) > self::MAX_UPLOAD_BYTES) {
            throw new RuntimeException('A imagem deve ter no máximo 10 MB.');
        }

        $mime = self::getImageMime($file['tmp_name']);
        $extension = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ][$mime] ?? null;
        if ($extension === null) {
            throw new RuntimeException('Formato de imagem não suportado. Use JPG, PNG ou WebP.');
        }

        $imageId = bin2hex(random_bytes(16));
        $base = self::ownerDirectory($owner);
        $originalDirectory = $base . DIRECTORY_SEPARATOR . 'originais';
        $thumbnailDirectory = $base . DIRECTORY_SEPARATOR . 'miniaturas';
        self::ensureDirectory($originalDirectory);
        self::ensureDirectory($thumbnailDirectory);

        $originalPath = $originalDirectory . DIRECTORY_SEPARATOR . $imageId . '.' . $extension;
        if (!move_uploaded_file($file['tmp_name'], $originalPath)) {
            throw new RuntimeException('Erro ao salvar a imagem enviada.');
        }

        try {
            return self::createThumbnail($originalPath, $thumbnailDirectory, $owner, $imageId);
        } catch (Throwable $e) {
            @unlink($originalPath);
            throw $e;
        }
    }

    /**
     * Gera uma miniatura a partir de um arquivo legado e mantém o arquivo legado.
     */
    public static function createThumbnailFromExisting(string $sourcePath, string $owner): string
    {
        self::validateOwner($owner);
        if (!is_file($sourcePath) || !is_readable($sourcePath)) {
            throw new RuntimeException('Arquivo de imagem legado não encontrado ou sem permissão de leitura.');
        }

        $imageId = bin2hex(random_bytes(16));
        $thumbnailDirectory = self::ownerDirectory($owner) . DIRECTORY_SEPARATOR . 'miniaturas';
        self::ensureDirectory($thumbnailDirectory);
        return self::createThumbnail($sourcePath, $thumbnailDirectory, $owner, $imageId, self::MAX_LEGACY_IMAGE_PIXELS);
    }

    private static function createThumbnail(string $sourcePath, string $thumbnailDirectory, string $owner, string $imageId, int $maxPixels = self::MAX_IMAGE_PIXELS): string
    {
        $info = @getimagesize($sourcePath);
        if (!$info || empty($info['mime'])) {
            throw new RuntimeException('O arquivo recebido não contém uma imagem válida.');
        }
        $mime = self::getImageMime($sourcePath);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new RuntimeException('Formato de imagem não suportado. Use JPG, PNG ou WebP.');
        }
        $width = (int)$info[0];
        $height = (int)$info[1];
        if ($width < 1 || $height < 1 || ($width * $height) > $maxPixels) {
            throw new RuntimeException('As dimensões da imagem são inválidas ou muito grandes.');
        }

        $useWebp = self::supportsWebp();
        $extension = $useWebp ? 'webp' : 'jpg';
        $destination = $thumbnailDirectory . DIRECTORY_SEPARATOR . $imageId . '.' . $extension;

        if (class_exists('Imagick')) {
            self::createWithImagick($sourcePath, $destination, $useWebp);
        } elseif (function_exists('imagecreatefromstring') && function_exists('imagecopyresampled')) {
            self::createWithGd($sourcePath, $destination, $width, $height, $mime, $useWebp);
        } else {
            throw new RuntimeException('Ative a extensão GD ou Imagick do PHP para gerar miniaturas.');
        }

        if (!is_file($destination) || filesize($destination) === 0) {
            throw new RuntimeException('Não foi possível gerar a miniatura da imagem.');
        }

        return '/uploads/' . $owner . '/miniaturas/' . $imageId . '.' . $extension;
    }

    private static function createWithImagick(string $sourcePath, string $destination, bool $useWebp): void
    {
        $image = new Imagick();
        try {
            $image->readImage($sourcePath);
            if (method_exists($image, 'autoOrientImage')) {
                $image->autoOrientImage();
            }
            $image->thumbnailImage(self::THUMBNAIL_SIZE, self::THUMBNAIL_SIZE, true, true);
            if ($useWebp) {
                $image->setImageFormat('webp');
                $image->setImageCompressionQuality(82);
            } else {
                $image->setImageBackgroundColor('white');
                $image = $image->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
                $image->setImageFormat('jpeg');
                $image->setImageCompressionQuality(84);
            }
            if (!$image->writeImage($destination)) {
                throw new RuntimeException('Falha ao gravar a miniatura.');
            }
        } finally {
            $image->clear();
            $image->destroy();
        }
    }

    private static function createWithGd(string $sourcePath, string $destination, int $width, int $height, string $mime, bool $useWebp): void
    {
        $loader = [
            'image/jpeg' => 'imagecreatefromjpeg',
            'image/png' => 'imagecreatefrompng',
            'image/webp' => 'imagecreatefromwebp'
        ][$mime] ?? null;
        $source = $loader !== null && function_exists($loader)
            ? @$loader($sourcePath)
            : false;
        if (!$source) {
            throw new RuntimeException('Não foi possível decodificar a imagem enviada.');
        }

        $scale = min(1, self::THUMBNAIL_SIZE / max($width, $height));
        $targetWidth = max(1, (int)round($width * $scale));
        $targetHeight = max(1, (int)round($height * $scale));
        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        if (!$canvas) {
            imagedestroy($source);
            throw new RuntimeException('Não há memória suficiente para gerar a miniatura.');
        }

        if ($useWebp) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
            imagefill($canvas, 0, 0, $transparent);
        } else {
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefill($canvas, 0, 0, $white);
        }

        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        $saved = $useWebp
            ? imagewebp($canvas, $destination, 82)
            : imagejpeg($canvas, $destination, 84);
        imagedestroy($canvas);
        imagedestroy($source);

        if (!$saved) {
            throw new RuntimeException('Falha ao gravar a miniatura.');
        }
    }

    private static function getImageMime(string $path): string
    {
        if (!is_file($path)) {
            throw new RuntimeException('Arquivo de imagem não encontrado.');
        }
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            return (string)$finfo->file($path);
        }
        $info = @getimagesize($path);
        return (string)($info['mime'] ?? '');
    }

    private static function supportsWebp(): bool
    {
        if (class_exists('Imagick')) {
            try {
                if (in_array('WEBP', Imagick::queryFormats('WEBP'), true)) {
                    return true;
                }
            } catch (Throwable $e) {
                // Tenta o suporte GD abaixo.
            }
        }
        return function_exists('imagewebp');
    }

    public static function register(PDO $pdo, string $thumbnailPath): int
    {
        $stmt = $pdo->prepare(
            'INSERT INTO Midias (CaminhoMiniatura) VALUES (:path)
             ON DUPLICATE KEY UPDATE Id = LAST_INSERT_ID(Id)'
        );
        $stmt->execute([':path' => $thumbnailPath]);
        return (int)$pdo->lastInsertId();
    }

    public static function deleteIfUnused(PDO $pdo, ?int $mediaId): void
    {
        if (!$mediaId || $mediaId < 1) {
            return;
        }

        $stmt = $pdo->prepare(
            'SELECT m.CaminhoMiniatura
             FROM Midias m
             WHERE m.Id = ?
               AND NOT EXISTS (SELECT 1 FROM Produtos p WHERE p.ImagemId = m.Id)
               AND NOT EXISTS (SELECT 1 FROM Pratos pr WHERE pr.ImagemId = m.Id)'
        );
        $stmt->execute([$mediaId]);
        $path = $stmt->fetchColumn();
        if ($path === false) {
            return;
        }

        $delete = $pdo->prepare('DELETE FROM Midias WHERE Id = ?');
        $delete->execute([$mediaId]);
        if ($delete->rowCount() > 0) {
            self::deleteFiles($path);
        }
    }

    public static function deleteFiles(string $thumbnailPath): void
    {
        if (!preg_match('~^/uploads/(pratos|produtos)/miniaturas/([a-f0-9]{32})\.(webp|jpg)$~', $thumbnailPath, $matches)) {
            return;
        }

        $publicRoot = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public';
        $thumbnail = $publicRoot . str_replace('/', DIRECTORY_SEPARATOR, $thumbnailPath);
        if (is_file($thumbnail)) {
            @unlink($thumbnail);
        }

        $owner = $matches[1];
        $imageId = $matches[2];
        $originalDirectory = self::ownerDirectory($owner) . DIRECTORY_SEPARATOR . 'originais';
        foreach (glob($originalDirectory . DIRECTORY_SEPARATOR . $imageId . '.*') ?: [] as $original) {
            if (is_file($original)) {
                @unlink($original);
            }
        }
    }

    private static function ownerDirectory(string $owner): string
    {
        self::validateOwner($owner);
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $owner;
    }

    private static function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Não foi possível criar a pasta para armazenar imagens.');
        }
        if (!is_writable($directory)) {
            throw new RuntimeException('A pasta de imagens não permite gravação pelo PHP.');
        }
    }

    private static function validateOwner(string $owner): void
    {
        if (!in_array($owner, ['pratos', 'produtos'], true)) {
            throw new InvalidArgumentException('Pasta de imagem inválida.');
        }
    }
}
