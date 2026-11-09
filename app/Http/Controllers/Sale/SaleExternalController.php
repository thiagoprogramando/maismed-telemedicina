<?php

namespace App\Http\Controllers\Sale;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Gateway\AssasController;
use App\Models\Extract;
use App\Models\Plan;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Sale;
use App\Mail\SalePaymentMail;
use App\Support\AssasConfig;
use App\Support\Kinship;
use App\Support\MailConfig;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SaleExternalController extends Controller {

    private const INSTALLMENTS      = 12;
    private const FIRST_DUE_DAYS    = 3;
    
    public function showcase(?string $parent = null) {

        $plans  = Plan::where('status', 'active')->orderBy('price', 'asc')->get();
        $parent = $this->resolveSeller($parent);

        return view('sale.showcase', compact('plans', 'parent'));
    }

    public function index(Request $request, string $plan, ?string $parent = null) {

        $plan = Plan::firstWhere('slug', $plan);
        if (!$plan) {
            return redirect()->back()->with('info', 'O plano não está disponível no momento. Fale com um de nossos consultores para obter mais informações!');
        }

        $parent = $this->resolveSeller($parent);

        return view('sale.index', [
            'plan'     => $plan,
            'parent'   => $parent,
            'qty'      => old('quantity', $request->query('qty')),
            'dep'      => old('dependents', $request->query('dep')),
            'kinships' => Kinship::options($plan->isBusiness()),
        ]);
    }

    public function thankYou () {
        return view('sale.thank_you');
    }

    public function store (Request $request) {

        $plan = Plan::where('uuid', $request->input('plan_id'))->first();
        if (!$plan) {
            return redirect()->back()->withInput()->with('infor', 'Plano indisponível no momento!');
        }

        $kinshipKeys = Kinship::keys($plan->isBusiness());

        $validated = $request->validate([
            'plan_id'              => ['required', 'string'],
            'parent_id'            => ['nullable', 'string'],
            'name'                 => ['required', 'string', 'max:255'],
            'document'             => ['required', 'string'],
            'birth_date'           => ['required', 'date', 'before:today'],
            'phone'                => ['required', 'string'],
            'email'                => ['required', 'email'],
            'payment_method'       => ['required', 'in:CREDIT_CARD,PIX,BOLETO'],
            'pay_timing'           => ['required', 'in:now,later'],
            'due_date'             => ['required', 'integer', 'min:1', 'max:28'],
            'quantity'             => ['nullable', 'integer', 'min:1'],
            'dependents'           => ['nullable', 'integer', 'min:0'],
            'members'              => ['nullable', 'array'],
            'members.*.name'       => ['nullable', 'required_with:members.*.document', 'string', 'max:255'],
            'members.*.document'   => ['nullable', 'required_with:members.*.name', 'string'],
            'members.*.kinship'    => ['nullable', 'required_with:members.*.name', Rule::in($kinshipKeys)],
        ], [
            'quantity.integer'               => 'A quantidade de pessoas deve ser um número.',
            'quantity.min'                   => 'A quantidade de pessoas deve ser no mínimo :min.',
            'dependents.integer'             => 'A quantidade de dependentes deve ser um número.',
            'dependents.min'                 => 'A quantidade de dependentes não pode ser negativa.',
            'plan_id.required'               => 'Selecione um plano para continuar.',
            'plan_id.string'                 => 'O plano selecionado é inválido.',
            'parent_id.string'               => 'O Consultor informado é inválido.',
            'name.required'                  => 'Informe seu nome completo.',
            'name.string'                    => 'O nome informado é inválido.',
            'name.max'                       => 'O nome não pode ter mais que :max caracteres.',
            'document.required'              => 'Informe seu CPF ou CNPJ.',
            'document.string'                => 'O documento informado é inválido.',
            'birth_date.required'            => 'Informe sua data de nascimento.',
            'birth_date.date'                => 'Informe uma data de nascimento válida.',
            'birth_date.before'              => 'A data de nascimento deve ser anterior a hoje.',
            'phone.required'                 => 'Informe um telefone para contato.',
            'phone.string'                   => 'O telefone informado é inválido.',
            'email.required'                 => 'Informe seu e-mail.',
            'email.email'                    => 'Informe um e-mail válido.',
            'payment_method.required'        => 'Selecione uma forma de pagamento.',
            'payment_method.in'              => 'Forma de pagamento inválida. Escolha entre Cartão de Crédito, Pix ou Boleto.',
            'pay_timing.required'            => 'Escolha se deseja pagar agora ou depois.',
            'pay_timing.in'                  => 'Opção de pagamento inválida.',
            'due_date.required'              => 'Informe o dia de vencimento das parcelas.',
            'due_date.integer'               => 'O dia de vencimento deve ser um número.',
            'due_date.min'                   => 'O dia de vencimento deve ser entre :min e :max.',
            'due_date.max'                   => 'O dia de vencimento deve ser entre :min e :max.',
            'members.*.name.required_with'   => 'Informe o nome de cada dependente.',
            'members.*.document.required_with' => 'Informe o CPF de cada dependente.',
            'members.*.kinship.required_with'=> 'Informe o vínculo de cada dependente.',
            'members.*.kinship.in'           => 'Vínculo informado é inválido.',
        ]);

        if (!empty($validated['parent_id'])) {
            $seller = $this->resolveSeller($validated['parent_id']);
            if (!$seller) {
                return redirect()->back()->withInput()->with('infor', 'Consultor não identificado. Verifique o link de acesso com seu Consultor!');
            }
        } else {
            $seller = $this->defaultSeller();
        }

        $members = $this->normalizeMembers($validated['members'] ?? []);
        [$quantity, $dependents] = $this->quoteQuantities($plan, $validated['quantity'] ?? null, $validated['dependents'] ?? null, $members);

        // O valor cobrado é sempre recalculado aqui; o total exibido no navegador não é confiável.
        $quote = $plan->quote($quantity, $dependents);

        // Asaas exige mínimo de R$ 5,00 por cobrança (sandbox e produção).
        if ((float) $quote['price'] < 5) {
            return redirect()->back()->withInput()->with('error', 'O valor do plano precisa ser de pelo menos R$ 5,00 para gerar cobrança no Asaas.');
        }

        if (!AssasConfig::isConfigured()) {
            Log::error('Checkout bloqueado: Asaas sem URL/token (.env ou admin/gateway).');
            return redirect()->back()->withInput()->with('error', 'Pagamentos indisponíveis no momento. Tente novamente em breve!');
        }

        $documentDigits = preg_replace('/\D/', '', $validated['document']);
        $emailOwner = User::withTrashed()
            ->where('email', $validated['email'])
            ->where('document', '!=', $documentDigits)
            ->first();
        if ($emailOwner) {
            return redirect()->back()->withInput()->with('error', 'Este e-mail já está cadastrado em outra conta. Use outro e-mail ou fale com o suporte.');
        }

        $assasController = app(AssasController::class);

        try {
            $customer = $assasController->createdCustomer(
                $validated['name'],
                $documentDigits,
                preg_replace('/\D/', '', $validated['phone']),
                $validated['email'],
                $validated['birth_date']
            );
        } catch (\Throwable $e) {
            Log::error('Falha ao criar cliente no Asaas', ['plan_id' => $plan->uuid, 'seller' => $seller?->uuid, 'error' => $e->getMessage()]);
            $customer = [
                'error'   => true,
                'message' => 'Não foi possível cadastrar seus dados no pagamento. Tente novamente em instantes.',
            ];
        }

        if (!is_array($customer) || !empty($customer['error']) || empty($customer['user'])) {
            $message = is_array($customer) && !empty($customer['message'])
                ? $customer['message']
                : 'Verifique seus dados e tente novamente!';

            return redirect()->back()->withInput()->with('error', $message);
        }

        // Endereço coletado no checkout (útil no cartão e no cadastro local).
        $customer['user']->fill([
            'postal_code'      => preg_replace('/\D/', '', (string) $request->input('postal_code', '')) ?: null,
            'address'           => $request->input('address'),
            'address_number'    => $request->input('address_number'),
            'address_city'      => $request->input('address_city'),
            'address_provincy'  => $request->input('address_provincy'),
        ])->save();

        $payNow = ($validated['pay_timing'] ?? 'later') === 'now';

        $sale                   = new Sale();
        $sale->uuid             = Str::uuid();
        $sale->user_id          = $customer['user']->id;
        $sale->seller_id        = $seller?->id;
        $sale->plan_id          = $plan->id;
        $sale->name             = $plan->name;
        $sale->description      = $plan->name;
        $sale->price            = $quote['price'];
        $sale->commission       = $seller ? $quote['commission'] : 0;
        $sale->quantity         = $quote['quantity'];
        $sale->dependents       = $quote['dependents'];
        $sale->payment_method   = $validated['payment_method'];
        $sale->pay_timing       = $validated['pay_timing'];
        $sale->payment_features = [
            'due_date' => (int) $validated['due_date'],
        ];
        $sale->status           = 'pendent';
        if (!$sale->save()) {
            Log::error('Falha ao salvar venda do checkout', ['plan_id' => $plan->uuid, 'seller' => $seller?->uuid]);
            return redirect()->back()->withInput()->with('error', 'Falha ao aderir o plano, tente novamente!');
        }

        $this->createMembers($customer['user'], $members, $plan->isBusiness());

        // Pagar agora: só adesão aqui; a 1ª cobrança acontece em /pay/{uuid}.
        if ($payNow) {
            return redirect()->route('pay', $sale->uuid);
        }

        try {
            $paymentResult = $this->processInstallmentPlan(
                $assasController,
                $customer['id'],
                $sale,
                $validated['payment_method'],
                $seller,
                $plan,
                $validated['due_date'],
                $validated['payment_method'],
                false,
                []
            );
        } catch (\RuntimeException $e) {
            Log::error('Falha ao processar cobrança/assinatura de plano', ['plan_id' => $plan->uuid, 'seller' => $seller?->uuid, 'sale' => $sale->uuid, 'error' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('error', 'Falha ao aderir o plano, nossa equipe entrará em contato com você!');
        }

        $this->sendPaymentEmail($customer['user'], $plan, $sale, $validated['payment_method'], $paymentResult['first'] ?? null);

        return redirect()->route('thank-you')->with([
            'success'  => 'Plano salvo! Enviamos o link de pagamento para o seu e-mail.',
            'checkout' => [
                'pay_now'        => false,
                'payment_method' => $validated['payment_method'],
                'plan_name'      => $plan->name,
                'price'          => (float) $sale->price,
                'email'          => $validated['email'],
                'invoice_url'    => $paymentResult['first']['invoiceUrl'] ?? null,
            ],
        ]);
    }

    public function payShow(string $uuid)
    {
        $sale = Sale::with(['user', 'plan'])->where('uuid', $uuid)->first();
        if (!$sale || !$sale->user || !$sale->plan) {
            return redirect()->route('showcase')->with('info', 'Pagamento indisponível. Verifique o link ou fale com seu consultor.');
        }

        if ($sale->status === 'active' || Invoice::where('sale_id', $sale->id)->where('status', 'paid')->exists()) {
            return redirect()->route('pay-confirmed', $sale->uuid);
        }

        if ($sale->status !== 'pendent') {
            return redirect()->route('showcase')->with('info', 'Pagamento indisponível. Verifique o link ou fale com seu consultor.');
        }

        if (!AssasConfig::isConfigured()) {
            return redirect()->route('showcase')->with('error', 'Pagamentos indisponíveis no momento. Tente novamente em breve!');
        }

        $features = is_array($sale->payment_features) ? $sale->payment_features : [];
        $needsAccept = !empty($features['needs_accept']) && empty($features['accepted_at']);

        if ($needsAccept) {
            return view('sale.pay', [
                'sale'         => $sale,
                'plan'         => $sale->plan,
                'method'       => $sale->payment_method ?: 'PIX',
                'pix'          => null,
                'invoiceUrl'   => null,
                'error'        => null,
                'needsAccept'  => true,
            ]);
        }

        $method = $sale->payment_method ?: 'PIX';
        $pix = null;
        $invoiceUrl = null;
        $error = null;

        if (in_array($method, ['PIX', 'BOLETO'], true)) {
            try {
                $first = $this->ensureFirstCharge($sale, $method);
                $invoiceUrl = $first['invoiceUrl'] ?? null;

                if ($method === 'PIX' && !empty($first['id'])) {
                    $pix = app(AssasController::class)->getPixQrCode($first['id']) ?: null;
                    if (!$pix) {
                        $error = 'Não foi possível gerar o QR Code PIX. Tente novamente em instantes.';
                        if (!empty($invoiceUrl)) {
                            $error .= ' Use o link da fatura enquanto isso.';
                        }
                    }
                }
            } catch (\RuntimeException $e) {
                Log::error('Falha ao preparar pagamento imediato', ['sale' => $sale->uuid, 'error' => $e->getMessage()]);
                $error = $e->getMessage() !== ''
                    ? $e->getMessage()
                    : 'Não foi possível iniciar o pagamento. Tente novamente em breve.';
            }
        }

        return view('sale.pay', [
            'sale'        => $sale,
            'plan'        => $sale->plan,
            'method'      => $method,
            'pix'         => $pix,
            'invoiceUrl'  => $invoiceUrl,
            'error'       => $error,
            'needsAccept' => false,
        ]);
    }

    public function acceptContract(Request $request, string $uuid)
    {
        $sale = Sale::with(['user', 'plan'])->where('uuid', $uuid)->first();
        if (!$sale || !$sale->user || !$sale->plan || $sale->status !== 'pendent') {
            return redirect()->route('showcase')->with('info', 'Contrato indisponível. Verifique o link ou fale com seu consultor.');
        }

        $request->validate([
            'accept_terms' => ['accepted'],
        ], [
            'accept_terms.accepted' => 'É necessário aceitar o contrato para continuar.',
        ]);

        $features = is_array($sale->payment_features) ? $sale->payment_features : [];
        $features['needs_accept'] = true;
        $features['accepted_at']  = now()->toIso8601String();
        $features['accepted_ip']  = $request->ip();
        $sale->payment_features   = $features;
        $sale->save();

        return redirect()->route('pay', $sale->uuid)->with('success', 'Contrato aceito. Prossiga com o pagamento da 1ª parcela.');
    }

    public function payStatus(string $uuid)
    {
        $sale = Sale::where('uuid', $uuid)->first();
        if (!$sale) {
            return response()->json(['paid' => false], 404);
        }

        $paid = $sale->status === 'active'
            || Invoice::where('sale_id', $sale->id)->where('status', 'paid')->exists();

        return response()->json([
            'paid'     => $paid,
            'redirect' => $paid ? route('pay-confirmed', $sale->uuid) : null,
        ]);
    }

    public function payConfirmed(string $uuid)
    {
        $sale = Sale::with(['user', 'plan'])->where('uuid', $uuid)->first();
        if (!$sale || !$sale->user || !$sale->plan) {
            return redirect()->route('showcase')->with('info', 'Pagamento indisponível.');
        }

        $paid = $sale->status === 'active'
            || Invoice::where('sale_id', $sale->id)->where('status', 'paid')->exists();

        if (!$paid) {
            return redirect()->route('pay', $sale->uuid);
        }

        return redirect()->route('thank-you')->with([
            'success'  => 'Pagamento confirmado! Seu plano já está ativo.',
            'checkout' => $this->checkoutFlash($sale, true, [
                'card_status' => 'CONFIRMED',
                'invoice_url' => Invoice::where('sale_id', $sale->id)->value('payment_url'),
            ]),
        ]);
    }

    public function payCard(Request $request, string $uuid)
    {
        $sale = Sale::with(['user', 'plan'])->where('uuid', $uuid)->first();
        if (!$sale || $sale->status !== 'pendent' || !$sale->user || !$sale->plan) {
            return redirect()->route('showcase')->with('info', 'Pagamento indisponível. Verifique o link ou fale com seu consultor.');
        }

        $features = is_array($sale->payment_features) ? $sale->payment_features : [];
        if (!empty($features['needs_accept']) && empty($features['accepted_at'])) {
            return redirect()->route('pay', $sale->uuid)->with('error', 'Aceite o contrato antes de pagar.');
        }

        if ($sale->payment_method !== 'CREDIT_CARD') {
            return redirect()->route('pay', $sale->uuid)->with('error', 'Esta venda não está configurada para cartão de crédito.');
        }

        if (Invoice::where('sale_id', $sale->id)->exists()) {
            return redirect()->route('thank-you')->with([
                'success'  => 'Pagamento já registrado. Obrigado!',
                'checkout' => $this->checkoutFlash($sale, true, ['card_status' => 'CONFIRMED']),
            ]);
        }

        $validated = $request->validate([
            'card_holder'    => ['required', 'string', 'max:255'],
            'card_number'    => ['required', 'string', 'max:25'],
            'card_expiry'    => ['required', 'string', 'max:7'],
            'card_cvv'       => ['required', 'string', 'max:4'],
            'postal_code'     => ['nullable', 'string', 'max:12'],
            'address_number' => ['nullable', 'string', 'max:20'],
        ], [
            'card_holder.required'  => 'Informe o nome impresso no cartão.',
            'card_number.required'  => 'Informe o número do cartão.',
            'card_expiry.required'  => 'Informe a validade do cartão.',
            'card_cvv.required'     => 'Informe o CVV do cartão.',
        ]);

        $expiry = preg_replace('/\D/', '', (string) $validated['card_expiry']);
        if (strlen($expiry) !== 4 && strlen($expiry) !== 6) {
            return redirect()->back()->withInput()->with('error', 'Validade do cartão inválida. Use MM/AA.');
        }

        if (!AssasConfig::isConfigured()) {
            return redirect()->back()->withInput()->with('error', 'Pagamentos indisponíveis no momento. Tente novamente em breve!');
        }

        $month = substr($expiry, 0, 2);
        $year  = strlen($expiry) === 4 ? ('20' . substr($expiry, 2, 2)) : substr($expiry, 2, 4);
        $user  = $sale->user;

        $cardExtras = [
            'creditCard' => [
                'holderName'  => trim($validated['card_holder']),
                'number'      => preg_replace('/\D/', '', $validated['card_number']),
                'expiryMonth' => $month,
                'expiryYear'  => $year,
                'ccv'         => preg_replace('/\D/', '', $validated['card_cvv']),
            ],
            'creditCardHolderInfo' => [
                'name'          => $user->name,
                'email'         => $user->email,
                'cpfCnpj'       => preg_replace('/\D/', '', (string) $user->document),
                'postalCode'     => preg_replace('/\D/', '', (string) ($validated['postal_code'] ?? '00000000')) ?: '00000000',
                'addressNumber' => (string) ($validated['address_number'] ?? 'S/N') ?: 'S/N',
                'phone'         => preg_replace('/\D/', '', (string) $user->phone),
                'mobilePhone'   => preg_replace('/\D/', '', (string) $user->phone),
            ],
            'remoteIp' => $request->ip(),
        ];

        $assasController = app(AssasController::class);
        $chargeId = null;

        try {
            $charge = $assasController->createdCharge(
                $user->token,
                'CREDIT_CARD',
                1,
                $sale->price,
                'Plano: ' . $sale->plan->name,
                now(),
                $cardExtras
            );

            if ($charge === false || !empty($charge['error']) || empty($charge['id'])) {
                $detail = is_array($charge) && !empty($charge['message'])
                    ? $charge['message']
                    : 'Cartão recusado. Verifique os dados e tente novamente.';
                return redirect()->back()->withInput()->with('error', $detail);
            }

            $chargeId = $charge['id'];
            $seller   = $sale->seller_id ? User::find($sale->seller_id) : null;
            $dueDay   = (int) (($sale->payment_features['due_date'] ?? null) ?: now()->day);

            DB::transaction(function () use ($sale, $seller, $dueDay, $charge) {
                $this->createInvoice(
                    $seller,
                    $sale,
                    $sale->plan,
                    'Plano: ' . $sale->plan->name,
                    'CREDIT_CARD',
                    $dueDay,
                    now(),
                    $charge
                );
                $this->createCommission($seller, $sale, $sale->plan, now(), $charge);
            });

            // 1ª avulsa já cobrada: assinatura cobre os 11 ciclos restantes (contrato 12 meses).
            $subscriptionExtras = [
                'remoteIp' => $request->ip(),
            ];
            if (!empty($charge['creditCardToken'])) {
                $subscriptionExtras['creditCardToken'] = $charge['creditCardToken'];
            } else {
                $subscriptionExtras['creditCard'] = $cardExtras['creditCard'];
                $subscriptionExtras['creditCardHolderInfo'] = $cardExtras['creditCardHolderInfo'];
            }

            $this->startSubscription($assasController, $sale, 'CREDIT_CARD', $subscriptionExtras);
        } catch (\Throwable $e) {
            if ($chargeId) {
                $assasController->canceledCharge($chargeId);
            }
            Log::error('Falha no pagamento com cartão', ['sale' => $sale->uuid, 'error' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('error', 'Não foi possível processar o cartão. Tente novamente.');
        }

        $sale->refresh();

        return redirect()->route('thank-you')->with([
            'success'  => 'Pagamento da 1ª parcela enviado!',
            'checkout' => $this->checkoutFlash($sale, true, [
                'card_status' => $charge['status'] ?? null,
                'invoice_url' => $charge['invoiceUrl'] ?? null,
            ]),
        ]);
    }

    private function ensureFirstCharge(Sale $sale, string $billingType): array
    {
        $existing = Invoice::where('sale_id', $sale->id)->orderBy('id')->first();
        if ($existing && $existing->payment_token) {
            return [
                'id'         => $existing->payment_token,
                'invoiceUrl' => $existing->payment_url,
                'status'     => $existing->status,
            ];
        }

        if (empty($sale->user?->token)) {
            throw new \RuntimeException('Cliente sem vínculo no gateway de pagamento. Refaça a adesão ou fale com o suporte.');
        }

        // Asaas exige valor mínimo de R$ 5,00 por cobrança.
        if ((float) $sale->price < 5) {
            throw new \RuntimeException('O valor da 1ª parcela precisa ser de pelo menos R$ 5,00 (regra do Asaas). Ajuste o preço do plano e tente novamente.');
        }

        $assasController = app(AssasController::class);
        $seller          = $sale->seller_id ? User::find($sale->seller_id) : null;
        $dueDay          = (int) (($sale->payment_features['due_date'] ?? null) ?: now()->day);
        $chargeId        = null;
        $invoiceSaved    = false;

        try {
            $charge = $assasController->createdCharge(
                $sale->user->token,
                $billingType,
                1,
                $sale->price,
                'Plano: ' . $sale->plan->name,
                now(),
                []
            );

            if ($charge === false || !empty($charge['error']) || empty($charge['id'])) {
                $detail = is_array($charge) && !empty($charge['message'])
                    ? $charge['message']
                    : 'Cobrança da 1ª parcela recusada pelo Asaas.';
                throw new \RuntimeException($detail);
            }

            $chargeId = $charge['id'];

            DB::transaction(function () use ($sale, $seller, $dueDay, $billingType, $charge) {
                $this->createInvoice(
                    $seller,
                    $sale,
                    $sale->plan,
                    'Plano: ' . $sale->plan->name,
                    $billingType,
                    $dueDay,
                    now(),
                    $charge
                );
                $this->createCommission($seller, $sale, $sale->plan, now(), $charge);
            });
            $invoiceSaved = true;
        } catch (\Throwable $e) {
            // Só cancela no Asaas se a fatura local ainda não foi gravada.
            if ($chargeId && !$invoiceSaved) {
                $assasController->canceledCharge($chargeId);
            }

            throw $e instanceof \RuntimeException ? $e : new \RuntimeException($e->getMessage(), 0, $e);
        }

        // Assinatura é complementar: falha aqui NÃO pode impedir o PIX da 1ª parcela.
        $this->startSubscription($assasController, $sale, $billingType, []);

        return $charge;
    }

    /**
     * Cria assinatura Asaas MONTHLY para os ciclos restantes do contrato (após 1ª parcela avulsa).
     * maxPayments = 11 → total 12 meses com a 1ª já cobrada.
     */
    private function startSubscription(AssasController $assasController, Sale $sale, string $billingType, array $extras = []): void
    {
        if (!empty($sale->subscription_token) || !$sale->user?->token) {
            return;
        }

        if (!Schema::hasColumn('sales', 'subscription_token')) {
            Log::warning('Coluna subscription_token ausente — rode as migrations antes de criar assinaturas.', [
                'sale' => $sale->uuid,
            ]);
            return;
        }

        $dueDay = (int) (($sale->payment_features['due_date'] ?? null) ?: now()->day);
        $nextMonth = now()->startOfMonth()->addMonth();
        $nextDueDate = $nextMonth->copy()->day(min($dueDay, $nextMonth->daysInMonth));

        $subscriptionExtras = array_merge([
            'maxPayments' => self::INSTALLMENTS - 1,
        ], $extras);

        try {
            $subscription = $assasController->createdSubscription(
                $sale->user->token,
                $billingType,
                $sale->price,
                $nextDueDate,
                'Plano: ' . ($sale->plan->name ?? $sale->name),
                $subscriptionExtras
            );

            if ($subscription === false || empty($subscription['id'])) {
                Log::error('Assinatura Asaas não criada após 1ª parcela', ['sale' => $sale->uuid]);
                return;
            }

            $sale->subscription_token = $subscription['id'];
            $sale->contract_months    = self::INSTALLMENTS;
            $sale->save();
        } catch (\Throwable $e) {
            Log::error('Falha ao criar assinatura Asaas após 1ª parcela', [
                'sale'  => $sale->uuid,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function checkoutFlash(Sale $sale, bool $payNow, array $extra = []): array
    {
        return array_merge([
            'pay_now'        => $payNow,
            'payment_method' => $sale->payment_method,
            'plan_name'      => $sale->plan->name ?? $sale->name,
            'price'          => (float) $sale->price,
            'email'          => $sale->user->email ?? null,
        ], $extra);
    }

    private function normalizeMembers(array $raw): array {
        $members = [];
        $seen    = [];

        foreach ($raw as $row) {
            $name     = trim((string) ($row['name'] ?? ''));
            $document = preg_replace('/\D/', '', (string) ($row['document'] ?? ''));
            $kinship  = (string) ($row['kinship'] ?? '');

            if ($name === '' && $document === '') {
                continue;
            }

            if ($document === '' || isset($seen[$document])) {
                continue;
            }

            $seen[$document] = true;
            $members[] = [
                'name'     => $name,
                'document' => $document,
                'kinship'  => $kinship,
            ];
        }

        return $members;
    }

    private function quoteQuantities(Plan $plan, $quantity, $dependents, array $members): array {
        if ($plan->isBusiness()) {
            $employees = 0;
            $deps      = 0;
            foreach ($members as $member) {
                if (Kinship::isBusinessEmployee($member['kinship'])) {
                    $employees++;
                } else {
                    $deps++;
                }
            }

            return [
                max((int) ($quantity ?? 1), max(1, $employees)),
                max((int) ($dependents ?? 0), $deps),
            ];
        }

        return [
            max((int) ($quantity ?? 1), 1 + count($members)),
            0,
        ];
    }

    private function createMembers(User $titular, array $members, bool $business): void {
        $titularDoc = preg_replace('/\D/', '', (string) $titular->document);

        foreach ($members as $member) {
            if ($member['document'] === $titularDoc) {
                continue;
            }

            if (User::where('document', $member['document'])->exists()) {
                Log::warning('Dependente ignorado no checkout: CPF já cadastrado', [
                    'document' => $member['document'],
                    'titular'  => $titular->uuid,
                ]);
                continue;
            }

            $user             = new User();
            $user->uuid       = Str::uuid();
            $user->parent_id  = $titular->id;
            $user->name       = $member['name'];
            $user->document   = $member['document'];
            $user->kinship    = $member['kinship'];
            $user->email      = 'dep.' . $member['document'] . '@maismed.local';
            $user->password   = bcrypt(substr($member['document'], 0, 4));
            $user->birth_date = '1970-01-01';
            $user->roles      = 'user';
            $user->status     = 'inactive';
            $user->save();
        }
    }

    private function resolveSeller(?string $uuid): ?User
    {
        if (empty($uuid)) {
            return null;
        }

        return User::where('uuid', $uuid)
            ->whereIn('roles', ['admin', 'collaborator'])
            ->first();
    }

    private function defaultSeller(): ?User {

        $uuid = config('services.maismed.default_seller');
        if (empty($uuid)) {
            return null;
        }

        $seller = $this->resolveSeller($uuid);
        if (!$seller) {
            Log::warning('Consultor padrão do checkout não encontrado', ['uuid' => $uuid]);
        }

        return $seller;
    }

    private function processInstallmentPlan(
        AssasController $assasController,
        $customer,
        Sale $sale,
        string $billingType,
        ?User $seller,
        Plan $plan,
        int $dueDay,
        string $paymentMethod,
        bool $payNow = false,
        array $cardExtras = []
    ): array {

        $chargeIds = [];
        $firstCharge = null;
        $pix = null;
        $cardToken = null;

        try {
            DB::transaction(function () use ($assasController, $customer, $sale, $billingType, $seller, $plan, $dueDay, $paymentMethod, $payNow, $cardExtras, &$chargeIds, &$firstCharge, &$cardToken) {
                for ($i = 1; $i <= self::INSTALLMENTS; $i++) {
                    $dueDate = ($i === 1)
                        ? ($payNow ? now() : now()->addDays(self::FIRST_DUE_DAYS))
                        : now()->startOfMonth()->addMonths($i)->day($dueDay);

                    $extras = [];
                    if ($billingType === 'CREDIT_CARD') {
                        if ($i === 1 && !empty($cardExtras)) {
                            $extras = $cardExtras;
                        } elseif ($cardToken) {
                            $extras = [
                                'creditCardToken' => $cardToken,
                                'remoteIp'        => $cardExtras['remoteIp'] ?? request()->ip(),
                            ];
                        }
                    }

                    try {
                        $charge = $assasController->createdCharge($customer, $billingType, 1, $sale->price, 'Plano: ' . $plan->name, $dueDate, $extras);
                    } catch (\Throwable $e) {
                        throw new \RuntimeException("Erro ao gerar cobrança da parcela {$i}: " . $e->getMessage());
                    }

                    if ($charge === false || !empty($charge['error']) || empty($charge['id'])) {
                        $detail = is_array($charge) && !empty($charge['message'])
                            ? $charge['message']
                            : "Cobrança da parcela {$i} recusada pelo Asaas.";
                        throw new \RuntimeException($detail);
                    }

                    if (!empty($charge['creditCardToken'])) {
                        $cardToken = $charge['creditCardToken'];
                    }

                    $chargeIds[] = $charge['id'];

                    if ($i === 1) {
                        $firstCharge = $charge;
                    }

                    $this->createInvoice($seller, $sale, $plan, $i === 1 && $paymentMethod === 'CREDIT_CARD' ? 'Plano: ' . $plan->name : "Parcela {$i} do plano: " . $plan->name, $paymentMethod, $dueDay, $dueDate, $charge);
                    $this->createCommission($seller, $sale, $plan, $dueDate, $charge);
                }
            });

            if ($payNow && $billingType === 'PIX' && !empty($firstCharge['id'])) {
                $pix = $assasController->getPixQrCode($firstCharge['id']) ?: null;
            }
        } catch (\Throwable $e) {
            // O rollback do banco não desfaz o Asaas: as cobranças já emitidas precisam ser canceladas lá.
            foreach ($chargeIds as $chargeId) {
                if (!$assasController->canceledCharge($chargeId)) {
                    Log::error('Cobrança órfã no Asaas após falha no checkout', ['sale' => $sale->uuid, 'payment' => $chargeId]);
                }
            }

            $sale->status = 'canceled';
            $sale->save();

            throw $e instanceof \RuntimeException ? $e : new \RuntimeException($e->getMessage(), 0, $e);
        }

        return [
            'first' => $firstCharge,
            'pix'   => $pix,
        ];
    }

    private function sendPaymentEmail(User $customer, Plan $plan, Sale $sale, string $paymentMethod, ?array $firstCharge): void {
        if (empty($customer->email) || empty($firstCharge['invoiceUrl'])) {
            return;
        }

        MailConfig::apply();

        $labels = [
            'CREDIT_CARD' => 'Cartão de Crédito',
            'PIX'         => 'PIX',
            'BOLETO'      => 'Boleto Bancário',
        ];

        try {
            $mailer = Mail::to($customer->email);
            $bcc = MailConfig::bcc();
            if (!empty($bcc)) {
                $mailer->bcc($bcc);
            }

            $mailer->send(new SalePaymentMail([
                'subject'              => 'Pagamento do plano ' . $plan->name . ' - ' . config('app.name'),
                'fromEmail'            => config('mail.from.address'),
                'fromName'             => config('mail.from.name', config('app.name')),
                'customer_name'        => $customer->name,
                'plan_name'            => $plan->name,
                'price'                => (float) $sale->price,
                'payment_method_label' => $labels[$paymentMethod] ?? $paymentMethod,
                'payment_url'          => $firstCharge['invoiceUrl'],
            ]));
        } catch (\Throwable $e) {
            Log::error('Falha ao enviar e-mail de pagamento do checkout', [
                'sale'  => $sale->uuid,
                'email' => $customer->email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function createInvoice(?User $seller, Sale $sale, Plan $plan, $description, $paymentMethod, $dueDay, $dueDate, $paymentFeatures): Invoice {
        
        $invoice                   = new Invoice();
        $invoice->uuid             = Str::uuid();
        $invoice->user_id          = $seller?->id;
        $invoice->sale_id          = $sale->id;
        $invoice->name             = $plan->name;
        $invoice->description      = $description;
        $invoice->price            = $sale->price;
        $invoice->commission       = $sale->commission;
        $invoice->status           = 'pendent';
        $invoice->payment_due_date = $dueDate;
        $invoice->payment_token     = $paymentFeatures['id'] ?? null;
        $invoice->payment_url       = $paymentFeatures['invoiceUrl'] ?? null;
        $invoice->payment_features = [
            'payment_method' => $paymentMethod,
            'installments'   => self::INSTALLMENTS,
            'due_date'       => $dueDay,
        ];
        $invoice->save();
 
        return $invoice;
    }

    private function createCommission (?User $user, $sale, $plan, $dueDate, $paymentFeatures) {
        
        if (!$user || $sale->commission <= 0) {
            return;
        }
        
        $extract              = new Extract();
        $extract->uuid        = Str::uuid();
        $extract->user_id     = $user->id;
        $extract->sale_id     = $sale->id;
        $extract->title       = 'Comissão';
        $extract->description = 'Comissão pela venda N '.$sale->id.' - '.$sale->user->name;
        $extract->value       = $sale->commission;
        $extract->payment_date      = $dueDate;
        $extract->payment_token     = $paymentFeatures['id'] ?? null;
        $extract->payment_url       = $paymentFeatures['invoiceUrl'] ?? null;
        $extract->type              = 'commission';
        $extract->status            = 'pendent';
        $extract->save();
 
        return $extract;
    }
}
