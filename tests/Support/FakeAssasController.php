<?php

namespace Tests\Support;

use App\Http\Controllers\Gateway\AssasController;
use App\Models\User;
use Illuminate\Support\Str;

class FakeAssasController extends AssasController
{
    public array $charges = [];
    public array $canceled = [];
    public array $cardExtras = [];
    public array $subscriptions = [];
    public array $canceledSubscriptions = [];
    public ?int $failAt = null;
    public bool $failSubscription = false;

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

    public function createdCharge($customer, $billingType, $installments, $value, $description, $dueDate, array $extras = [])
    {
        if ($this->failAt !== null && count($this->charges) + 1 === $this->failAt) {
            return false;
        }

        $this->charges[] = number_format((float) $value, 2, '.', '');
        $this->cardExtras[] = $extras;

        $id = 'pay_' . count($this->charges);

        return [
            'id'              => $id,
            'invoiceUrl'      => 'https://example.test/' . $id,
            'status'          => !empty($extras['creditCard']) || !empty($extras['creditCardToken']) ? 'CONFIRMED' : 'PENDING',
            'creditCardToken' => !empty($extras['creditCard']) ? 'tok_fake' : ($extras['creditCardToken'] ?? null),
            'splits'          => [],
        ];
    }

    public function createdSubscription($customer, $billingType, $value, $nextDueDate, $description, array $extras = []): array|false
    {
        if ($this->failSubscription) {
            return false;
        }

        $id = 'sub_' . (count($this->subscriptions) + 1);

        $this->subscriptions[] = [
            'id'          => $id,
            'customer'    => $customer,
            'billingType' => $billingType,
            'value'       => number_format((float) $value, 2, '.', ''),
            'nextDueDate' => $nextDueDate,
            'description' => $description,
            'cycle'       => 'MONTHLY',
            'maxPayments' => (int) ($extras['maxPayments'] ?? 12),
            'extras'      => $extras,
        ];

        return [
            'id'              => $id,
            'status'          => 'ACTIVE',
            'creditCardToken' => $extras['creditCardToken'] ?? (!empty($extras['creditCard']) ? 'tok_fake' : null),
        ];
    }

    public function canceledSubscription($id): bool
    {
        $this->canceledSubscriptions[] = $id;

        return true;
    }

    public function listSubscriptionPayments($id): array|false
    {
        return [];
    }

    public function getPixQrCode(string $paymentId)
    {
        return [
            'encodedImage'   => 'aGVsbG8=',
            'payload'        => '00020126580014br.gov.bcb.pix',
            'expirationDate' => now()->addDay()->toIso8601String(),
        ];
    }

    public function canceledCharge($token)
    {
        $this->canceled[] = $token;

        return true;
    }
}
