# Módulos

## Pedidos (padrão)

Provisionado no `ModuleInstallSeeder` com slug `pedidos`, ícone `shopping-cart`,
sem conexão inicial. O Admin configura integração em **Módulos → Editar**.

## Estrutura de um módulo

| Campo / relação | Descrição |
|-----------------|-----------|
| `name`, `slug`, `icon`, `status` | Identificação e sidebar (ícone Lucide kebab-case) |
| `connection_id` | Conexão obrigatória para board integrado |
| `status_column` | Coluna na tabela externa que guarda a etapa |
| `callback_url`, `callback_method` | Callback HTTP ao mover card (POST/PUT/PATCH) |
| `module_fields` | Colunas exibidas; `key` = nome da coluna externa |
| `module_kanban_statuses` | Etapas do Kanban (slug, label, order, `external_value`) |
| `detail_layout` | JSON opcional — grupos de campos na visualização detalhada |
| Permissões | Via roles/usuários do CRM (`create`, `read`, `update`, `delete`) |

### Campos (`module_fields`)

* `key` — alias interno = **nome da coluna** na tabela externa
* `label`, `type`, `order`, `required`, `options`
* Flags de exibição: `show_in_card`, `show_in_list`, `show_in_detail`, `highlighted`, `visible`

Na criação/edição do módulo:

1. Escolher **conexão** (tabela definida em `database_connections.table_name`)
2. Informar **`status_column`** (deve existir na tabela e estar entre as colunas selecionadas)
3. Selecionar **colunas** para cards/listagem
4. Configurar **status Kanban** e **callback**

Validação (`ModuleIntegrationValidator`): colunas e `status_column` devem existir na tabela externa.

## Kanban — status (etapas)

Status padrão na criação (`DefaultKanbanStatuses`):

| slug | label | external_value |
|------|-------|----------------|
| `inputar` | Inputar | Inputar |
| `em_andamento` | Em Andamento | Em Andamento |
| `aprovados` | Aprovados | Aprovados |
| `reprovados` | Reprovados | Reprovados |

O Admin pode registrar outros status. Cada um mapeia `external_value` ao valor na
coluna `status_column` da tabela externa.

## Board integrado

* Endpoint: `GET /api/modules/{module}/kanban`
* Leitura via `ExternalBoardReader` — `SELECT` na tabela externa, filtrado por `status_column = external_value`
* Paginação por coluna (`{slug}_page`, `per_page`)
* Filtros: `q` (busca textual), `filters` (JSON de filtros por campo)

## Alteração de etapa (callback)

* Arrastar card **não** grava no banco externo pelo CRM
* Endpoint: `POST /api/modules/{module}/records/{externalId}/move`
* Backend chama `callback_url` com payload JSON
* Requer permissão `update` ou `is_admin`
* Falha no callback: HTTP 422/502, log em `activity_logs`, UI reverte movimento otimista

Payload enviado:

```json
{
  "record_id": 12345,
  "status": "Em Andamento",
  "previous_status": "Inputar",
  "module_slug": "pedidos"
}
```

(`status` / `previous_status` usam `external_value`, não o slug interno.)

## Notas locais (CRM)

* Tabela `module_record_notes` — texto por `(module_id, record_key)`
* Endpoints: `GET/PUT /api/modules/{module}/records/{recordKey}/note`
* Não altera dados do banco externo

## Modo EAV (sem integração)

Se `connection_id` é null, o board usa `module_records` + `record_values`:

* `GET /api/modules/{module}/kanban` — consulta EAV interno
* `PUT /api/records/{record}/move` — move card localmente
* CRUD completo via `/api/modules/{module}/records`

## O que o CRM não faz em registros integrados

* Criar registros de negócio (dados nascem no sistema do cliente)
* Editar campos de negócio no banco externo
* Excluir registros externos

## Layout

* Campos no card = colunas com `show_in_card`
* Listagem = subconjunto com `show_in_list`
* Detalhe = `show_in_detail` + `detail_layout` (grupos configuráveis em `/m/:slug/config`)
* API: `PUT /api/modules/{module}/layout`

## Relatórios

Relatórios (`reports`) associados a um módulo, com `field_keys` e `filters`.
Consultam dados do módulo (integrado ou EAV). Ver `.cursor/docs/api.md`.

## Auditoria

* Callback: `RECORD_STAGE_CALLBACK_SENT/SUCCESS/FAILED` em `activity_logs`
* Movimentos EAV: `record_audits` + `RECORD_MOVED`
* Não manter histórico campo a campo de dados externos
