<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model {
    
    protected $table = 'invoices';

    protected $fillable = [
        'user_id',
        'sale_id',
        'name',
        'description',
        'price',
        'commission',
        'payment_features',
        'payment_token',
        'payment_url',
        'payment_due_date',
        'status'
    ];

    protected $casts = [
        'payment_features' => 'array',
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
}