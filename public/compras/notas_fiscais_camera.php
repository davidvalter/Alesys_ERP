<?php
// /public/compras/notas_fiscais_camera.php
session_start();
$numero = $_GET['numero'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <title>Escanear Nota Fiscal</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 15px;
            background: #f5f5f5;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .1);
            max-width: 450px;
            margin: auto;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #ccc;
            margin-top: 8px;
            box-sizing: border-box; /* 👈 importante */
        }

        .btn {
            display: block;
            width: 100%;
            padding: 14px;
            background: #105c47;
            color: white;
            text-align: center;
            border-radius: 8px;
            margin-top: 15px;
            border: none;
            font-size: 16px;
            cursor: pointer;
        }

        .btn-secondary {
            background: #666;
        }

        #preview {
            width: 100%;
            margin-top: 15px;
            border-radius: 8px;
            display: none;
        }
    </style>
</head>

<body>

    <div class="card">

        <h2>📸 Escanear Nota Fiscal</h2>

        <?php if (!$numero): ?>
            <!-- Se número NÃO vier pela URL, pedir aqui -->
            <form id="formNumero">
                <label>Informe o número da nota fiscal:</label>
                <input type="number" id="numeroNota" class="form-control" required>
                <button type="submit" class="btn">Continuar</button>
            </form>

            <script>
                document.getElementById("formNumero").addEventListener("submit", function (e) {
                    e.preventDefault();
                    const num = document.getElementById("numeroNota").value.trim();
                    if (!num) { alert("Informe o número da nota!"); return; }
                    window.location.href = `notas_fiscais_camera.php?numero=${encodeURIComponent(num)}`;
                });
            </script>

        <?php else: ?>

            <p><strong>Número da Nota:</strong> <?= htmlspecialchars($numero) ?></p>

            <button id="btnFotografar" class="btn">Abrir Câmera</button>

            <input type="file" id="inputCamera" accept="image/*" capture="environment" style="display:none">

            <img id="preview">

            <div id="listaPreviews"></div>

            <button id="btnNovaPagina" class="btn" style="display:none;">
                ➕ Adicionar outra página
            </button>


            <button id="btnEnviar" class="btn" style="display:none;">Salvar Nota</button>
            <button onclick="history.back()" class="btn btn-secondary">Voltar</button>

            <!-- EXIF (OBRIGATÓRIO para Safari iOS) -->
            <script src="/js/libs/exif-js-master/exif.js"></script>

            <!-- Funções Mobile -->
            <script src="/js/notas_fiscais_mobile_functions.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'].'/js/notas_fiscais_mobile_functions.js') ?>"></script>

            <script>
                window.NOTA_NUMERO = "<?= htmlspecialchars($numero) ?>";
            </script>


        <?php endif; ?>

    </div>

</body>

</html>