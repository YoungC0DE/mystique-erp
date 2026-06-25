# Instalação

O Mystique CRM é **self-hosted**: cada cliente roda sua própria instância via Docker. Uma instalação corresponde a **uma organização** (single-tenant).

## Pré-requisitos

- Docker e Docker Compose
- Portas livres: `8000`, `5173`, `3306`, `6379`, `8080` (e `9090` se usar a demo de callback)

## Subir o ambiente

```bash
git clone <repo-url> mystique-crm
cd mystique-crm
docker compose up -d --build
```

- **API:** http://localhost:8000
- **Frontend (dev):** http://localhost:5173
- **Área pública:** Home, Documentação, Entrar, Registrar

## Setup inicial (primeira execução)

```bash
docker compose exec backend cp .env.example .env
docker compose exec backend composer install
docker compose exec backend php artisan key:generate
docker compose exec backend php artisan migrate
docker compose exec backend php artisan db:seed          # permissões + módulo Pedidos
docker compose exec backend php artisan app:ensure-passport-password-client
docker compose exec backend php artisan app:create-admin
```

O comando `app:create-admin` solicita nome, e-mail e senha do administrador da instalação.

## Primeiro Admin

| Ambiente | Recomendação |
|----------|--------------|
| **Produção** | `REGISTRATION_ENABLED=false` + `app:create-admin` |
| **Desenvolvimento** | `REGISTRATION_ENABLED=true` — o **primeiro** usuário registrado vira Admin automaticamente |

## Variáveis de ambiente (`backend/.env`)

| Variável | Default | Descrição |
|----------|---------|-----------|
| `REGISTRATION_ENABLED` | `false` | Habilita `POST /api/auth/register` e página `/registrar` |
| `STAGE_CALLBACK_TIMEOUT` | `15` | Timeout (s) da requisição HTTP ao mover card |
| `STAGE_CALLBACK_CONNECT_TIMEOUT` | `5` | Timeout de conexão do callback |
| `DB_*` | — | Banco da aplicação (MySQL) |
| `PASSPORT_PASSWORD_CLIENT_*` | — | Gerado por `app:ensure-passport-password-client` |
| `REVERB_*` | — | WebSocket para atualização em tempo real do board |

Frontend local: copie `frontend/.env.example` para `frontend/.env` se rodar `npm run dev` fora do Docker.

## Testes

```bash
# Criar banco isolado (uma vez)
docker compose exec -T mysql sh < backend/tests/setup-test-db.sh

docker compose exec backend php artisan test
docker compose exec frontend npm test
```

## Próximos passos

1. [Criar conexão de banco](integration.md#conexões)
2. [Configurar módulo integrado](modules.md)
3. [Implementar callback de etapa](callback.md) no sistema do cliente

Para testar sem ERP, use a [demo de pedidos](demo/README.md).
