<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Plan extends Model {
    
    use SoftDeletes;

    protected $fillable = [
        'created_by',
        'name',
        'slug',
        'description',
        'terms',
        'price',
        'commission',
        'max_users',
        'features',
        'status',
        'time',
    ];

    public function user () {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusLabel () {
        if ($this->status == 'active') {
            return 'Ativo';
        }

        return 'Inativo';
    }

    public function timeLabel () {

        switch ($this->time) {
            case 'month':
                $status = 'Mensal';
                break;
            case 'semi-annual':
                $status = 'Semestral';
                break;
            case 'year':
                $status = 'Anual';
                break;
            case 'lifetime':
                $status = 'Vitálicio';
                break;
        }

        return $status;
    }

    protected static function boot() {

        parent::boot();

        static::creating(function (Plan $plan) {
            if (empty($plan->uuid)) {
                $plan->uuid = (string) Str::uuid();
            }

            if (empty($plan->slug)) {
                $plan->slug = $plan->generateUniqueSlug($plan->name);
            }
        });
    }

    public function generateUniqueSlug(string $name): string {

        $slug           = Str::slug($name);
        $originalSlug   = $slug;
        $count          = 1;

        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        return $slug;
    }
}
