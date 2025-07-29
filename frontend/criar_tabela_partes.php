<?php
require_once 'config.php';

$sql = "CREATE TABLE IF NOT EXISTS partes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    numero TEXT NOT NULL,
    data TEXT NOT NULL,
    hora TEXT NOT NULL,
    do_usuario_id INTEGER NOT NULL,
    do_nome TEXT NOT NULL,
    ao TEXT NOT NULL,
    assunto TEXT NOT NULL,
    referencia TEXT NOT NULL,
    relato TEXT NOT NULL,
    assinatura TEXT NOT NULL
)";
$pdo->exec($sql);
echo '<p>Tabela partes criada/verificada com sucesso!</p>'; 