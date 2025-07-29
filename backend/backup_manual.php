<?php
// backup_manual.php - Backup manual do banco SQLite
$origem = __DIR__ . '/../database/smart_system.db';
$destino = __DIR__ . '/../database/backup_' . date('Ymd_His') . '.db';
if (copy($origem, $destino)) {
    echo 'Backup realizado com sucesso: ' . basename($destino);
} else {
    echo 'Erro ao realizar backup.';
} 