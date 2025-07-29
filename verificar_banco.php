<?php
// Script de verificação automática do ambiente do Sistema SMART

function checkDir($dir) {
    if (!is_dir($dir)) {
        echo "[ERRO] Diretório '$dir' não existe.\n";
        return false;
    }
    if (!is_writable($dir)) {
        echo "[ERRO] Diretório '$dir' não tem permissão de escrita.\n";
        return false;
    }
    echo "[OK] Diretório '$dir' existe e tem permissão de escrita.\n";
    return true;
}

function checkFile($file) {
    if (!file_exists($file)) {
        echo "[ERRO] Arquivo '$file' não existe.\n";
        return false;
    }
    echo "[OK] Arquivo '$file' existe.\n";
    return true;
}

function checkComposerDeps() {
    if (!file_exists('vendor/autoload.php')) {
        echo "[ERRO] Dependências do Composer não instaladas. Rode 'composer install'.\n";
        return false;
    }
    echo "[OK] Dependências do Composer instaladas.\n";
    return true;
}

// Checagem de diretórios essenciais
echo "\n--- Verificação de Diretórios ---\n";
checkDir('uploads');
checkDir('logs');
checkDir('database');

// Checagem do banco de dados
echo "\n--- Verificação do Banco de Dados ---\n";
checkFile('database/smart_system.db');

// Checagem de dependências Composer
echo "\n--- Verificação de Dependências ---\n";
checkComposerDeps();

// Checagem de arquivos de configuração
echo "\n--- Verificação de Configuração ---\n";
checkFile('backend/config.php');

// Checagem de permissões de escrita nos diretórios
function testWrite($dir) {
    $testFile = rtrim($dir, '/\\') . '/test_write.tmp';
    $ok = @file_put_contents($testFile, 'test');
    if ($ok === false) {
        echo "[ERRO] Não foi possível escrever em '$dir'.\n";
        return false;
    } else {
        unlink($testFile);
        echo "[OK] Permissão de escrita confirmada em '$dir'.\n";
        return true;
    }
}
echo "\n--- Teste de Escrita em Diretórios ---\n";
testWrite('uploads');
testWrite('logs');
testWrite('database');

echo "\nVerificação concluída.\n";
?> 