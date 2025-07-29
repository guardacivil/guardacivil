<?php
require_once 'config.php';

echo "<h1>Verificação de Tabelas do Banco de Dados</h1>";

try {
    // Verificar tabela usuarios
    $stmt = $pdo->query("SHOW TABLES LIKE 'usuarios'");
    $usuarios_exists = $stmt->rowCount() > 0;
    echo "<p><strong>Tabela 'usuarios':</strong> " . ($usuarios_exists ? '✅ Existe' : '❌ Não existe') . "</p>";
    
    if ($usuarios_exists) {
        $stmt = $pdo->query("DESCRIBE usuarios");
        $columns = $stmt->fetchAll();
        echo "<p><strong>Colunas da tabela usuarios:</strong></p>";
        echo "<ul>";
        foreach ($columns as $col) {
            echo "<li>" . $col['Field'] . " - " . $col['Type'] . "</li>";
        }
        echo "</ul>";
    }
    
    // Verificar tabela perfis
    $stmt = $pdo->query("SHOW TABLES LIKE 'perfis'");
    $perfis_exists = $stmt->rowCount() > 0;
    echo "<p><strong>Tabela 'perfis':</strong> " . ($perfis_exists ? '✅ Existe' : '❌ Não existe') . "</p>";
    
    if ($perfis_exists) {
        $stmt = $pdo->query("DESCRIBE perfis");
        $columns = $stmt->fetchAll();
        echo "<p><strong>Colunas da tabela perfis:</strong></p>";
        echo "<ul>";
        foreach ($columns as $col) {
            echo "<li>" . $col['Field'] . " - " . $col['Type'] . "</li>";
        }
        echo "</ul>";
    }
    
    // Verificar tabela logs
    $stmt = $pdo->query("SHOW TABLES LIKE 'logs'");
    $logs_exists = $stmt->rowCount() > 0;
    echo "<p><strong>Tabela 'logs':</strong> " . ($logs_exists ? '✅ Existe' : '❌ Não existe') . "</p>";
    
    if ($logs_exists) {
        $stmt = $pdo->query("DESCRIBE logs");
        $columns = $stmt->fetchAll();
        echo "<p><strong>Colunas da tabela logs:</strong></p>";
        echo "<ul>";
        foreach ($columns as $col) {
            echo "<li>" . $col['Field'] . " - " . $col['Type'] . "</li>";
        }
        echo "</ul>";
    }
    
    // Verificar dados nas tabelas
    if ($usuarios_exists) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
        $count = $stmt->fetch()['total'];
        echo "<p><strong>Total de usuários:</strong> $count</p>";
    }
    
    if ($perfis_exists) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM perfis");
        $count = $stmt->fetch()['total'];
        echo "<p><strong>Total de perfis:</strong> $count</p>";
        
        // Mostrar perfis existentes
        $stmt = $pdo->query("SELECT * FROM perfis");
        $perfis = $stmt->fetchAll();
        echo "<p><strong>Perfis existentes:</strong></p>";
        echo "<ul>";
        foreach ($perfis as $perfil) {
            echo "<li>ID: " . $perfil['id'] . " - Nome: " . htmlspecialchars($perfil['nome']) . " - Permissões: " . htmlspecialchars($perfil['permissoes'] ?? 'Nenhuma') . "</li>";
        }
        echo "</ul>";
    }
    
} catch (PDOException $e) {
    echo "<p>❌ Erro ao verificar tabelas: " . $e->getMessage() . "</p>";
}

echo "<p><a href='usuarios.php'>Voltar para Gestão de Usuários</a></p>";
?> 