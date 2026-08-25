<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Http\Request;

class FinanceController extends Controller {
    
    public function invoices (Request $request) {

        $validated = $request->validate([
            'cpfcnpj'          => ['required', 'string'],
            'nextInvoice'      => ['sometimes', 'boolean'],
            'firstInvoice'     => ['sometimes', 'boolean'],
            'pendentInvoice'   => ['sometimes', 'boolean'],
            'cancelledInvoice' => ['sometimes', 'boolean'],
            'paidInvoice'      => ['sometimes', 'boolean'],
        ]);
    
        $response = [];
        $document = preg_replace('/\D/', '', $validated['cpfcnpj']);
        if ($document === '') {
            return response()->json(['message' => 'CPF/CNPJ inválido!'], 422);
        }
 
        $user = User::where('document', $document)->first();
        if (!$user) {
            return response()->json(['message' => 'Usuário não encontrado para o CPF/CNPJ informado!'], 404);
        }
 
        if ($request->boolean('nextInvoice')) {
            $response['nextInvoice'] = Invoice::where('user_id', $user->id)->where('status', 'pendent')->orderBy('payment_due_date', 'asc')->first();
        }
        if ($request->boolean('firstInvoice')) {
            $response['firstInvoice'] = Invoice::where('user_id', $user->id)->oldest('created_at')->first();
        }
        if ($request->boolean('pendentInvoice')) {
            $response['pendentInvoice'] = Invoice::where('user_id', $user->id)->where('status', 'pendent')->orderBy('payment_due_date')->get();
        }
        if ($request->boolean('cancelledInvoice')) {
            $response['cancelledInvoice'] = Invoice::where('user_id', $user->id)
                ->where(function ($query) {
                    $query->where('status', 'canceled')
                        ->orWhere(function ($overdue) {
                            $overdue->where('status', 'pendent')
                                ->where('payment_due_date', '<', now()->toDateString());
                        });
                })->orderBy('payment_due_date')->get();
        }
        if ($request->boolean('paidInvoice')) {
            $response['paidInvoice'] = Invoice::where('user_id', $user->id)->where('status', 'paid')->orderBy('payment_due_date')->get();
        }
    
        return response()->json([
            'invoices' => $response,
        ]);
    }
}
