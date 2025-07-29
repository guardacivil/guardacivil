<?php
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    // Conectar sem especificar banco
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Criação do Banco de Dados</h1>";
    
    // Verificar se o banco police_system existe
    $stmt = $pdo->query("SHOW DATABASES LIKE 'police_system'");
    $exists = $stmt->rowCount() > 0;
    
    if (!$exists) {
        echo "<p>Criando banco de dados 'police_system'...</p>";
        $pdo->exec("CREATE DATABASE police_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "<p style='color: green;'>✅ Banco 'police_system' criado com sucesso!</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ Banco 'police_system' já existe.</p>";
    }
    
    // Conectar ao banco police_system
    $pdo_system = new PDO("mysql:host=$host;dbname=police_system;charset=utf8mb4", $user, $pass);
    $pdo_system->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar se as tabelas básicas existem
    $stmt = $pdo_system->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Tabelas Existentes:</h2>";
    if (empty($tables)) {
        echo "<p>Nenhuma tabela encontrada. Criando tabelas básicas...</p>";
        
        // Criar tabela de usuários
        $pdo_system->exec("
            CREATE TABLE usuarios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(255) NOT NULL,
                usuario VARCHAR(100) NOT NULL UNIQUE,
                senha VARCHAR(255) NOT NULL,
                perfil_id INT NOT NULL,
                ativo TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p style='color: green;'>✅ Tabela 'usuarios' criada</p>";
        
        // Criar tabela de perfis
        $pdo_system->exec("
            CREATE TABLE perfis (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                tipo VARCHAR(50) NOT NULL,
                permissoes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p style='color: green;'>✅ Tabela 'perfis' criada</p>";
        
        // Criar tabela de partes
        $pdo_system->exec("
            CREATE TABLE partes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                titulo VARCHAR(255) NOT NULL,
                conteudo TEXT NOT NULL,
                status VARCHAR(50) NOT NULL DEFAULT 'pendente_resposta',
                data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                data_resposta TIMESTAMP NULL,
                resposta TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p style='color: green;'>✅ Tabela 'partes' criada</p>";
        
        // Criar tabela de suporte
        $pdo_system->exec("
            CREATE TABLE suporte (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                titulo VARCHAR(255) NOT NULL,
                mensagem TEXT NOT NULL,
                prioridade VARCHAR(50) NOT NULL,
                status VARCHAR(50) NOT NULL DEFAULT 'aberto',
                data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                resposta TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p style='color: green;'>✅ Tabela 'suporte' criada</p>";
        
        // Criar tabela de ocorrências
        $pdo_system->exec("
            CREATE TABLE ocorrencias (
                id INT AUTO_INCREMENT PRIMARY KEY,
                data DATE NOT NULL,
                hora_inicio TIME NOT NULL,
                local VARCHAR(255) NOT NULL,
                natureza VARCHAR(255),
                data_fato DATE,
                hora_fato TIME,
                local_fato VARCHAR(255),
                bairro VARCHAR(100),
                cidade VARCHAR(100),
                estado VARCHAR(50),
                cep VARCHAR(10),
                nome_solicitante VARCHAR(255),
                nascimento_solicitante DATE,
                rg_solicitante VARCHAR(20),
                cpf_solicitante VARCHAR(14),
                telefone_solicitante VARCHAR(20),
                endereco_solicitante VARCHAR(255),
                nome_vitima VARCHAR(255),
                nascimento_vitima DATE,
                rg_vitima VARCHAR(20),
                cpf_vitima VARCHAR(14),
                telefone_vitima VARCHAR(20),
                endereco_vitima VARCHAR(255),
                nome_suspeito VARCHAR(255),
                nascimento_suspeito DATE,
                rg_suspeito VARCHAR(20),
                cpf_suspeito VARCHAR(14),
                telefone_suspeito VARCHAR(20),
                endereco_suspeito VARCHAR(255),
                descricao_fatos TEXT,
                providencias_tomadas TEXT,
                observacoes TEXT,
                usuario_id INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p style='color: green;'>✅ Tabela 'ocorrencias' criada</p>";
        
        // Inserir perfil administrador padrão
        $pdo_system->exec("
            INSERT INTO perfis (nome, tipo, permissoes) VALUES 
            ('Administrador', 'admin', '[\"usuarios\",\"pessoal\",\"graduacoes\",\"setores\",\"comunicacao\",\"escalas\",\"minhas_escalas\",\"ocorrencias\",\"gerenciar_ocorrencias\",\"relatorios\",\"relatorios_agendados\",\"filtros_avancados\",\"relatorios_hierarquia\",\"perfis\",\"logs\",\"config\",\"db\",\"alertas\",\"suporte\",\"checklist\"]'),
            ('Comando', 'comando', '[\"pessoal\",\"graduacoes\",\"setores\",\"escalas\",\"ocorrencias\",\"relatorios\",\"suporte\"]'),
            ('Secretário', 'secretario', '[\"pessoal\",\"graduacoes\",\"setores\",\"escalas\",\"ocorrencias\",\"relatorios\",\"suporte\"]'),
            ('Suporte', 'suporte', '[\"suporte\"]'),
            ('Guarda Civil', 'guarda', '[\"minhas_escalas\",\"ocorrencias\",\"suporte\"]')
        ");
        echo "<p style='color: green;'>✅ Perfis padrão criados</p>";
        
        // Inserir usuário administrador padrão (senha: admin123)
        $senha_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo_system->exec("
            INSERT INTO usuarios (nome, usuario, senha, perfil_id) VALUES 
            ('Administrador', 'admin', '$senha_hash', 1)
        ");
        echo "<p style='color: green;'>✅ Usuário administrador criado (login: admin, senha: admin123)</p>";
        
    } else {
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
    }
    
    echo "<h2>✅ Banco de dados configurado com sucesso!</h2>";
    echo "<p><a href='index.php'>Ir para o login</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
}
?> 