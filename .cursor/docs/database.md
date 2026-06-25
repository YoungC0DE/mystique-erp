# Banco de Dados

## Visão geral

O Mystique utiliza **dois níveis de dados**:

| Onde | O que armazena |
|------|----------------|
| **Banco da aplicação** (MySQL da instância) | Usuários, roles, permissões, módulos, campos, conexões, status Kanban, notas locais, relatórios, logs |
| **Banco externo** (configurado pelo cliente) | Dados operacionais (ex.: pedidos) — fonte de verdade do negócio |

Não há multi-empresa: uma instalação possui um único contexto organizacional.
A tabela `companies` foi **removida**; `company_id` não existe mais nas entidades.

## Banco da aplicação

Armazena metadados e configuração:

* `users` — incluindo `locale` (default `pt-BR`), `is_admin`
* `roles`, `permissions` — CRUD slugs globais (sem `company_id`)
* `database_connections` — credenciais criptografadas (`password` cast `encrypted`)
* `modules` — configuração de integração e layout
* `module_fields` — colunas mapeadas (`key` = nome externo)
* `module_kanban_statuses` — etapas por módulo
* `module_record_notes` — anotações locais por registro integrado
* `reports` — definições de relatório por módulo
* `module_records` + `record_values` — modo EAV legado (módulos sem conexão)
* `activity_logs`, `record_audits` — auditoria de ações no CRM

Toda alteração estrutural deve ser feita via migrations.

### Conexões (`database_connections`)

| Coluna | Descrição |
|--------|-----------|
| `name` | Nome amigável |
| `host`, `port`, `database`, `username`, `password` | Credenciais (senha criptografada) |
| `table_name` | Tabela usada por conexões/módulos associados |

Regras:

* Validação obrigatória ao criar/editar: teste de conectividade
* Pode haver **várias** conexões
* Acesso restrito a **Admin** (`DatabaseConnectionPolicy`)

### Módulos (`modules`)

Campos de integração:

* `connection_id` — FK nullable (`nullOnDelete`)
* `status_column` — coluna de etapa na tabela externa
* `callback_url`, `callback_method` (default `POST`)
* `detail_layout` — JSON de grupos de campos

Relacionamentos:

* `hasMany` → `module_fields`, `module_kanban_statuses`, `reports`
* `belongsTo` → `database_connections`

### Status Kanban (`module_kanban_statuses`)

* `slug`, `label`, `order`, `external_value`
* Unique `(module_id, slug)`

## Banco externo (integração nativa)

### Leitura

* `ExternalBoardReader` consulta `database_connections.table_name`
* Cada card = linha identificada por **`id`** (PK)
* Colunas exibidas = `module_fields.key` presentes no `SELECT`
* Filtro por coluna: `WHERE status_column = external_value`

### Escrita (limitada)

* **Proibido** no CRM: editar campos de negócio e excluir registros integrados
* **Permitido**: callback HTTP ao mover card (`StageCallbackService`)
* **Permitido no CRM**: notas locais em `module_record_notes`

### Validação

Ao associar colunas (`ModuleIntegrationValidator`):

* Colunas selecionadas devem existir na tabela
* `status_column` deve existir e estar entre as colunas selecionadas

## Modo EAV (legado)

Tabelas `module_records` e `record_values` permanecem para módulos sem `connection_id`.
Não é o caminho principal para novos módulos (criação exige conexão na API).

## Remoções concluídas

* `companies`, `company_id`, planos `free`/`pro` — **removidos**
* `is_super_admin` → renomeado para `is_admin`
* Multi-empresa e feature gating por plano — **removidos**

## Futuro

* Cache local de leitura para boards muito grandes
* Sincronização bidirecional opcional (V3+)
* `avatar_path` em `users`
