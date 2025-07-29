# Backend do Sistema SMART - GCM Araçoiaba da Serra

## 📋 Visão Geral

Backend completo para o Sistema SMART da Guarda Civil Municipal de Araçoiaba da Serra, implementado em PHP seguindo padrões modernos de desenvolvimento. Sistema completo de gestão corporativa incluindo ocorrências, pessoal, comunicação interna e escalas.

## 🏗️ Arquitetura

```
backend/
├── config.php                    # Configurações centralizadas
├── Database.php                  # Classe de banco de dados unificada
├── Auth.php                      # Sistema de autenticação e autorização
├── OcorrenciaController.php      # Controlador de ocorrências
├── PessoalController.php         # Controlador de gestão de pessoal
├── ComunicacaoController.php     # Controlador de comunicação interna
├── EscalaController.php          # Controlador de escalas
├── api/                          # APIs REST
│   ├── ocorrencias.php           # API de ocorrências
│   ├── auth.php                  # API de autenticação
│   ├── pessoal.php               # API de gestão de pessoal
│   ├── comunicacao.php           # API de comunicação interna
│   └── escalas.php               # API de escalas
├── db.php                        # Conexão MySQL (legado)
└── login.php                     # Login (legado)
```

## 🔧 Configuração

### 1. Configurações do Sistema

Edite `config.php` para configurar:

- **Banco de dados**: SQLite (padrão) ou MySQL
- **E-mail**: Configurações SMTP
- **Segurança**: Timeouts, senhas mínimas
- **Sistema**: Nome, versão, dados do órgão

### 2. Banco de Dados

O sistema suporta dois tipos de banco:

#### SQLite (Recomendado)
- Banco embutido em arquivo
- Não requer instalação de servidor
- Ideal para desenvolvimento e pequenos sistemas

#### MySQL
- Banco de dados relacional
- Requer servidor MySQL/MariaDB
- Ideal para sistemas maiores

## 🚀 Funcionalidades Principais

### 1. Sistema de Autenticação (`Auth.php`)

```php
$auth = new Auth();

// Login
$result = $auth->login($usuario, $senha);

// Verificar login
if ($auth->isLoggedIn()) {
    $user = $auth->getCurrentUser();
}

// Verificar permissões
if ($auth->hasPermission('usuarios')) {
    // Acesso permitido
}

// Logout
$auth->logout();
```

### 2. Gestão de Pessoal (`PessoalController.php`)

```php
$controller = new PessoalController();

// Criar novo membro
$result = $controller->create($data);

// Listar pessoal
$result = $controller->list($filters);

// Obter dados de um membro
$result = $controller->get($id);

// Atualizar dados
$result = $controller->update($id, $data);

// Alterar status
$result = $controller->changeStatus($id, 'ativo');

// Gerar relatórios
$result = $controller->generateRelatorio($tipo, $parametros);
```

### 3. Comunicação Interna (`ComunicacaoController.php`)

```php
$controller = new ComunicacaoController();

// Criar comunicação
$result = $controller->create($data);

// Listar comunicações
$result = $controller->list($filters);

// Enviar por e-mail
$result = $controller->sendEmail($id);

// Obter estatísticas
$result = $controller->getEstatisticas();
```

### 4. Gestão de Escalas (`EscalaController.php`)

```php
$controller = new EscalaController();

// Criar escala
$result = $controller->create($data);

// Adicionar pessoal à escala
$result = $controller->addPessoal($escala_id, $data);

// Obter escala do usuário
$result = $controller->getEscalaUsuario($usuario_id);

// Gerar PDF da escala
$result = $controller->generatePDF($id);
```

### 5. Ocorrências (`OcorrenciaController.php`)

```php
$controller = new OcorrenciaController();

// Criar ocorrência
$result = $controller->create($data);

// Obter ocorrência
$result = $controller->get($id);

// Listar ocorrências
$result = $controller->list($filters);

// Gerar PDF
$pdf_content = $controller->generatePDF($id);

// Enviar e-mail
$result = $controller->sendEmail($id);
```

## 📡 APIs REST

### 1. API de Ocorrências

**Base URL**: `/backend/api/ocorrencias.php`

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/ocorrencias` | Listar ocorrências |
| GET | `/api/ocorrencias/{id}` | Obter ocorrência específica |
| POST | `/api/ocorrencias` | Criar nova ocorrência |
| PUT | `/api/ocorrencias/{id}` | Atualizar ocorrência |
| DELETE | `/api/ocorrencias/{id}` | Excluir ocorrência |

### 2. API de Autenticação

**Base URL**: `/backend/api/auth.php`

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/api/auth/login` | Fazer login |
| POST | `/api/auth/logout` | Fazer logout |
| GET | `/api/auth/me` | Obter dados do usuário |
| GET | `/api/auth/csrf-token` | Obter token CSRF |
| POST | `/api/auth/change-password` | Alterar senha |
| POST | `/api/auth/create-user` | Criar usuário |
| PUT | `/api/auth/update-user/{id}` | Atualizar usuário |
| DELETE | `/api/auth/deactivate-user/{id}` | Desativar usuário |

### 3. API de Pessoal

**Base URL**: `/backend/api/pessoal.php`

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/pessoal` | Listar pessoal |
| GET | `/api/pessoal/{id}` | Obter dados de um membro |
| GET | `/api/pessoal/graduacoes` | Obter graduações |
| GET | `/api/pessoal/setores` | Obter setores |
| GET | `/api/pessoal/estatisticas` | Obter estatísticas |
| POST | `/api/pessoal/create` | Criar novo membro |
| PUT | `/api/pessoal/{id}` | Atualizar dados |
| POST | `/api/pessoal/{id}/change-password` | Alterar senha |
| POST | `/api/pessoal/{id}/change-status` | Alterar status |
| DELETE | `/api/pessoal/{id}` | Desativar membro |

### 4. API de Comunicação

**Base URL**: `/backend/api/comunicacao.php`

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/comunicacao` | Listar comunicações |
| GET | `/api/comunicacao/{id}` | Obter comunicação específica |
| GET | `/api/comunicacao/tipos` | Obter tipos de comunicação |
| GET | `/api/comunicacao/prioridades` | Obter prioridades |
| GET | `/api/comunicacao/estatisticas` | Obter estatísticas |
| POST | `/api/comunicacao/create` | Criar comunicação |
| POST | `/api/comunicacao/{id}/send-email` | Enviar por e-mail |
| PUT | `/api/comunicacao/{id}` | Atualizar comunicação |
| DELETE | `/api/comunicacao/{id}` | Excluir comunicação |

### 5. API de Escalas

**Base URL**: `/backend/api/escalas.php`

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/escalas` | Listar escalas |
| GET | `/api/escalas/{id}` | Obter escala específica |
| GET | `/api/escalas/turnos` | Obter turnos disponíveis |
| GET | `/api/escalas/funcoes` | Obter funções disponíveis |
| GET | `/api/escalas/estatisticas` | Obter estatísticas |
| GET | `/api/escalas/usuario` | Obter escala do usuário |
| GET | `/api/escalas/{id}/pessoal` | Obter pessoal da escala |
| GET | `/api/escalas/{id}/pdf` | Gerar PDF da escala |
| POST | `/api/escalas/create` | Criar escala |
| POST | `/api/escalas/{id}/add-pessoal` | Adicionar pessoal |
| PUT | `/api/escalas/{id}` | Atualizar escala |
| DELETE | `/api/escalas/{id}/remove-pessoal/{pessoal_id}` | Remover pessoal |
| DELETE | `/api/escalas/{id}` | Desativar escala |

## 🏛️ Estrutura Organizacional

### Graduações da GCM
1. **Comandante Geral** (Nível 10)
2. **Subcomandante** (Nível 9)
3. **Major** (Nível 8)
4. **Capitão** (Nível 7)
5. **Tenente** (Nível 6)
6. **Subtenente** (Nível 5)
7. **Sargento** (Nível 4)
8. **Cabo** (Nível 3)
9. **Soldado** (Nível 2)
10. **Recruta** (Nível 1)

### Setores da GCM
- **Comando Geral** (CG)
- **Operacional** (OP)
- **Administrativo** (ADM)
- **Recursos Humanos** (RH)
- **Inteligência** (INT)
- **Trânsito** (TRANS)
- **Preventivo** (PREV)

### Perfis de Acesso
- **Comandante Geral**: Acesso total ao sistema
- **Subcomandante**: Acesso total ao sistema
- **Oficial**: Gestão de ocorrências, pessoal, comunicação e escalas
- **Guarda Civil**: Registro de ocorrências e acesso à comunicação
- **Administrativo**: Gestão de ocorrências, pessoal e comunicação
- **Visitante**: Apenas visualização de ocorrências

## 🔒 Segurança

### 1. Autenticação
- Sessões seguras com regeneração de ID
- Timeout de sessão configurável
- Logout automático por inatividade

### 2. Autorização
- Sistema de permissões baseado em perfis
- Controle de acesso granular
- Verificação de permissões em todas as operações

### 3. Proteção CSRF
- Tokens CSRF em todas as operações
- Validação automática de tokens
- Regeneração de tokens

### 4. Validação de Dados
- Sanitização de entrada
- Validação de CPF
- Mascaramento de dados sensíveis

### 5. Logs
- Logs detalhados de todas as operações
- Rastreamento de tentativas de acesso
- Logs de erro para debugging

## 📊 Estrutura do Banco

### Tabela: ocorrencias
- Dados completos da ocorrência
- Assinaturas digitais em BLOB
- Controle de versão com timestamps

### Tabela: usuarios (expandida)
- Dados completos dos membros da corporação
- Graduação, setor, matrícula, CPF
- Dados pessoais e de contato
- Hierarquia organizacional

### Tabela: graduacoes
- Hierarquia da corporação
- Níveis de acesso
- Descrições das funções

### Tabela: setores
- Organização departamental
- Responsáveis por setor
- Controle de acesso por setor

### Tabela: perfis
- Perfis de acesso
- Permissões em JSON
- Tipos de perfil

### Tabela: comunicacoes
- Sistema de comunicação interna
- Controle de acesso por setor/graduação
- Expiração automática

### Tabela: escalas
- Gestão de escalas de serviço
- Controle de turnos e funções
- Responsáveis por escala

### Tabela: escalas_pessoal
- Pessoal escalado
- Datas e turnos
- Funções específicas

### Tabela: tickets
- Sistema de suporte
- Controle de prioridades
- Respostas de administradores

### Tabela: relatorios
- Geração de relatórios
- Controle de status
- Arquivos gerados

## 🛠️ Desenvolvimento

### 1. Adicionando Novas Funcionalidades

1. **Criar controlador** em `backend/`
2. **Implementar API** em `backend/api/`
3. **Adicionar validações** de segurança
4. **Documentar** endpoints

### 2. Testando APIs

```bash
# Testar login
curl -X POST http://localhost/sys.gcm/backend/api/auth.php/login \
  -H "Content-Type: application/json" \
  -d '{"usuario":"comandante","senha":"comandante123"}'

# Testar listagem de pessoal
curl -X GET http://localhost/sys.gcm/backend/api/pessoal.php

# Testar criação de comunicação
curl -X POST http://localhost/sys.gcm/backend/api/comunicacao.php/create \
  -H "Content-Type: application/json" \
  -d '{"titulo":"Teste","conteudo":"Mensagem de teste","tipo":"geral","prioridade":"normal","csrf_token":"token_aqui"}'
```

### 3. Logs e Debugging

Os logs são salvos em `logs/` com formato:
```
[2025-07-19 15:30:45] [INFO] Login realizado com sucesso {"usuario":"comandante","id":1}
[2025-07-19 15:31:12] [ERROR] Erro ao inserir ocorrência {"error":"Campo obrigatório"}
```

## 📝 Migração do Sistema Atual

### 1. Atualizar Frontend

Substituir chamadas diretas ao banco por chamadas à API:

```php
// Antes
$sqlite = new DatabaseSQLite();
$ocorrencias = $sqlite->getAllOcorrencias();

// Depois
$response = file_get_contents('/backend/api/ocorrencias.php');
$data = json_decode($response, true);
$ocorrencias = $data['data'];
```

### 2. Atualizar Autenticação

```php
// Antes
require_once 'auth_check.php';
requireLogin();

// Depois
require_once '../backend/Auth.php';
$auth = new Auth();
$auth->requireLogin();
```

## 🎯 Benefícios da Nova Arquitetura

1. **Gestão Completa de Pessoal**: Controle total da corporação com graduações e setores
2. **Comunicação Interna**: Sistema robusto de comunicação entre membros
3. **Gestão de Escalas**: Controle eficiente de escalas de serviço
4. **Hierarquia Organizacional**: Respeito à estrutura da GCM
5. **Segurança Aprimorada**: Controle de acesso granular
6. **APIs RESTful**: Interface padronizada e reutilizável
7. **Manutenibilidade**: Código organizado e documentado
8. **Escalabilidade**: Fácil adição de novas funcionalidades
9. **Testabilidade**: APIs podem ser testadas independentemente

## 📞 Suporte

Para dúvidas ou problemas:
- Verificar logs em `logs/`
- Consultar documentação das APIs
- Testar endpoints individualmente
- Verificar configurações em `config.php`

## 🔄 Próximos Passos

1. **Implementar frontend** para as novas funcionalidades
2. **Configurar e-mail** para comunicação interna
3. **Implementar relatórios** em PDF
4. **Adicionar notificações** em tempo real
5. **Implementar backup** automático do banco
6. **Adicionar auditoria** completa de ações 