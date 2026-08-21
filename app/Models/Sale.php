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
        'payment_features',
        'status'
    ];

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
}
