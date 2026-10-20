<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model {

    protected $fillable = ['key', 'value'];

    public static function getValue(string $key, ?string $default = null): ?string {
        $row = static::query()->where('key', $key)->first();

        return $row?->value ?? $default;
    }

    public static function putValue(string $key, ?string $value): void {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    public static function getSecret(string $key, ?string $default = null): ?string {
        $raw = static::getValue($key);

        if ($raw === null || $raw === '') {
            return $default;
        }

        try {
            return Crypt::decryptString($raw);
        } catch (\Throwable) {
            // Valor legado sem criptografia: usa como está até o próximo save no admin.
            return $raw;
        }
    }

    public static function putSecret(string $key, ?string $value): void {
        if ($value === null || $value === '') {
            static::putValue($key, null);
            return;
        }

        static::putValue($key, Crypt::encryptString($value));
    }
}
