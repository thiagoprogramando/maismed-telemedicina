<?php

namespace App\Http\Controllers\Gateway;

use App\Http\Controllers\Controller;
use App\Models\Extract;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Sale;
use App\Support\AssasConfig;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class AssasController extends Controller {

    private function headers(): array {
        return [
            'Content-Type' => 'application/json',
            'accept'       => 'application/json',
            'access_token' => AssasConfig::token(),
            'User-Agent'   => config('app.name'),
        ];
    }

    private function ensureConfigured(): bool {
        if (AssasConfig::isConfigured()) {
            return true;
        }

        Log::error('Asaas sem URL/token configurados (.env ou admin/gateway).');
        return false;
    }
    
    public function createdCustomer($name, $cpfcnpj, $mobilePhone = null, $email = null, $birth_date = null) {
        
        try {
            if (!$this->ensureConfigured()) {
                return [
                    'error'   => true,
                    'message' => 'Gateway de pagamento não configurado. Contate o administrador.',
                ];
            }

            $cpfcnpj     = preg_replace('/\D/', '', (string) $cpfcnpj);
            $mobilePhone = $mobilePhone !== null ? preg_replace('/\D/', '', (string) $mobilePhone) : null;
            $email       = $email !== null ? trim((string) $email) : null;

            if ($cpfcnpj === '' || (strlen($cpfcnpj) !== 11 && strlen($cpfcnpj) !== 14)) {
                return [
                    'error'   => true,
                    'message' => 'Informe um CPF ou CNPJ válido.',
                ];
            }

            $existingUser = User::withTrashed()->where('document', $cpfcnpj)->first();
            if ($existingUser && $existingUser->trashed()) {
                $existingUser->restore();
            }

            if ($existingUser && !empty($existingUser->token)) {
                Log::info('Reutilizando cliente Asaas já vinculado ao usuário', [
                    'document' => $cpfcnpj,
                    'user_id'  => $existingUser->id,
                ]);

                return [
                    'user' => $existingUser,
                    'id'   => $existingUser->token,
                ];
            }

            // Evita e-mail único colidindo com outro CPF (comum em re-tentativas de checkout).
            if ($email) {
                $emailOwnerQuery = User::withTrashed()->where('email', $email);
                if ($existingUser) {
                    $emailOwnerQuery->where('id', '!=', $existingUser->id);
                }

                if ($emailOwnerQuery->exists()) {
                    return [
                        'error'   => true,
                        'message' => 'Este e-mail já está cadastrado em outra conta. Use outro e-mail ou fale com o suporte.',
                    ];
                }
            }

            $asaasId = $this->findCustomerIdByDocument($cpfcnpj);

            if (!$asaasId) {
                $created = $this->postCustomerToAssas($name, $cpfcnpj, $mobilePhone, $email);
                if (!empty($created['error'])) {
                    // Timeout / resposta ambígua: tenta localizar antes de falhar.
                    $asaasId = $this->findCustomerIdByDocument($cpfcnpj);
                    if (!$asaasId) {
                        return $created;
                    }
                } else {
                    $asaasId = $created['id'];
                }
            }

            $user = $existingUser ?? new User();
            if (!$existingUser) {
                $user->uuid     = Str::uuid();
                $user->document = $cpfcnpj;
                $user->password = bcrypt(substr($cpfcnpj, 0, 4));
            }

            $user->name       = $name;
            $user->phone      = $mobilePhone;
            $user->email      = $email;
            $user->birth_date = $birth_date ?: $user->birth_date;
            $user->token      = $asaasId;
            $user->roles      = $user->roles ?: 'user';
            $user->status     = $user->status ?: 'inactive';

            try {
                $user->save();
            } catch (\Throwable $e) {
                Log::error('Falha ao salvar usuário local após vincular cliente no Asaas', [
                    'token'    => $asaasId,
                    'document' => $cpfcnpj,
                    'error'    => $e->getMessage(),
                ]);

                return [
                    'error'   => true,
                    'message' => 'Não foi possível concluir o cadastro local. Se o e-mail ou CPF já existir, use os mesmos dados da conta ou fale com o suporte.',
                ];
            }

            return [
                'user' => $user,
                'id'   => $asaasId,
            ];
        } catch (RequestException $e) {
            $details = $this->assasErrorMessage($e);
            Log::error('Erro de requisição na API Assas CreateCustomer: ' . $details);

            return [
                'error'   => true,
                'message' => 'Não foi possível cadastrar no pagamento: ' . $details,
            ];
        } catch (\Exception $e) {
            Log::error('Erro geral na função createCustomer: ' . $e->getMessage());

            return [
                'error'   => true,
                'message' => 'Falha no cadastro. Verifique os dados e tente novamente.',
            ];
        }
    }

    private function findCustomerIdByDocument(string $cpfcnpj): ?string
    {
        try {
            $client   = new Client(['timeout' => 30, 'connect_timeout' => 15]);
            $response = $client->get(AssasConfig::url() . 'v3/customers', [
                'headers' => $this->headers(),
                'query'   => [
                    'cpfCnpj' => $cpfcnpj,
                    'limit'   => 1,
                ],
                'verify'  => !app()->environment('local'),
            ]);

            $data = json_decode((string) $response->getBody(), true);
            $id   = $data['data'][0]['id'] ?? null;

            return is_string($id) && $id !== '' ? $id : null;
        } catch (\Throwable $e) {
            Log::warning('Falha ao buscar cliente Asaas por CPF/CNPJ', [
                'document' => $cpfcnpj,
                'error'    => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function postCustomerToAssas(string $name, string $cpfcnpj, ?string $mobilePhone, ?string $email): array
    {
        try {
            $client   = new Client(['timeout' => 30, 'connect_timeout' => 15]);
            $response = $client->post(AssasConfig::url() . 'v3/customers', [
                'headers' => $this->headers(),
                'json'    => [
                    'name'                 => $name,
                    'cpfCnpj'              => $cpfcnpj,
                    'mobilePhone'          => $mobilePhone,
                    'email'                => $email,
                    'notificationDisabled' => true,
                ],
                'verify'  => !app()->environment('local'),
            ]);

            $data   = json_decode((string) $response->getBody(), true);
            $status = $response->getStatusCode();

            if (in_array($status, [200, 201], true) && !empty($data['id'])) {
                return ['id' => $data['id']];
            }

            Log::error('Erro na criação do cliente Asaas: ' . json_encode($data));

            return [
                'error'   => true,
                'message' => $this->formatAssasErrors($data) ?? 'Falha no cadastro no gateway de pagamento.',
            ];
        } catch (RequestException $e) {
            return [
                'error'   => true,
                'message' => $this->assasErrorMessage($e),
            ];
        }
    }

    private function assasErrorMessage(RequestException $e): string
    {
        $response = $e->getResponse();
        if ($response) {
            $data = json_decode((string) $response->getBody(), true);
            $msg  = $this->formatAssasErrors($data);
            if ($msg) {
                return $msg;
            }
        }

        return 'Não foi possível falar com o gateway de pagamento. Tente novamente.';
    }

    private function formatAssasErrors(?array $data): ?string
    {
        if (empty($data['errors']) || !is_array($data['errors'])) {
            return null;
        }

        $parts = [];
        foreach ($data['errors'] as $error) {
            if (!empty($error['description'])) {
                $parts[] = $error['description'];
            } elseif (!empty($error['code'])) {
                $parts[] = $error['code'];
            }
        }

        return $parts !== [] ? implode(' ', $parts) : null;
    }
    
    public function createdCharge ($customer, $billingType, $installments, $value, $description, $dueDate, array $extras = []) {
        
        try {
            if (!$this->ensureConfigured()) {
                return false;
            }

            $client = new Client([
                'timeout'         => 60,
                'connect_timeout' => 15,
            ]);
            $count  = max(1, (int) ($installments ?? 1));
            $amount = number_format((float) $value, 2, '.', '');

            // Cobrança avulsa: só value. Parcelamento Asaas: installmentCount + installmentValue.
            $payload = [
                'customer'          => $customer,
                'billingType'       => $billingType,
                'value'             => $amount,
                'dueDate'           => Carbon::parse($dueDate)->format('Y-m-d'),
                'description'       => $description,
                'isAddressRequired' => false,
            ];

            if ($count > 1) {
                $payload['installmentCount'] = $count;
                $payload['installmentValue'] = number_format(((float) $value) / $count, 2, '.', '');
            }

            if (!empty($extras['creditCardToken'])) {
                $payload['creditCardToken'] = $extras['creditCardToken'];
            } elseif (!empty($extras['creditCard'])) {
                $payload['creditCard'] = $extras['creditCard'];
                if (!empty($extras['creditCardHolderInfo'])) {
                    $payload['creditCardHolderInfo'] = $extras['creditCardHolderInfo'];
                }
            }

            if (!empty($extras['remoteIp'])) {
                $payload['remoteIp'] = $extras['remoteIp'];
            }
    
            $options = [
                'headers' => $this->headers(),
                'json'    => $payload,
                'verify'  => !app()->environment('local'),
            ];
    
            $response = $client->post(AssasConfig::url() . 'v3/payments', $options);
            $body     = (string) $response->getBody();
    
            if ($response->getStatusCode() === 200) {
                $data = json_decode($body, true);
                return [
                    'id'              => $data['id'],
                    'invoiceUrl'      => $data['invoiceUrl'] ?? null,
                    'status'          => $data['status'] ?? null,
                    'creditCardToken' => $data['creditCard']['creditCardToken'] ?? ($data['creditCardToken'] ?? null),
                    'splits'          => $data['split'] ?? [],
                ];
            }

            Log::error('Erro ao Gerar Fatura (Controller AssasController) de '.$customer.': ' . $body);
            return false;
        } catch (\Exception $e) {
            Log::error('Erro ao Gerar Fatura (Controller AssasController) de '.$customer.': ' . $e->getMessage());
            return false;
        }
    }

    public function getPixQrCode(string $paymentId) {

        try {
            if (!$this->ensureConfigured()) {
                return false;
            }

            $client = new Client([
                'timeout'         => 30,
                'connect_timeout' => 10,
            ]);
            $response = $client->get(AssasConfig::url() . 'v3/payments/' . $paymentId . '/pixQrCode', [
                'headers' => $this->headers(),
                'verify'  => !app()->environment('local'),
            ]);
            $body = (string) $response->getBody();

            if ($response->getStatusCode() === 200) {
                $data = json_decode($body, true);
                return [
                    'encodedImage' => $data['encodedImage'] ?? null,
                    'payload'      => $data['payload'] ?? null,
                    'expirationDate' => $data['expirationDate'] ?? null,
                ];
            }

            Log::error('Erro ao obter QR Code PIX: ' . $body);
            return false;
        } catch (\Throwable $e) {
            Log::error('Erro ao obter QR Code PIX: ' . $e->getMessage());
            return false;
        }
    }

    public function canceledCharge ($token) {
        
        try {
            if (!$this->ensureConfigured()) {
                return false;
            }

            $client = new Client();
            $options = [
                'headers' => $this->headers(),
                'verify'  => !app()->environment('local'),
            ];

            $response = $client->delete(AssasConfig::url() . 'v3/payments/' . $token, $options);
            if ($response->getStatusCode() === 200) {
                $data = json_decode((string) $response->getBody(), true);
                return isset($data['deleted']) && $data['deleted'] === true;
            }

            return false;
        } catch (\Throwable $e) {
            Log::error('Erro ao cancelar fatura (ID: ' . $token . '): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cria assinatura mensal no Asaas (recorrência).
     * Preferir creditCardToken quando a 1ª parcela avulsa já foi cobrada.
     *
     * @return array{id: string, status: ?string, creditCardToken: ?string}|false
     */
    public function createdSubscription($customer, $billingType, $value, $nextDueDate, $description, array $extras = []): array|false
    {
        try {
            if (!$this->ensureConfigured()) {
                return false;
            }

            $client = new Client([
                'timeout'         => 60,
                'connect_timeout' => 15,
            ]);

            $maxPayments = (int) ($extras['maxPayments'] ?? 12);
            if ($maxPayments < 1) {
                $maxPayments = 12;
            }

            $payload = [
                'customer'    => $customer,
                'billingType' => $billingType,
                'value'       => number_format((float) $value, 2, '.', ''),
                'nextDueDate' => Carbon::parse($nextDueDate)->format('Y-m-d'),
                'cycle'       => 'MONTHLY',
                'description' => $description,
                'maxPayments' => $maxPayments,
            ];

            if (!empty($extras['creditCardToken'])) {
                $payload['creditCardToken'] = $extras['creditCardToken'];
            } elseif (!empty($extras['creditCard'])) {
                $payload['creditCard'] = $extras['creditCard'];
                if (!empty($extras['creditCardHolderInfo'])) {
                    $payload['creditCardHolderInfo'] = $extras['creditCardHolderInfo'];
                }
            }

            if (!empty($extras['remoteIp'])) {
                $payload['remoteIp'] = $extras['remoteIp'];
            }

            $response = $client->post(AssasConfig::url() . 'v3/subscriptions', [
                'headers' => $this->headers(),
                'json'    => $payload,
                'verify'  => !app()->environment('local'),
            ]);
            $body = (string) $response->getBody();

            if ($response->getStatusCode() === 200) {
                $data = json_decode($body, true);

                return [
                    'id'              => $data['id'],
                    'status'          => $data['status'] ?? null,
                    'creditCardToken' => $data['creditCard']['creditCardToken'] ?? ($data['creditCardToken'] ?? null),
                ];
            }

            Log::error('Erro ao criar assinatura Asaas de ' . $customer . ': ' . $body);
            return false;
        } catch (\Throwable $e) {
            Log::error('Erro ao criar assinatura Asaas de ' . $customer . ': ' . $e->getMessage());
            return false;
        }
    }

    public function canceledSubscription($id): bool
    {
        try {
            if (!$this->ensureConfigured()) {
                return false;
            }

            $client = new Client();
            $response = $client->delete(AssasConfig::url() . 'v3/subscriptions/' . $id, [
                'headers' => $this->headers(),
                'verify'  => !app()->environment('local'),
            ]);

            if ($response->getStatusCode() === 200) {
                $data = json_decode((string) $response->getBody(), true);
                return isset($data['deleted']) && $data['deleted'] === true;
            }

            return false;
        } catch (\Throwable $e) {
            Log::error('Erro ao cancelar assinatura Asaas (ID: ' . $id . '): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Lista cobranças geradas por uma assinatura.
     *
     * @return array|false
     */
    public function listSubscriptionPayments($id): array|false
    {
        try {
            if (!$this->ensureConfigured()) {
                return false;
            }

            $client = new Client([
                'timeout'         => 30,
                'connect_timeout' => 10,
            ]);
            $response = $client->get(AssasConfig::url() . 'v3/subscriptions/' . $id . '/payments', [
                'headers' => $this->headers(),
                'verify'  => !app()->environment('local'),
            ]);
            $body = (string) $response->getBody();

            if ($response->getStatusCode() === 200) {
                $data = json_decode($body, true);
                return $data['data'] ?? [];
            }

            Log::error('Erro ao listar pagamentos da assinatura ' . $id . ': ' . $body);
            return false;
        } catch (\Throwable $e) {
            Log::error('Erro ao listar pagamentos da assinatura ' . $id . ': ' . $e->getMessage());
            return false;
        }
    }

    public function webhook(Request $request) {

        $jsonData   = $request->json()->all();
        $token      = $jsonData['payment']['id'];

        if ($jsonData['event'] === 'PAYMENT_CONFIRMED' || $jsonData['event'] === 'PAYMENT_RECEIVED') {
            
            $invoice = Invoice::where('payment_token', $token)->whereIn('status', ['pendent', 'canceled'])->first();
            if ($invoice) {

                $invoice->status = 'paid';
                if ($invoice->save()) {
                    
                    // Ativa o comprador (titular da venda), não o consultor em invoice.user_id.
                    $sale = Sale::find($invoice->sale_id);
                    if ($sale && $sale->user) {
                        $sale->user->status = 'active';
                        $sale->user->save();
                    }

                    $extract = Extract::where('payment_token', $token) ->first();
                    if ($extract) { 
                        $extract->status = 'paid'; 
                        $extract->payment_date = now(); 
                        $extract->save();

                        $extractUser = User::find($extract->user_id); 
                        if ($extractUser) {
                            $extractUser->increment('wallet', $invoice->commission);
                        }
                    }

                    return response()->json(['message' => 'Fatura Aprovada!'], 200);
                } else {
                    return response()->json(['message' => 'Falha ao tentar Aprovar Fatura!'], 400);
                }
            }

            return response()->json(['message' => 'Nenhuma Fatura para o TOKEN!'], 200);
        };

        if ($jsonData['event'] === 'PAYMENT_OVERDUE') {

            $invoice = Invoice::where('payment_token', $token)->whereIn('status', ['pendent', 'canceled'])->first();
            if ($invoice) {

                $invoice->status = 'canceled';
                if ($invoice->save()) {

                    $extract = Extract::where('payment_token', $token) ->first();
                    if ($extract) { 
                        $extract->status = 'canceled'; 
                        $extract->payment_date = now(); 
                        $extract->save();
                    }

                    return response()->json(['message' => 'Fatura Cancelada por vencimento!'], 200);
                } else {
                    return response()->json(['message' => 'Falha o tentar Cancelar Fatura!'], 400);
                }
            }

            return response()->json(['message' => 'Nenhuma Fatura para o TOKEN!'], 200);
        };

        if ($jsonData['event'] === 'PAYMENT_DELETED') {
            
            $invoice = Invoice::where('payment_token', $token)->first();
            if ($invoice && $invoice->delete()) {

                $extract = Extract::where('payment_token', $token) ->first();
                if ($extract) { 
                    $extract->status = 'canceled'; 
                    $extract->payment_date = now(); 
                    $extract->save();
                }

                return response()->json(['message' => 'Fatura deletada via Assas e espelhada no app!'], 200);
            }

            return response()->json(['message' => 'Nenhuma Fatura para o TOKEN!'], 200);
        };

        if ($jsonData['event'] === 'PAYMENT_RESTORED') {

            $invoice = Invoice::withTrashed()->where('payment_token', $token)->first();
            if ($invoice) {
                if ($invoice->trashed()) {
                    $invoice->restore();

                    $extract = Extract::where('payment_token', $token) ->first();
                    if ($extract) { 
                        $extract->status = 'pendent'; 
                        $extract->payment_date = now(); 
                        $extract->save();
                    }

                    return response()->json(['message' => 'Fatura restaurada via Assas e espelhada no app!'], 200);
                }

                return response()->json(['message' => 'Fatura já está ativa, nenhuma ação necessária!'], 200);
            }

            return response()->json(['message' => 'Nenhuma Fatura para o TOKEN!'], 200);
        };
        
        return response()->json(['message' => 'Nenhum Evento disponível!'], 200);
    }
}
