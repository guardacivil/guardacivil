-- Script para alterar os campos de assinatura para BLOB
-- Execute este script para permitir armazenar imagens de assinatura

-- Alterar campos de assinatura para MEDIUMBLOB (suporta até 16MB)
ALTER TABLE ocorrencias 
MODIFY COLUMN assinatura_solicitante MEDIUMBLOB,
MODIFY COLUMN assinatura_vitima MEDIUMBLOB,
MODIFY COLUMN assinatura_autor MEDIUMBLOB,
MODIFY COLUMN assinatura_testemunha1 MEDIUMBLOB,
MODIFY COLUMN assinatura_testemunha2 MEDIUMBLOB;

-- Verificar a estrutura atualizada
DESCRIBE ocorrencias;

-- Mensagem de confirmação
SELECT 'Campos de assinatura alterados para MEDIUMBLOB com sucesso!' AS resultado; 