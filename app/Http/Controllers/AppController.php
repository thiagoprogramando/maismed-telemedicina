<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Sale;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppController extends Controller {
    
    public function index() {

        $plans      = Plan::where('status', 'active')->get();
        $sales      = Sale::with(['user', 'plan', 'invoices'])->where('seller_id', Auth::user()->id)->latest()->paginate(30);
        $contracts  = Sale::with(['user', 'plan', 'invoices'])->where('user_id', Auth::user()->id)->latest()->paginate(30);

        return view('app.app', [
            'plans'     => $plans,
            'sales'     => $sales,
            'contracts' => $contracts
        ]);
    }
}
