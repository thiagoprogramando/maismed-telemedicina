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
        'type',
        'badge',
        'description',
        'terms',
        'price',
        'commission',
        'included_users',
        'extra_price',
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

    public function typeLabel () {
        if ($this->type == 'business') {
            return 'Empresarial';
        }

        return 'Familiar';
    }

    public function periodSuffix () {

        switch ($this->time) {
            case 'semi-annual':
                return '/semestre';
            case 'year':
                return '/ano';
            case 'lifetime':
                return ' único';
        }

        return '/mês';
    }

    public function featuresList () {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $this->features))));
    }

    public function isBusiness (): bool {
        return $this->type === 'business';
    }

    public function includedUsers (): int {
        return max(1, (int) $this->included_users);
    }

    // Maior quantidade que o cliente pode escolher no simulador.
    public function simulatorMax (): int {
        return max($this->includedUsers(), (int) ($this->max_users ?: ($this->isBusiness() ? 500 : 20)));
    }

    public function hasSimulator (): bool {
        return $this->isBusiness() || $this->cents($this->extra_price) > 0;
    }

    public function defaultQuantity (): int {
        return $this->isBusiness() ? 5 : $this->includedUsers();
    }

    // Fonte única do valor contratado: a vitrine simula, a adesão cobra o que este método devolve.
    // Os cálculos são feitos em centavos; quantidades fora do intervalo são ajustadas aos limites do plano.
    public function quote ($quantity = null, $dependents = null): array {

        $price      = $this->cents($this->price);
        $extra      = $this->cents($this->extra_price);
        $commission = $this->cents($this->commission);

        if (!$this->hasSimulator()) {
            return [
                'quantity'   => null,
                'dependents' => 0,
                'price'      => $this->decimal($price),
                'commission' => $this->decimal($commission),
            ];
        }

        $max        = $this->simulatorMax();
        $quantity   = min($max, max(1, (int) ($quantity ?? $this->defaultQuantity())));
        $dependents = $this->isBusiness() && $extra > 0 ? min($max, max(0, (int) $dependents)) : 0;

        if ($this->isBusiness()) {
            $total = $quantity * $price + $dependents * $extra;
        } else {
            $total = $price + max(0, $quantity - $this->includedUsers()) * $extra;
        }

        return [
            'quantity'   => $quantity,
            'dependents' => $dependents,
            'price'      => $this->decimal($total),
            'commission' => $this->decimal($commission * ($quantity + $dependents)),
        ];
    }

    private function cents ($value): int {
        return (int) round(((float) $value) * 100);
    }

    private function decimal (int $cents): string {
        return number_format($cents / 100, 2, '.', '');
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
