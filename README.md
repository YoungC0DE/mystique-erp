# Mystique CRM

[![License: MIT](https://img.shields.io/badge/License-MIT-purple.svg)](LICENSE)

Plataforma modular **open-source** e **self-hosted** para gestão de processos baseada em Kanban. Cada cliente implanta sua própria instância (modelo Strapi): **single-tenant**, sem planos pagos.

Dados operacionais vêm do **banco externo** do cliente (leitura direta). A única mutação em registros integrados é a **mudança de etapa**, via **callback HTTP** para a API do cliente.

> Documentação: [`docs/`](docs/) · Especificação dev: `.cursor/docs` · Progresso: [`board.md`](board.md)

## Funcionalidades

- Kanban com status configuráveis (Inputar · Em Andamento · Aprovados · Reprovados)
- Módulos dinâmicos com conexão MySQL, colunas e callback
- Usuários, roles e permissões CRUD
- Landing pública (Home, Documentação) + registro condicional
- Tempo real (Laravel Reverb + Echo)
- pt-BR / en

## Stack

| Camada          | Tecnologias                                              |
| --------------- | ------------------------------------------------------- |
| Frontend        | Vue 3, TypeScript, Pinia, Vue Router, Vite, Tailwind v4, Laravel Echo |
| Backend         | Laravel 12, Passport (OAuth2), MySQL, Redis, Reverb      |
| Infraestrutura  | Docker, Docker Compose                                   |

## Estrutura

```
.
├── backend/            # Laravel 12 (API)
├── frontend/           # Vue 3 + Vite (SPA)
├── docs/               # Guias de instalação e integração
├── docker/
│   ├── php/            # Dockerfile do PHP-FPM (backend e reverb)
│   ├── nginx/          # Configuração do Nginx
│   └── node/           # Dockerfile do frontend (Vite)
├── docker-compose.yml
└── .cursor/            # Regras e documentação para desenvolvimento
```

## Serviços (docker-compose)

| Serviço        | Porta  | Descrição                        |
| -------------- | ------ | -------------------------------- |
| nginx          | 8000   | Entrada HTTP do backend          |
| backend        | 9000   | PHP-FPM (Laravel)                |
| frontend       | 5173   | Vite dev server                  |
| mysql          | 3306   | Banco de dados                   |
| redis          | 6379   | Cache, fila e broadcast          |
| reverb         | 8080   | WebSocket (tempo real)           |
| demo-callback  | 9090   | Mock de callback (demo pedidos)  |

## Como rodar

Pré-requisitos: Docker e Docker Compose.

```bash
docker compose up -d --build
```

- Backend: http://localhost:8000
- Frontend: http://localhost:5173

Guia completo: [docs/installation.md](docs/installation.md)

### Setup inicial do backend (primeira execução)

```bash
docker compose exec backend cp .env.example .env
docker compose exec backend composer install
docker compose exec backend php artisan key:generate
docker compose exec backend php artisan migrate
docker compose exec backend php artisan db:seed
docker compose exec backend php artisan app:ensure-passport-password-client
docker compose exec backend php artisan app:create-admin
```

### Demo local (pedidos + callback)

```bash
docker compose exec -T mysql mysql -umystique -psecret mystique < docs/demo/pedidos.sql
docker compose up -d demo-callback
```

Configure a conexão e o módulo Pedidos conforme [docs/demo/README.md](docs/demo/README.md).

### Variáveis importantes (`backend/.env`)

| Variável | Default | Descrição |
| -------- | ------- | --------- |
| `REGISTRATION_ENABLED` | `false` | Registro público; se não houver usuários, o primeiro vira Admin |
| `STAGE_CALLBACK_TIMEOUT` | `15` | Timeout (s) do callback HTTP ao mover card |
| `STAGE_CALLBACK_CONNECT_TIMEOUT` | `5` | Timeout de conexão do callback |
| `DB_*` | — | Banco da aplicação |
| `PASSPORT_PASSWORD_CLIENT_*` | — | Gerado por `app:ensure-passport-password-client` |

## Integração

1. Admin cria **conexão** (host, tabela, validação)
2. Cria/edita **módulo** (colunas, status, URL de callback)
3. Board lê a tabela externa; mover card chama o callback do cliente

Detalhes: [docs/integration.md](docs/integration.md) · [docs/callback.md](docs/callback.md)

## Testes

### Backend (PHPUnit)

O banco de testes é `mystique_test` (mesmo host/credenciais do `.env`).

```bash
docker compose exec -T mysql sh < backend/tests/setup-test-db.sh
docker compose exec backend php artisan test
```

### Frontend (Vitest + vue-tsc)

```bash
docker compose exec frontend npm run test
docker compose exec frontend npm run build
```

## Primeiro Admin

- **Produção:** `php artisan app:create-admin` (com `REGISTRATION_ENABLED=false`)
- **Desenvolvimento:** `REGISTRATION_ENABLED=true` — o primeiro usuário registrado vira Admin

## Contributing

Contribuições são bem-vindas.

1. Faça fork do repositório
2. Crie uma branch (`git checkout -b feat/minha-feature`)
3. Commit com mensagem clara do **porquê** da mudança
4. Garanta que os testes passam (`php artisan test`, `npm test`)
5. Abra um Pull Request

Diretrizes: siga as convenções em `.cursor/rules/`, mantenha escopo mínimo e documente mudanças de integração em `docs/`.

## Licença

[MIT](LICENSE) — veja [CHANGELOG.md](CHANGELOG.md) para breaking changes da migração SaaS → OSS.
