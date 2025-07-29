<?php
// ajustar_relatorio_ocorrencias.php - Diagnóstico de ocorrências em ambos os bancos
require_once 'config.php';

// Conexão MySQL
$mysql_host = 'localhost';
$mysql_db   = 'police_system';
$mysql_user = 'root';
$mysql_pass = '';
$mysql_charset = 'utf8mb4';
$mysql_dsn = "mysql:host=$mysql_host;dbname=$mysql_db;charset=$mysql_charset";
$mysql_options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
    $pdo_mysql = new PDO($mysql_dsn, $mysql_user, $mysql_pass, $mysql_options);
} catch (PDOException $e) {
    $pdo_mysql = null;
}

// SQLite
$total_sqlite = $pdo->query('SELECT COUNT(*) as total FROM ocorrencias')->fetch()['total'] ?? 0;
$ultimas_sqlite = $pdo->query('SELECT * FROM ocorrencias ORDER BY id DESC LIMIT 3')->fetchAll();

// MySQL
if ($pdo_mysql) {
    $total_mysql = $pdo_mysql->query('SELECT COUNT(*) as total FROM ocorrencias')->fetch()['total'] ?? 0;
    $ultimas_mysql = $pdo_mysql->query('SELECT * FROM ocorrencias ORDER BY id DESC LIMIT 3')->fetchAll();
} else {
    $total_mysql = 0;
    $ultimas_mysql = [];
}

echo "<h2>Diagnóstico de Ocorrências</h2>";
echo "<h3>SQLite</h3>";
echo "Total: <b>$total_sqlite</b><br>";
foreach ($ultimas_sqlite as $o) {
    echo "ID: {$o['id']} | Data: {$o['data']} | Local: {$o['local']}<br>";
}
echo "<h3>MySQL</h3>";
echo "Total: <b>$total_mysql</b><br>";
foreach ($ultimas_mysql as $o) {
    echo "ID: {$o['id']} | Data: {$o['data']} | Local: {$o['local']}<br>";
}
?> 