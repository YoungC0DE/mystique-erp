# Módulos

Cada módulo representa um board Kanban ligado a uma **tabela externa** e, opcionalmente, a um **callback** para mudança de etapa.

## Módulo padrão: Pedidos

Provisionado no `db:seed` da instalação (`slug: pedidos`). Inicialmente **sem conexão** — o Admin associa conexão, colunas e callback em **Módulos → Editar**.

Novos módulos criados pela interface exigem conexão, colunas e coluna de status desde a criação.

## Estrutura

| Campo | Descrição |
|-------|-----------|
| Nome / slug / ícone | Identificação na sidebar (ícones **Lucide**, kebab-case — ex.: `shopping-cart`) |
| Conexão | Banco + tabela de leitura (obrigatório para board integrado) |
| Colunas selecionadas | Campos exibidos nos cards (`key` = nome da coluna externa) |
| Coluna de status | Campo na tabela externa que guarda a etapa (`status_column`) |
| Status Kanban | Etapas configuráveis (label + `external_value` para leitura e callback) |
| URL de callback | Endpoint HTTP do cliente ao mover card |
| Método HTTP | `POST`, `PUT` ou `PATCH` (padrão `POST`) |

## Status padrão do Kanban

| Etapa | Valor externo (`external_value`) |
|-------|----------------------------------|
| Inputar | Inputar |
| Em Andamento | Em Andamento |
| Aprovados | Aprovados |
| Reprovados | Reprovados |

O board filtra registros pela coluna de status usando o `external_value` de cada etapa. Ao mover um card, o callback envia esses valores (não o slug interno).

## Colunas nos cards

1. Admin escolhe a **conexão** (já define a tabela)
2. Informa a **coluna de status** (deve existir na tabela e estar entre as colunas selecionadas)
3. Seleciona colunas existentes na tabela
4. Ajusta ordem e visibilidade (card, listagem, detalhe) em **Módulos → Configurar layout** (`/m/:slug/config`)

Regra: o **`key`** do campo no CRM deve ser **igual ao nome da coluna** na tabela externa.

## Registros integrados — somente leitura

No board de módulo integrado o CRM **não** permite:

- Criar registro de negócio
- Editar campos de negócio no banco externo
- Excluir registro

A **única mutação** no banco do cliente é mover o card entre colunas → dispara o callback HTTP.

### Notas locais

O CRM pode guardar **anotações** por card (`module_record_notes`). Isso fica apenas no banco da aplicação e **não altera** dados do ERP.

## Modo sem integração (legado)

Módulos sem conexão configurada usam registros internos (EAV). O módulo **Pedidos** inicia assim até o Admin completar a integração. Esse modo permite CRUD local; não é o fluxo principal do produto.

## Permissões

Permissões globais (`create`, `read`, `update`, `delete`) controlam o que o usuário pode fazer no CRM. Mover card integrado exige `update` ou ser **Admin**. Conexões de banco exigem **Admin** (menu Configurações).

## Relatórios

Relatórios associados a um módulo podem ser criados em **Relatórios** no menu lateral, com colunas e filtros sobre os dados do módulo.

## Auditoria

O CRM registra movimentações de etapa e resultado do callback (sucesso/falha). Não replica auditoria campo a campo dos dados externos.
