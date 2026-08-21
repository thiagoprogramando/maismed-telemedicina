<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use App\Models\User;

class FirstUser extends Seeder {
    
    public function run(): void {

        User::firstOrCreate(
            [
                'email' => 'admin@telemedicina.com',
            ],
            [
                'uuid'          => Str::uuid(),
                'name'          => 'Administrador',
                'email'        => 'admin@telemedicina.com',
                'phone'         => '11999999999',
                'document'      => '70663235480',
                'birth_date'    => now()->subYears(30)->format('Y-m-d'),
                'password'      => Hash::make('admin'),
                'roles'         => 'admin',
                'status'        => 'active',
            ]
        );
    }
}
