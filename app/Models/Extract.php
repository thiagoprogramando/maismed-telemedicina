<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Extract extends Model {
    
    protected $table = 'extracts';

    protected $fillable = [
        'uuid',
        'user_id',
        'sale_id',
        'title',
        'description',
        'value',
        'payment_features',
        'payment_token',
        'payment_url',
        'payment_date',
        'type',
        'status'
    ];

    protected $casts = [
        'payment_features'  => 'array',
        'payment_date'      => 'datetime',
    ];

    public function sale () {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function user () {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function labelStatus () {
        switch ($this->status) {
            case 'pendent':
                return '<span class="badge bg-primary"> Pendente </span>';
            case 'paid':
                return '<span class="badge bg-success"> Pago </span>';
            case 'canceled':
                return '<span class="badge bg-danger"> Cancelado </span>';
            default:
                return '<span class="badge bg-info"> N/a </span>';
        }
    }

    public function labelType () {
        switch ($this->type) {
            case 'debit':
                return '<span class="badge bg-primary"> Débito </span>';
            case 'commission':
                return '<span class="badge bg-success"> Comissão </span>';
            case 'credit':
                return '<span class="badge bg-info"> Crédito </span>';
            case 'payment':
                return '<span class="badge bg-danger"> Pagamento </span>';
            default:
                return '<span class="badge bg-info"> N/a </span>';
        }
    }
}
