<?php

namespace App\Support;

use App\Models\Setting;

class MailConfig {

    /**
     * SMTP: prioriza .env; se vazio, usa o que o admin salvou no banco.
     */
    public static function host(): string {
        $fromEnv = config('mail.mailers.smtp.host');
        if (!self::isBlank($fromEnv)) {
            return (string) $fromEnv;
        }

        return (string) Setting::getValue('smtp_host', '');
    }

    public static function port(): int {
        $fromEnv = config('mail.mailers.smtp.port');
        if (!self::isBlank($fromEnv)) {
            return (int) $fromEnv;
        }

        $fromDb = Setting::getValue('smtp_port');

        return !self::isBlank($fromDb) ? (int) $fromDb : 587;
    }

    public static function username(): ?string {
        $fromEnv = config('mail.mailers.smtp.username');
        if (!self::isBlank($fromEnv)) {
            return (string) $fromEnv;
        }

        $fromDb = Setting::getValue('smtp_username');

        return self::isBlank($fromDb) ? null : (string) $fromDb;
    }

    public static function password(): ?string {
        $fromEnv = config('mail.mailers.smtp.password');
        if (!self::isBlank($fromEnv)) {
            return (string) $fromEnv;
        }

        return Setting::getSecret('smtp_password');
    }

    /**
     * tls | ssl | null (ou scheme smtp/smtps do .env).
     */
    public static function encryption(): ?string {
        $fromEnv = config('mail.mailers.smtp.scheme');
        if (!self::isBlank($fromEnv)) {
            return self::normalizeEncryption((string) $fromEnv);
        }

        $fromDb = Setting::getValue('smtp_encryption');

        return self::isBlank($fromDb) ? null : self::normalizeEncryption((string) $fromDb);
    }

    public static function fromAddress(): string {
        $fromEnv = config('mail.from.address');
        if (!self::isBlank($fromEnv)) {
            return (string) $fromEnv;
        }

        return (string) Setting::getValue('smtp_from_address', '');
    }

    public static function fromName(): string {
        $fromEnv = config('mail.from.name');
        if (!self::isBlank($fromEnv)) {
            return (string) $fromEnv;
        }

        $fromDb = Setting::getValue('smtp_from_name');

        return !self::isBlank($fromDb) ? (string) $fromDb : (string) config('app.name');
    }

    public static function bcc(): ?string {
        $fromEnv = config('mail.bcc');
        if (!self::isBlank($fromEnv)) {
            return (string) $fromEnv;
        }

        $fromDb = Setting::getValue('smtp_bcc');

        return self::isBlank($fromDb) ? null : (string) $fromDb;
    }

    public static function isConfigured(): bool {
        return self::host() !== '' && self::fromAddress() !== '';
    }

    public static function source(): string {
        $hostFromEnv = !self::isBlank(config('mail.mailers.smtp.host'));
        $fromFromEnv = !self::isBlank(config('mail.from.address'));

        if ($hostFromEnv && $fromFromEnv) {
            return 'env';
        }

        if ($hostFromEnv || $fromFromEnv) {
            return 'mixed';
        }

        if (Setting::getValue('smtp_host') || Setting::getValue('smtp_from_address') || Setting::getSecret('smtp_password')) {
            return 'database';
        }

        return 'none';
    }

    /**
     * Aplica host/porta/credenciais/from resolvidos (env → banco) na config em runtime.
     */
    public static function apply(): void {
        if (!self::isConfigured()) {
            return;
        }

        config([
            'mail.default'                => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host'      => self::host(),
            'mail.mailers.smtp.port'      => self::port(),
            'mail.mailers.smtp.username'  => self::username(),
            'mail.mailers.smtp.password'  => self::password(),
            'mail.mailers.smtp.scheme'    => self::schemeForEncryption(self::encryption()),
            'mail.from.address'           => self::fromAddress(),
            'mail.from.name'              => self::fromName(),
        ]);

        if (app()->bound('mail.manager')) {
            app('mail.manager')->purge('smtp');
        }
    }

    private static function isBlank(mixed $value): bool {
        return $value === null || $value === '';
    }

    private static function normalizeEncryption(string $value): ?string {
        $value = strtolower(trim($value));

        if ($value === '' || $value === 'null' || $value === 'none') {
            return null;
        }

        return $value;
    }

    private static function schemeForEncryption(?string $encryption): ?string {
        return match ($encryption) {
            'ssl', 'smtps' => 'smtps',
            'tls', 'smtp'  => null,
            default        => $encryption,
        };
    }
}
