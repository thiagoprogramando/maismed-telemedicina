<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model {
    
    protected $table = 'sales';

    protected $fillable = [
        'user_id',
        'seller_id',
        'plan_id',
        'name',
        'description',
        'price',
        'commission',
        'quantity',
        'dependents',
        'payment_method',
        'pay_timing',
        'subscription_token',
        'contract_months',
        'payment_features',
        'status'
    ];

    protected $casts = [
        'payment_features' => 'array',
    ];

    // Limite de pessoas (titular + dependentes) da venda. Vendas sem quantidade contratada seguem o limite do plano.
    public function maxUsers () {

        if ($this->quantity === null) {
            return $this->plan->max_users;
        }

        if ($this->plan->isBusiness()) {
            return $this->quantity + $this->dependents;
        }

        return max($this->quantity, $this->plan->includedUsers());
    }

    public function sale () {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function invoices () {
        return $this->hasMany(Invoice::class, 'sale_id');
    }

    public function user () {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function plan () {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function labelStatus () {
        switch ($this->status) {
            case 'active':
                return '<span class="badge bg-success">Ativo</span>';
                break;
            case 'pendent':
                return '<span class="badge bg-warning">Pendente</span>';
                break;
            case 'canceled':
                return '<span class="badge bg-danger">Cancelada</span>';
                break;
            default:
                return '<span class="badge bg-info">N/a</span>';
                break;
        }
    }

    /**
     * Venda pendente com fatura paga (webhook antigo / falha parcial) → ativa venda e titular.
     */
    public static function healPaidPending(): int
    {
        $sales = static::query()
            ->where('status', 'pendent')
            ->whereHas('invoices', fn ($q) => $q->where('status', 'paid'))
            ->with('user')
            ->get();

        foreach ($sales as $sale) {
            $sale->status = 'active';
            $sale->save();

            if ($sale->user && $sale->user->status !== 'active') {
                $sale->user->status = 'active';
                $sale->user->save();
                User::where('parent_id', $sale->user_id)->update(['status' => 'active']);
            }
        }

        return $sales->count();
    }
}
