# Demo — Pedidos + callback mock

Ambiente de demonstração sem ERP: tabela MySQL de pedidos e servidor HTTP que persiste mudanças de etapa.

## 1. Criar a tabela de exemplo

```bash
docker compose exec -T mysql mysql -umystique -psecret mystique < docs/demo/pedidos.sql
```

## 2. Subir o callback mock

Com Docker Compose (serviço `demo-callback` na porta **9090**):

```bash
docker compose up -d demo-callback
```

Ou manualmente:

```bash
cd docs/demo
php -S 0.0.0.0:9090 callback-server.php
```

Health check: `GET http://localhost:9090/health`

## 3. Configurar no Mystique

### Conexão (Configurações)

| Campo | Valor (Docker) |
|-------|----------------|
| Host | `mysql` |
| Porta | `3306` |
| Database | `mystique` |
| Usuário / senha | conforme `.env` |
| Tabela | `demo_pedidos` |

### Módulo Pedidos

| Campo | Valor |
|-------|-------|
| Conexão | a criada acima |
| Coluna de status | `status` |
| Colunas | `cliente`, `valor_total`, `status` |
| Callback URL | `http://demo-callback:9090/` (backend → container na rede Docker) |

Status Kanban: use os padrões (Inputar, Em Andamento, Aprovados, Reprovados) com `external_value` igual aos valores na tabela (`Inputar`, `Em Andamento`, etc.).

## 4. Testar

1. Abra o board **Pedidos**
2. Arraste um card para outra coluna
3. Verifique no MySQL: `SELECT id, cliente, status FROM demo_pedidos;`

O callback mock atualiza a coluna `status` na tabela externa — simulando o ERP do cliente.
