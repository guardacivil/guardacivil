<?php
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
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$placeholders = substr_count($sql, '?');
echo "Total de placeholders (?): $placeholders\n";

// Contar campos no INSERT
$campos = [
    'data', 'hora_inicio', 'local', 'natureza', 'data_fato', 'hora_fato', 'local_fato', 'bairro', 'cidade', 'estado', 'cep',
    'nome_solicitante', 'nascimento_solicitante', 'rg_solicitante', 'cpf_solicitante', 'telefone_solicitante', 'endereco_solicitante', 'bairro_solicitante', 'cidade_solicitante', 'estado_solicitante', 'cep_solicitante',
    'relato',
    'nome_vitima', 'nascimento_vitima', 'rg_vitima', 'cpf_vitima', 'telefone_vitima', 'endereco_vitima',
    'nome_autor', 'nascimento_autor', 'rg_autor', 'cpf_autor', 'telefone_autor', 'endereco_autor',
    'nome_testemunha1', 'rg_testemunha1', 'cpf_testemunha1', 'telefone_testemunha1', 'endereco_testemunha1',
    'nome_testemunha2', 'rg_testemunha2', 'cpf_testemunha2', 'telefone_testemunha2', 'endereco_testemunha2',
    'providencias', 'observacoes', 'usuario_id', 'data_registro', 'status', 'numero_ocorrencia',
    'foto_nome_vitima', 'foto_nome_autor', 'foto_nome_testemunha1', 'foto_nome_testemunha2',
    'assinatura_solicitante', 'assinatura_vitima', 'assinatura_autor', 'assinatura_testemunha1', 'assinatura_testemunha2'
];

echo "Total de campos no INSERT: " . count($campos) . "\n";

if ($placeholders === count($campos)) {
    echo "✅ Placeholders e campos estão corretos!\n";
} else {
    echo "❌ PROBLEMA: Placeholders ($placeholders) != Campos (" . count($campos) . ")\n";
}
?> 