<?php

namespace Tests\Feature;

use App\Mail\SaleTransferMail;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\FirstUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class ConsultantAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(FirstUser::class);

        return User::firstWhere('email', 'admin@telemedicina.com');
    }

    private function collaborator(string $email = 'consultor@example.com', string $doc = '11122233344'): User
    {
        $user             = new User();
        $user->uuid       = (string) Str::uuid();
        $user->name       = 'Consultor Teste';
        $user->email      = $email;
        $user->document   = $doc;
        $user->phone      = '84999990000';
        $user->birth_date = '1990-01-01';
        $user->password   = bcrypt('secret');
        $user->roles      = 'collaborator';
        $user->status     = 'active';
        $user->save();

        return $user;
    }

    public function test_admin_can_list_consultants_and_see_showcase_link(): void
    {
        $admin = $this->admin();
        $collab = $this->collaborator();

        $this->actingAs($admin)
            ->get(route('consultants'))
            ->assertOk()
            ->assertSee('Consultores', false)
            ->assertSee($collab->name, false)
            ->assertSee(route('showcase', ['parent' => $collab->uuid]), false);
    }

    public function test_collaborator_cannot_open_consultants(): void
    {
        $this->admin();
        $collab = $this->collaborator();

        $this->actingAs($collab)
            ->get(route('consultants'))
            ->assertRedirect(route('app'));
    }

    public function test_admin_can_transfer_sale_and_sends_email(): void
    {
        Mail::fake();
        config([
            'mail.mailers.smtp.host' => 'smtp.test',
            'mail.from.address'      => 'noreply@test.com',
        ]);

        $admin  = $this->admin();
        $from   = $this->collaborator('from@example.com', '22233344455');
        $to     = $this->collaborator('to@example.com', '33344455566');
        $plan   = Plan::create(['name' => 'Individual', 'price' => 49.90, 'status' => 'active']);

        $buyer             = new User();
        $buyer->uuid       = (string) Str::uuid();
        $buyer->name       = 'Cliente';
        $buyer->email      = 'cliente@example.com';
        $buyer->document   = '08746658416';
        $buyer->phone      = '84999731202';
        $buyer->password   = bcrypt('secret');
        $buyer->birth_date = '1990-07-27';
        $buyer->roles      = 'user';
        $buyer->status     = 'active';
        $buyer->save();

        $sale                 = new Sale();
        $sale->uuid           = (string) Str::uuid();
        $sale->user_id        = $buyer->id;
        $sale->seller_id      = $from->id;
        $sale->plan_id        = $plan->id;
        $sale->name           = $plan->name;
        $sale->description    = $plan->name;
        $sale->price          = 49.90;
        $sale->commission     = 0;
        $sale->payment_method = 'PIX';
        $sale->status         = 'active';
        $sale->save();

        $this->actingAs($admin)
            ->post(route('transfer-sale'), [
                'sale_uuid'          => $sale->uuid,
                'to_consultant_uuid' => $to->uuid,
            ])
            ->assertRedirect();

        $sale->refresh();
        $this->assertSame($to->id, $sale->seller_id);

        Mail::assertSent(SaleTransferMail::class);
    }

    public function test_admin_destroy_all_sales_requires_confirm(): void
    {
        $admin = $this->admin();
        $plan  = Plan::create(['name' => 'Individual', 'price' => 49.90, 'status' => 'active']);

        $buyer             = new User();
        $buyer->uuid       = (string) Str::uuid();
        $buyer->name       = 'Cliente';
        $buyer->email      = 'c2@example.com';
        $buyer->document   = '52998224725';
        $buyer->phone      = '11999990000';
        $buyer->password   = bcrypt('secret');
        $buyer->birth_date = '1990-01-01';
        $buyer->roles      = 'user';
        $buyer->status     = 'active';
        $buyer->save();

        $sale                 = new Sale();
        $sale->uuid           = (string) Str::uuid();
        $sale->user_id        = $buyer->id;
        $sale->seller_id      = $admin->id;
        $sale->plan_id        = $plan->id;
        $sale->name           = $plan->name;
        $sale->description    = $plan->name;
        $sale->price          = 49.90;
        $sale->commission     = 0;
        $sale->status         = 'pendent';
        $sale->save();

        $this->actingAs($admin)
            ->post(route('deleted-sales-all'), ['confirm' => 'NAO'])
            ->assertSessionHasErrors('confirm');

        $this->actingAs($admin)
            ->post(route('deleted-sales-all'), ['confirm' => 'EXCLUIR'])
            ->assertRedirect(route('sales'));

        $sale->refresh();
        $buyer->refresh();
        $this->assertSame('canceled', $sale->status);
        $this->assertSame('inactive', $buyer->status);
    }

    public function test_dashboard_period_filter_returns_metrics(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('app', ['period' => 7]))
            ->assertOk()
            ->assertSee('7 dias', false)
            ->assertSee('Usuários ativos', false)
            ->assertSee('Não pagaram', false);
    }
}
