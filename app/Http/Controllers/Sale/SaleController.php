<?php

namespace App\Http\Controllers\Sale;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Gateway\AssasController;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;
use App\Models\Sale;
use App\Models\User;

class SaleController extends Controller {

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
        return view('app.Sale.show', [
            'sale' => $sale,
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
