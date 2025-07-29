<?php
// Script para adicionar a coluna 'data_registro' na tabela 'ocorrencias' (MySQL ou SQLite)
require_once 'config_mysql.php';

try {
    // Detectar se é MySQL ou SQLite
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $colunaExiste = false;
    if ($driver === 'mysql') {
        $result = $pdo->query("SHOW COLUMNS FROM ocorrencias LIKE 'data_registro'");
        $colunaExiste = $result && $result->rowCount() > 0;
    } else if ($driver === 'sqlite') {
        $result = $pdo->query("PRAGMA table_info(ocorrencias)");
        foreach ($result as $row) {
            if ($row['name'] === 'data_registro') {
                $colunaExiste = true;
                break;
            }
        }
    }
    if ($colunaExiste) {
        echo "A coluna 'data_registro' já existe na tabela 'ocorrencias'.<br>";
    } else {
        $pdo->exec("ALTER TABLE ocorrencias ADD COLUMN data_registro TEXT");
        echo "Coluna 'data_registro' adicionada com sucesso na tabela 'ocorrencias'.<br>";
    }
} catch (Exception $e) {
    echo "Erro ao ajustar a tabela: " . $e->getMessage();
} 