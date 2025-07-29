<?php
// Verificação do Banco de Dados SQLite
// Configurado para GitHub Pages: https://guardacivil.github.io/guardacivil/

$db_path = __DIR__ . '/../database/smart_system.db';

echo "<h1>Verificação do Banco de Dados SQLite</h1>";
echo "<p><strong>URL do Sistema:</strong> https://guardacivil.github.io/guardacivil/frontend/</p>";

try {
    // Verificar se o arquivo do banco existe
    if (file_exists($db_path)) {
        echo "<p style='color: green;'>✅ Arquivo do banco SQLite existe: $db_path</p>";
        
        // Conectar ao banco SQLite
        $pdo = new PDO('sqlite:' . $db_path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Listar tabelas
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<h2>Tabelas no smart_system.db:</h2>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
        
        // Verificar tabelas específicas necessárias
        $required_tables = ['usuarios', 'ocorrencias', 'escalas', 'setores', 'graduacoes'];
        echo "<h2>Verificação de Tabelas Necessárias:</h2>";
        foreach ($required_tables as $table) {
            if (in_array($table, $tables)) {
                echo "<p style='color: green;'>✅ Tabela '$table' existe</p>";
            } else {
                echo "<p style='color: red;'>❌ Tabela '$table' NÃO existe</p>";
            }
        }
        
        // Verificar permissões de escrita
        if (is_writable($db_path)) {
            echo "<p style='color: green;'>✅ Banco de dados tem permissão de escrita</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Banco de dados não tem permissão de escrita</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Arquivo do banco SQLite NÃO existe: $db_path</p>";
        echo "<p>Você precisa criar o banco de dados SQLite primeiro.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Erro de conexão: " . $e->getMessage() . "</p>";
}

// Verificar diretórios essenciais
echo "<h2>Verificação de Diretórios:</h2>";
$directories = [
    '../database' => 'Banco de dados',
    '../uploads' => 'Uploads',
    '../logs' => 'Logs',
    '../pdfs' => 'PDFs',
    '../temp' => 'Temporários'
];

foreach ($directories as $dir => $name) {
    $full_path = __DIR__ . '/' . $dir;
    if (is_dir($full_path)) {
        echo "<p style='color: green;'>✅ Diretório $name existe: $dir</p>";
        if (is_writable($full_path)) {
            echo "<p style='color: green;'>✅ Diretório $name tem permissão de escrita</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Diretório $name não tem permissão de escrita</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Diretório $name NÃO existe: $dir</p>";
    }
}
?> 