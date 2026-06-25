# Visão do Produto

Nome: **Mystique CRM**

Projeto **open-source**, self-hosted, inspirado no modelo de implantação do Strapi:
cada cliente roda sua própria instância (Docker) no ambiente dele. Não há SaaS
multi-tenant centralizado nem planos pagos no escopo atual.

## Propósito

Plataforma modular de gestão de processos baseada em Kanban, com foco em
**integração nativa via banco de dados** — leitura direta das tabelas do cliente,
sem API externa de integração de dados (evita delay).

Objetivo imediato: **portfólio open-source** para a comunidade. Monetização
futura (ex.: edição Enterprise) fica fora do escopo atual.

## Single-tenant

Uma instalação = **uma organização**. Não existe multi-empresa, menu Empresas
nem operador de plataforma. O administrador da instalação (`is_admin`) gerencia
usuários, módulos, conexões e configurações localmente.

A tabela `companies` e `company_id` foram **removidos** (migration single-tenant).

## Módulos

O primeiro módulo provisionado no seed é **Pedidos** (`slug: pedidos`), inicialmente
**sem conexão** — o Admin associa conexão, colunas e callback em **Módulos → Editar**.

Novos módulos criados pela UI exigem conexão, colunas e `status_column` desde a criação.

Cada módulo possui:

* Nome, slug, ícone (**Lucide**, kebab-case — ex.: `shopping-cart`) e status (ativo/inativo)
* Conexão de banco associada (`connection_id`)
* Colunas da tabela externa (`module_fields`, `key` = nome da coluna)
* Coluna de status externa (`status_column`)
* Status do Kanban em `module_kanban_statuses` (slug, label, order, `external_value`)
* URL e método de callback (`callback_url`, `callback_method`: POST/PUT/PATCH)
* Layout de exibição (`show_in_card`, `show_in_list`, `show_in_detail`, `detail_layout`)
* Permissões CRUD (`create`, `read`, `update`, `delete`) via roles/usuários

## Integração de dados

* Dados operacionais vêm do **banco externo** configurado pelo cliente.
* Registros integrados são **somente leitura** no CRM (não editar/excluir campos de negócio).
* A única mutação no board integrado é **mudança de etapa**, via **callback HTTP**.
* Registros no board são identificados pelo **`id`** da linha na tabela externa.
* **Notas locais** (`module_record_notes`) podem ser salvas no CRM sem alterar o banco externo.

## Modo legado (EAV interno)

Módulos **sem** `connection_id` ainda usam `module_records` + `record_values` (EAV).
O fluxo principal do produto é integração externa; o EAV permanece para compatibilidade
e para o módulo Pedidos antes da configuração.

## Área pública (não autenticada)

* **Home** (`/`) — resumo do projeto
* **Documentação** (`/documentacao`) — guias de instalação e integração
* Header com **Entrar** (`/entrar`) e **Registrar** (`/registrar`, condicionado a env)

## Área autenticada

Sidebar — gestão:

* Dashboard, Módulos, Usuários, Roles e Permissões, Relatórios

Sidebar — módulos dinâmicos (boards ativos permitidos ao usuário).

Dropdown do usuário:

* **Configurações** (`/configuracoes`) — conexões de banco (**somente Admin**)
* **Perfil** (`/perfil`) — nome, e-mail, idioma, senha
* **Sair**

Configuração de integração por módulo (conexão, colunas, status, callback): **Módulos → Editar**
e layout de campos em **`/m/:slug/config`**.

## Conta e personalização

* Internacionalização pt-BR / en (`users.locale`, default `pt-BR`)
* Registro controlado por `REGISTRATION_ENABLED`
* Primeiro usuário registrado vira Admin automaticamente
* Admin bootstrap via `php artisan app:create-admin`
* Ícones: **Lucide** (componente `Icon`, nomes kebab-case)

## Futuro (fora do escopo atual)

Home com fluxograma interativo, foto de perfil (`avatar_path`), colunas Kanban
totalmente customizáveis, upload/comentários em cards, automações e possível
edição Enterprise paga — sem feature gating por plano hoje.
