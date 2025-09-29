#!/bin/bash

# =====================================================
# IT Ticketing System - Database Backup Script
# =====================================================

# Configuration
DB_HOST="localhost"
DB_NAME="it_ticketing_live"
DB_USER="ticketing_user"
DB_PASS="your_secure_password"
BACKUP_DIR="/path/to/backups"
DATE=$(date +"%Y%m%d_%H%M%S")
RETENTION_DAYS=30

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Perform backup
echo "Starting database backup..."
mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_DIR/ticketing_backup_$DATE.sql"

# Compress backup
gzip "$BACKUP_DIR/ticketing_backup_$DATE.sql"

# Remove old backups (older than retention period)
find "$BACKUP_DIR" -name "ticketing_backup_*.sql.gz" -mtime +$RETENTION_DAYS -delete

echo "Backup completed: ticketing_backup_$DATE.sql.gz"
echo "Old backups cleaned up (kept last $RETENTION_DAYS days)"

# Optional: Send notification email
# echo "Database backup completed successfully" | mail -s "Ticketing System Backup" admin@yourdomain.com