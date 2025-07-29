# Sistema SMART - Município de Araçoiaba da Serra

Sistema de Gestão para Guarda Civil Municipal com controle de ocorrências, usuários e relatórios.

## 📋 Requisitos do Sistema

- **Servidor Web:** Apache/Nginx
- **PHP:** 7.4 ou superior
- **Banco de Dados:** MySQL 5.7 ou MariaDB 10.2+
- **Extensões PHP:** PDO, PDO_MySQL, JSON, mbstring
- **Servidor Local:** XAMPP, WAMP ou similar

## 🚀 Instalação

### 1. Configuração do Ambiente

1. **Instale o XAMPP** (recomendado para Windows):
   - Baixe em: https://www.apachefriends.org/
   - Instale na pasta padrão: `C:\xampp\`

2. **Inicie os serviços**:
   - Abra o XAMPP Control Panel
   - Inicie Apache e MySQL

### 2. Configuração do Banco de Dados

1. **Acesse o phpMyAdmin**:
   - Abra: http://localhost/phpmyadmin

2. **Execute o script SQL**:
   - Clique em "SQL" no menu superior
   - Cole o conteúdo do arquivo `database_setup.sql`
   - Clique em "Executar"

3. **Verifique a criação**:
   - O banco `police_system` deve ser criado
   - As tabelas devem ser criadas automaticamente
   - Um usuário admin padrão será criado

### 3. Configuração do Projeto

1. **Coloque os arquivos**:
   - Copie todos os arquivos para: `C:\xampp\htdocs\sys.gcm\`

2. **Verifique as configurações**:
   - Abra `frontend/config.php`
   - Confirme as configurações do banco:
     ```php
     $host = 'localhost';
     $db   = 'police_system';
     $user = 'root';
     $pass = '';
     ```

3. **Acesse o sistema**:
   - Abra: http://localhost/sys.gcm/frontend/

## 🔐 Credenciais Padrão

### Usuário Administrador
- **Usuário:** `admin`
- **Senha:** `admin123`
- **Perfil:** Administrador

### Perfis Disponíveis
- **Guarda Civil:** Acesso a ocorrências e relatórios
- **Visitante:** Acesso limitado ao dashboard
- **Comando:** Acesso administrativo
- **Secretário:** Acesso administrativo
- **Administrador:** Acesso total ao sistema

## 📁 Estrutura do Projeto

```
sys.gcm/
├── frontend/                 # Interface principal
│   ├── index.php            # Página de login
│   ├── dashboard.php        # Dashboard principal
│   ├── config.php           # Configuração do banco
│   ├── auth_check.php       # Verificação de autenticação
│   ├── valida_login.php     # Validação de login
│   ├── valida_admin.php     # Validação de admin
│   ├── logout.php           # Logout
│   ├── usuarios.php         # Gestão de usuários
│   ├── perfis.php           # Gestão de perfis
│   ├── alertas.php          # Alertas e notificações
│   ├── logs.php             # Logs do sistema
│   ├── configuracoes.php    # Configurações
│   ├── banco_dados.php      # Gestão do banco
│   ├── suporte.php          # Suporte
│   ├── ROGCM.php            # Registro de ocorrências
│   ├── sidebar.php          # Menu lateral
│   └── img/                 # Imagens do sistema
├── backend/                 # Backend (se necessário)
├── vendor/                  # Dependências (PHPMailer, TCPDF)
├── logs/                    # Logs do sistema
├── pdfs/                    # PDFs gerados
├── temp/                    # Arquivos temporários
└── database_setup.sql       # Script de criação do banco
```

## 🔧 Configurações Importantes

### 1. Permissões de Arquivos
Certifique-se que as pastas tenham permissão de escrita:
- `logs/`
- `pdfs/`
- `temp/`

### 2. Configurações do PHP
No `php.ini`, verifique:
```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
memory_limit = 256M
```

### 3. Configurações de Segurança
- Altere a senha do usuário admin após o primeiro login
- Configure HTTPS em produção
- Mantenha o PHP atualizado

## 📊 Funcionalidades Principais

### Gestão de Usuários
- Cadastro, edição e exclusão de usuários
- Atribuição de perfis
- Controle de acesso por permissões

### Registro de Ocorrências
- Formulário completo de ocorrências
- Geração de PDF
- Histórico de ocorrências

### Sistema de Alertas
- Criação de alertas e notificações
- Controle de status e prioridade
- Notificações por perfil

### Logs do Sistema
- Registro de todas as ações
- Controle de acesso
- Auditoria completa

### Relatórios
- Relatórios de ocorrências
- Estatísticas do sistema
- Exportação de dados

## 🛠️ Manutenção

### Backup do Banco
```sql
mysqldump -u root -p police_system > backup_$(date +%Y%m%d).sql
```

### Limpeza de Logs
- Os logs são mantidos automaticamente
- Arquivos temporários são limpos periodicamente
- PDFs antigos podem ser removidos manualmente

### Atualizações
1. Faça backup do banco de dados
2. Substitua os arquivos do sistema
3. Execute scripts de migração (se houver)
4. Teste todas as funcionalidades

## 🆘 Suporte

### Problemas Comuns

1. **Erro de conexão com banco**:
   - Verifique se MySQL está rodando
   - Confirme as credenciais em `config.php`

2. **Página em branco**:
   - Verifique os logs de erro do PHP
   - Confirme se todas as extensões estão ativas

3. **Erro de permissão**:
   - Verifique as permissões das pastas
   - Confirme se o usuário do servidor tem acesso

### Logs de Erro
- **PHP:** `C:\xampp\php\logs\php_error_log`
- **Apache:** `C:\xampp\apache\logs\error.log`
- **Sistema:** `logs/xvba_debug.log`

## 📞 Contato

Para suporte técnico ou dúvidas:
- **Email:** suporte@sistema.com
- **Telefone:** (11) 1234-5678

---

**Versão:** 1.0.0  
**Última atualização:** Janeiro 2025  
**Desenvolvido para:** Município de Araçoiaba da Serra 