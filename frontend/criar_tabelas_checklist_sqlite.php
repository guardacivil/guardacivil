<?php
require_once 'config.php';

// Criar tabela checklists
$sql1 = "CREATE TABLE IF NOT EXISTS checklists (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER NOT NULL,
    data TEXT NOT NULL,
    turno TEXT NOT NULL,
    local TEXT NOT NULL,
    observacoes TEXT,
    status TEXT DEFAULT 'concluido'
)";
$pdo->exec($sql1);

// Criar tabela checklist_itens
$sql2 = "CREATE TABLE IF NOT EXISTS checklist_itens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    checklist_id INTEGER NOT NULL,
    item_id INTEGER NOT NULL,
    status TEXT NOT NULL,
    observacao TEXT
)";
$pdo->exec($sql2);

echo '<p>Tabelas checklists e checklist_itens criadas/verificadas com sucesso!</p>'; 