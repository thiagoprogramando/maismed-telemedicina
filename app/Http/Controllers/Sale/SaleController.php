<?php

namespace App\Http\Controllers\Sale;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Gateway\AssasController;
use App\Models\Invoice;
use App\Models\Plan;
use App\Support\AssasConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use Carbon\Carbon;
use App\Models\Sale;
use App\Models\User;

class SaleController extends Controller {

    public function create()
    {
        $user = Auth::user();
        if (!in_array($user->roles, ['admin', 'collaborator'], true)) {
            return redirect()->route('app')->with('infor', 'Acesso restrito a consultores e administradores.');
        }

        $consultants = [];
        if ($user->roles === 'admin') {
            $consultants = User::whereIn('roles', ['admin', 'collaborator'])
                ->orderBy('name')
                ->get();
        }

        return view('app.Sale.create', [
            'plans'       => Plan::where('status', 'active')->orderBy('price')->get(),
            'consultants' => $consultants,
        ]);
    }

    public function storeInvite(Request $request)
    {
        $auth = Auth::user();
        if (!in_array($auth->roles, ['admin', 'collaborator'], true)) {
            return redirect()->route('app')->with('infor', 'Acesso restrito a consultores e administradores.');
        }

        $validated = $request->validate([
            'plan_id'         => ['required', 'string'],
            'seller_uuid'     => ['nullable', 'string'],
            'name'            => ['required', 'string', 'max:255'],
            'document'        => ['required', 'string'],
            'birth_date'      => ['required', 'date', 'before:today'],
            'phone'           => ['required', 'string'],
            'email'           => ['required', 'email'],
            'payment_method'  => ['required', 'in:CREDIT_CARD,PIX,BOLETO'],
            'due_date'        => ['required', 'integer', 'min:1', 'max:28'],
            'quantity'        => ['nullable', 'integer', 'min:1'],
            'dependents'      => ['nullable', 'integer', 'min:0'],
            'postal_code'     => ['nullable', 'string', 'max:12'],
            'address'         => ['nullable', 'string', 'max:255'],
            'address_number'  => ['nullable', 'string', 'max:20'],
            'address_city'    => ['nullable', 'string', 'max:120'],
            'address_provincy'=> ['nullable', 'string', 'max:120'],
        ], [
            'plan_id.required'        => 'Selecione um plano.',
            'name.required'           => 'Informe o nome completo.',
            'document.required'       => 'Informe o CPF ou CNPJ.',
            'birth_date.required'     => 'Informe a data de nascimento.',
            'phone.required'          => 'Informe o telefone.',
            'email.required'          => 'Informe o e-mail.',
            'payment_method.required' => 'Selecione a forma de pagamento.',
            'due_date.required'       => 'Informe o dia de vencimento.',
        ]);

        $plan = Plan::where('uuid', $validated['plan_id'])->where('status', 'active')->first();
        if (!$plan) {
            return redirect()->back()->withInput()->with('error', 'Plano indisponível.');
        }

        if ($auth->roles === 'admin' && !empty($validated['seller_uuid'])) {
            $seller = User::where('uuid', $validated['seller_uuid'])
                ->whereIn('roles', ['admin', 'collaborator'])
                ->first();
            if (!$seller) {
                return redirect()->back()->withInput()->with('error', 'Consultor inválido.');
            }
        } else {
            $seller = $auth;
        }

        $quote = $plan->quote($validated['quantity'] ?? null, $validated['dependents'] ?? null);
        if ((float) $quote['price'] < 5) {
            return redirect()->back()->withInput()->with('error', 'O valor do plano precisa ser de pelo menos R$ 5,00.');
        }

        if (!AssasConfig::isConfigured()) {
            return redirect()->back()->withInput()->with('error', 'Pagamentos indisponíveis. Configure o Gateway Asaas.');
        }

        $documentDigits = preg_replace('/\D/', '', $validated['document']);
        $emailOwner = User::withTrashed()
            ->where('email', $validated['email'])
            ->where('document', '!=', $documentDigits)
            ->first();
        if ($emailOwner) {
            return redirect()->back()->withInput()->with('error', 'Este e-mail já está cadastrado em outra conta.');
        }

        $assas = app(AssasController::class);
        try {
            $customer = $assas->createdCustomer(
                $validated['name'],
                $documentDigits,
                preg_replace('/\D/', '', $validated['phone']),
                $validated['email'],
                $validated['birth_date']
            );
        } catch (\Throwable $e) {
            Log::error('Falha ao criar cliente no convite interno', ['error' => $e->getMessage()]);
            $customer = ['error' => true, 'message' => 'Não foi possível cadastrar no gateway de pagamento.'];
        }

        if (!is_array($customer) || !empty($customer['error']) || empty($customer['user'])) {
            return redirect()->back()->withInput()->with(
                'error',
                is_array($customer) && !empty($customer['message']) ? $customer['message'] : 'Verifique os dados e tente novamente.'
            );
        }

        $customer['user']->fill([
            'postal_code'     => preg_replace('/\D/', '', (string) ($validated['postal_code'] ?? '')) ?: null,
            'address'          => $validated['address'] ?? null,
            'address_number'   => $validated['address_number'] ?? null,
            'address_city'     => $validated['address_city'] ?? null,
            'address_provincy' => $validated['address_provincy'] ?? null,
        ])->save();

        $sale                   = new Sale();
        $sale->uuid             = (string) Str::uuid();
        $sale->user_id          = $customer['user']->id;
        $sale->seller_id        = $seller->id;
        $sale->plan_id          = $plan->id;
        $sale->name             = $plan->name;
        $sale->description      = $plan->name;
        $sale->price            = $quote['price'];
        $sale->commission       = $quote['commission'];
        $sale->quantity         = $quote['quantity'];
        $sale->dependents       = $quote['dependents'];
        $sale->payment_method   = $validated['payment_method'];
        $sale->pay_timing       = 'now';
        $sale->payment_features = [
            'due_date'     => (int) $validated['due_date'],
            'invited_by'   => $auth->uuid,
            'needs_accept' => true,
        ];
        $sale->status = 'pendent';
        $sale->save();

        $payUrl = route('pay', $sale->uuid);

        return redirect()->route('sale', $sale->uuid)->with([
            'success'  => 'Beneficiário incluído. Envie o link abaixo para o cliente aceitar o contrato e pagar.',
            'pay_link' => $payUrl,
        ]);
    }


    public function index (Request $request) {

        $validated = $request->validate([
            'seller_uuid'=> ['nullable', 'string'],
            'search'     => ['nullable', 'string', 'max:255'],
            'document'   => ['nullable', 'string', 'max:20'],
            'date_start' => ['nullable', 'date'],
            'date_end'   => ['nullable', 'date', 'after_or_equal:date_start'],
        ]);

        $user   = Auth::user();
        $query  = Sale::query();
    
        if ($user->roles === 'admin') {
            if (!empty($validated['seller_uuid'])) {
                $sellerId = User::where('uuid', $validated['seller_uuid'])->value('id');
                if ($sellerId) {
                    $query->where('seller_id', $sellerId);
                }
            }
        } else {
            $query->where('seller_id', $user->id);
        }
    
        if (!empty($validated['search'])) {
            $term = $this->escapeLikeTerm($validated['search']);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                ->orWhereHas('user', function ($userQuery) use ($term) {
                    $userQuery->where('name', 'like', "%{$term}%");
                });
            });
        }
    
        if (!empty($validated['document'])) {
            $documentDigits = preg_replace('/\D/', '', $validated['document']);
            if ($documentDigits !== '') {
                $query->whereHas('user', function ($userQuery) use ($documentDigits) {
                    $userQuery->whereRaw(
                        "REPLACE(REPLACE(REPLACE(document, '.', ''), '-', ''), '/', '') LIKE ?",
                        ["%{$documentDigits}%"]
                    );
                });
            }
        }
    
        if (!empty($validated['date_start'])) {
            $query->where('created_at', '>=', Carbon::parse($validated['date_start'])->startOfDay());
        }
        if (!empty($validated['date_end'])) {
            $query->where('created_at', '<=', Carbon::parse($validated['date_end'])->endOfDay());
        }

        // Fatura "Pago" + venda "Pendente" = inconsistência; alinha antes de listar.
        Sale::healPaidPending();
    
        $sales  = $query->with(['user', 'plan'])->latest()->paginate(30)->withQueryString();
 
        return view('app.Sale.index', [
            'sales'   => $sales,
            'filters' => $validated,
        ]);
    }
    
    public function show ($uuid) {

        Sale::healPaidPending();

        $sale = Sale::with(['user', 'plan', 'invoices'])->where('uuid', $uuid)->firstOrFail();

        $currentSeller = $sale->seller_id ? User::find($sale->seller_id) : null;
        $consultants   = [];

        if (Auth::user()->roles === 'admin') {
            $consultants = User::whereIn('roles', ['admin', 'collaborator'])
                ->when($sale->seller_id, fn ($q) => $q->where('id', '!=', $sale->seller_id))
                ->orderBy('name')
                ->get();
        }

        return view('app.Sale.show', [
            'sale'          => $sale,
            'currentSeller' => $currentSeller,
            'consultants'   => $consultants,
        ]);
    }

    public function destroy (Request $request, $uuid) {

        if (Auth::user()->roles !== 'admin') {
            return redirect()->back()->with('infor', 'Você não tem permissão para cancelar uma Venda/Beneficiário, envie uma solicitação para revisão!');
        }

        $sale = Sale::where('uuid', $uuid)->first();
        if (!$sale) {
            return redirect()->back()->with('error', 'Venda/Beneficiário não localizado/ou disponível, verifique os dados e tente novamente!');
        }

         $assasController = new AssasController();
 
        try {
            DB::transaction(function () use ($sale, $assasController) {
                
                $invoices = Invoice::where('sale_id', $sale->id)->whereIn('status', ['pendent', 'canceled'])->get();
                foreach ($invoices as $invoice) {
                    
                    if (empty($invoice->payment_token)) {
                        $invoice->status = 'canceled';
                        $invoice->save();
                        continue;
                    }
    
                    $cancelled = $assasController->canceledCharge($invoice->payment_token);
                    if ($cancelled === false) {
                        throw new \RuntimeException(
                            "Falha ao cancelar cobrança na Asaas (invoice {$invoice->uuid})."
                        );
                    }
    
                    $invoice->status = 'canceled';
                    $invoice->save();
                }
    
                $sale->status = 'canceled';
                $sale->save();
    
                User::where('parent_id', $sale->user_id)->orWhere('id', $sale->user_id)->update(['status' => 'inactive']);
            });
        } catch (\Throwable $e) {
            Log::error('Falha ao cancelar Venda/Beneficiário', [
                'sale'  => $sale->uuid,
                'error' => $e->getMessage(),
            ]);
    
            return redirect()->back()->with('error', 'Falha ao cancelar a Venda/Beneficiário, envie uma solicitação para revisão!');
        }
    
        return redirect()->back()->with('success', 'Venda/Beneficiário cancelada com sucesso, os dependentes vinculados foram desativados!');
    }

    public function destroyAll(Request $request)
    {
        if (Auth::user()->roles !== 'admin') {
            return redirect()->back()->with('infor', 'Apenas o administrador pode excluir todos os beneficiários.');
        }

        $request->validate([
            'confirm' => ['required', 'in:EXCLUIR'],
        ], [
            'confirm.required' => 'Digite EXCLUIR para confirmar.',
            'confirm.in'       => 'Confirmação inválida. Digite EXCLUIR.',
        ]);

        $count = 0;

        try {
            DB::transaction(function () use (&$count) {
                $sales = Sale::whereIn('status', ['pendent', 'active'])->get();
                foreach ($sales as $sale) {
                    Invoice::where('sale_id', $sale->id)
                        ->whereIn('status', ['pendent', 'canceled'])
                        ->update(['status' => 'canceled']);

                    $sale->status = 'canceled';
                    $sale->save();

                    if ($sale->user_id) {
                        User::where('parent_id', $sale->user_id)
                            ->orWhere('id', $sale->user_id)
                            ->update(['status' => 'inactive']);
                    }

                    $count++;
                }
            });
        } catch (\Throwable $e) {
            Log::error('Falha ao excluir todos os beneficiários', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Não foi possível concluir a exclusão em massa.');
        }

        Log::warning('Admin excluiu beneficiários em massa', [
            'admin' => Auth::user()->uuid,
            'count' => $count,
        ]);

        return redirect()->route('sales')->with('success', "{$count} venda(s)/beneficiário(s) cancelada(s).");
    }

    private function escapeLikeTerm(string $term): string {
        return str_replace(['%', '_'], ['\\%', '\\_'], $term);
    }
}
