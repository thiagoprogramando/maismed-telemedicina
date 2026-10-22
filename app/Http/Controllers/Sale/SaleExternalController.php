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

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SaleExternalController extends Controller {

    private const INSTALLMENTS      = 12;
    private const FIRST_DUE_DAYS    = 3;
    
    public function showcase(?string $parent = null) {

        $plans  = Plan::where('status', 'active')->orderBy('price', 'asc')->get();
        $parent = $parent ? User::firstWhere('uuid', $parent) : null;

        return view('sale.showcase', compact('plans', 'parent'));
    }

    public function index(Request $request, string $plan, ?string $parent = null) {

        $plan = Plan::firstWhere('slug', $plan);
        if (!$plan) {
            return redirect()->back()->with('info', 'O plano não está disponível no momento. Fale com um de nossos consultores para obter mais informações!');
        }

        $parent = $parent ? User::firstWhere('uuid', $parent) : null;

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
            'card_holder'          => ['nullable', Rule::requiredIf(fn () => $request->input('payment_method') === 'CREDIT_CARD' && $request->input('pay_timing') === 'now'), 'string', 'max:255'],
            'card_number'          => ['nullable', Rule::requiredIf(fn () => $request->input('payment_method') === 'CREDIT_CARD' && $request->input('pay_timing') === 'now'), 'string', 'max:25'],
            'card_expiry'          => ['nullable', Rule::requiredIf(fn () => $request->input('payment_method') === 'CREDIT_CARD' && $request->input('pay_timing') === 'now'), 'string', 'max:7'],
            'card_cvv'             => ['nullable', Rule::requiredIf(fn () => $request->input('payment_method') === 'CREDIT_CARD' && $request->input('pay_timing') === 'now'), 'string', 'max:4'],
            'postal_code'           => ['nullable', 'string', 'max:12'],
            'address_number'       => ['nullable', 'string', 'max:20'],
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
            'phone.required'                 => 'Informe um telefone para contato.',
            'phone.string'                   => 'O telefone informado é inválido.',
            'email.required'                 => 'Informe seu e-mail.',
            'email.email'                    => 'Informe um e-mail válido.',
            'payment_method.required'        => 'Selecione uma forma de pagamento.',
            'payment_method.in'              => 'Forma de pagamento inválida. Escolha entre Cartão de Crédito, Pix ou Boleto.',
            'pay_timing.required'            => 'Escolha se deseja pagar agora ou depois.',
            'pay_timing.in'                  => 'Opção de pagamento inválida.',
            'card_holder.required'           => 'Informe o nome impresso no cartão.',
            'card_number.required'           => 'Informe o número do cartão.',
            'card_expiry.required'           => 'Informe a validade do cartão.',
            'card_cvv.required'              => 'Informe o CVV do cartão.',
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
            $seller = User::where('uuid', $validated['parent_id'])->first();
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

        if (!AssasConfig::isConfigured()) {
            Log::error('Checkout bloqueado: Asaas sem URL/token (.env ou admin/gateway).');
            return redirect()->back()->withInput()->with('error', 'Pagamentos indisponíveis no momento. Tente novamente em breve!');
        }

        $assasController = app(AssasController::class);

        try {
            $customer = $assasController->createdCustomer($validated['name'], preg_replace('/\D/', '', $validated['document']), preg_replace('/\D/', '', $validated['phone']), $validated['email'], $request->birth_date);
        } catch (\Throwable $e) {
            Log::error('Falha ao criar cliente no Asaas', ['plan_id' => $plan->uuid, 'seller' => $seller?->uuid, 'error' => $e->getMessage()]);
            $customer = false;
        }

        if (!is_array($customer) || !empty($customer['error']) || empty($customer['user'])) {
            return redirect()->back()->withInput()->with('error', 'Verifique seus dados e tente novamente!');
        }

        $sale              = new Sale();
        $sale->uuid        = Str::uuid();
        $sale->user_id     = $customer['user']->id;
        $sale->seller_id   = $seller?->id;
        $sale->plan_id     = $plan->id;
        $sale->name        = $plan->name;
        $sale->description = $plan->name;
        $sale->price       = $quote['price'];
        $sale->commission  = $seller ? $quote['commission'] : 0;
        $sale->quantity    = $quote['quantity'];
        $sale->dependents  = $quote['dependents'];
        $sale->status      = 'pendent';
        if (!$sale->save()) {
            Log::error('Falha ao salvar venda do checkout', ['plan_id' => $plan->uuid, 'seller' => $seller?->uuid]);
            return redirect()->back()->withInput()->with('error', 'Falha ao aderir o plano, tente novamente!');
        }

        $this->createMembers($customer['user'], $members, $plan->isBusiness());

        $payNow = ($validated['pay_timing'] ?? 'later') === 'now';
        $cardExtras = [];

        if ($payNow && $validated['payment_method'] === 'CREDIT_CARD') {
            $expiry = preg_replace('/\D/', '', (string) $request->input('card_expiry'));
            if (strlen($expiry) !== 4 && strlen($expiry) !== 6) {
                return redirect()->back()->withInput()->with('error', 'Validade do cartão inválida. Use MM/AA.');
            }

            $month = substr($expiry, 0, 2);
            $year  = strlen($expiry) === 4 ? ('20' . substr($expiry, 2, 2)) : substr($expiry, 2, 4);

            $cardExtras = [
                'creditCard' => [
                    'holderName'  => trim((string) $request->input('card_holder')),
                    'number'      => preg_replace('/\D/', '', (string) $request->input('card_number')),
                    'expiryMonth' => $month,
                    'expiryYear'  => $year,
                    'ccv'         => preg_replace('/\D/', '', (string) $request->input('card_cvv')),
                ],
                'creditCardHolderInfo' => [
                    'name'          => $validated['name'],
                    'email'         => $validated['email'],
                    'cpfCnpj'       => preg_replace('/\D/', '', $validated['document']),
                    'postalCode'     => preg_replace('/\D/', '', (string) ($request->input('postal_code') ?: '00000000')),
                    'addressNumber' => (string) ($request->input('address_number') ?: 'S/N'),
                    'phone'         => preg_replace('/\D/', '', $validated['phone']),
                    'mobilePhone'   => preg_replace('/\D/', '', $validated['phone']),
                ],
                'remoteIp' => $request->ip(),
            ];
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
                $payNow,
                $cardExtras
            );
        } catch (\RuntimeException $e) {
            Log::error('Falha ao processar cobrança/assinatura de plano', ['plan_id' => $plan->uuid, 'seller' => $seller?->uuid, 'sale' => $sale->uuid, 'error' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('error', 'Falha ao aderir o plano, nossa equipe entrará em contato com você!');
        }

        if (!$payNow) {
            $this->sendPaymentEmail($customer['user'], $plan, $sale, $validated['payment_method'], $paymentResult['first'] ?? null);
        }

        return redirect()->route('thank-you')->with([
            'success' => $payNow
                ? 'Plano contratado! Finalize o pagamento abaixo.'
                : 'Plano salvo! Enviamos o link de pagamento para o seu e-mail.',
            'checkout' => [
                'pay_now'        => $payNow,
                'payment_method' => $validated['payment_method'],
                'plan_name'      => $plan->name,
                'price'          => (float) $sale->price,
                'email'          => $validated['email'],
                'invoice_url'    => $paymentResult['first']['invoiceUrl'] ?? null,
                'pix'            => $paymentResult['pix'] ?? null,
                'card_status'    => $paymentResult['first']['status'] ?? null,
            ],
        ]);
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

    private function defaultSeller(): ?User {

        $uuid = config('services.maismed.default_seller');
        if (empty($uuid)) {
            return null;
        }

        $seller = User::where('uuid', $uuid)->first();
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

                    if ($charge === false || empty($charge['id'])) {
                        throw new \RuntimeException("Cobrança da parcela {$i} recusada pelo Asaas.");
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

        $labels = [
            'CREDIT_CARD' => 'Cartão de Crédito',
            'PIX'         => 'PIX',
            'BOLETO'      => 'Boleto Bancário',
        ];

        try {
            Mail::to($customer->email)->send(new SalePaymentMail([
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
