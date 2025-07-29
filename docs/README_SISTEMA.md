# Sistema SMART - Documentação

## 🎯 Visão Geral

O Sistema SMART é uma plataforma de gestão para a Guarda Civil Municipal de Araçoiaba da Serra, com separação completa de perfis e funcionalidades específicas para cada tipo de usuário.

## 👥 Perfis de Usuário

### 1. **Guarda Civil** 👮‍♂️
- **Acesso:** Dashboard específico com menu lateral dedicado
- **Funcionalidades:**
  - 📊 Dashboard personalizado
  - 📝 R.O. (Registro de Ocorrências)
  - ✅ CheckList (verificação de rotina)
  - 👤 Parte (gerenciamento de partes)
  - 🚪 Sair

### 2. **Admin** 👑
- **Acesso:** Dashboard administrativo completo
- **Funcionalidades:**
  - 📊 Dashboard geral do sistema
  - 👥 Gestão de Usuários
  - 🔐 Perfis e Permissões
  - 📋 Logs do Sistema
  - ⚙️ Configurações Gerais
  - 🗄️ Banco de Dados
  - 🔔 Alertas e Notificações
  - 🆘 Suporte
  - 📝 Registro de Ocorrências
  - 🚪 Sair

### 3. **Comando** 🎖️
- **Acesso:** Dashboard administrativo (sem acesso ao menu do guarda)
- **Funcionalidades:** Mesmas do Admin

### 4. **Secretário** 📋
- **Acesso:** Dashboard administrativo (sem acesso ao menu do guarda)
- **Funcionalidades:** Mesmas do Admin

### 5. **Visitante** 👀
- **Acesso:** Dashboard administrativo (sem acesso ao menu do guarda)
- **Funcionalidades:** Limitadas conforme permissões

## 🔐 Sistema de Segurança

### Redirecionamento Automático
- **Guarda Civil** → `dashboard_guarda.php` (menu lateral específico)
- **Outros perfis** → `dashboard.php` (menu administrativo)

### Proteção de Acesso
- Guarda Civil não pode acessar dashboard administrativo
- Outros perfis não podem acessar dashboard do guarda
- Verificação de perfil em todas as páginas

## 📋 Credenciais de Teste

### Guarda Civil
```
👤 Nome: João Silva Santos
🔑 Login: guarda
🔒 Senha: 123456
👮‍♂️ Perfil: Guarda Civil
```

### Admin (Modo Deus)
```
👤 Nome: Admin Sistema
🔑 Login: admin
🔒 Senha: 6014
👑 Perfil: Admin
```

## 🗄️ Tabelas do Sistema

### Tabelas Principais
- `usuarios` - Cadastro de usuários
- `perfis` - Perfis e permissões
- `logs` - Logs de atividades
- `alertas` - Sistema de alertas

### Tabelas do Guarda Civil
- `checklists` - Verificações de rotina
- `checklist_itens` - Itens verificados
- `partes` - Pessoas envolvidas
- `ocorrencias` - Registros de ocorrência

## 🚀 Como Usar

### 1. Acessar o Sistema
```
URL: http://localhost/sys.gcm/frontend/
```

### 2. Fazer Login
1. Selecione o perfil desejado
2. Digite o login e senha
3. Clique em "Entrar"

### 3. Navegar no Sistema
- **Guarda Civil:** Menu lateral com 4 opções principais
- **Admin/Outros:** Menu lateral com todas as funcionalidades administrativas

## 🔧 Funcionalidades Específicas

### Para Guarda Civil
- **CheckList:** Verificação diária de equipamentos e procedimentos
- **Partes:** Registro de vítimas, autores, testemunhas
- **R.O.:** Formulário completo de registro de ocorrências
- **Dashboard:** Estatísticas pessoais e últimas ocorrências

### Para Admin
- **Gestão completa** de usuários e perfis
- **Monitoramento** de logs e atividades
- **Configurações** do sistema
- **Relatórios** e estatísticas gerais

## 🛡️ Segurança

- Senhas criptografadas com `password_hash()`
- Verificação de sessão em todas as páginas
- Logs de todas as atividades
- Separação completa de perfis
- Prepared statements para prevenir SQL injection

## 📱 Interface

- **Design responsivo** com Tailwind CSS
- **Ícones** FontAwesome
- **Gradientes** e animações modernas
- **Sidebar** fixo com navegação intuitiva
- **Cards** interativos com hover effects

## 🔄 Fluxo de Trabalho

### Guarda Civil
1. Login → Dashboard Guarda Civil
2. CheckList diário → Verificação de equipamentos
3. R.O. → Registro de ocorrências
4. Parte → Cadastro de envolvidos
5. Logout

### Admin
1. Login → Dashboard Administrativo
2. Gestão de usuários e perfis
3. Monitoramento de logs
4. Configurações do sistema
5. Logout

---

**Sistema desenvolvido para máxima segurança e usabilidade!** 🎯 