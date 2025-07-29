# Configuração para GitHub Pages

## URL do Sistema
**URL Principal:** https://guardacivil.github.io/guardacivil/

**URLs Específicas:**
- Frontend: https://guardacivil.github.io/guardacivil/frontend/
- Backend: https://guardacivil.github.io/guardacivil/backend/
- Uploads: https://guardacivil.github.io/guardacivil/uploads/

## Configurações Aplicadas

### 1. Banco de Dados
- ✅ Configurado para SQLite (`database/smart_system.db`)
- ✅ Removidas referências ao MySQL
- ✅ Atualizado `frontend/config.php` para SQLite

### 2. URLs do Sistema
- ✅ Atualizado `backend/config.php` com nova URL base
- ✅ Corrigidas referências hardcoded em arquivos PHP
- ✅ Atualizado `frontend/ver_ocorrencia.php` para caminhos relativos

### 3. Verificação do Sistema
- ✅ Atualizado `frontend/verificar_banco.php` para SQLite
- ✅ Configurado para verificar diretórios e permissões

### 4. Documentação
- ✅ Atualizado `README.md` com nova URL
- ✅ Removidas referências mobile/PWA

## Limitações do GitHub Pages

⚠️ **Importante:** O GitHub Pages tem algumas limitações:

1. **PHP não é suportado** - O GitHub Pages só serve arquivos estáticos
2. **Banco de dados** - Não há suporte a SQLite ou MySQL
3. **Uploads** - Não há suporte a uploads de arquivos
4. **Sessões** - Não há suporte a sessões PHP

## Soluções Alternativas

Para usar este sistema no GitHub Pages, você precisará:

1. **Converter para JavaScript/HTML** - Transformar o sistema em uma aplicação frontend
2. **Usar APIs externas** - Conectar com serviços como Firebase, Supabase, etc.
3. **Usar GitHub Actions** - Para processamento server-side
4. **Deploy em servidor PHP** - Usar serviços como Heroku, Vercel, etc.

## Arquivos de Configuração

### Configurações Atualizadas:
- `backend/config.php` - URLs do sistema
- `frontend/config.php` - Conexão SQLite
- `frontend/verificar_banco.php` - Verificação SQLite
- `README.md` - Documentação atualizada

### Arquivos Removidos (Mobile):
- `frontend/dashboard_mobile.php`
- `frontend/manifest.json`
- `frontend/service-worker.js`
- Todos os arquivos Flutter da raiz

## Próximos Passos

1. **Testar localmente** com as novas configurações
2. **Converter para aplicação web** se necessário
3. **Configurar CI/CD** para deploy automático
4. **Documentar processo** de migração

---
**Última atualização:** $(date)
**Versão:** 2.0.0
**Status:** Configurado para GitHub Pages 