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
use Illuminate\Support\Facades\Mail;
use Tests\Support\FakeAssasController;
use Tests\TestCase;

class SalePaymentTest extends TestCase
{
    use RefreshDatabase;

    private FakeAssasController $asaas;

    protected function setUp(): void
    {
        parent::setUp();

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
            'pay_timing'     => 'now',
            'due_date'       => 10,
        ], $extra));
    }

    public function test_pay_now_redirects_to_payment_page(): void
    {
        $seller = $this->seller();
        $plan   = Plan::create(['name' => 'Individual', 'price' => 49.90, 'status' => 'active']);

        $response = $this->checkout($plan, $seller, [
            'payment_method' => 'PIX',
            'pay_timing'     => 'now',
        ]);

        $sale = Sale::first();
        $this->assertNotNull($sale);
        $response->assertRedirect(route('pay', $sale->uuid));
        $this->assertSame('PIX', $sale->payment_method);
        $this->assertSame('now', $sale->pay_timing);
        $this->assertCount(0, $this->asaas->charges);
        $this->assertSame(0, Invoice::count());
        Mail::assertNothingSent();
    }

    public function test_pay_show_pix_creates_first_charge_and_shows_qr(): void
    {
        $seller = $this->seller();
        $plan   = Plan::create(['name' => 'Individual', 'price' => 49.90, 'commission' => 7, 'status' => 'active']);

        $this->checkout($plan, $seller, [
            'payment_method' => 'PIX',
            'pay_timing'     => 'now',
        ]);

        $sale = Sale::first();

        $response = $this->get(route('pay', $sale->uuid));

        $response->assertOk();
        $response->assertSee('00020126580014br.gov.bcb.pix', false);
        $response->assertSee('Pague com PIX', false);

        $this->assertCount(1, $this->asaas->charges);
        $this->assertSame(1, Invoice::count());
        $this->assertSame(1, Extract::where('value', 7)->count());
    }

    public function test_pay_show_pix_is_idempotent_on_reload(): void
    {
        $seller = $this->seller();
        $plan   = Plan::create(['name' => 'Individual', 'price' => 49.90, 'status' => 'active']);

        $this->checkout($plan, $seller);
        $sale = Sale::first();

        $this->get(route('pay', $sale->uuid))->assertOk();
        $this->get(route('pay', $sale->uuid))->assertOk();

        $this->assertCount(1, $this->asaas->charges);
        $this->assertSame(1, Invoice::count());
    }

    public function test_pay_show_credit_card_renders_form_without_charging(): void
    {
        $seller = $this->seller();
        $plan   = Plan::create(['name' => 'Individual', 'price' => 49.90, 'status' => 'active']);

        $this->checkout($plan, $seller, [
            'payment_method' => 'CREDIT_CARD',
            'pay_timing'     => 'now',
        ]);

        $sale = Sale::first();
        $response = $this->get(route('pay', $sale->uuid));

        $response->assertOk();
        $response->assertSee('name="card_holder"', false);
        $response->assertSee(route('pay-card', $sale->uuid), false);
        $this->assertCount(0, $this->asaas->charges);
    }

    public function test_pay_card_requires_card_fields(): void
    {
        $seller = $this->seller();
        $plan   = Plan::create(['name' => 'Individual', 'price' => 49.90, 'status' => 'active']);

        $this->checkout($plan, $seller, [
            'payment_method' => 'CREDIT_CARD',
            'pay_timing'     => 'now',
        ]);

        $sale = Sale::first();

        $this->post(route('pay-card', $sale->uuid), [])
            ->assertSessionHasErrors(['card_holder', 'card_number', 'card_expiry', 'card_cvv']);
    }

    public function test_pay_card_charges_first_installment_and_redirects_thank_you(): void
    {
        $seller = $this->seller();
        $plan   = Plan::create(['name' => 'Individual', 'price' => 49.90, 'commission' => 7, 'status' => 'active']);

        $this->checkout($plan, $seller, [
            'payment_method' => 'CREDIT_CARD',
            'pay_timing'     => 'now',
        ]);

        $sale = Sale::first();

        $response = $this->post(route('pay-card', $sale->uuid), [
            'card_holder'    => 'Cliente Teste',
            'card_number'    => '5162306219378829',
            'card_expiry'    => '05/30',
            'card_cvv'       => '318',
            'postal_code'     => '01310-100',
            'address_number' => '100',
        ]);

        $response->assertRedirect(route('thank-you'));
        $response->assertSessionHas('checkout.payment_method', 'CREDIT_CARD');
        $response->assertSessionHas('checkout.card_status', 'CONFIRMED');

        $this->assertCount(1, $this->asaas->charges);
        $this->assertNotEmpty($this->asaas->cardExtras[0]['creditCard'] ?? null);
        $this->assertSame(1, Invoice::count());
        $this->assertSame(1, Extract::count());
    }

    public function test_pay_now_does_not_generate_twelve_installments_on_store(): void
    {
        $seller = $this->seller();
        $plan   = Plan::create(['name' => 'Individual', 'price' => 49.90, 'status' => 'active']);

        $this->checkout($plan, $seller, [
            'payment_method' => 'CREDIT_CARD',
            'pay_timing'     => 'now',
        ])->assertRedirect();

        $this->assertCount(0, $this->asaas->charges);
        $this->assertSame(0, Invoice::count());
    }

    public function test_first_card_payment_creates_twelve_month_subscription(): void
    {
        $seller = $this->seller();
        $plan   = Plan::create(['name' => 'Individual', 'price' => 49.90, 'commission' => 7, 'status' => 'active']);

        $this->checkout($plan, $seller, [
            'payment_method' => 'CREDIT_CARD',
            'pay_timing'     => 'now',
            'due_date'       => 10,
        ]);

        $sale = Sale::first();

        $this->post(route('pay-card', $sale->uuid), [
            'card_holder'    => 'Cliente Teste',
            'card_number'    => '5162306219378829',
            'card_expiry'    => '05/30',
            'card_cvv'       => '318',
            'postal_code'     => '01310-100',
            'address_number' => '100',
        ])->assertRedirect(route('thank-you'));

        // 1ª parcela avulsa + assinatura dos 11 ciclos restantes (contrato 12 meses).
        $this->assertCount(1, $this->asaas->charges);
        $this->assertCount(1, $this->asaas->subscriptions);
        $this->assertSame('MONTHLY', $this->asaas->subscriptions[0]['cycle']);
        $this->assertSame(11, $this->asaas->subscriptions[0]['maxPayments']);
        $this->assertSame('CREDIT_CARD', $this->asaas->subscriptions[0]['billingType']);
        $this->assertSame('tok_fake', $this->asaas->subscriptions[0]['extras']['creditCardToken'] ?? null);

        $sale->refresh();
        $this->assertSame('sub_1', $sale->subscription_token);
        $this->assertSame(12, (int) $sale->contract_months);
    }

    public function test_pay_show_pix_starts_subscription_after_first_charge(): void
    {
        $seller = $this->seller();
        $plan   = Plan::create(['name' => 'Individual', 'price' => 49.90, 'status' => 'active']);

        $this->checkout($plan, $seller, [
            'payment_method' => 'PIX',
            'pay_timing'     => 'now',
        ]);

        $sale = Sale::first();
        $this->get(route('pay', $sale->uuid))->assertOk();

        $this->assertCount(1, $this->asaas->charges);
        $this->assertCount(1, $this->asaas->subscriptions);
        $this->assertSame('PIX', $this->asaas->subscriptions[0]['billingType']);
        $this->assertSame(11, $this->asaas->subscriptions[0]['maxPayments']);

        $sale->refresh();
        $this->assertSame('sub_1', $sale->subscription_token);
    }

    public function test_webhook_activates_buyer_not_seller(): void
    {
        $seller = $this->seller();
        $seller->wallet = 0;
        $seller->save();

        $buyer             = new User();
        $buyer->uuid       = (string) \Illuminate\Support\Str::uuid();
        $buyer->name       = 'Comprador';
        $buyer->email      = 'comprador@example.com';
        $buyer->document   = '39053344705';
        $buyer->phone      = '11988887777';
        $buyer->password   = bcrypt('secret');
        $buyer->birth_date = '1990-01-01';
        $buyer->roles      = 'user';
        $buyer->status     = 'inactive';
        $buyer->token      = 'cus_buyer';
        $buyer->save();

        $plan = Plan::create(['name' => 'Individual', 'price' => 49.90, 'commission' => 7, 'status' => 'active']);

        $sale                 = new Sale();
        $sale->uuid           = (string) \Illuminate\Support\Str::uuid();
        $sale->user_id        = $buyer->id;
        $sale->seller_id      = $seller->id;
        $sale->plan_id        = $plan->id;
        $sale->name           = $plan->name;
        $sale->description    = $plan->name;
        $sale->price          = 49.90;
        $sale->commission     = 7;
        $sale->payment_method = 'PIX';
        $sale->pay_timing     = 'now';
        $sale->status         = 'pendent';
        $sale->save();

        $invoice                   = new Invoice();
        $invoice->uuid             = (string) \Illuminate\Support\Str::uuid();
        $invoice->user_id          = $seller->id;
        $invoice->sale_id          = $sale->id;
        $invoice->name             = $plan->name;
        $invoice->description      = '1ª parcela';
        $invoice->price            = 49.90;
        $invoice->commission       = 7;
        $invoice->payment_token    = 'pay_webhook_1';
        $invoice->payment_url      = 'https://example.test/pay_webhook_1';
        $invoice->payment_due_date = now();
        $invoice->status           = 'pendent';
        $invoice->save();

        $extract                = new Extract();
        $extract->uuid          = (string) \Illuminate\Support\Str::uuid();
        $extract->user_id       = $seller->id;
        $extract->sale_id       = $sale->id;
        $extract->title         = 'Comissão';
        $extract->description   = 'Comissão teste';
        $extract->value         = 7;
        $extract->payment_date  = now();
        $extract->payment_token = 'pay_webhook_1';
        $extract->type          = 'commission';
        $extract->status        = 'pendent';
        $extract->save();

        $this->postJson('/api/webhook', [
            'event'   => 'PAYMENT_CONFIRMED',
            'payment' => ['id' => 'pay_webhook_1'],
        ])->assertOk();

        $buyer->refresh();
        $seller->refresh();
        $invoice->refresh();

        $this->assertSame('active', $buyer->status);
        $this->assertSame('active', $seller->status);
        $this->assertSame('paid', $invoice->status);
        $this->assertEquals(7.0, (float) $seller->wallet);
    }
}
