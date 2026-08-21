<?php

namespace App\Http\Controllers\Sale;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Gateway\AssasController;

use App\Models\Plan;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Sale;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SaleExternalController extends Controller {

    private const INSTALLMENTS      = 12;
    private const FIRST_DUE_DAYS    = 3;
    
    public function index(string $plan, ?string $parent = null) {

        $plan = Plan::firstWhere('slug', $plan);
        if (!$plan) {
            return redirect()->back()->with('info', 'O plano não está disponível no momento. Fale com um de nossos consultores para obter mais informações!');
        }

        $parent = $parent ? User::firstWhere('uuid', $parent) : null;

        return view('sale.index', compact('plan', 'parent'));
    }

    public function thankYou () {
        return view('sale.thank_you');
    }

    public function store (Request $request) {

        $validated = $request->validate([
            'plan_id'        => ['required', 'string'],
            'parent_id'      => ['required', 'string'],
            'name'           => ['required', 'string', 'max:255'],
            'document'       => ['required', 'string'],
            'phone'          => ['required', 'string'],
            'email'          => ['required', 'email'],
            'payment_method' => ['required', 'in:CREDIT_CARD,PIX,BOLETO'],
            'due_date'       => ['required', 'integer', 'min:1', 'max:28'],
        ], [
            'plan_id.required'        => 'Selecione um plano para continuar.',
            'plan_id.string'          => 'O plano selecionado é inválido.',
            'parent_id.required'      => 'Consultor não identificado. Verifique o link de acesso.',
            'parent_id.string'        => 'O Consultor informado é inválido.',
            'name.required'           => 'Informe seu nome completo.',
            'name.string'             => 'O nome informado é inválido.',
            'name.max'                => 'O nome não pode ter mais que :max caracteres.',
            'document.required'       => 'Informe seu CPF ou CNPJ.',
            'document.string'         => 'O documento informado é inválido.',
            'phone.required'          => 'Informe um telefone para contato.',
            'phone.string'            => 'O telefone informado é inválido.',
            'email.required'          => 'Informe seu e-mail.',
            'email.email'             => 'Informe um e-mail válido.',
            'payment_method.required' => 'Selecione uma forma de pagamento.',
            'payment_method.in'       => 'Forma de pagamento inválida. Escolha entre Cartão de Crédito, Pix ou Boleto.',
            'due_date.required'       => 'Informe o dia de vencimento das parcelas.',
            'due_date.integer'        => 'O dia de vencimento deve ser um número.',
            'due_date.min'            => 'O dia de vencimento deve ser entre :min e :max.',
            'due_date.max'            => 'O dia de vencimento deve ser entre :min e :max.',
        ]);

        $plan = Plan::where('uuid', $validated['plan_id'])->first();
        if (!$plan) {
            return redirect()->back()->withInput()->with('infor', 'Plano indisponível no momento!');
        }

        $seller = User::where('uuid', $validated['parent_id'])->first();
        if (!$seller) {
            return redirect()->back()->withInput()->with('infor', 'Falha ao aderir o plano, verifique com seu Consultor!');
        }

        $assasController = new AssasController();

        try {
            $customer = $assasController->createdCustomer($validated['name'], preg_replace('/\D/', '', $validated['document']), preg_replace('/\D/', '', $validated['phone']), preg_replace('/\D/', '', $validated['email']), $request->birth_date);
        } catch (\Throwable $e) {
            Log::error('Falha ao criar cliente no Asaas', ['plan_id' => $plan->uuid, 'seller' => $seller->uuid, 'error' => $e->getMessage()]);
            $customer = false;
        }

        if (isset($customer['error']) && ($customer['error'] === true || $customer == false)) {
            return redirect()->back()->withInput()->with('error', $customer['message']);
        }

        $sale              = new Sale();
        $sale->uuid        = Str::uuid();
        $sale->user_id     = $customer['user']->id;
        $sale->seller_id   = $seller->id;
        $sale->plan_id     = $plan->id;
        $sale->name        = $plan->name;
        $sale->description = $plan->name;
        $sale->price       = $plan->price;
        $sale->commission  = $plan->commission;
        $sale->status      = 'pendent';
        if ($sale->save()) {

            try {
                switch ($validated['payment_method']) {
                    case 'CREDIT_CARD':
                        $this->processInstallmentPlan($assasController, $customer['id'], $sale, 'CREDIT_CARD', $seller, $plan, $validated['due_date'], $validated['payment_method']);
                        break;
                    case 'PIX':
                    case 'BOLETO':
                        $this->processInstallmentPlan($assasController, $customer['id'], $sale, $validated['payment_method'], $seller, $plan, $validated['due_date'], $validated['payment_method']);
                        break;
                    default:
                        return redirect()->back()->withInput()->with('error', 'Método de pagamento inválido!');
                }
            } catch (\RuntimeException $e) {
                Log::error('Falha ao processar cobrança/assinatura de plano', ['plan_id' => $plan->uuid, 'seller'  => $seller->uuid, 'error'   => $e->getMessage()]);
                return redirect()->back()->withInput()->with('error', 'Falha ao aderir o plano, nossa equipe entrará em contato com você!');
            }
    
            return redirect()->route('thank-you')->withInput()->with('success', 'Plano contratado com sucesso!');
        }              
    }

    private function processInstallmentPlan(AssasController $assasController, $customer, $sale, string $billingType, User $seller, Plan $plan, int $dueDay, string $paymentMethod): void {
        DB::transaction(function () use ($assasController, $customer, $sale, $billingType, $seller, $plan, $dueDay, $paymentMethod) {
            for ($i = 1; $i <= self::INSTALLMENTS; $i++) {
                $dueDate = $i === 1 ? now()->addDays(self::FIRST_DUE_DAYS) : now()->startOfMonth()->addMonths($i)->day($dueDay);
                try {
                    $charge = $assasController->createdCharge($customer, $billingType, 1, $plan->price, 'Plano: ' . $plan->name, $dueDate);
                } catch (\Throwable $e) {
                    throw new \RuntimeException("Erro ao gerar cobrança da parcela {$i}: " . $e->getMessage());
                }
 
                if ($charge === false) {
                    throw new \RuntimeException("Cobrança da parcela {$i} recusada pelo Asaas.");
                }
                
                $this->createInvoice($seller, $sale, $plan, $i === 1 && $paymentMethod === 'CREDIT_CARD' ? 'Plano: ' . $plan->name : "Parcela {$i} do plano: " . $plan->name, $paymentMethod, $dueDay, $dueDate, $charge);
            }
        });
    }
 
    private function createInvoice(User $seller, Sale $sale, Plan $plan, $description, $paymentMethod, $dueDay, $dueDate, $paymentFeatures): Invoice {
        
        $invoice                   = new Invoice();
        $invoice->uuid             = Str::uuid();
        $invoice->user_id          = $seller->id;
        $invoice->sale_id          = $sale->id;
        $invoice->name             = $plan->name;
        $invoice->description      = $description;
        $invoice->price            = $plan->price;
        $invoice->commission       = $plan->commission;
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
}
