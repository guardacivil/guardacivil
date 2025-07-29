<?php
/**
 * Classe Database - Gerenciamento unificado de banco de dados
 * Suporta MySQL e SQLite
 */

require_once __DIR__ . '/config.php';

class Database {
    private $connection;
    private $type;
    private static $instance = null;
    
    private function __construct() {
        $this->type = getConfig('DB_TYPE', 'sqlite');
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            if ($this->type === 'sqlite') {
                $this->connection = new PDO('sqlite:' . getConfig('DB_PATH'));
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->createTables();
            } else {
                $this->connection = new PDO(
                    "mysql:host=" . getConfig('DB_HOST') . ";dbname=" . getConfig('DB_NAME') . ";charset=utf8",
                    getConfig('DB_USER'),
                    getConfig('DB_PASS')
                );
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
            
            logMessage('INFO', 'Conexão com banco de dados estabelecida', ['type' => $this->type]);
        } catch (PDOException $e) {
            logMessage('ERROR', 'Erro na conexão com banco de dados', ['error' => $e->getMessage()]);
            throw new Exception('Erro de conexão com banco de dados');
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function getType() {
        return $this->type;
    }
    
    private function createTables() {
        if ($this->type !== 'sqlite') return;
        
        // Tabela de ocorrências
        $sql = "CREATE TABLE IF NOT EXISTS ocorrencias (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            data TEXT NOT NULL,
            hora_inicio TEXT NOT NULL,
            local TEXT NOT NULL,
            natureza TEXT,
            data_fato TEXT,
            hora_fato TEXT,
            local_fato TEXT,
            bairro TEXT,
            cidade TEXT,
            estado TEXT,
            cep TEXT,
            nome_solicitante TEXT,
            nascimento_solicitante TEXT,
            rg_solicitante TEXT,
            cpf_solicitante TEXT,
            telefone_solicitante TEXT,
            endereco_solicitante TEXT,
            bairro_solicitante TEXT,
            cidade_solicitante TEXT,
            estado_solicitante TEXT,
            cep_solicitante TEXT,
            relato TEXT,
            nome_vitima TEXT,
            nascimento_vitima TEXT,
            rg_vitima TEXT,
            cpf_vitima TEXT,
            telefone_vitima TEXT,
            endereco_vitima TEXT,
            nome_autor TEXT,
            nascimento_autor TEXT,
            rg_autor TEXT,
            cpf_autor TEXT,
            telefone_autor TEXT,
            endereco_autor TEXT,
            nome_testemunha1 TEXT,
            rg_testemunha1 TEXT,
            cpf_testemunha1 TEXT,
            telefone_testemunha1 TEXT,
            endereco_testemunha1 TEXT,
            nome_testemunha2 TEXT,
            rg_testemunha2 TEXT,
            cpf_testemunha2 TEXT,
            telefone_testemunha2 TEXT,
            endereco_testemunha2 TEXT,
            providencias TEXT,
            observacoes TEXT,
            usuario_id INTEGER,
            data_registro TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
            status TEXT DEFAULT 'aberta',
            numero_ocorrencia TEXT UNIQUE,
            foto_nome_vitima TEXT,
            foto_nome_autor TEXT,
            foto_nome_testemunha1 TEXT,
            foto_nome_testemunha2 TEXT,
            assinatura_solicitante BLOB,
            assinatura_vitima BLOB,
            assinatura_autor BLOB,
            assinatura_testemunha1 BLOB,
            assinatura_testemunha2 BLOB
        )";
        
        $this->connection->exec($sql);
        
        // Tabela de graduações
        $sql = "CREATE TABLE IF NOT EXISTS graduacoes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            nivel INTEGER NOT NULL,
            descricao TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->connection->exec($sql);
        
        // Tabela de setores/departamentos
        $sql = "CREATE TABLE IF NOT EXISTS setores (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            sigla TEXT,
            responsavel_id INTEGER,
            descricao TEXT,
            ativo INTEGER DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->connection->exec($sql);
        
        // Tabela de usuários (expandida para gestão de pessoal)
        $sql = "CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            nome_guerra TEXT UNIQUE NOT NULL,
            perfil_id INTEGER NOT NULL,
            graduacao_id INTEGER,
            setor_id INTEGER,
            matricula TEXT UNIQUE,
            cpf TEXT UNIQUE,
            rg TEXT,
            data_nascimento TEXT,
            data_admissao TEXT,
            telefone TEXT,
            celular TEXT,
            email TEXT,
            endereco TEXT,
            bairro TEXT,
            cidade TEXT,
            estado TEXT,
            cep TEXT,
            foto TEXT,
            status TEXT DEFAULT 'ativo',
            ativo INTEGER DEFAULT 1,
            supervisor_id INTEGER,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->connection->exec($sql);
        
        // Tabela de perfis
        $sql = "CREATE TABLE IF NOT EXISTS perfis (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            tipo TEXT NOT NULL,
            permissoes TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->connection->exec($sql);
        
        // Tabela de tickets de suporte
        $sql = "CREATE TABLE IF NOT EXISTS tickets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER NOT NULL,
            assunto TEXT NOT NULL,
            mensagem TEXT NOT NULL,
            prioridade TEXT DEFAULT 'media',
            status TEXT DEFAULT 'aberto',
            resposta TEXT,
            respondido_por INTEGER,
            respondido_em TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->connection->exec($sql);
        
        // Tabela de comunicação interna
        $sql = "CREATE TABLE IF NOT EXISTS comunicacoes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            titulo TEXT NOT NULL,
            conteudo TEXT NOT NULL,
            tipo TEXT DEFAULT 'geral',
            prioridade TEXT DEFAULT 'normal',
            autor_id INTEGER NOT NULL,
            destinatarios TEXT,
            setor_id INTEGER,
            graduacao_minima INTEGER,
            publico INTEGER DEFAULT 1,
            data_publicacao TEXT,
            data_expiracao TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->connection->exec($sql);
        
        // Tabela de escalas
        $sql = "CREATE TABLE IF NOT EXISTS escalas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            data_inicio TEXT NOT NULL,
            data_fim TEXT NOT NULL,
            turno TEXT NOT NULL,
            setor_id INTEGER,
            responsavel_id INTEGER,
            observacoes TEXT,
            status TEXT DEFAULT 'ativa',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->connection->exec($sql);
        
        // Tabela de escalas de pessoal
        $sql = "CREATE TABLE IF NOT EXISTS escalas_pessoal (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            escala_id INTEGER NOT NULL,
            usuario_id INTEGER NOT NULL,
            data TEXT NOT NULL,
            turno TEXT NOT NULL,
            funcao TEXT,
            observacoes TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->connection->exec($sql);
        
        // Tabela de relatórios
        $sql = "CREATE TABLE IF NOT EXISTS relatorios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            titulo TEXT NOT NULL,
            tipo TEXT NOT NULL,
            parametros TEXT,
            gerado_por INTEGER NOT NULL,
            arquivo_path TEXT,
            status TEXT DEFAULT 'processando',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->connection->exec($sql);
        
        // Inserir dados básicos
        $this->insertBasicData();
    }
    
    private function insertBasicData() {
        // Verificar se já existem graduações
        $stmt = $this->connection->query("SELECT COUNT(*) FROM graduacoes");
        if ($stmt->fetchColumn() == 0) {
            $sql = "INSERT INTO graduacoes (nome, nivel, descricao) VALUES 
                ('Comandante Geral', 10, 'Comandante Geral da GCM'),
                ('Subcomandante', 9, 'Subcomandante da GCM'),
                ('Major', 8, 'Major da GCM'),
                ('Capitão', 7, 'Capitão da GCM'),
                ('Tenente', 6, 'Tenente da GCM'),
                ('Subtenente', 5, 'Subtenente da GCM'),
                ('Sargento', 4, 'Sargento da GCM'),
                ('Cabo', 3, 'Cabo da GCM'),
                ('Soldado', 2, 'Soldado da GCM'),
                ('Recruta', 1, 'Recruta da GCM')";
            $this->connection->exec($sql);
        }
        
        // Verificar se já existem setores
        $stmt = $this->connection->query("SELECT COUNT(*) FROM setores");
        if ($stmt->fetchColumn() == 0) {
            $sql = "INSERT INTO setores (nome, sigla, descricao) VALUES 
                ('Comando Geral', 'CG', 'Comando Geral da GCM'),
                ('Operacional', 'OP', 'Setor Operacional'),
                ('Administrativo', 'ADM', 'Setor Administrativo'),
                ('Recursos Humanos', 'RH', 'Recursos Humanos'),
                ('Inteligência', 'INT', 'Setor de Inteligência'),
                ('Trânsito', 'TRANS', 'Setor de Trânsito'),
                ('Preventivo', 'PREV', 'Setor Preventivo')";
            $this->connection->exec($sql);
        }
        
        // Verificar se já existem perfis
        $stmt = $this->connection->query("SELECT COUNT(*) FROM perfis");
        if ($stmt->fetchColumn() == 0) {
            $sql = "INSERT INTO perfis (nome, tipo, permissoes) VALUES 
                ('Comandante Geral', 'admin', '[\"usuarios\",\"perfis\",\"logs\",\"ocorrencias\",\"checklists\",\"suporte\",\"pessoal\",\"comunicacao\",\"escalas\",\"relatorios\"]'),
                ('Subcomandante', 'admin', '[\"usuarios\",\"perfis\",\"logs\",\"ocorrencias\",\"checklists\",\"suporte\",\"pessoal\",\"comunicacao\",\"escalas\",\"relatorios\"]'),
                ('Oficial', 'supervisor', '[\"ocorrencias\",\"checklists\",\"pessoal\",\"comunicacao\",\"escalas\"]'),
                ('Guarda Civil', 'operacional', '[\"ocorrencias\",\"checklists\",\"comunicacao\"]'),
                ('Administrativo', 'administrativo', '[\"ocorrencias\",\"checklists\",\"pessoal\",\"comunicacao\"]'),
                ('Visitante', 'publico', '[\"ocorrencias\"]')";
            $this->connection->exec($sql);
        }
        
        // Verificar se já existe usuário admin
        $stmt = $this->connection->query("SELECT COUNT(*) FROM usuarios");
        if ($stmt->fetchColumn() == 0) {
            $sql = "INSERT INTO usuarios (nome, nome_guerra, perfil_id, graduacao_id, setor_id, matricula, cpf, data_admissao, email, status) VALUES 
                ('Comandante Geral', 'comandante', 1, 1, 1, 'CG001', '000.000.000-00', '2020-01-01', 'comandante@gcm.aracoiaba.sp.gov.br', 'ativo')";
            $this->connection->exec($sql);
        }
    }
    
    // Métodos para ocorrências
    public function insertOcorrencia($data) {
        try {
            $expected_fields = 59;
            $actual_fields = count($data);
            
            if ($actual_fields !== $expected_fields) {
                throw new Exception("Número de campos incorreto. Esperado: $expected_fields, Encontrado: $actual_fields");
            }
            
            $sql = "INSERT INTO ocorrencias (
                data, hora_inicio, local, natureza, data_fato, hora_fato, local_fato, bairro, cidade, estado, cep,
                nome_solicitante, nascimento_solicitante, rg_solicitante, cpf_solicitante, telefone_solicitante, endereco_solicitante, bairro_solicitante, cidade_solicitante, estado_solicitante, cep_solicitante,
                relato,
                nome_vitima, nascimento_vitima, rg_vitima, cpf_vitima, telefone_vitima, endereco_vitima,
                nome_autor, nascimento_autor, rg_autor, cpf_autor, telefone_autor, endereco_autor,
                nome_testemunha1, rg_testemunha1, cpf_testemunha1, telefone_testemunha1, endereco_testemunha1,
                nome_testemunha2, rg_testemunha2, cpf_testemunha2, telefone_testemunha2, endereco_testemunha2,
                providencias, observacoes, usuario_id, data_registro, status, numero_ocorrencia,
                foto_nome_vitima, foto_nome_autor, foto_nome_testemunha1, foto_nome_testemunha2,
                assinatura_solicitante, assinatura_vitima, assinatura_autor, assinatura_testemunha1, assinatura_testemunha2
            ) VALUES (" . str_repeat('?,', 58) . "?)";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($data);
            
            $id = $this->connection->lastInsertId();
            logMessage('INFO', 'Ocorrência registrada', ['id' => $id]);
            
            return $id;
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao inserir ocorrência', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    public function getOcorrencia($id) {
        $stmt = $this->connection->prepare("SELECT * FROM ocorrencias WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getAllOcorrencias($usuario_id = null) {
        if ($usuario_id) {
            $stmt = $this->connection->prepare("SELECT * FROM ocorrencias WHERE usuario_id = ? ORDER BY created_at DESC");
            $stmt->execute([$usuario_id]);
        } else {
            $stmt = $this->connection->query("SELECT * FROM ocorrencias ORDER BY created_at DESC");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function updateOcorrencia($id, $data) {
        $fields = array_keys($data);
        $placeholders = array_map(function($field) { return "$field = ?"; }, $fields);
        $sql = "UPDATE ocorrencias SET " . implode(', ', $placeholders) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        
        $values = array_values($data);
        $values[] = $id;
        
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute($values);
    }
    
    public function deleteOcorrencia($id) {
        $stmt = $this->connection->prepare("DELETE FROM ocorrencias WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    // Métodos para usuários
    public function getUser($usuario) {
        $stmt = $this->connection->prepare("SELECT * FROM usuarios WHERE nome_guerra = ? AND ativo = 1");
        $stmt->execute([$usuario]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getUserById($id) {
        $stmt = $this->connection->prepare("SELECT * FROM usuarios WHERE id = ? AND ativo = 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function createUser($data) {
        $sql = "INSERT INTO usuarios (nome, nome_guerra, perfil_id, ativo) VALUES (?, ?, ?, ?)";
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute([
            $data['nome'],
            $data['nome_guerra'],
            $data['perfil_id'],
            1
        ]);
    }
    
    public function updateUser($id, $data) {
        $fields = array_keys($data);
        $placeholders = array_map(function($field) { return "$field = ?"; }, $fields);
        $sql = "UPDATE usuarios SET " . implode(', ', $placeholders) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        
        $values = array_values($data);
        $values[] = $id;
        
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute($values);
    }
    
    // Métodos para perfis
    public function getPerfil($id) {
        $stmt = $this->connection->prepare("SELECT * FROM perfis WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getAllPerfis() {
        $stmt = $this->connection->query("SELECT * FROM perfis ORDER BY nome");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Métodos para tickets
    public function createTicket($data) {
        $sql = "INSERT INTO tickets (usuario_id, assunto, mensagem, prioridade) VALUES (?, ?, ?, ?)";
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute([
            $data['usuario_id'],
            $data['assunto'],
            $data['mensagem'],
            $data['prioridade'] ?? 'media'
        ]);
    }
    
    public function getTicketsByUser($usuario_id) {
        $stmt = $this->connection->prepare("SELECT * FROM tickets WHERE usuario_id = ? ORDER BY created_at DESC");
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAllTickets() {
        $stmt = $this->connection->query("SELECT t.*, u.nome as usuario_nome FROM tickets t JOIN usuarios u ON t.usuario_id = u.id ORDER BY t.created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function respondTicket($id, $resposta, $respondido_por) {
        $sql = "UPDATE tickets SET resposta = ?, respondido_por = ?, respondido_em = CURRENT_TIMESTAMP, status = 'respondido', updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute([$resposta, $respondido_por, $id]);
    }
    
    // Métodos utilitários
    public function getNextOcorrenciaNumber($ano) {
        $stmt = $this->connection->prepare("SELECT COUNT(*) FROM ocorrencias WHERE numero_ocorrencia LIKE ?");
        $stmt->execute(["%/$ano"]);
        $count = $stmt->fetchColumn();
        return str_pad($count + 1, 4, '0', STR_PAD_LEFT) . '/' . $ano;
    }
    
    public function getStats() {
        $stats = [];
        
        // Total de ocorrências
        $stmt = $this->connection->query("SELECT COUNT(*) FROM ocorrencias");
        $stats['total_ocorrencias'] = $stmt->fetchColumn();
        
        // Ocorrências hoje
        $stmt = $this->connection->prepare("SELECT COUNT(*) FROM ocorrencias WHERE DATE(created_at) = DATE('now')");
        $stmt->execute();
        $stats['ocorrencias_hoje'] = $stmt->fetchColumn();
        
        // Total de usuários
        $stmt = $this->connection->query("SELECT COUNT(*) FROM usuarios WHERE ativo = 1");
        $stats['total_usuarios'] = $stmt->fetchColumn();
        
        // Tickets abertos
        $stmt = $this->connection->query("SELECT COUNT(*) FROM tickets WHERE status = 'aberto'");
        $stats['tickets_abertos'] = $stmt->fetchColumn();
        
        return $stats;
    }
    
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollback() {
        return $this->connection->rollback();
    }
    
    public function __destruct() {
        $this->connection = null;
    }

    // Métodos para graduações
    public function getAllGraduacoes() {
        $stmt = $this->connection->query("SELECT * FROM graduacoes ORDER BY nivel DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getGraduacao($id) {
        $stmt = $this->connection->prepare("SELECT * FROM graduacoes WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function createGraduacao($data) {
        $sql = "INSERT INTO graduacoes (nome, nivel, descricao) VALUES (?, ?, ?)";
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute([$data['nome'], $data['nivel'], $data['descricao']]);
    }
    
    // Métodos para setores
    public function getAllSetores() {
        $stmt = $this->connection->query("SELECT s.*, u.nome as responsavel_nome FROM setores s LEFT JOIN usuarios u ON s.responsavel_id = u.id WHERE s.ativo = 1 ORDER BY s.nome");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getSetor($id) {
        $stmt = $this->connection->prepare("SELECT s.*, u.nome as responsavel_nome FROM setores s LEFT JOIN usuarios u ON s.responsavel_id = u.id WHERE s.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function createSetor($data) {
        $sql = "INSERT INTO setores (nome, sigla, responsavel_id, descricao) VALUES (?, ?, ?, ?)";
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute([$data['nome'], $data['sigla'], $data['responsavel_id'], $data['descricao']]);
    }
    
    // Métodos para gestão de pessoal
    public function getAllPessoal($filters = []) {
        $sql = "SELECT u.*, g.nome as graduacao_nome, g.nivel as graduacao_nivel, s.nome as setor_nome, p.nome as perfil_nome 
                FROM usuarios u 
                LEFT JOIN graduacoes g ON u.graduacao_id = g.id 
                LEFT JOIN setores s ON u.setor_id = s.id 
                LEFT JOIN perfis p ON u.perfil_id = p.id 
                WHERE u.ativo = 1";
        
        $params = [];
        
        if (!empty($filters['setor_id'])) {
            $sql .= " AND u.setor_id = ?";
            $params[] = $filters['setor_id'];
        }
        
        if (!empty($filters['graduacao_id'])) {
            $sql .= " AND u.graduacao_id = ?";
            $params[] = $filters['graduacao_id'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND u.status = ?";
            $params[] = $filters['status'];
        }
        
        $sql .= " ORDER BY g.nivel DESC, u.nome";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getPessoalBySetor($setor_id) {
        $stmt = $this->connection->prepare("SELECT u.*, g.nome as graduacao_nome FROM usuarios u LEFT JOIN graduacoes g ON u.graduacao_id = g.id WHERE u.setor_id = ? AND u.ativo = 1 ORDER BY g.nivel DESC, u.nome");
        $stmt->execute([$setor_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function createPessoal($data) {
        $sql = "INSERT INTO usuarios (nome, nome_guerra, perfil_id, graduacao_id, setor_id, matricula, cpf, rg, data_nascimento, data_admissao, telefone, celular, email, endereco, bairro, cidade, estado, cep, supervisor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute([
            $data['nome'],
            $data['nome_guerra'],
            $data['perfil_id'],
            $data['graduacao_id'],
            $data['setor_id'],
            $data['matricula'],
            $data['cpf'],
            $data['rg'],
            $data['data_nascimento'],
            $data['data_admissao'],
            $data['telefone'],
            $data['celular'],
            $data['email'],
            $data['endereco'],
            $data['bairro'],
            $data['cidade'],
            $data['estado'],
            $data['cep'],
            $data['supervisor_id']
        ]);
    }
    
    public function updatePessoal($id, $data) {
        $fields = array_keys($data);
        $placeholders = array_map(function($field) { return "$field = ?"; }, $fields);
        $sql = "UPDATE usuarios SET " . implode(', ', $placeholders) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        
        $values = array_values($data);
        $values[] = $id;
        
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute($values);
    }
    
    // Métodos para comunicação interna
    public function createComunicacao($data) {
        $sql = "INSERT INTO comunicacoes (titulo, conteudo, tipo, prioridade, autor_id, destinatarios, setor_id, graduacao_minima, publico, data_publicacao, data_expiracao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute([
            $data['titulo'],
            $data['conteudo'],
            $data['tipo'],
            $data['prioridade'],
            $data['autor_id'],
            $data['destinatarios'],
            $data['setor_id'],
            $data['graduacao_minima'],
            $data['publico'],
            $data['data_publicacao'],
            $data['data_expiracao']
        ]);
    }
    
    public function getAllComunicacoes($usuario_id = null) {
        $sql = "SELECT c.*, u.nome as autor_nome, s.nome as setor_nome, g.nome as graduacao_minima_nome 
                FROM comunicacoes c 
                LEFT JOIN usuarios u ON c.autor_id = u.id 
                LEFT JOIN setores s ON c.setor_id = s.id 
                LEFT JOIN graduacoes g ON c.graduacao_minima = g.id 
                WHERE c.data_expiracao IS NULL OR c.data_expiracao >= DATE('now')";
        
        if ($usuario_id) {
            $sql .= " AND (c.publico = 1 OR c.autor_id = ? OR c.destinatarios LIKE ?)";
        }
        
        $sql .= " ORDER BY c.created_at DESC";
        
        $stmt = $this->connection->prepare($sql);
        
        if ($usuario_id) {
            $stmt->execute([$usuario_id, "%$usuario_id%"]);
        } else {
            $stmt->execute();
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getComunicacao($id) {
        $stmt = $this->connection->prepare("SELECT c.*, u.nome as autor_nome, s.nome as setor_nome, g.nome as graduacao_minima_nome FROM comunicacoes c LEFT JOIN usuarios u ON c.autor_id = u.id LEFT JOIN setores s ON c.setor_id = s.id LEFT JOIN graduacoes g ON c.graduacao_minima = g.id WHERE c.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Métodos para escalas
    public function createEscala($data) {
        $sql = "INSERT INTO escalas (nome, data_inicio, data_fim, turno, setor_id, responsavel_id, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute([
            $data['nome'],
            $data['data_inicio'],
            $data['data_fim'],
            $data['turno'],
            $data['setor_id'],
            $data['responsavel_id'],
            $data['observacoes']
        ]);
    }
    
    public function getAllEscalas($filters = []) {
        $sql = "SELECT e.*, s.nome as setor_nome, u.nome as responsavel_nome FROM escalas e LEFT JOIN setores s ON e.setor_id = s.id LEFT JOIN usuarios u ON e.responsavel_id = u.id WHERE e.status = 'ativa'";
        
        $params = [];
        
        if (!empty($filters['setor_id'])) {
            $sql .= " AND e.setor_id = ?";
            $params[] = $filters['setor_id'];
        }
        
        if (!empty($filters['data_inicio'])) {
            $sql .= " AND e.data_inicio >= ?";
            $params[] = $filters['data_inicio'];
        }
        
        $sql .= " ORDER BY e.data_inicio DESC";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getEscala($id) {
        $stmt = $this->connection->prepare("SELECT e.*, s.nome as setor_nome, u.nome as responsavel_nome FROM escalas e LEFT JOIN setores s ON e.setor_id = s.id LEFT JOIN usuarios u ON e.responsavel_id = u.id WHERE e.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function addPessoalEscala($data) {
        $sql = "INSERT INTO escalas_pessoal (escala_id, usuario_id, data, turno, funcao, observacoes) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute([
            $data['escala_id'],
            $data['usuario_id'],
            $data['data'],
            $data['turno'],
            $data['funcao'],
            $data['observacoes']
        ]);
    }
    
    public function getEscalaPessoal($escala_id) {
        $stmt = $this->connection->prepare("SELECT ep.*, u.nome as usuario_nome, g.nome as graduacao_nome FROM escalas_pessoal ep LEFT JOIN usuarios u ON ep.usuario_id = u.id LEFT JOIN graduacoes g ON u.graduacao_id = g.id WHERE ep.escala_id = ? ORDER BY ep.data, ep.turno");
        $stmt->execute([$escala_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getEscalaUsuario($usuario_id, $data_inicio = null, $data_fim = null) {
        $sql = "SELECT ep.*, e.nome as escala_nome, s.nome as setor_nome FROM escalas_pessoal ep LEFT JOIN escalas e ON ep.escala_id = e.id LEFT JOIN setores s ON e.setor_id = s.id WHERE ep.usuario_id = ?";
        
        $params = [$usuario_id];
        
        if ($data_inicio) {
            $sql .= " AND ep.data >= ?";
            $params[] = $data_inicio;
        }
        
        if ($data_fim) {
            $sql .= " AND ep.data <= ?";
            $params[] = $data_fim;
        }
        
        $sql .= " ORDER BY ep.data, ep.turno";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Métodos para relatórios
    public function createRelatorio($data) {
        $sql = "INSERT INTO relatorios (titulo, tipo, parametros, gerado_por) VALUES (?, ?, ?, ?)";
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute([
            $data['titulo'],
            $data['tipo'],
            json_encode($data['parametros']),
            $data['gerado_por']
        ]);
    }
    
    public function updateRelatorio($id, $arquivo_path, $status = 'concluido') {
        $sql = "UPDATE relatorios SET arquivo_path = ?, status = ? WHERE id = ?";
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute([$arquivo_path, $status, $id]);
    }
    
    public function getRelatoriosByUser($usuario_id) {
        $stmt = $this->connection->prepare("SELECT * FROM relatorios WHERE gerado_por = ? ORDER BY created_at DESC");
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Métodos para estatísticas
    public function getEstatisticasGerais() {
        $stats = [];
        
        // Total de pessoal
        $stmt = $this->connection->query("SELECT COUNT(*) FROM usuarios WHERE ativo = 1");
        $stats['total_pessoal'] = $stmt->fetchColumn();
        
        // Pessoal por setor
        $stmt = $this->connection->query("SELECT s.nome, COUNT(u.id) as total FROM setores s LEFT JOIN usuarios u ON s.id = u.setor_id WHERE u.ativo = 1 GROUP BY s.id, s.nome");
        $stats['pessoal_por_setor'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Pessoal por graduação
        $stmt = $this->connection->query("SELECT g.nome, COUNT(u.id) as total FROM graduacoes g LEFT JOIN usuarios u ON g.id = u.graduacao_id WHERE u.ativo = 1 GROUP BY g.id, g.nome ORDER BY g.nivel DESC");
        $stats['pessoal_por_graduacao'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Ocorrências do mês
        $stmt = $this->connection->prepare("SELECT COUNT(*) FROM ocorrencias WHERE strftime('%Y-%m', created_at) = strftime('%Y-%m', 'now')");
        $stmt->execute();
        $stats['ocorrencias_mes'] = $stmt->fetchColumn();
        
        // Escalas ativas
        $stmt = $this->connection->query("SELECT COUNT(*) FROM escalas WHERE status = 'ativa'");
        $stats['escalas_ativas'] = $stmt->fetchColumn();
        
        // Comunicações ativas
        $stmt = $this->connection->query("SELECT COUNT(*) FROM comunicacoes WHERE data_expiracao IS NULL OR data_expiracao >= DATE('now')");
        $stats['comunicacoes_ativas'] = $stmt->fetchColumn();
        
        return $stats;
    }
    
    public function getEstatisticasSetor($setor_id) {
        $stats = [];
        
        // Pessoal do setor
        $stmt = $this->connection->prepare("SELECT COUNT(*) FROM usuarios WHERE setor_id = ? AND ativo = 1");
        $stmt->execute([$setor_id]);
        $stats['total_pessoal'] = $stmt->fetchColumn();
        
        // Ocorrências do setor
        $stmt = $this->connection->prepare("SELECT COUNT(*) FROM ocorrencias o JOIN usuarios u ON o.usuario_id = u.id WHERE u.setor_id = ? AND strftime('%Y-%m', o.created_at) = strftime('%Y-%m', 'now')");
        $stmt->execute([$setor_id]);
        $stats['ocorrencias_mes'] = $stmt->fetchColumn();
        
        // Escalas do setor
        $stmt = $this->connection->prepare("SELECT COUNT(*) FROM escalas WHERE setor_id = ? AND status = 'ativa'");
        $stmt->execute([$setor_id]);
        $stats['escalas_ativas'] = $stmt->fetchColumn();
        
        return $stats;
    }
}
?> 