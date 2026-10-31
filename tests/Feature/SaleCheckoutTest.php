<?php

namespace Tests\Feature;

use App\Http\Controllers\Gateway\AssasController;
use App\Mail\SalePaymentMail;
use App\Models\Extract;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\FirstUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\Support\FakeAssasController;
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

        config([
            'services.asaas.url'   => 'https://sandbox.asaas.com/api/',
            'services.asaas.token' => 'test_token',
        ]);

        Mail::fake();
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
            'pay_timing'     => 'later',
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

    public function test_checkout_without_seller_uses_the_configured_default_seller(): void
    {
        $seller = $this->seller();
        config(['services.maismed.default_seller' => $seller->uuid]);
        $plan = Plan::create(['name' => 'Individual', 'price' => 49.90, 'max_users' => 3, 'commission' => 7, 'status' => 'active']);

        $this->checkout($plan, $seller, ['parent_id' => null])->assertRedirect(route('thank-you'));

        $sale = Sale::first();

        $this->assertSame($seller->id, $sale->seller_id);
        $this->assertEquals(7.00, $sale->commission);
        $this->assertSame(12, Extract::where('user_id', $seller->id)->count());
    }

    public function test_checkout_without_any_seller_creates_the_sale_without_commission(): void
    {
        $seller = $this->seller();
        config(['services.maismed.default_seller' => null]);
        $plan = Plan::create(['name' => 'Individual', 'price' => 49.90, 'max_users' => 3, 'commission' => 7, 'status' => 'active']);

        $this->checkout($plan, $seller, ['parent_id' => ''])->assertRedirect(route('thank-you'));

        $sale = Sale::first();

        $this->assertNull($sale->seller_id);
        $this->assertEquals(0, $sale->commission);
        $this->assertSame(12, Invoice::whereNull('user_id')->count());
        $this->assertSame(0, Extract::count());
    }

    public function test_unknown_seller_link_is_rejected_before_any_charge(): void
    {
        $seller = $this->seller();
        $plan   = Plan::create(['name' => 'Individual', 'price' => 49.90, 'status' => 'active']);

        $this->checkout($plan, $seller, ['parent_id' => (string) Str::uuid()])->assertSessionHas('infor');

        $this->assertSame(0, Sale::count());
        $this->assertCount(0, $this->asaas->charges);
    }

    public function test_user_role_cannot_be_seller_on_adhesion_link(): void
    {
        $this->seller();

        $regular             = new User();
        $regular->uuid       = (string) Str::uuid();
        $regular->name       = 'Usuario Comum';
        $regular->email      = 'user@example.com';
        $regular->document   = '52998224725';
        $regular->phone      = '11977776666';
        $regular->password   = bcrypt('secret');
        $regular->birth_date = '1990-01-01';
        $regular->roles      = 'user';
        $regular->status     = 'active';
        $regular->save();

        $plan = Plan::create(['name' => 'Individual', 'price' => 49.90, 'status' => 'active']);

        $this->checkout($plan, $regular, ['parent_id' => $regular->uuid])->assertSessionHas('infor');

        $this->assertSame(0, Sale::count());
        $this->assertCount(0, $this->asaas->charges);
    }

    public function test_failed_installment_cancels_charges_already_created_in_asaas(): void
    {
        $seller = $this->seller();
        $plan   = Plan::create(['name' => 'Individual', 'price' => 49.90, 'commission' => 7, 'status' => 'active']);
        $this->asaas->failAt = 4;

        $this->checkout($plan, $seller)->assertSessionHas('error');

        $this->assertSame(['pay_1', 'pay_2', 'pay_3'], $this->asaas->canceled);
        $this->assertSame('canceled', Sale::first()->status);
        $this->assertSame(0, Invoice::count());
        $this->assertSame(0, Extract::count());
    }

    public function test_adhesion_form_carries_the_simulated_quantity(): void
    {
        $plan = Plan::create(['name' => 'Empresarial', 'type' => 'business', 'price' => 39.90, 'extra_price' => 22.90, 'status' => 'active']);

        $response = $this->get('/create-sale/' . $plan->slug . '?qty=10&dep=3');

        $response->assertOk();
        $response->assertSee('name="quantity" form="sale-form" data-in="qty" value="10"', false);
        $response->assertSee('name="dependents" form="sale-form" data-in="dep" value="3"', false);
        $response->assertSee('R$ 467,70');
        $response->assertSee('Dependentes', false);
        $response->assertSee('add-member', false);
        $response->assertSee('memberOffcanvas', false);
        $response->assertSee('sale-members.js', false);
    }

    public function test_optional_family_members_are_created_and_raise_quantity(): void
    {
        $seller = $this->seller();
        $plan   = Plan::create(['name' => 'Familiar', 'price' => 89.90, 'extra_price' => 22.90, 'included_users' => 4, 'max_users' => 20, 'commission' => 10, 'status' => 'active']);

        $this->checkout($plan, $seller, [
            'quantity' => 2,
            'members'  => [
                ['name' => 'Maria Silva', 'document' => '529.982.247-25', 'kinship' => 'esposa'],
                ['name' => 'João Silva', 'document' => '390.533.447-05', 'kinship' => 'filho'],
            ],
        ])->assertRedirect(route('thank-you'));

        $sale = Sale::first();
        $titular = User::find($sale->user_id);

        // Titular + 2 dependentes listados = 3 pessoas cobradas.
        $this->assertEquals(3, $sale->quantity);
        $this->assertEquals(89.90, $sale->price);
        $this->assertCount(2, $titular->children);
        $this->assertSame('esposa', $titular->children->first()->kinship);
    }

    public function test_plan_card_script_does_not_show_average_price_per_person(): void
    {
        $script = file_get_contents(public_path('Assets/js/plan-card.js'));

        $this->assertStringNotContainsString('Valor por pessoa', $script);
    }

    public function test_pay_later_sends_payment_email(): void
    {
        $seller = $this->seller();
        $plan   = Plan::create(['name' => 'Individual', 'price' => 49.90, 'status' => 'active']);

        $this->checkout($plan, $seller, ['pay_timing' => 'later'])->assertRedirect(route('thank-you'));

        Mail::assertSent(SalePaymentMail::class, function (SalePaymentMail $mail) {
            return $mail->hasTo('cliente@example.com')
                && ($mail->data['payment_url'] ?? null) === 'https://example.test/pay_1';
        });
    }

    public function test_pay_now_pix_redirects_to_payment_page_without_charges(): void
    {
        $seller = $this->seller();
        $plan   = Plan::create(['name' => 'Individual', 'price' => 49.90, 'status' => 'active']);

        $response = $this->checkout($plan, $seller, [
            'payment_method' => 'PIX',
            'pay_timing'     => 'now',
        ]);

        $sale = Sale::first();
        $response->assertRedirect(route('pay', $sale->uuid));
        $this->assertCount(0, $this->asaas->charges);
        Mail::assertNothingSent();
    }

    public function test_pay_now_credit_card_redirects_to_payment_page_without_card_fields_on_store(): void
    {
        $seller = $this->seller();
        $plan   = Plan::create(['name' => 'Individual', 'price' => 49.90, 'status' => 'active']);

        $response = $this->checkout($plan, $seller, [
            'payment_method' => 'CREDIT_CARD',
            'pay_timing'     => 'now',
        ]);

        $sale = Sale::first();
        $response->assertRedirect(route('pay', $sale->uuid));
        $this->assertCount(0, $this->asaas->charges);
        $this->assertSame(0, Invoice::count());
    }
}
