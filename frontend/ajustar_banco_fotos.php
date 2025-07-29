<?php
require_once 'config_mysql.php';

$colunas = [
    'foto_nome_vitima',
    'foto_nome_autor',
    'foto_nome_testemunha1',
    'foto_nome_testemunha2'
];

try {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    foreach ($colunas as $coluna) {
        $existe = false;
        if ($driver === 'mysql') {
            $result = $pdo->query("SHOW COLUMNS FROM ocorrencias LIKE '$coluna'");
            $existe = $result && $result->rowCount() > 0;
        } else if ($driver === 'sqlite') {
            $result = $pdo->query("PRAGMA table_info(ocorrencias)");
            foreach ($result as $row) {
                if ($row['name'] === $coluna) {
                    $existe = true;
                    break;
                }
            }
        }
        if ($existe) {
            echo "A coluna '$coluna' já existe na tabela 'ocorrencias'.<br>";
        } else {
            $pdo->exec("ALTER TABLE ocorrencias ADD COLUMN $coluna TEXT");
            echo "Coluna '$coluna' adicionada com sucesso na tabela 'ocorrencias'.<br>";
        }
    }
} catch (Exception $e) {
    echo "Erro ao ajustar a tabela: " . $e->getMessage();
} 