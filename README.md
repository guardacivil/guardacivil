# Checklist de Inicialização do Sistema SMART

Siga os passos abaixo para garantir o funcionamento perfeito do sistema:

## 1. Instalar dependências PHP

```bash
composer install
```

## 2. Garantir permissões de escrita

Certifique-se de que os diretórios abaixo existem e têm permissão de escrita para o usuário do servidor web:

- `uploads/`
- `logs/`
- `database/`

No Windows, normalmente basta garantir que não estejam como "somente leitura". No Linux:

```bash
chmod -R 775 uploads logs database
```

## 3. Configurar o arquivo de configuração

Edite `backend/config.php` e ajuste:
- Credenciais de e-mail (SMTP)
- Parâmetros do banco de dados, se necessário
- URLs do sistema

## 4. Criar o banco de dados SQLite

Execute o script SQL adaptado para SQLite:

```bash
sqlite3 database/smart_system.db < docs/database_setup.sql
```

## 5. Testar o sistema

- Acesse a URL do sistema no navegador (ex: http://localhost/sys.gcm/frontend/)
- Faça login com o usuário de exemplo criado no SQL (`joaosilva`)
- Teste as principais funcionalidades (cadastro, ocorrências, uploads, geração de PDF, envio de e-mail)

## 6. Verificar logs

- Os logs de erro do PHP estarão em `logs/error.log`
- Os logs do sistema estarão em `logs/YYYY-MM-DD.log`

---

Se algum passo falhar, revise permissões, dependências e configurações. Consulte o README original para detalhes avançados. 

# Testes Automáticos Básicos Sugeridos

O sistema já possui alguns scripts de teste manual em PHP, como `frontend/teste_usuarios.php` e `frontend/teste_menu_permissoes.php`. Para garantir o funcionamento das principais funcionalidades, recomenda-se:

## 1. Testes de API (usando Postman ou PHPUnit)
- Testar login (`/backend/api/auth.php`)
- Testar criação/listagem de usuários
- Testar criação/listagem de ocorrências
- Testar permissões de acesso (usuário comum x admin)
- Testar upload de arquivos
- Testar geração de PDF
- Testar envio de e-mail

## 2. Testes de Interface (scripts PHP já existentes)
- `frontend/teste_usuarios.php`: Verifica autenticação, permissões e acesso à gestão de usuários
- `frontend/teste_menu_permissoes.php`: Simula o menu lateral conforme permissões do usuário

## 3. Teste de Ambiente
- Execute `verificar_banco.php` para checar dependências, banco e permissões

## 4. Sugestão de Automação
- Crie testes automatizados com PHPUnit para as principais classes do backend (Auth, Controllers)
- Use ferramentas como Postman/Newman para rodar coleções de testes de API
- Automatize a execução dos scripts de teste PHP após cada alteração relevante

---

**Dica:** Sempre rode os scripts de teste após alterações importantes para garantir a integridade do sistema. 