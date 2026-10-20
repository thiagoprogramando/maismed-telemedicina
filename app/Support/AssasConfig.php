<?php

namespace App\Support;

use App\Models\Setting;

class AssasConfig {

    /**
     * Credenciais do Asaas: prioriza .env; se vazio, usa o que o admin salvou no banco.
     */
    public static function url(): string {
        $fromEnv = config('services.asaas.url');
        if (!empty($fromEnv)) {
            return self::normalizeUrl((string) $fromEnv);
        }

        return self::normalizeUrl((string) Setting::getValue('asaas_api_url', ''));
    }

    public static function token(): string {
        $fromEnv = config('services.asaas.token');
        if (!empty($fromEnv)) {
            return (string) $fromEnv;
        }

        return (string) (Setting::getSecret('asaas_api_token') ?? '');
    }

    public static function isConfigured(): bool {
        return self::url() !== '' && self::token() !== '';
    }

    public static function source(): string {
        $urlFromEnv   = !empty(config('services.asaas.url'));
        $tokenFromEnv = !empty(config('services.asaas.token'));

        if ($urlFromEnv && $tokenFromEnv) {
            return 'env';
        }

        if ($urlFromEnv || $tokenFromEnv) {
            return 'mixed';
        }

        if (Setting::getValue('asaas_api_url') || Setting::getSecret('asaas_api_token')) {
            return 'database';
        }

        return 'none';
    }

    private static function normalizeUrl(string $url): string {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        return rtrim($url, '/') . '/';
    }
}
