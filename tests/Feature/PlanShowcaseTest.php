<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Database\Seeders\FirstUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanShowcaseTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(FirstUser::class);

        return User::firstWhere('email', 'admin@telemedicina.com');
    }

    public function test_showcase_lists_only_active_plans(): void
    {
        Plan::create(['name' => 'Familiar', 'price' => 89.90, 'extra_price' => 22.90, 'included_users' => 4, 'max_users' => 20, 'status' => 'active']);
        Plan::create(['name' => 'Desativado', 'price' => 10, 'status' => 'inactive']);

        $response = $this->get('/planos');

        $response->assertOk();
        $response->assertSee('Familiar');
        $response->assertSee('data-price="8990"', false);
        $response->assertSee('data-extra="2290"', false);
        $response->assertSee('data-included="4"', false);
        $response->assertDontSee('Desativado');
    }

    public function test_showcase_links_adhesion_to_the_consultant(): void
    {
        $admin = $this->admin();
        $plan  = Plan::create(['name' => 'Empresarial', 'type' => 'business', 'price' => 39.90, 'extra_price' => 22.90, 'status' => 'active']);

        $response = $this->get('/planos/' . $admin->uuid);

        $response->assertOk();
        $response->assertSee('data-type="business"', false);
        $response->assertSee('create-sale/' . $plan->slug . '/' . $admin->uuid, false);
    }

    public function test_adhesion_page_shows_the_plan_card(): void
    {
        $plan = Plan::create(['name' => 'Familiar', 'price' => 89.90, 'extra_price' => 22.90, 'included_users' => 4, 'max_users' => 20, 'status' => 'active']);

        $response = $this->get('/create-sale/' . $plan->slug . '?qty=6');

        $response->assertOk();
        $response->assertSee('data-qty="6"', false);
    }

    public function test_admin_stores_and_updates_pricing_fields(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/created-plan', [
            'name'           => 'Familiar',
            'type'           => 'family',
            'badge'          => 'Mais escolhido',
            'price'          => '89,90',
            'extra_price'    => '22,90',
            'included_users' => 4,
            'max_users'      => 20,
            'commission'     => '10,00',
            'status'         => 'active',
            'time'           => 'month',
        ])->assertRedirect();

        $plan = Plan::firstWhere('name', 'Familiar');

        $this->assertSame('family', $plan->type);
        $this->assertSame('Mais escolhido', $plan->badge);
        $this->assertEquals(89.90, $plan->price);
        $this->assertEquals(22.90, $plan->extra_price);
        $this->assertEquals(4, $plan->included_users);

        $this->actingAs($admin)->post('/updated-plan/' . $plan->uuid, [
            'type'        => 'business',
            'badge'       => '',
            'extra_price' => '0,00',
        ])->assertRedirect();

        $plan->refresh();

        $this->assertSame('business', $plan->type);
        $this->assertNull($plan->badge);
        $this->assertEquals(0, $plan->extra_price);
    }

    public function test_admin_plan_screens_render_the_form_and_preview(): void
    {
        $admin = $this->admin();
        $plan  = Plan::create(['name' => 'Familiar', 'price' => 89.90, 'extra_price' => 22.90, 'included_users' => 4, 'max_users' => 20, 'status' => 'active']);

        $index = $this->actingAs($admin)->get('/plans');

        $index->assertOk();
        $index->assertSee('name="included_users"', false);
        $index->assertSee('planos/' . $admin->uuid, false);

        $show = $this->actingAs($admin)->get('/plan/' . $plan->uuid);

        $show->assertOk();
        $show->assertSee('name="extra_price"', false);
        $show->assertSee('value="22,90"', false);
        $show->assertSee('data-price="8990"', false);
    }

    public function test_invalid_plan_type_is_rejected(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/created-plan', [
            'name'   => 'Invalido',
            'type'   => 'outro',
            'status' => 'active',
            'time'   => 'month',
        ])->assertSessionHasErrors('type');

        $this->assertNull(Plan::firstWhere('name', 'Invalido'));
    }
}
