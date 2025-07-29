<?php
// config.php - conexão com o banco de dados SQLite
// Configurado para GitHub Pages: https://guardacivil.github.io/guardacivil/

$db_path = __DIR__ . '/../database/smart_system.db';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO('sqlite:' . $db_path, null, null, $options);
} catch (PDOException $e) {
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}
?>
