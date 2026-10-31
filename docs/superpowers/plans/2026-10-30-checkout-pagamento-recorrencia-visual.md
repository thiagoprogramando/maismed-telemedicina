# Checkout, Pagamento Imediato, Recorrência e Visual — Plano de Implementação

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Após a adesão, o cliente paga na hora (cartão ou PIX) numa tela dedicada; a venda fica atrelada ao representante do link; gera contrato/recorrência de 12 meses no Asaas; SMTP configurável no Gateway envia cópia do acordo; visual do checkout e painel fica mais moderno com Lucide.

**Architecture:** Manter Laravel/Blade/Bootstrap (sem Vue/React). Checkout público em duas etapas claras: (1) dados/contrato → POST cria venda + cliente Asaas; (2) rota `/pay/{saleUuid}` exibe PIX/cartão e conclui a 1ª cobrança. Recorrência via assinatura Asaas (`POST /v3/subscriptions`, `cycle=MONTHLY`, `maxPayments=12`) após sucesso da 1ª parcela (ou na mesma criação com `nextDueDate` hoje). Link `/planos/{uuid}` e `/create-sale/{slug}/{uuid}` exige usuário com papel `collaborator` ou `admin` e grava `seller_id`. SMTP e Asaas na tela Gateway via `settings` (env primeiro, banco depois).

**Tech Stack:** Laravel 13, Blade, Bootstrap 5 + SB Admin 2, Lucide (`public/Assets/vendor/lucide`), Guzzle/Asaas, PHPUnit 12, Docker para testes.

**Spec:** Requisitos alinhados nesta conversa + domínio em `.agents/skills/maismed-project/references/domain.md`.

## Global Constraints

- Preservar rotas públicas existentes quando houver consumidores; novas rotas podem ser adicionadas (`pay`, `paid`).
- Preservar grafia `pendent`, nome `AssasController`, `address_provincy`.
- Não chamar Asaas real em testes; mockar `AssasController`.
- Valores monetários em `decimal`; CPF/telefone só dígitos no banco.
- Chamadas Asaas fora do rollback automático — compensar com `canceledCharge` / cancelar assinatura.
- Webhook idempotente; não ativar o consultor no lugar do comprador.
- Visual: modernizar Blade existente + Lucide; não migrar para Vite/Tailwind/Vue.
- Mensagens de UI em português UTF-8.
- Trabalhar via Docker (`php artisan test` no container).

## Review Focus

- Cliente fecha adesão e não vê pagamento → deve redirecionar sempre para `/pay/{uuid}` quando `pay_timing=now`.
- Link de consultor inválido ou com papel `user` → bloquear antes de qualquer cobrança.
- 1ª parcela cartão recusada → não criar assinatura 12 meses; venda `canceled` ou `pendent` sem recorrência.
- Webhook da 1ª parcela pago → ativa `sale.user` (comprador), não `invoice.user` se for vendedor.
- SMTP vazio no env e no banco → falha de e-mail logada, adesão não quebra.

## Decisão de UX (pagamento)

**Página dedicada de pagamento** (`/pay/{saleUuid}`), não offcanvas na mesma tela de dados. Motivo: fluxo mais claro, reload seguro, QR PIX e cartão sem misturar com contrato/dependentes.

---

## File map

| Arquivo | Responsabilidade |
|---------|------------------|
| `routes/web.php` | Rotas `pay`, `pay.card`, `pay.pix-status` |
| `SaleExternalController.php` | Separar adesão (store) de pagamento; redirect para pay |
| `AssasController.php` | `createdSubscription`, listar 1ª cobrança, cancelar assinatura |
| `app/Support/MailConfig.php` | URL/token SMTP: env → settings |
| `GatewayController` + `Gateway/index` | Form Asaas + SMTP |
| `resources/views/sale/pay.blade.php` | Tela pagamento (PIX QR + cartão) |
| `resources/views/sale/index.blade.php` | Visual moderno Lucide; remove cobrança inline |
| `resources/views/sale/thank_you.blade.php` | Confirmação pós-pagamento |
| `resources/views/app/layout.blade.php` | Menu Lucide + visual |
| `app/Mail/SaleAgreementMail.php` | Cópia do acordo + link/recibo |
| Migration | `sales.subscription_token`, `sales.contract_months` (opcional) |
| `tests/Feature/SaleCheckoutTest.php` | Fluxo pay now → pay page |
| `tests/Feature/SalePaymentTest.php` | PIX/cartão na pay page + subscription |
| `tests/Feature/GatewayConfigTest.php` | SMTP settings |

---

### Task 1: Tela dedicada de pagamento (bug fix principal)

**Files:**
- Create: `resources/views/sale/pay.blade.php`
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/Sale/SaleExternalController.php`
- Modify: `resources/views/sale/index.blade.php` (só adesão; pay_timing now redireciona)
- Modify: `resources/views/sale/thank_you.blade.php`
- Test: `tests/Feature/SalePaymentTest.php`

- [ ] **Step 1: Teste falhando — store com `pay_timing=now` redireciona para pay**

```php
public function test_pay_now_redirects_to_payment_page(): void
{
    // checkout PIX now → assertRedirect(route('pay', $sale->uuid))
    // session NÃO precisa carregar QR ainda; pay page carrega
}
```

- [ ] **Step 2: Rodar teste e ver falhar**

```bash
docker run --rm -v "${PWD}:/src:ro" -v maismed_vendor:/opt/vendor:ro -e APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= --entrypoint sh maismed-app -c 'cp -a /src/. /var/www/html/; cd /var/www/html; cp .env.example .env; rm -rf vendor; cp -r /opt/vendor vendor; php artisan package:discover -q; php artisan test --filter=test_pay_now_redirects_to_payment_page'
```

- [ ] **Step 3: Implementar rotas e actions**

```
GET  /pay/{uuid}           → payShow   (nome: pay)
POST /pay/{uuid}/card      → payCard   (nome: pay-card)
GET  /pay/{uuid}/pix       → payPix    (nome: pay-pix)  // JSON/status opcional
```

No `store`:
- Se `pay_timing=now`: criar venda + cliente Asaas **sem** gerar as 12 cobranças ainda (ou gerar só placeholder local); redirect `route('pay', $sale->uuid)`.
- Se `pay_timing=later`: manter criar 1ª cobrança futura + e-mail + thank-you.

Na `payShow`:
- Validar venda `pendent` e uuid.
- PIX: criar cobrança da 1ª parcela (due today) se ainda não existir; buscar QR; renderizar.
- Cartão: formulário; `payCard` cobra 1ª + dispara Task 2 (assinatura).

- [ ] **Step 4: Testes PIX/cartão na pay page passam; commit**

```bash
git add routes/web.php app/Http/Controllers/Sale/SaleExternalController.php resources/views/sale/pay.blade.php resources/views/sale/index.blade.php resources/views/sale/thank_you.blade.php tests/Feature/SalePaymentTest.php
git commit -m "$(cat <<'EOF'
fix: abrir tela de pagamento após adesão (PIX/cartão)

EOF
)"
```

---

### Task 2: Recorrência 12 meses (assinatura Asaas) + vínculo representante

**Files:**
- Create: `database/migrations/2026_10_30_000001_add_subscription_to_sales_table.php`
- Modify: `app/Models/Sale.php`
- Modify: `app/Http/Controllers/Gateway/AssasController.php` (`createdSubscription`, `canceledSubscription`)
- Modify: `SaleExternalController` (após 1ª paga/confirmada → subscription)
- Modify: `AssasController@webhook` (ativar comprador `sale.user_id`)
- Test: `tests/Feature/SalePaymentTest.php`, ajuste `SaleCheckoutTest`

- [ ] **Step 1: Teste — cartão pay cria subscription com maxPayments 12**

```php
public function test_first_card_payment_creates_twelve_month_subscription(): void
{
    // FakeAssas registra createdSubscription chamado 1x com cycle MONTHLY maxPayments 12
    // sale.subscription_token preenchido
}
```

- [ ] **Step 2: Teste — link parent exige collaborator/admin**

```php
public function test_user_role_cannot_be_seller_on_adhesion_link(): void
{
    // parent_id de User roles=user → session infor / erro
}
```

- [ ] **Step 3: Implementar migration + Assas subscription**

```php
// AssasController::createdSubscription(...)
// POST v3/subscriptions
// customer, billingType, value, nextDueDate, cycle=MONTHLY, maxPayments=12,
// creditCardToken ou creditCard, remoteIp
```

Fluxo cartão:
1. Cobrar/autorizar 1ª parcela (ou nextDueDate=hoje na subscription).
2. Salvar `sales.subscription_token`.
3. Gerar invoices locais alinhadas às cobranças da assinatura (1ª imediata; demais via webhook `PAYMENT_CREATED` se necessário — MVP: criar 12 invoices espelho a partir da listagem ou manter 1ª + webhook para as seguintes).

**MVP recomendado (menor risco):**  
- Subscription Asaas com cartão (`nextDueDate` hoje, `maxPayments=12`).  
- Buscar pagamentos da assinatura (`GET /v3/subscriptions/{id}/payments`) e espelhar invoices.  
- PIX: 1ª cobrança avulsa now + subscription PIX para os 11 ciclos seguintes **ou** 12 avulsas PIX (documentar escolha). Preferência: **1ª PIX avulsa + subscription PIX** se Asaas permitir; senão manter 12 PIX avulsas e registrar `contract_months=12` na sale.

- [ ] **Step 4: Corrigir webhook — ativar comprador**

```php
$buyer = $invoice->sale_id ? Sale::find($invoice->sale_id)?->user : null;
if ($buyer) { $buyer->status = 'active'; $buyer->save(); }
```

Comissão/carteira continua no vendedor (`extract.user_id`).

- [ ] **Step 5: Validar seller no store/showcase**

```php
$seller = User::where('uuid', $parentId)->whereIn('roles', ['admin', 'collaborator'])->first();
```

- [ ] **Step 6: Testes verdes + commit**

```bash
git commit -m "$(cat <<'EOF'
feat: assinatura Asaas 12 meses e vínculo do link ao representante

EOF
)"
```

---

### Task 3: SMTP na Gateway + e-mail cópia do acordo

**Files:**
- Create: `app/Support/MailConfig.php`
- Create: `app/Mail/SaleAgreementMail.php`
- Create: `resources/views/mails/sale_agreement.blade.php`
- Modify: `GatewayController.php`, `resources/views/app/Gateway/index.blade.php`
- Modify: `SaleExternalController` (enviar acordo após adesão/pagamento)
- Modify: `.env.example`
- Test: `tests/Feature/GatewayConfigTest.php`

- [ ] **Step 1: Teste — admin salva SMTP no settings**

```php
public function test_admin_can_save_smtp_settings(): void
{
    // post updated-gateway com smtp_host, port, username, password, encryption, from
    // Setting::getValue / getSecret
}
```

- [ ] **Step 2: MailConfig apply before send**

```php
MailConfig::apply(); // config(['mail.mailers.smtp' => ..., 'mail.from' => ...])
// prioridade: env não vazio > settings
```

- [ ] **Step 3: SaleAgreementMail**

Conteúdo: nome, plano, valor, método, data, link pagamento/recibo, resumo dependentes (sem dados sensíveis demais), BCC opcional `mail_bcc` setting.

Enviar:
- `pay_timing=later` → acordo + link (já existe SalePaymentMail; unificar ou complementar).
- Após pagamento now bem-sucedido → cópia do acordo + confirmação 1ª parcela.

- [ ] **Step 4: Botão “Enviar e-mail de teste” (admin only)** opcional no mesmo form.

- [ ] **Step 5: Testes + commit**

```bash
git commit -m "$(cat <<'EOF'
feat: SMTP no gateway e e-mail de cópia do acordo

EOF
)"
```

---

### Task 4: Visual moderno (checkout + painel) com Lucide

**Files:**
- Modify: `resources/views/sale/showcase.blade.php`, `index.blade.php`, `pay.blade.php`, `thank_you.blade.php`, `plan_card.blade.php`
- Modify: `resources/views/app/layout.blade.php`
- Create/Modify: `public/Assets/css/checkout.css` (ou estender `plan-card.css`)
- Prefer Lucide no lugar de Font Awesome nas telas tocadas; não remover FA global de uma vez.

- [ ] **Step 1: Design tokens CSS** (cores marca azul `#0b1b5c`, tipografia Nunito já usada, cards com menos “dashboard”, hierarquia clara).

- [ ] **Step 2: Checkout** — passos visuais (Dados → Dependentes → Contrato → Pagamento), ícones Lucide, botões e empty states.

- [ ] **Step 3: Painel** — sidebar com Lucide, headings mais limpos, Gateway com seções Asaas / E-mail.

- [ ] **Step 4: Smoke visual** (manual) + `php artisan test` no Docker.

- [ ] **Step 5: Commit**

```bash
git commit -m "$(cat <<'EOF'
style: visual moderno no checkout e painel com Lucide

EOF
)"
```

---

### Task 5: Hardening e regressão

**Files:** testes existentes + ajustes domain notes se necessário.

- [ ] **Step 1:** Suite completa no Docker (esperar 30+ testes verdes).
- [ ] **Step 2:** Checklist manual sandbox Asaas: PIX QR, cartão teste, webhook, e-mail log/SMTP.
- [ ] **Step 3:** Confirmar migrate em produção: `subscription_token`, settings SMTP.
- [ ] **Step 4:** Commit final se houver fixes.

```bash
git commit -m "$(cat <<'EOF'
test: regressão checkout pagamento e gateway

EOF
)"
```

---

## Ordem de execução

1 → 2 → 3 → 4 → 5  

Task 1 desbloqueia o bug “abrir pagamento na hora”.  
Task 2 muda regra de negócio (assinatura 12 meses) — não misturar com visual.  
Task 3 SMTP independente após e-mails existirem.  
Task 4 visual por último para não conflitar com forms da pay page.

## Critérios de pronto

- [ ] `pay_timing=now` sempre abre `/pay/{uuid}` com PIX ou cartão funcional.
- [ ] Representante do link = `seller_id` e só `admin|collaborator`.
- [ ] 12 meses registrados no Asaas (subscription ou equivalente documentado).
- [ ] Webhook ativa o **cliente**.
- [ ] Admin configura SMTP no Gateway; cliente recebe cópia do acordo.
- [ ] Lucide nas telas de checkout/painel tocadas; `composer test` / Docker verde.

## Notas para o executor

- Não fazer rewrite de services/repositories.
- Não renomear `AssasController`.
- Preferir commits por task.
- Usar subagents só para tarefas independentes (ex.: visual // SMTP em paralelo **depois** da Task 1).
