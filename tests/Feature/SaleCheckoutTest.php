<?php

namespace Tests\Feature;

use App\Http\Controllers\Gateway\AssasController;
use App\Models\Extract;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\FirstUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SaleCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private FakeAssasController $asaas;

    protected function setUp(): void
    {
        parent::setUp();

        // O Asaas real nunca é chamado: o controller é trocado por um dublê que registra as cobranças.
        $this->asaas = new FakeAssasController();
        $this->app->instance(AssasController::class, $this->asaas);
    }

    private function seller(): User
    {
        $this->seed(FirstUser::class);

        return User::firstWhere('email', 'admin@telemedicina.com');
    }

    private function checkout(Plan $plan, User $seller, array $extra = [])
    {
        return $this->post('/created-sale', array_merge([
            'plan_id'        => $plan->uuid,
            'parent_id'      => $seller->uuid,
            'name'           => 'Cliente Teste',
            'document'       => '123.456.789-09',
            'phone'          => '(11) 99999-0000',
            'email'          => 'cliente@example.com',
            'birth_date'     => '1990-01-01',
            'payment_method' => 'PIX',
            'due_date'       => 10,
        ], $extra));
    }

    public function test_family_plan_charges_base_plus_extra_people(): void
    {
        $seller = $this->seller();
        $plan   = Plan::create(['name' => 'Familiar', 'price' => 89.90, 'extra_price' => 22.90, 'included_users' => 4, 'max_users' => 20, 'commission' => 10, 'status' => 'active']);

        $this->checkout($plan, $seller, ['quantity' => 6])->assertRedirect(route('thank-you'));

        $sale = Sale::first();

        // 89,90 + 2 pessoas extras x 22,90 = 135,70; comissão 10,00 x 6 pessoas.
        $this->assertEquals(135.70, $sale->price);
        $this->assertEquals(60.00, $sale->commission);
        $this->assertEquals(6, $sale->quantity);
        $this->assertEquals(0, $sale->dependents);
        $this->assertSame(6, $sale->maxUsers());

        $this->assertCount(12, $this->asaas->charges);
        $this->assertSame(['135.70'], array_values(array_unique($this->asaas->charges)));
        $this->assertSame(12, Invoice::where('price', 135.70)->where('commission', 60)->count());
        $this->assertSame(12, Extract::where('value', 60)->count());
    }

    public function test_family_plan_within_included_people_charges_only_the_base_price(): void
    {
        $seller = $this->seller();
        $plan   = Plan::create(['name' => 'Familiar', 'price' => 89.90, 'extra_price' => 22.90, 'included_users' => 4, 'max_users' => 20, 'commission' => 10, 'status' => 'active']);

        $this->checkout($plan, $seller, ['quantity' => 2])->assertRedirect(route('thank-you'));

        $sale = Sale::first();

        $this->assertEquals(89.90, $sale->price);
        $this->assertEquals(20.00, $sale->commission);
        $this->assertSame(4, $sale->maxUsers());
    }

    public function test_business_plan_charges_per_employee_and_dependent(): void
    {
        $seller = $this->seller();
        $plan   = Plan::create(['name' => 'Empresarial', 'type' => 'business', 'price' => 39.90, 'extra_price' => 22.90, 'commission' => 5, 'status' => 'active']);

        $this->checkout($plan, $seller, ['quantity' => 10, 'dependents' => 3])->assertRedirect(route('thank-you'));

        $sale = Sale::first();

        // 10 x 39,90 + 3 x 22,90 = 467,70; comissão 5,00 x 13 vidas.
        $this->assertEquals(467.70, $sale->price);
        $this->assertEquals(65.00, $sale->commission);
        $this->assertEquals(10, $sale->quantity);
        $this->assertEquals(3, $sale->dependents);
        $this->assertSame(13, $sale->maxUsers());
        $this->assertSame(['467.70'], array_values(array_unique($this->asaas->charges)));
    }

    public function test_fixed_price_plan_keeps_plan_price_and_commission(): void
    {
        $seller = $this->seller();
        $plan   = Plan::create(['name' => 'Individual', 'price' => 49.90, 'max_users' => 3, 'commission' => 7, 'status' => 'active']);

        // Quantidade enviada por fora do formulário não altera um plano de preço fixo.
        $this->checkout($plan, $seller, ['quantity' => 9])->assertRedirect(route('thank-you'));

        $sale = Sale::first();

        $this->assertEquals(49.90, $sale->price);
        $this->assertEquals(7.00, $sale->commission);
        $this->assertNull($sale->quantity);
        $this->assertEquals(3, $sale->maxUsers());
        $this->assertSame(['49.90'], array_values(array_unique($this->asaas->charges)));
    }

    public function test_quantity_above_the_plan_limit_is_capped_on_the_server(): void
    {
        $seller = $this->seller();
        $plan   = Plan::create(['name' => 'Familiar', 'price' => 89.90, 'extra_price' => 22.90, 'included_users' => 4, 'max_users' => 6, 'commission' => 0, 'status' => 'active']);

        $this->checkout($plan, $seller, ['quantity' => 999])->assertRedirect(route('thank-you'));

        $sale = Sale::first();

        $this->assertEquals(6, $sale->quantity);
        $this->assertEquals(135.70, $sale->price);
        $this->assertSame(0, Extract::count());
    }

    public function test_invalid_quantity_is_rejected_before_any_charge(): void
    {
        $seller = $this->seller();
        $plan   = Plan::create(['name' => 'Familiar', 'price' => 89.90, 'extra_price' => 22.90, 'included_users' => 4, 'status' => 'active']);

        $this->checkout($plan, $seller, ['quantity' => 0])->assertSessionHasErrors('quantity');
        $this->checkout($plan, $seller, ['dependents' => -1])->assertSessionHasErrors('dependents');

        $this->assertSame(0, Sale::count());
        $this->assertCount(0, $this->asaas->charges);
    }

    public function test_adhesion_form_carries_the_simulated_quantity(): void
    {
        $plan = Plan::create(['name' => 'Empresarial', 'type' => 'business', 'price' => 39.90, 'extra_price' => 22.90, 'status' => 'active']);

        $response = $this->get('/create-sale/' . $plan->slug . '?qty=10&dep=3');

        $response->assertOk();
        $response->assertSee('name="quantity" form="sale-form" data-in="qty" value="10"', false);
        $response->assertSee('name="dependents" form="sale-form" data-in="dep" value="3"', false);
        $response->assertSee('R$ 467,70');
    }
}

class FakeAssasController extends AssasController
{
    public array $charges = [];

    public function createdCustomer($name, $cpfcnpj, $mobilePhone = null, $email = null, $birth_date = null)
    {
        $user = User::firstWhere('document', $cpfcnpj) ?? new User();

        $user->uuid     = $user->uuid ?? Str::uuid();
        $user->name     = $name;
        $user->document = $cpfcnpj;
        $user->phone    = $mobilePhone;
        $user->email    = $email;
        $user->password   = bcrypt('secret');
        $user->birth_date = $birth_date;
        $user->token    = 'cus_fake';
        $user->save();

        return ['user' => $user, 'id' => $user->token];
    }

    public function createdCharge($customer, $billingType, $installments, $value, $description, $dueDate)
    {
        $this->charges[] = number_format((float) $value, 2, '.', '');

        $id = 'pay_' . count($this->charges);

        return ['id' => $id, 'invoiceUrl' => 'https://example.test/' . $id, 'splits' => []];
    }
}
