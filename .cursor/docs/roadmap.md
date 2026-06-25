# Roadmap

## MVP — concluído / em produção no código

- [x] Single-tenant (`company_id` e `companies` removidos; `is_admin`)
- [x] Login como entrada da aplicação (`/`); aliases `/entrar`, `/login`
- [x] Docker + Docker Compose
- [x] Área autenticada sem menu Empresas
- [x] Dropdown: Configurações (Admin), Perfil, Sair
- [x] Usuários, roles, permissões CRUD, activity logs
- [x] Módulos dinâmicos + Kanban (Reverb/Echo)
- [x] Ícones Lucide (migrados de Material Symbols)
- [x] Conexões: CRUD, validação, múltiplas conexões, `table_name`
- [x] Módulo: conexão, colunas, `status_column`, status customizáveis, callback
- [x] Board integrado: leitura direta da tabela externa por `id`
- [x] Mudança de etapa via callback HTTP
- [x] Admin bootstrap (`app:create-admin`)
- [x] Documentação no README e pasta `docs/` do repositório
- [x] Relatórios básicos (`reports` + UI)
- [x] Dashboard (página inicial autenticada)
- [x] Layout de campos (`detail_layout`, flags show/highlight)
- [x] Notas locais em registros integrados (`module_record_notes`)

## Refatoração — concluída

- [x] Remover multi-empresa, planos, `EnsureProPlan`
- [x] `is_super_admin` → `is_admin`
- [x] Leitura de banco externo nos boards integrados (`ExternalBoardReader`)
- [x] Configurações (conexões) separadas de Perfil
- [x] EAV mantido apenas para módulos sem conexão (legado / Pedidos inicial)

## Em andamento / parcial

- [ ] Cache e paginação otimizada para tabelas externas muito grandes

## Pós-MVP

* Foto de perfil (`avatar_path`)
* Upload de arquivos em cards
* Comentários em cards (além de notas simples)
* Dashboard com métricas agregadas
* Relatórios avançados (export, agendamento)

## V3+

* Automações e regras de negócio
* Integração WhatsApp / E-mail
* Sync bidirecional opcional com banco externo
* Marketplace de módulos (comunidade)

## Monetização (futuro, fora do escopo)

Possível edição Enterprise (suporte, SSO, auditoria avançada) — sem implementar
planos ou gating no open-source atual.
