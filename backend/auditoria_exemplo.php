<?php
// auditoria_exemplo.php - Exemplo de consulta de logs
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';
$db = Database::getInstance();
$stmt = $db->getConnection()->query("SELECT * FROM logs ORDER BY created_at DESC LIMIT 50");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($logs as $log) {
    echo date('d/m/Y H:i', strtotime($log['created_at'])) . ' - ' . $log['acao'] . ' - ' . $log['usuario'] . '<br>';
} 