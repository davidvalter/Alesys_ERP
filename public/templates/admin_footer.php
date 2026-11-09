</main>

<script src="../../js/topbar.js"></script>
<script src="../../js/sidebar.js"></script>
<script src="../../js/admin.js"></script>
<script>
    // Confirmação para ações críticas
    document.querySelectorAll('.btn-excluir').forEach(btn => {
        btn.addEventListener('click', (e) => {
            if (!confirm('Tem certeza que deseja excluir este item?')) {
                e.preventDefault();
            }
        });
    });
</script>
</body>

</html>
<?php
// Libera o buffer de saída
ob_end_flush();