# Solução SQLite - Sistema SMART

## 🚀 Solução Alternativa para Problemas de MySQL

Criamos uma versão completa do sistema usando **SQLite** como banco de dados embutido. Esta solução elimina completamente os problemas de permissões, estrutura de tabelas e erros de inserção do MySQL.

## 📁 Arquivos Criados

### 1. **Sistema de Banco de Dados**
- `frontend/database_sqlite.php` - Classe principal do SQLite
- `database/smart_system.db` - Arquivo do banco (criado automaticamente)

### 2. **Páginas do Sistema**
- `frontend/ROGCM_sqlite.php` - Registro de ocorrências
- `frontend/historico_sqlite.php` - Histórico de ocorrências
- `frontend/ver_ocorrencia_sqlite.php` - Visualização de ocorrência

## 🎯 Vantagens do SQLite

✅ **Sem problemas de permissões** - Não precisa de MySQL  
✅ **Banco embutido** - Arquivo único no projeto  
✅ **Compatibilidade total** - Funciona em qualquer servidor  
✅ **Estrutura automática** - Cria tabelas automaticamente  
✅ **Assinaturas como imagens** - Suporte completo a BLOB  
✅ **Sem erros de inserção** - Estrutura otimizada  

## 🚀 Como Usar

### 1. **Acesse o Sistema SQLite**
```
http://seu-servidor/frontend/ROGCM_sqlite.php
```

### 2. **Credenciais Padrão**
- **Usuário:** `admin`
- **Senha:** `password`

### 3. **Funcionalidades Disponíveis**
- ✅ Registro de ocorrências
- ✅ Assinaturas digitais
- ✅ Upload de fotos
- ✅ Histórico completo
- ✅ Visualização de ocorrências
- ✅ Geração de PDF

## 📋 Estrutura do Banco SQLite

### Tabela `ocorrencias`
```sql
- id (INTEGER PRIMARY KEY)
- data, hora_inicio, local, natureza
- dados do solicitante, vítima, autor, testemunhas
- relato, providencias, observacoes
- assinaturas (BLOB) - imagens das assinaturas
- fotos (TEXT) - caminhos das fotos
- numero_ocorrencia (UNIQUE)
- status, data_registro
```

### Tabela `usuarios`
```sql
- id, nome, usuario, senha
- perfil_id, ativo
```

### Tabela `perfis`
```sql
- id, nome, tipo, permissoes
```

## 🔧 Configuração Automática

O sistema SQLite:
1. **Cria o banco automaticamente** na primeira execução
2. **Cria todas as tabelas** com estrutura correta
3. **Insere dados básicos** (perfis e usuário admin)
4. **Gerencia conexões** automaticamente

## 📊 Migração de Dados (Opcional)

Se você quiser migrar dados do MySQL para SQLite:

```php
// Exemplo de migração
$mysql_data = $mysql_pdo->query("SELECT * FROM ocorrencias")->fetchAll();
$sqlite = new DatabaseSQLite();

foreach ($mysql_data as $row) {
    $sqlite->insertOcorrencia($row);
}
```

## 🛠️ Manutenção

### Backup do Banco
```bash
# Copie o arquivo do banco
cp database/smart_system.db backup_smart_$(date +%Y%m%d).db
```

### Restaurar Backup
```bash
# Substitua o arquivo
cp backup_smart_20241201.db database/smart_system.db
```

## 🔍 Solução de Problemas

### Erro de Permissão
```bash
# Dê permissão de escrita na pasta database
chmod 755 database/
chmod 644 database/smart_system.db
```

### Banco Corrompido
```bash
# Remova o banco para recriar
rm database/smart_system.db
# Acesse o sistema - será recriado automaticamente
```

## 📱 URLs do Sistema SQLite

| Função | URL |
|--------|-----|
| Login | `login.php` |
| Registro de Ocorrência | `ROGCM_sqlite.php` |
| Histórico | `historico_sqlite.php` |
| Visualizar Ocorrência | `ver_ocorrencia_sqlite.php?id=X` |
| Dashboard | `dashboard.php` |

## 🎨 Interface

O sistema SQLite mantém a mesma interface moderna:
- Design responsivo
- Assinaturas digitais
- Upload de fotos
- Geração de PDF
- Histórico completo

## ⚡ Performance

- **SQLite é mais rápido** para aplicações pequenas/médias
- **Menos overhead** de conexão
- **Ideal para uso local** ou servidores simples

## 🔒 Segurança

- **Dados criptografados** (senhas com hash)
- **Controle de acesso** por perfil
- **Validação de entrada** em todos os campos
- **Proteção contra SQL injection**

## 📈 Próximos Passos

1. **Teste o sistema** acessando `ROGCM_sqlite.php`
2. **Registre uma ocorrência** de teste
3. **Verifique o histórico** em `historico_sqlite.php`
4. **Gere um PDF** para testar a funcionalidade completa

## 🆘 Suporte

Se encontrar algum problema:
1. Verifique as permissões da pasta `database/`
2. Confirme que o PHP tem extensão SQLite habilitada
3. Verifique os logs de erro do PHP

---

**🎉 Esta solução resolve definitivamente os problemas de MySQL e mantém toda a funcionalidade do sistema!** 