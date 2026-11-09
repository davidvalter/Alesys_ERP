#!/bin/bash

# ===== CONFIGURAÇÕES =====
LOCAL_DIR="/www/wwwroot/alesys.alecriminteligente.com.br/public/uploads/nf_compras"
REMOTE_DIR="OneDrive:CONTABILIDADE/MOVIMENTAÇÕES/UPLOAD_AUTOMATICO/"
LOG_DIR="/www/wwwroot/alesys.alecriminteligente.com.br/public/logs"
LOG_FILE="$LOG_DIR/sync_nfcompras_onedrive.log"

# ===== GARANTIR DIRETÓRIO DE LOG =====
mkdir -p "$LOG_DIR"

# ===== DATA =====
echo "======================================" >> "$LOG_FILE"
echo "Início do backup (COPY): $(date)" >> "$LOG_FILE"

# ===== EXECUÇÃO =====
/usr/bin/rclone copy "$LOCAL_DIR" "$REMOTE_DIR" \
    --ignore-existing \
    --checksum \
    --transfers=4 \
    --checkers=8 \
    --create-empty-src-dirs \
    --log-file="$LOG_FILE" \
    --log-level INFO

STATUS=$?

# ===== RESULTADO =====
if [ $STATUS -eq 0 ]; then
    echo "Backup concluído com sucesso: $(date)" >> "$LOG_FILE"
else
    echo "ERRO no backup (exit code $STATUS): $(date)" >> "$LOG_FILE"
fi

echo "======================================" >> "$LOG_FILE"
