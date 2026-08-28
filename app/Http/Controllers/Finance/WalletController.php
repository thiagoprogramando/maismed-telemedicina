<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Extract;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller {
    
    public function show (Request $request) {

        $request->validate([
            'user_id'    => ['nullable', 'integer', 'exists:users,uuid'],
            'date_start' => ['nullable', 'date'],
            'date_end'   => ['nullable', 'date', 'after_or_equal:date_start'],
        ], [
            'user_id.integer'          => 'O usuário informado é inválido!',
            'user_id.exists'           => 'O usuário informado não foi encontrado!',
            'date_start.date'          => 'A data inicial informada é inválida!',
            'date_end.date'            => 'A data final informada é inválida!',
            'date_end.after_or_equal'  => 'A data final deve ser igual ou posterior à data inicial!',
        ]);

        $user       = $request->user_id ? User::where('uuid', $request->user_id)->first() : Auth::user();
        $extracts   = Extract::where('user_id', $user->id)
                        ->when($request->date_start, function ($query, $dateStart) { $query->whereDate('payment_date', '>=', $dateStart); })
                        ->when($request->date_end, function ($query, $dateEnd) { $query->whereDate('payment_date', '<=', $dateEnd); })
                        ->when($request->type, function ($query, $type) { $query->where('type', $type); })
                        ->when($request->status, function ($query, $status) { $query->where('status', $status); })
                        ->orderByDesc('payment_date')
                        ->paginate(30);

        return view ('app.Finance.show', [
            'user'      => $user,
            'extracts'  => $extracts
        ]);
    }
}
