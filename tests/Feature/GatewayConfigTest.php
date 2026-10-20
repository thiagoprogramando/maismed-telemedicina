<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Support\AssasConfig;
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
            ->assertSee('Gateway de Pagamento', false);

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

    public function test_checkout_page_uses_absolute_fontawesome_and_collapsed_contract(): void
    {
        $plan = \App\Models\Plan::create([
            'name'   => 'Familiar',
            'price'  => 89.90,
            'status' => 'active',
            'terms'  => '<p>Contrato de adesão do plano familiar.</p>',
        ]);

        $this->get('/create-sale/' . $plan->slug)
            ->assertOk()
            ->assertSee('Assets/vendor/fontawesome-free/css/all.min.css', false)
            ->assertDontSee('create-sale/' . $plan->slug . '/vendor/fontawesome', false)
            ->assertSee('id="contract-preview"', false)
            ->assertSee('Leia completo', false);
    }
}
