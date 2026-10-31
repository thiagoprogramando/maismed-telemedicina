<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Support\AssasConfig;
use App\Support\MailConfig;
use Database\Seeders\FirstUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GatewayConfigTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(FirstUser::class);

        return User::firstWhere('email', 'admin@telemedicina.com');
    }

    public function test_admin_can_open_and_save_gateway_settings(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get('/gateway')
            ->assertOk()
            ->assertSee('Gateway', false)
            ->assertSee('Asaas', false)
            ->assertSee('E-mail / SMTP', false);

        $this->actingAs($admin)
            ->post('/updated-gateway', [
                'asaas_api_url'   => 'https://sandbox.asaas.com/api',
                'asaas_api_token' => 'tok_sandbox_123',
            ])
            ->assertRedirect(route('gateway'));

        $this->assertSame('https://sandbox.asaas.com/api/', Setting::getValue('asaas_api_url'));
        $this->assertSame('tok_sandbox_123', Setting::getSecret('asaas_api_token'));
    }

    public function test_collaborator_cannot_access_gateway_settings(): void
    {
        $this->admin();

        $collab = new User();
        $collab->uuid = (string) \Illuminate\Support\Str::uuid();
        $collab->name = 'Consultor';
        $collab->email = 'consultor@example.com';
        $collab->document = '11122233344';
        $collab->birth_date = '1990-01-01';
        $collab->password = bcrypt('secret');
        $collab->roles = 'collaborator';
        $collab->status = 'active';
        $collab->save();

        $this->actingAs($collab)
            ->get('/gateway')
            ->assertRedirect(route('app'));
    }

    public function test_asaas_config_prefers_env_over_database(): void
    {
        Setting::putValue('asaas_api_url', 'https://from-db.example/api/');
        Setting::putSecret('asaas_api_token', 'db_token');

        config([
            'services.asaas.url'   => 'https://from-env.example/api/',
            'services.asaas.token' => 'env_token',
        ]);

        $this->assertSame('https://from-env.example/api/', AssasConfig::url());
        $this->assertSame('env_token', AssasConfig::token());
        $this->assertSame('env', AssasConfig::source());
    }

    public function test_asaas_config_falls_back_to_database_when_env_is_empty(): void
    {
        Setting::putValue('asaas_api_url', 'https://from-db.example/api/');
        Setting::putSecret('asaas_api_token', 'db_token');

        config([
            'services.asaas.url'   => null,
            'services.asaas.token' => null,
        ]);

        $this->assertSame('https://from-db.example/api/', AssasConfig::url());
        $this->assertSame('db_token', AssasConfig::token());
        $this->assertSame('database', AssasConfig::source());
        $this->assertTrue(AssasConfig::isConfigured());
    }

    public function test_admin_can_save_smtp_settings(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get('/gateway')
            ->assertOk()
            ->assertSee('E-mail / SMTP', false);

        $this->actingAs($admin)
            ->post('/updated-gateway', [
                'asaas_api_url'     => 'https://sandbox.asaas.com/api',
                'asaas_api_token'   => 'tok_sandbox_123',
                'smtp_host'         => 'smtp.example.com',
                'smtp_port'         => 587,
                'smtp_username'     => 'mailer@example.com',
                'smtp_password'     => 'secret-smtp',
                'smtp_encryption'   => 'tls',
                'smtp_from_address' => 'noreply@example.com',
                'smtp_from_name'    => 'MaisMed',
                'smtp_bcc'          => 'copia@example.com',
            ])
            ->assertRedirect(route('gateway'));

        $this->assertSame('smtp.example.com', Setting::getValue('smtp_host'));
        $this->assertSame('587', Setting::getValue('smtp_port'));
        $this->assertSame('mailer@example.com', Setting::getValue('smtp_username'));
        $this->assertSame('secret-smtp', Setting::getSecret('smtp_password'));
        $this->assertSame('tls', Setting::getValue('smtp_encryption'));
        $this->assertSame('noreply@example.com', Setting::getValue('smtp_from_address'));
        $this->assertSame('MaisMed', Setting::getValue('smtp_from_name'));
        $this->assertSame('copia@example.com', Setting::getValue('smtp_bcc'));

        // Senha em branco não sobrescreve.
        $this->actingAs($admin)
            ->post('/updated-gateway', [
                'smtp_host'         => 'smtp.example.com',
                'smtp_port'         => 587,
                'smtp_username'     => 'mailer@example.com',
                'smtp_password'     => '',
                'smtp_encryption'   => 'tls',
                'smtp_from_address' => 'noreply@example.com',
                'smtp_from_name'    => 'MaisMed',
                'smtp_bcc'          => 'copia@example.com',
            ])
            ->assertRedirect(route('gateway'));

        $this->assertSame('secret-smtp', Setting::getSecret('smtp_password'));
    }

    public function test_mail_config_prefers_env_over_database(): void
    {
        Setting::putValue('smtp_host', 'smtp.from-db.example');
        Setting::putValue('smtp_port', '465');
        Setting::putValue('smtp_username', 'db@example.com');
        Setting::putSecret('smtp_password', 'db_pass');
        Setting::putValue('smtp_encryption', 'ssl');
        Setting::putValue('smtp_from_address', 'db-from@example.com');
        Setting::putValue('smtp_from_name', 'From DB');
        Setting::putValue('smtp_bcc', 'db-bcc@example.com');

        config([
            'mail.mailers.smtp.host'     => 'smtp.from-env.example',
            'mail.mailers.smtp.port'     => 587,
            'mail.mailers.smtp.username' => 'env@example.com',
            'mail.mailers.smtp.password' => 'env_pass',
            'mail.mailers.smtp.scheme'   => 'tls',
            'mail.from.address'          => 'env-from@example.com',
            'mail.from.name'             => 'From Env',
            'mail.bcc'                   => 'env-bcc@example.com',
        ]);

        $this->assertSame('smtp.from-env.example', MailConfig::host());
        $this->assertSame(587, MailConfig::port());
        $this->assertSame('env@example.com', MailConfig::username());
        $this->assertSame('env_pass', MailConfig::password());
        $this->assertSame('tls', MailConfig::encryption());
        $this->assertSame('env-from@example.com', MailConfig::fromAddress());
        $this->assertSame('From Env', MailConfig::fromName());
        $this->assertSame('env-bcc@example.com', MailConfig::bcc());
        $this->assertSame('env', MailConfig::source());
        $this->assertTrue(MailConfig::isConfigured());
    }

    public function test_mail_config_falls_back_to_database_when_env_is_empty(): void
    {
        Setting::putValue('smtp_host', 'smtp.from-db.example');
        Setting::putValue('smtp_port', '465');
        Setting::putValue('smtp_username', 'db@example.com');
        Setting::putSecret('smtp_password', 'db_pass');
        Setting::putValue('smtp_encryption', 'ssl');
        Setting::putValue('smtp_from_address', 'db-from@example.com');
        Setting::putValue('smtp_from_name', 'From DB');
        Setting::putValue('smtp_bcc', 'db-bcc@example.com');

        config([
            'mail.mailers.smtp.host'     => null,
            'mail.mailers.smtp.port'     => null,
            'mail.mailers.smtp.username' => null,
            'mail.mailers.smtp.password' => null,
            'mail.mailers.smtp.scheme'   => null,
            'mail.from.address'          => null,
            'mail.from.name'             => null,
            'mail.bcc'                   => null,
        ]);

        $this->assertSame('smtp.from-db.example', MailConfig::host());
        $this->assertSame(465, MailConfig::port());
        $this->assertSame('db@example.com', MailConfig::username());
        $this->assertSame('db_pass', MailConfig::password());
        $this->assertSame('ssl', MailConfig::encryption());
        $this->assertSame('db-from@example.com', MailConfig::fromAddress());
        $this->assertSame('From DB', MailConfig::fromName());
        $this->assertSame('db-bcc@example.com', MailConfig::bcc());
        $this->assertSame('database', MailConfig::source());
        $this->assertTrue(MailConfig::isConfigured());

        MailConfig::apply();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.from-db.example', config('mail.mailers.smtp.host'));
        $this->assertSame(465, config('mail.mailers.smtp.port'));
        $this->assertSame('db-from@example.com', config('mail.from.address'));
        $this->assertSame('smtps', config('mail.mailers.smtp.scheme'));
    }

    public function test_checkout_page_uses_absolute_lucide_and_collapsed_contract(): void
    {
        $plan = \App\Models\Plan::create([
            'name'   => 'Familiar',
            'price'  => 89.90,
            'status' => 'active',
            'terms'  => '<p>Contrato de adesão do plano familiar.</p>',
        ]);

        $this->get('/create-sale/' . $plan->slug)
            ->assertOk()
            ->assertSee('Assets/vendor/lucide/lucide.min.js', false)
            ->assertDontSee('create-sale/' . $plan->slug . '/vendor/lucide', false)
            ->assertSee('id="contract-preview"', false)
            ->assertSee('Leia completo', false);
    }
}
