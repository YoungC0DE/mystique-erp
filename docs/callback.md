# Callback de etapa

Quando o usuário **move um card** no board de um módulo integrado, o backend do Mystique chama a **URL de callback** configurada no módulo. O CRM **não** grava a etapa no banco externo — isso é responsabilidade do sistema do cliente.

## Endpoint (no sistema do cliente)

Configurado por módulo: URL + método HTTP (padrão `POST`).

### Payload de referência

```json
{
  "record_id": 12345,
  "status": "Em Andamento",
  "previous_status": "Inputar",
  "module_slug": "pedidos"
}
```

| Campo | Descrição |
|-------|-----------|
| `record_id` | `id` da linha na tabela externa |
| `status` | `external_value` do **novo** status Kanban (valor na coluna de status da tabela externa) |
| `previous_status` | `external_value` do status anterior |
| `module_slug` | Slug do módulo no CRM |

### Respostas esperadas

| HTTP | Comportamento no CRM |
|------|----------------------|
| `2xx` | Sucesso; board mantém a nova coluna |
| `4xx` | Erro 422; UI reverte o movimento |
| `5xx` / timeout | Erro 502; UI reverte o movimento |

Timeouts configuráveis em `STAGE_CALLBACK_TIMEOUT` e `STAGE_CALLBACK_CONNECT_TIMEOUT`.

## Implementação mínima (exemplo)

```php
// POST /api/pedidos/stage
$payload = json_decode(file_get_contents('php://input'), true);
$recordId = (int) $payload['record_id'];
$newStatus = $payload['status'];

// Persistir no ERP / atualizar coluna de etapa
$db->prepare('UPDATE pedidos SET status = ? WHERE id = ?')
   ->execute([$newStatus, $recordId]);

http_response_code(200);
echo json_encode(['ok' => true]);
```

## Segurança

- Use **HTTPS** em produção
- Valide origem/autenticação no endpoint do cliente (token, IP allowlist, etc.)
- O Mystique não segue redirects abertos no callback

## Demo

Servidor mock para desenvolvimento: [docs/demo](demo/README.md).
