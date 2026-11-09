<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Sale;
use App\Models\User;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppController extends Controller {
    
    public function index(Request $request) {

        $period = (int) $request->query('period', 30);
        if (!in_array($period, [7, 15, 30], true)) {
            $period = 30;
        }

        $from = Carbon::now()->subDays($period)->startOfDay();
        $user = Auth::user();
        $isStaff = in_array($user->roles, ['admin', 'collaborator'], true);

        $plans = Plan::where('status', 'active')->get();

        $salesQuery = Sale::with(['user', 'plan', 'invoices'])->whereHas('user');
        if ($user->roles === 'collaborator') {
            $salesQuery->where('seller_id', $user->id);
        } elseif ($user->roles === 'admin') {
            // global
        } else {
            $salesQuery->where('user_id', $user->id);
        }

        $sales = (clone $salesQuery)->latest()->paginate(30);
        $contracts = Sale::with(['user', 'plan', 'invoices'])
            ->where('user_id', $user->id)
            ->whereHas('user')
            ->latest()
            ->paginate(30);

        $metrics = null;
        if ($isStaff) {
            $scopedSales = Sale::query()->whereHas('user');
            if ($user->roles === 'collaborator') {
                $scopedSales->where('seller_id', $user->id);
            }

            $saleIds = (clone $scopedSales)->pluck('id');

            $activeSales = (clone $scopedSales)->where('status', 'active')->count();
            $pendingSales = (clone $scopedSales)->where('status', 'pendent')->count();

            $activeUsers = User::query()
                ->where('status', 'active')
                ->where('roles', 'user')
                ->when($user->roles === 'collaborator', function ($q) use ($user) {
                    $q->whereIn('id', Sale::where('seller_id', $user->id)->pluck('user_id'));
                })
                ->count();

            $paidInvoices = Invoice::query()
                ->whereIn('sale_id', $saleIds)
                ->where('status', 'paid')
                ->where('updated_at', '>=', $from)
                ->count();

            $unpaidInvoices = Invoice::query()
                ->whereIn('sale_id', $saleIds)
                ->where('status', 'pendent')
                ->count();

            $dueSoon = Invoice::query()
                ->whereIn('sale_id', $saleIds)
                ->where('status', 'pendent')
                ->whereBetween('payment_due_date', [now()->startOfDay(), now()->addDays($period)->endOfDay()])
                ->count();

            $newSalesInPeriod = (clone $scopedSales)->where('created_at', '>=', $from)->count();

            $metrics = [
                'period'           => $period,
                'active_sales'     => $activeSales,
                'pending_sales'    => $pendingSales,
                'active_users'     => $activeUsers,
                'paid_invoices'    => $paidInvoices,
                'unpaid_invoices'  => $unpaidInvoices,
                'due_soon'         => $dueSoon,
                'new_sales'        => $newSalesInPeriod,
            ];
        }

        return view('app.app', [
            'plans'     => $plans,
            'sales'     => $sales,
            'contracts' => $contracts,
            'metrics'   => $metrics,
            'period'    => $period,
        ]);
    }
}
