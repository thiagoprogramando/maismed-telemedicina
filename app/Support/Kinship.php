<?php

namespace App\Support;

class Kinship {

    public const FAMILY = [
        'esposa'         => 'Esposa / Companheira',
        'esposo'         => 'Esposo / Companheiro',
        'filho'          => 'Filho(a)',
        'pai'            => 'Pai',
        'mae'            => 'Mãe',
        'irmao'          => 'Irmão(ã)',
        'outro_parente'  => 'Outro parente',
    ];

    public const BUSINESS = [
        'administrador'      => 'Administrador',
        'empregado'          => 'Empregado',
        'esposa'             => 'Esposa / Companheira',
        'esposo'             => 'Esposo / Companheiro',
        'filho'              => 'Filho(a)',
        'afiliado_empregado' => 'Afiliado do empregado',
    ];

    public const BUSINESS_EMPLOYEES = ['administrador', 'empregado'];

    public static function options(bool $business): array {
        return $business ? self::BUSINESS : self::FAMILY;
    }

    public static function label(?string $key): string {
        if ($key === null || $key === '') {
            return '—';
        }

        return self::FAMILY[$key] ?? self::BUSINESS[$key] ?? $key;
    }

    public static function keys(bool $business): array {
        return array_keys(self::options($business));
    }

    public static function isBusinessEmployee(string $key): bool {
        return in_array($key, self::BUSINESS_EMPLOYEES, true);
    }
}
