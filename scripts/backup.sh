#!/bin/bash
# =============================================================================
# Sauvegarde de l'application Vacancies
# Cron recommandé : 0 2 * * * /path/to/app/scripts/backup.sh >> /var/log/vacancies-backup.log 2>&1
# =============================================================================

set -euo pipefail

# =============================================================================
# CONFIGURATION — à adapter selon le serveur
# =============================================================================

APP_DIR="/var/www/html/vacancies/back"        # Chemin racine de l'application
UPLOADS_DIR="$APP_DIR/public/uploads"         # Dossier uploads

DB_HOST="127.0.0.1"
DB_PORT="3306"
DB_NAME="triplaning"
DB_USER="root"
DB_PASS="password"

LOCAL_BACKUP_DIR="/var/backups/vacancies"     # Dossier de stockage local temporaire
BACKUP_EXT="tar.gz"                           # Format d'archive
LOCAL_RETENTION_DAYS=7                        # Nombre de jours de rétention locale

RCLONE_REMOTE="b2backup"                     # Nom du remote rclone (voir : rclone listremotes)
RCLONE_DEST="triplaning-backup"              # Dossier de destination dans le cloud

# =============================================================================
# INITIALISATION
# =============================================================================

DATE=$(date +%Y-%m-%d_%H-%M-%S)
BACKUP_NAME="vacancies_backup_${DATE}"
TEMP_DIR=$(mktemp -d)
BACKUP_FILE="${LOCAL_BACKUP_DIR}/${BACKUP_NAME}.${BACKUP_EXT}"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1"
}

cleanup() {
    log "Nettoyage du répertoire temporaire..."
    rm -rf "$TEMP_DIR"
}
trap cleanup EXIT

log "=== Début de la sauvegarde : $BACKUP_NAME ==="
mkdir -p "$LOCAL_BACKUP_DIR"

# =============================================================================
# DUMP SQL
# =============================================================================

log "Dump de la base de données '$DB_NAME'..."
mysqldump \
    -h "$DB_HOST" \
    -P "$DB_PORT" \
    -u "$DB_USER" \
    -p"$DB_PASS" \
    --single-transaction \
    --routines \
    --triggers \
    "$DB_NAME" > "$TEMP_DIR/database.sql"

log "Dump SQL OK ($(du -sh "$TEMP_DIR/database.sql" | cut -f1))"

# =============================================================================
# COPIE DES UPLOADS
# =============================================================================

if [ -d "$UPLOADS_DIR" ]; then
    log "Copie de public/uploads..."
    cp -r "$UPLOADS_DIR" "$TEMP_DIR/uploads"
    log "Uploads OK ($(du -sh "$TEMP_DIR/uploads" | cut -f1))"
else
    log "AVERTISSEMENT : le dossier uploads est introuvable ($UPLOADS_DIR), ignoré."
fi

# =============================================================================
# CRÉATION DE L'ARCHIVE
# =============================================================================

log "Création de l'archive $BACKUP_FILE..."
tar -czf "$BACKUP_FILE" -C "$(dirname "$TEMP_DIR")" "$(basename "$TEMP_DIR")"
log "Archive créée ($(du -sh "$BACKUP_FILE" | cut -f1))"

# =============================================================================
# ENVOI VERS LE CLOUD VIA RCLONE
# =============================================================================

log "Envoi vers le cloud ($RCLONE_REMOTE:$RCLONE_DEST)..."
rclone copy "$BACKUP_FILE" "${RCLONE_REMOTE}:${RCLONE_DEST}" --progress

log "Envoi rclone OK"

# =============================================================================
# ROTATION DES BACKUPS LOCAUX
# =============================================================================

log "Rotation : suppression des backups locaux de plus de ${LOCAL_RETENTION_DAYS} jours..."
find "$LOCAL_BACKUP_DIR" -name "vacancies_backup_*.tar.gz" -mtime +"$LOCAL_RETENTION_DAYS" -delete

REMAINING=$(find "$LOCAL_BACKUP_DIR" -name "vacancies_backup_*.tar.gz" | wc -l)
log "Backups locaux restants : $REMAINING"

# =============================================================================
# FIN
# =============================================================================

log "=== Sauvegarde terminée avec succès ==="
