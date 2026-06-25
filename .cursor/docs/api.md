# API

Base: `/api` · Autenticação: Laravel Passport (OAuth2 password grant)

Access token expira em 1 hora; refresh token obrigatório.

## Escopo

A API REST serve o **frontend SPA**. Não há API pública de integração de dados —
registros integrados vêm do **banco externo** configurado pelo cliente.

A única integração HTTP **saindo** do Mystique é o **callback de etapa** ao mover card.

## Autenticação

| Método | Rota | Auth |
|--------|------|------|
| POST | `/auth/login` | — |
| POST | `/auth/register` | — (se `REGISTRATION_ENABLED`) |
| POST | `/auth/refresh` | — |
| POST | `/auth/logout` | sim |
| GET | `/auth/me` | sim |

## Perfil (auto-serviço)

| Método | Rota | Descrição |
|--------|------|-----------|
| PUT | `/me` | nome, e-mail, `locale` |
| PUT | `/me/password` | troca de senha |

> O usuário só altera a si mesmo; não depende de permissão CRUD.

## Conexões de banco (Admin)

Policy: `is_admin` obrigatório.

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/connections` | listar |
| POST | `/connections` | criar (testa conectividade) |
| GET | `/connections/{connection}` | detalhe |
| PUT | `/connections/{connection}` | atualizar (revalida credenciais) |
| DELETE | `/connections/{connection}` | remover |
| POST | `/connections/validate` | testar payload sem salvar |
| POST | `/connections/{connection}/test` | testar conexão existente |
| GET | `/connections/{connection}/columns` | colunas da `table_name` |

Campos: `name`, `host`, `port`, `database`, `username`, `password`, `table_name`

## Módulos

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/modules` | listar (paginado) |
| POST | `/modules` | criar (exige `connection_id`, `status_column`, `columns`, `statuses` opcional) |
| GET | `/modules/{module}` | detalhe |
| PUT | `/modules/{module}` | atualizar |
| DELETE | `/modules/{module}` | remover |
| GET | `/me/modules` | módulos ativos permitidos (navbar) |
| PUT | `/modules/{module}/layout` | flags de exibição + `detail_layout` |

### Campos do módulo

| Método | Rota |
|--------|------|
| GET/POST | `/modules/{module}/fields` |
| GET/PUT/DELETE | `/fields/{field}` |

## Kanban / registros

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/modules/{module}/kanban` | board (integrado ou EAV); query: `per_page`, `{slug}_page`, `q`, `filters` |
| POST | `/modules/{module}/records/{externalId}/move` | move integrado + callback |
| PUT | `/records/{record}/move` | move EAV interno |

### Registros EAV (módulos sem conexão)

| Método | Rota |
|--------|------|
| GET/POST | `/modules/{module}/records` |
| GET/PUT/DELETE | `/records/{record}` |
| GET | `/records/{record}/audits` |

> Registros integrados: **sem** CRUD de campos de negócio.

### Notas locais (integrado)

| Método | Rota |
|--------|------|
| GET | `/modules/{module}/records/{recordKey}/note` |
| PUT | `/modules/{module}/records/{recordKey}/note` |

Body: `{ "body": "..." }`

## Relatórios

| Método | Rota |
|--------|------|
| GET/POST | `/reports` |
| GET/PUT/DELETE | `/reports/{report}` |
| GET | `/reports/{report}/run` | query: `page`, `per_page` |

## Roles & Permissions

| Método | Rota |
|--------|------|
| GET | `/permissions` |
| GET/POST | `/roles` |
| GET/PUT/DELETE | `/roles/{role}` |
| GET/POST/PUT/DELETE | `/users` |

Permissões: `create`, `read`, `update`, `delete`. Admin (`is_admin`) bypassa checks.

### Payloads unificados

Grupos (roles) e permissões de usuário são enviados **no mesmo body** do create/update — não há endpoints separados de vínculo.

#### `POST /users`

```json
{
  "name": "string",
  "email": "string",
  "password": "string",
  "is_admin": false,
  "roles": ["role-uuid-1"],
  "permissions": ["permission-uuid-1"]
}
```

`roles` e `permissions` são opcionais (arrays de UUIDs). Persistência ocorre em uma única transação.

#### `PUT /users/{user}`

Mesmos campos do create; `password` opcional; `roles` / `permissions` substituem os vínculos atuais quando presentes (array vazio remove todos).

#### `POST /roles`

```json
{
  "name": "string",
  "slug": "opcional",
  "permissions": ["permission-uuid-1"]
}
```

#### `PUT /roles/{role}`

```json
{
  "name": "string",
  "slug": "opcional",
  "permissions": ["permission-uuid-1"]
}
```

`permissions` substitui o conjunto vinculado quando presente.

### Cache estrutural

Consultas frequentes usam cache persistente (invalidado apenas em create/update/delete):

| Domínio | Endpoints beneficiados |
|---------|------------------------|
| Módulos | `GET /modules/{module}`, `GET /me/modules` |
| Relatórios | `GET /reports`, `GET /reports/{report}` |
| Roles / permissões | `GET /permissions`, `GET /roles`, `GET /roles/{role}` |
| Usuários | `GET /users/{user}`, `GET /auth/me`, `GET /me` |

## Tempo real

| Método | Rota |
|--------|------|
| POST | `/broadcasting/auth` | auth Reverb/Echo |

Evento `RecordMoved` ao mover card (integrado ou EAV).

## Callback de etapa (contrato do cliente)

Endpoint **no sistema do cliente**, chamado pelo Mystique.

Request (JSON, método configurável — default POST):

```json
{
  "record_id": 12345,
  "status": "Em Andamento",
  "previous_status": "Inputar",
  "module_slug": "pedidos"
}
```

Respostas:

* `2xx` — sucesso
* `4xx` — rejeitado (CRM responde 422)
* `5xx` / timeout — falha (CRM responde 502)

Timeout: `config('mystique.stage_callback.timeout')` (default 15s)

## Removidos do escopo

* CRUD de empresas multi-tenant
* Endpoints de plano (`free` / `pro`)
* API Token por módulo para terceiros
* Webhooks de integração (futuro opcional)
* Endpoints de vínculo isolado: `PUT /roles/{role}/permissions`, `PUT /users/{user}/roles`, `PUT /users/{user}/permissions` (substituídos por payloads unificados em create/update)
