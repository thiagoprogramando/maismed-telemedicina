# Admin consultores, dashboard e configuração — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Dar ao administrador gestão completa de consultores (links, clientes, transferência com e-mail), dashboard com métricas e filtros 7/15/30, menu Administração (ícone de config) com Gateway, e exclusão em massa segura em `/sales`.

**Architecture:** Continuar Laravel/Blade/SB Admin. Consultores = `users` com `roles` em `admin|collaborator`. Clientes = `sales` via `seller_id`. Transferência atualiza `seller_id` (+ e-mail). Dashboard agrega vendas/faturas/usuários no `AppController`. Menu: item Administração (só admin) agrupando Pessoas/Consultores/Gateway.

**Tech Stack:** Laravel 13, Blade, Lucide, Chart.js (CDN), PHPUnit, Mail existente (`MailConfig`).

---

## Escopo fechado

| # | Entrega |
|---|---------|
| 1 | Tela **Consultores** (admin): listar, ver link de adesão, ver clientes |
| 2 | **Transferir** venda/cliente de um consultor para outro + e-mail de transparência |
| 3 | **Dashboard** com cards/gráficos: ativos, pagos, não pagos, a vencer; filtro 7 / 15 / 30 dias |
| 4 | Menu **Administração** (ícone config): Pessoas, Consultores, Gateway |
| 5 | `/sales`: botão admin **Excluir tudo** (com confirmação + soft delete / destroy existente) |

Fora de escopo nesta leva: comissão recalculada no Asaas, mudança de `invoice.user_id` legado, app mobile.

---

## Arquivos principais

| Arquivo | Papel |
|---------|--------|
| `routes/web.php` | Rotas consultants, transfer, destroy-all sales |
| `app/Http/Controllers/User/ConsultantController.php` | CRUD leve / listagem / transfer |
| `app/Http/Controllers/Sale/SaleController.php` | destroyAll; filtros seller |
| `app/Http/Controllers/AppController.php` | Métricas + período |
| `app/Mail/SaleTransferMail.php` + view | E-mail transferência |
| `resources/views/app/Consultant/*` | Index / show |
| `resources/views/app/app.blade.php` | Dashboard gráficos |
| `resources/views/app/layout.blade.php` | Menu Administração |
| `resources/views/app/Sale/index.blade.php` | Botão excluir tudo |
| `tests/Feature/ConsultantTransferTest.php` | Transfer + mail |
| `tests/Feature/DashboardMetricsTest.php` | Filtro período |

---

### Task 1: Consultores — listar, link, clientes

**Files:** `ConsultantController`, views, rotas, menu.

- [ ] **Step 1:** Teste: admin vê lista só `admin|collaborator`; collaborator/user recebe 403/redirect.
- [ ] **Step 2:** `GET /consultants` → index com nome, e-mail, status, qtd vendas, botão copiar link `route('showcase', $uuid)`.
- [ ] **Step 3:** `GET /consultant/{uuid}` → vendas desse `seller_id` + link Individual/Familiar.
- [ ] **Step 4:** Docker test verde.

### Task 2: Transferir cliente + e-mail

**Files:** `ConsultantController@transfer` ou `SaleController@transferSeller`, `SaleTransferMail`.

- [ ] **Step 1:** Teste: POST troca `seller_id`; Mail::fake assert enviado ao admin + consultores origem/destino.
- [ ] **Step 2:** Validar admin only; destino deve ser `admin|collaborator`.
- [ ] **Step 3:** Blade modal na ficha do consultor / beneficiário.
- [ ] **Step 4:** Docker test verde. Sem commit sem pedido.

### Task 3: Dashboard métricas + filtro 7/15/30

**Files:** `AppController`, `app.blade.php`, Chart.js CDN.

- [ ] **Step 1:** Teste: `?period=7|15|30` altera agregados (scoped por `seller_id` se collaborator; global se admin).
- [ ] **Step 2:** Cards: usuários/vendas ativas; faturas pagas; faturas pendentes; a vencer no período.
- [ ] **Step 3:** 1–2 gráficos simples (barras/donut) sem migrar para Vite.
- [ ] **Step 4:** Docker test verde.

### Task 4: Menu Administração

**Files:** `layout.blade.php`.

- [ ] **Step 1:** Só `roles === admin`: grupo “Administração” com ícone `settings` → Pessoas, Consultores, Gateway.
- [ ] **Step 2:** Remover Gateway/Pessoas soltos do menu admin (evitar duplicata).

### Task 5: `/sales` excluir tudo (admin)

**Files:** `SaleController@destroyAll`, index blade.

- [ ] **Step 1:** Teste: admin POST confirma e soft-delete/cancela vendas (reusar lógica de `destroy` em lote); não-admin bloqueado.
- [ ] **Step 2:** Botão perigoso + SweetAlert “digite EXCLUIR” ou checkbox de confirmação.
- [ ] **Step 3:** Log da ação. Docker test verde.

---

## Ordem

1 → 2 → 4 (menu) → 3 (dashboard) → 5 (delete all)

## Critérios de pronto

- [ ] Admin gerencia consultores, copia link, vê clientes.
- [ ] Transferência altera `seller_id` e dispara e-mail.
- [ ] Dashboard com filtro 7/15/30 e números coerentes.
- [ ] Menu Administração com Gateway.
- [ ] Excluir tudo só admin + confirmação.
- [ ] Suite Docker dos testes novos + regressão SalePayment/SaleCheckout.

## Notas

- Usar `seller_id` (não `parent_id` legado nas sales).
- Não inventar valores comerciais; identidade → skill `maismed-identidade`.
- Commit só se o usuário pedir.
