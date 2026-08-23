<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]

class User extends Authenticatable {

    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'avatar',
        'name',
        'email',
        'phone',
        'document',
        'birth_date',
        'postal_code',
        'address',
        'address_number',
        'address_city',
        'address_provincy', 
        'password',
        'token',
        'status',
        'roles',
    ];

    public function parent() {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function children() {
        return $this->hasMany(User::class, 'parent_id');
    }

    public function invoices() {
        return $this->hasMany(Invoice::class);
    }

    public function maskName() {
        
        if (empty($this->name)) {
            return '';
        }

        $nameParts = explode(' ', trim($this->name));

        if (count($nameParts) === 1) {
            return $nameParts[0];
        }

        return $nameParts[0] . ' ' . $nameParts[1];
    }

    public function maskCpfCnpj() {

        $value = preg_replace('/\D/', '', $this->document);
        if (strlen($value) === 11) {
            return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "$1.$2.$3-$4", $value);
        } elseif (strlen($value) === 14) {
            return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "$1.$2.$3/$4-$5", $value);
        }

        return $this->cpfcnpj;
    }

    public function labelStatus () {
        switch ($this->status) {
            case 'active':
                return '<span class="badge bg-success">Ativo</span>';
                break;
            case 'inactive':
                return '<span class="badge bg-danger">Inativo</span>';
                break;
            default:
                return '<span class="badge bg-info">N/a</span>';
                break;
        }
    }

    public function labelRoles () {
        switch ($this->roles) {
            case 'admin':
                return '<span class="badge bg-dark">Administrador</span>';
                break;
            case 'collaborator':
                return '<span class="badge bg-dark">Vendedor/Colaborador</span>';
                break;
            default:
                return '<span class="badge bg-dark">N/a | Cliente | Beneficiário</span>';
                break;
        }
    }

    protected function casts(): array {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
