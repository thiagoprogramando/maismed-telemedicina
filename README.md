# MaisMed Telemedicina

Sistema de gestão de planos de telemedicina: cadastro de usuários e dependentes, planos, vendas por consultores, faturas recorrentes, comissões e carteira, com cobrança integrada ao [Asaas](https://www.asaas.com/).

## Stack

- **Backend:** Laravel 13, PHP 8.3, Eloquent, PHPUnit 12.
- **Frontend:** Blade com Bootstrap/SB Admin 2, jQuery, máscaras e SweetAlert, servidos de `public/Assets`.
- **Banco:** SQLite por padrão em desenvolvimento. Sessão, cache e fila usam o banco.
- **Pagamentos:** Asaas via Guzzle.

> Vite e Tailwind existem no esqueleto do Laravel, mas não são a base visual das telas atuais (`resources/js/app.js` está vazio).

## Como rodar

### Docker

```bash
docker compose up --build
```

A aplicação sobe em `http://localhost:8088` (altere com a variável `MAISMED_PORT`). O entrypoint cria o `.env`, gera a `APP_KEY`, executa as migrations e, com `APP_SEED_DATABASE=true`, cria o usuário administrador inicial. O SQLite fica no volume `maismed_storage`.

### Local

Requisitos: PHP 8.3 (extensões `mbstring`, `pdo_sqlite`, `zip`), Composer e Node.js.

```bash
composer setup          # instala dependências, cria .env, gera a chave, migra e builda os assets
php artisan db:seed     # cria o usuário administrador inicial
composer dev            # servidor, fila, logs e Vite em paralelo
```

### Primeiro acesso

O seeder `FirstUser` cria o administrador `admin@telemedicina.com` com senha `admin`. Troque a senha antes de qualquer uso fora do ambiente de desenvolvimento.

## Configuração

Além das variáveis padrão do Laravel (`.env.example`), a integração de pagamentos exige:

| Variável | Descrição |
|---|---|
| `API_URL_ASSAS` | URL base da API do Asaas (sandbox ou produção) |
| `API_TOKEN_ASSAS` | Token de acesso da conta Asaas |

O webhook do Asaas deve apontar para `POST /api/webhook`.

## Estrutura

```text
app/
|-- Http/Controllers/
|   |-- Access/       login e recuperação de senha
|   |-- Api/          consulta pública de faturas
|   |-- Finance/      carteira e extratos
|   |-- Gateway/      comunicação e webhook do Asaas
|   |-- Plan/         CRUD de planos
|   |-- Sale/         vendas internas e adesão pública
|   `-- User/         perfis, dependentes e papéis
|-- Mail/             e-mail de recuperação
`-- Models/           User, Plan, Sale, Invoice e Extract
database/
|-- migrations/       fonte do schema e dos enums persistidos
`-- seeders/          usuário administrador inicial
resources/views/
|-- app/              área autenticada (app/layout.blade.php)
|-- sale/             vitrine de planos, adesão pública e agradecimento
|-- mails/            templates de e-mail
|-- login.blade.php
`-- forgout.blade.php
routes/
|-- web.php           páginas públicas + grupo auth
`-- api.php           faturas e webhook
public/Assets/        Bootstrap/SB Admin 2, jQuery, máscaras e SweetAlert
tests/                PHPUnit (cobertura ainda inicial)
```

## Rotas

### Públicas (`routes/web.php`)

| Método | URL | Descrição |
|---|---|---|
| GET | `/` | Login |
| POST | `/logon` | Autenticação |
| GET | `/forgout/{code?}` | Recuperação de senha |
| POST | `/forgout-password` | Envia o e-mail de recuperação |
| POST | `/recover-password/{code}` | Define a nova senha |
| GET | `/planos/{parent?}` | Vitrine pública dos planos ativos com simulador de preço (`parent` = UUID do consultor) |
| GET | `/create-sale/{plan}/{parent?}` | Adesão pública (`plan` = slug do plano, `parent` = UUID do consultor; `qty` e `dep` na query preenchem o simulador) |
| POST | `/created-sale` | Conclui a adesão |
| GET | `/thank-you` | Página de agradecimento |

### Autenticadas (middleware `auth`)

| Área | Rotas |
|---|---|
| Dashboard | `GET /app` |
| Vendas | `GET /sales`, `GET /sale/{uuid}`, `POST /deleted-sale/{uuid}` |
| Carteira | `GET /wallet/{uuid}` |
| Planos | `GET /plans`, `GET /plan/{uuid}`, `POST /created-plan`, `POST /updated-plan/{uuid}`, `POST /deleted-plan/{uuid}` |
| Usuários | `GET /users`, `GET /user/{uuid}`, `POST /created-user`, `POST /updated-user/{uuid}`, `POST /deleted-user/{uuid}` |
| Sessão | `GET /logout` |

### API (`routes/api.php`)

| Método | URL | Descrição |
|---|---|---|
| POST | `/api/invoices` | Consulta faturas por CPF/CNPJ |
| POST | `/api/webhook` | Recebe eventos de pagamento do Asaas |

## Domínio

### Entidades

| Entidade | Papel | Valores persistidos |
|---|---|---|
| **User** | Titular, dependente (`parent_id`), consultor ou administrador. `document` único e sem máscara; `token` é o cliente no Asaas; `wallet` é o saldo de comissões | Papéis: `admin`, `collaborator`, `user`. Status: `active`, `inactive` |
| **Plan** | Plano comercializado. `price` é o preço base (familiar) ou o valor por funcionário (empresarial); `included_users` é o grupo coberto pelo preço base; `extra_price` é o valor por pessoa extra ou por dependente; `max_users` limita titular + dependentes e o simulador; `badge` é o selo do card; `features` tem um benefício por linha | Tipos: `family`, `business`. Períodos: `month`, `semi-annual`, `year`, `lifetime` |
| **Sale** | Liga cliente (`user_id`), consultor (`seller_id`) e plano; guarda um snapshot do nome e do valor contratado: `price` e `commission` já calculados para a quantidade escolhida (`quantity`, `dependents`; `quantity` nulo em planos de preço fixo e vendas antigas) | `pendent`, `active`, `canceled` |
| **Invoice** | Cobrança do Asaas ligada a uma venda | `pendent`, `paid`, `canceled` |
| **Extract** | Lançamento da carteira | Tipos: `commission`, `payment`, `credit`, `debit`. Status: `pendent`, `paid`, `canceled` |

Identificadores públicos são `uuid` (usuários, planos na administração) e `slug` (plano no link de adesão). IDs numéricos ficam restritos a chaves estrangeiras e consultas internas.

### Papéis

- **admin:** gestão geral e operações destrutivas/cancelamentos.
- **collaborator:** vendas, links de adesão e a própria carteira.
- **user:** contrato/perfil e dependentes permitidos pelo plano.

### Fluxo de adesão

1. O link público identifica o plano por `slug` e o consultor por `uuid`.
2. O formulário envia dados pessoais, método de pagamento (`CREDIT_CARD`, `PIX` ou `BOLETO`) e dia de vencimento (1 a 28).
3. O sistema localiza ou cria o cliente no Asaas e espelha/atualiza o usuário local.
4. O servidor recalcula o valor com `Plan::quote()` a partir da quantidade enviada (o total exibido no navegador nunca é usado): familiar = preço base + pessoas além das incluídas × valor extra; empresarial = funcionários × preço + dependentes × valor extra. A comissão do plano é multiplicada pelas pessoas contratadas. A venda é criada como `pendent` com esse valor.
5. São geradas 12 cobranças individuais no valor da venda: a primeira vence em 3 dias; as demais usam o dia escolhido nos meses seguintes.
6. Cada cobrança gera uma fatura e, se a comissão for positiva, um extrato pendente para o consultor.

```text
Plano + consultor
       |
       v
adesão pública --> cliente Asaas/usuário --> venda
                                         |--> 12 faturas
                                         `--> 12 extratos de comissão

webhook Asaas --> fatura --> usuário/status
                       `--> extrato/status --> carteira do consultor
```

### Eventos do webhook

| Evento | Efeito |
|---|---|
| `PAYMENT_CONFIRMED`, `PAYMENT_RECEIVED` | Fatura e extrato passam a `paid`, o usuário é ativado e a comissão é creditada na carteira |
| `PAYMENT_OVERDUE` | Fatura e extrato passam a `canceled` |
| `PAYMENT_DELETED` | Fatura removida logicamente e extrato cancelado |
| `PAYMENT_RESTORED` | Fatura restaurada e extrato de volta a `pendent` |

## Desenvolvimento

### Convenções

- Controllers agrupados por domínio (`Access`, `User`, `Plan`, `Sale`, `Finance`, `Api`, `Gateway`); telas internas em `resources/views/app/<Dominio>` usando `app.layout`.
- Web retorna Blade/redirecionamentos; JSON apenas na API.
- Listagens paginam em 30 itens.
- Feedback usa as flash keys `success`, `error` e `infor`, exibidas por SweetAlert.
- Formulários usam `@csrf`; exclusões e cancelamentos usam POST com confirmação por senha.
- Mensagens da interface em português, em UTF-8.
- Nomes legados (`pendent`, `roles`, `forgout`, `AssasController`, `address_provincy`) são valores e identificadores em uso: não renomeie sem migração e ajuste coordenado dos consumidores.
- Autorização e validação devem ser aplicadas no servidor; valores monetários permanecem em colunas `decimal`.
- Webhooks devem ser idempotentes: um evento repetido não pode creditar a carteira duas vezes.

### Testes e validação

```bash
composer test     # limpa a config e roda php artisan test
npm run build     # quando alterar assets gerenciados pelo Vite
```

- Não chame o Asaas real em testes; isole/mocke o cliente HTTP.
- Em mudanças de banco, valide migrate e rollback em um banco descartável.
- A suíte atual é apenas inicial; fluxos financeiros alterados precisam de testes direcionados.

### Pontos de atenção (legado)

- `sales` possui `seller_id`, mas existem consultas antigas a `parent_id`.
- A semântica de `invoices.user_id` é inconsistente: a criação associa o vendedor, enquanto a API busca faturas pelo usuário encontrado por CPF/CNPJ.
- Há `softDeletes()` nas migrations de vendas, faturas e extratos, mas nem todos os models usam `SoftDeletes`.
- Algumas verificações de papel e regras de validação estão incompletas.
- Há sinais de mojibake em textos legados; não os copie para código novo.

### Guia para agentes de IA

As regras de trabalho detalhadas ficam em [`.agents/skills/maismed-project`](.agents/skills/maismed-project/SKILL.md), com referências de [arquitetura](.agents/skills/maismed-project/references/architecture.md) e [domínio](.agents/skills/maismed-project/references/domain.md).
