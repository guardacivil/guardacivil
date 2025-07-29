-- Criação das tabelas principais do sistema SMART (versão SQLite)

-- 1. Tabela de usuários
DROP TABLE IF EXISTS usuarios;
CREATE TABLE usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    usuario TEXT NOT NULL UNIQUE,
    senha TEXT NOT NULL,
    perfil_id INTEGER NOT NULL,
    ativo INTEGER NOT NULL DEFAULT 1
);

-- 2. Tabela de perfis
DROP TABLE IF EXISTS perfis;
CREATE TABLE perfis (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    tipo TEXT NOT NULL,
    permissoes TEXT
);

-- 3. Tabela de logs
DROP TABLE IF EXISTS logs;
CREATE TABLE logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER,
    acao TEXT,
    tabela TEXT,
    registro_id INTEGER,
    dados_anteriores TEXT,
    dados_novos TEXT,
    ip TEXT,
    user_agent TEXT,
    data_log DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 4. Tabela de alertas
DROP TABLE IF EXISTS alertas;
CREATE TABLE alertas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    titulo TEXT NOT NULL,
    mensagem TEXT NOT NULL,
    prioridade TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'pendente',
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 5. Tabela de suporte
DROP TABLE IF EXISTS suporte;
CREATE TABLE suporte (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER,
    titulo TEXT NOT NULL,
    mensagem TEXT NOT NULL,
    prioridade TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'aberto',
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 6. Tabela de checklists
DROP TABLE IF EXISTS checklists;
CREATE TABLE checklists (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER,
    data DATE NOT NULL,
    turno TEXT NOT NULL,
    local TEXT NOT NULL,
    observacoes TEXT,
    status TEXT NOT NULL
);

-- 7. Tabela de checklist_itens
DROP TABLE IF EXISTS checklist_itens;
CREATE TABLE checklist_itens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    checklist_id INTEGER NOT NULL,
    item_id INTEGER NOT NULL,
    status TEXT NOT NULL,
    observacao TEXT
);

-- 8. Tabela de ocorrencias
DROP TABLE IF EXISTS ocorrencias;
CREATE TABLE ocorrencias (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    data DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    local TEXT NOT NULL,
    natureza TEXT,
    data_fato DATE,
    hora_fato TIME,
    local_fato TEXT,
    bairro TEXT,
    cidade TEXT,
    estado TEXT,
    cep TEXT,
    nome_solicitante TEXT,
    nascimento_solicitante DATE,
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
    nascimento_vitima DATE,
    rg_vitima TEXT,
    cpf_vitima TEXT,
    telefone_vitima TEXT,
    endereco_vitima TEXT,
    nome_autor TEXT,
    nascimento_autor DATE,
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
    data_registro DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status TEXT DEFAULT 'aberta',
    numero_ocorrencia TEXT UNIQUE,
    foto_nome_vitima TEXT,
    foto_nome_autor TEXT,
    foto_nome_testemunha1 TEXT,
    foto_nome_testemunha2 TEXT,
    assinatura_solicitante TEXT,
    assinatura_vitima TEXT,
    assinatura_autor TEXT,
    assinatura_testemunha1 TEXT,
    assinatura_testemunha2 TEXT
);

-- Adiciona colunas para até 5 fotos de cada tipo na tabela ocorrencias
ALTER TABLE ocorrencias
ADD COLUMN foto_vitima_1 VARCHAR(255) NULL,
ADD COLUMN foto_vitima_2 VARCHAR(255) NULL,
ADD COLUMN foto_vitima_3 VARCHAR(255) NULL,
ADD COLUMN foto_vitima_4 VARCHAR(255) NULL,
ADD COLUMN foto_vitima_5 VARCHAR(255) NULL,

ADD COLUMN foto_autor_1 VARCHAR(255) NULL,
ADD COLUMN foto_autor_2 VARCHAR(255) NULL,
ADD COLUMN foto_autor_3 VARCHAR(255) NULL,
ADD COLUMN foto_autor_4 VARCHAR(255) NULL,
ADD COLUMN foto_autor_5 VARCHAR(255) NULL,

ADD COLUMN foto_testemunha1_1 VARCHAR(255) NULL,
ADD COLUMN foto_testemunha1_2 VARCHAR(255) NULL,
ADD COLUMN foto_testemunha1_3 VARCHAR(255) NULL,
ADD COLUMN foto_testemunha1_4 VARCHAR(255) NULL,
ADD COLUMN foto_testemunha1_5 VARCHAR(255) NULL,

ADD COLUMN foto_testemunha2_1 VARCHAR(255) NULL,
ADD COLUMN foto_testemunha2_2 VARCHAR(255) NULL,
ADD COLUMN foto_testemunha2_3 VARCHAR(255) NULL,
ADD COLUMN foto_testemunha2_4 VARCHAR(255) NULL,
ADD COLUMN foto_testemunha2_5 VARCHAR(255) NULL;

-- 9. Tabela de configurações gerais do sistema
DROP TABLE IF EXISTS configuracoes;
CREATE TABLE configuracoes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome_sistema TEXT NOT NULL DEFAULT 'Sistema Integrado da Guarda Civil',
    orgao TEXT NOT NULL DEFAULT 'Município de Araçoiaba da Serra',
    cor TEXT DEFAULT '#1e40af',
    modo TEXT DEFAULT 'claro',
    itens_pagina INTEGER DEFAULT 20,
    idioma TEXT DEFAULT 'pt-BR',
    fuso_horario TEXT DEFAULT 'America/Sao_Paulo',
    notificacoes_email INTEGER DEFAULT 1,
    notificacoes_push INTEGER DEFAULT 0,
    alertas_seguranca INTEGER DEFAULT 1,
    limite_ocorrencias INTEGER DEFAULT 1000,
    limite_usuarios INTEGER DEFAULT 100,
    smtp_host TEXT,
    smtp_port INTEGER DEFAULT 587,
    smtp_user TEXT,
    smtp_pass TEXT,
    api_externa TEXT,
    logo TEXT
);

INSERT INTO configuracoes (
    id, nome_sistema, orgao, cor, modo, itens_pagina, idioma, fuso_horario, notificacoes_email, notificacoes_push, alertas_seguranca, limite_ocorrencias, limite_usuarios, smtp_host, smtp_port, smtp_user, smtp_pass, api_externa, logo
) VALUES (
    1, 'Sistema Integrado da Guarda Civil', 'Município de Araçoiaba da Serra', '#1e40af', 'claro', 20, 'pt-BR', 'America/Sao_Paulo', 1, 0, 1, 1000, 100, '', 587, '', '', '', NULL
);

-- Exemplos de inserção para cada tabela

INSERT INTO usuarios (nome, usuario, senha, perfil_id, ativo) VALUES ('João da Silva', 'joaosilva', '$2y$10$hashsenha', 2, 1);
INSERT INTO perfis (nome, tipo, permissoes) VALUES ('Administrador', 'admin', '["usuarios","perfis","logs"]');
INSERT INTO logs (usuario_id, acao, tabela, ip, user_agent) VALUES (1, 'login', 'usuarios', '127.0.0.1', 'Mozilla/5.0');
INSERT INTO alertas (titulo, mensagem, prioridade, status) VALUES ('Alerta de Teste', 'Mensagem de teste', 'alta', 'pendente');
INSERT INTO suporte (usuario_id, titulo, mensagem, prioridade, status) VALUES (1, 'Problema no sistema', 'Não consigo acessar o painel', 'alta', 'aberto');
INSERT INTO checklists (usuario_id, data, turno, local, observacoes, status) VALUES (1, '2024-06-01', 'Diurno', 'Base GCM', 'Tudo ok', 'finalizado');
INSERT INTO checklist_itens (checklist_id, item_id, status, observacao) VALUES (1, 3, 'Bom', 'Sem observações');
-- Exemplo de ocorrência (ajuste os valores conforme necessário)
INSERT INTO ocorrencias (
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
) VALUES (
  '2024-06-01', '12:00:00', 'Rua Exemplo', 'Furto', '2024-06-01', '12:00:00', 'Rua Exemplo', 'Centro', 'Cidade', 'SP', '12345-678',
  'Solicitante', '2000-01-01', '123456', '123.456.789-00', '(15)99999-9999', 'Rua X', 'Centro', 'Cidade', 'SP', '12345-678',
  'Relato exemplo',
  'Vítima', '2000-01-01', '654321', '987.654.321-00', '(15)98888-8888', 'Rua Y',
  'Autor', '1990-01-01', '111111', '111.111.111-11', '(15)97777-7777', 'Rua Z',
  'Testemunha1', '222222', '222.222.222-22', '(15)96666-6666', 'Rua T1',
  'Testemunha2', '333333', '333.333.333-33', '(15)95555-5555', 'Rua T2',
  'Providências', 'Observações', 1, '2024-06-01 12:00:00', 'aberta', '0001/2024',
  '', '', '', '',
  '', '', '', '', ''
);

-- 10. Tabela de escalas
CREATE TABLE IF NOT EXISTS escalas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    setor_id INTEGER,
    responsavel_id INTEGER,
    data_inicio DATE,
    data_fim DATE,
    turno TEXT,
    status TEXT,
    observacoes TEXT
);

-- 11. Tabela de membros da escala
CREATE TABLE IF NOT EXISTS escala_membros (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    escala_id INTEGER,
    usuario_id INTEGER,
    data DATE,
    turno TEXT,
    funcao TEXT,
    observacoes TEXT
);

-- 12. Tabela de localizações dos usuários (para rastreamento em tempo real)
DROP TABLE IF EXISTS localizacoes_usuarios;
CREATE TABLE localizacoes_usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER NOT NULL,
    latitude REAL NOT NULL,
    longitude REAL NOT NULL,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Exemplo de inserção de localização
-- INSERT INTO localizacoes_usuarios (usuario_id, latitude, longitude) VALUES (1, -23.5, -47.6);

