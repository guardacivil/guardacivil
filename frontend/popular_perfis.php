<?php
require_once 'config.php';

$perfis = [
    [1, 'Administrador', 'admin', '["usuarios","perfis","logs","ocorrencias","checklists","suporte","parte"]'],
    [2, 'Guarda Civil', 'operacional', '["comunicacao","minhas_escalas","ocorrencias","suporte","checklists","parte"]'],
    [3, 'Comando', 'administrativo', '["ocorrencias","checklists","usuarios","parte"]'],
    [4, 'Secretário', 'administrativo', '["ocorrencias","checklists","parte"]'],
    [5, 'Visitante', 'publico', '["ocorrencias"]'],
    [6, 'Comandante Geral', 'admin', '["usuarios","perfis","logs","ocorrencias","checklists","suporte","pessoal","comunicacao","escalas","relatorios","parte"]'],
];

foreach ($perfis as $p) {
    $stmt = $pdo->prepare("INSERT OR REPLACE INTO perfis (id, nome, tipo, permissoes) VALUES (?, ?, ?, ?)");
    $stmt->execute($p);
}
echo "Perfis atualizados com sucesso!"; 