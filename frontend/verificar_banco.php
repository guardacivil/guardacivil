<?php
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    // Conectar sem especificar banco
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h1>Verificação do Banco de Dados</h1>";

    // Listar bancos disponíveis
    $stmt = $pdo->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "<h2>Bancos de Dados Disponíveis:</h2>";
    echo "<ul>";
    foreach ($databases as $db) {
        $selected = ($db === 'police_system') ? " <strong>(USADO)</strong>" : "";
        echo "<li>$db$selected</li>";
    }
    echo "</ul>";

    // Verificar se police_system existe
    if (in_array('police_system', $databases)) {
        echo "<p style='color: green;'>✅ Banco 'police_system' existe</p>";

        // Conectar ao police_system
        $pdo_system = new PDO("mysql:host=$host;dbname=police_system", $user, $pass);
        $pdo_system->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Listar tabelas
        $stmt = $pdo_system->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo "<h2>Tabelas no police_system:</h2>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";

        // Verificar tabelas específicas necessárias
        $required_tables = ['usuarios', 'partes', 'suporte', 'ocorrencias'];
        echo "<h2>Verificação de Tabelas Necessárias:</h2>";
        foreach ($required_tables as $table) {
            if (in_array($table, $tables)) {
                echo "<p style='color: green;'>✅ Tabela '$table' existe</p>";
            } else {
                echo "<p style='color: red;'>❌ Tabela '$table' NÃO existe</p>";
            }
        }

    } else {
        echo "<p style='color: red;'>❌ Banco 'police_system' NÃO existe</p>";
        echo "<p>Você precisa criar o banco de dados 'police_system' primeiro.</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>Erro de conexão: " . $e->getMessage() . "</p>";
}
?> 