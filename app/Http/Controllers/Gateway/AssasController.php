<?php

namespace App\Http\Controllers\Gateway;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Invoice;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class AssasController extends Controller {
    
    public function createdCustomer($name, $cpfcnpj, $mobilePhone = null, $email = null, $birth_date = null) {
        try {

            $existingUser = User::where('document', $cpfcnpj)->first();
            if ($existingUser) {
                Log::warning("Tentativa de recriar cliente já existente", [
                    'document' => $cpfcnpj,
                    'email'    => $email,
                ]);
                return [
                    'error'   => true,
                    'message' => 'CPF/CNPJ já cadastrado no sistema. Por favor, utilize outro documento ou entre em contato com o suporte!',
                ];
            }
    
            $client     = new Client();
            $options    = [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'accept'       => 'application/json',
                    'access_token' => env('API_TOKEN_ASSAS'),
                    'User-Agent'   => env('APP_NAME')
                ],
                'json' => [
                    'name'                 => $name,
                    'cpfCnpj'              => $cpfcnpj,
                    'mobilePhone'          => $mobilePhone,
                    'email'                => $email,
                    'notificationDisabled' => true,
                ],
                'verify' => !app()->environment('local'),
            ];
    
            $response = $client->post(env('API_URL_ASSAS') . 'v3/customers', $options);
            $body     = (string) $response->getBody();
            $data     = json_decode($body, true);
    
            if ($response->getStatusCode() === 200 && isset($data['id'])) {

                $user             = new User();
                $user->uuid       = Str::uuid();
                $user->name       = $name;
                $user->document   = $cpfcnpj;
                $user->phone      = $mobilePhone;
                $user->email      = $email;
                $user->password   = bcrypt(substr(preg_replace('/\D/', '', $cpfcnpj), 0, 4));
                $user->birth_date = $birth_date;
                $user->token      = $data['id'];
                if ($user->save()) {
                    return [
                        'user' => $user,
                        'id'   => $data['id'],
                    ];
                } else {
                    Log::error("Falha ao salvar usuário local após criar cliente no Asaas", [
                        'token'    => $data['id'],
                        'document' => $cpfcnpj,
                    ]);
                    return [
                        'error'   => true,
                        'message' => 'Falha no cadastro, verifique seus dados e tente novamente!',
                    ];
                }
            }
    
            Log::error("Erro na criação do cliente: " . json_encode($data));
            return [
                'error'   => true,
                'message' => 'Falha no cadastro, verifique seus dados e tente novamente!',
            ];
        } catch (RequestException $e) {
            Log::error("Erro de requisição na API Assas CreateCustomer: " . $e->getMessage());
            return [
                'error'   => true,
                'message' => 'Falha no cadastro, verifique seus dados e tente novamente! Problema: '.$e->getMessage(),
            ];
        } catch (\Exception $e) {
            Log::error("Erro geral na função createCustomer: " . $e->getMessage());
            return [
                'error'   => true,
                'message' => 'Falha no cadastro, verifique seus dados e tente novamente! Problema: '.$e->getMessage(),
            ];
        }
    }
    
    public function createdCharge ($customer, $billingType, $installments, $value, $description, $dueDate) {
        try {
            $client = new Client();
    
            $options = [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'access_token' => env('API_TOKEN_ASSAS'),
                    'User-Agent'   => env('APP_NAME')
                ],
                'json' => [
                    'customer'          => $customer,
                    'billingType'       => $billingType,
                    'installmentCount'  => $installments ?? 1,
                    'installmentValue'  => number_format(($value / ($installments ?? 1)), 2, '.', ''),
                    'value'             => number_format($value, 2, '.', ''),
                    'dueDate'           => Carbon::parse($dueDate)->toIso8601String(),
                    'description'       => $description, 
                    'isAddressRequired' => false,
                ],
                'verify' => false
            ];

            if (env('APP_ENV') !== 'local') {
                $options['json']['callback'] =  ['successUrl' => env('APP_URL')];
            }
    
            $response =  $client->post(env('API_URL_ASSAS') . 'v3/payments', $options);
            $body       = (string) $response->getBody();
    
            if ($response->getStatusCode() === 200) {
                $data = json_decode($body, true);
                return [
                    'id'            => $data['id'],
                    'invoiceUrl'    => $data['invoiceUrl'],
                    'splits'        => $data['split'] ?? [],
                ];
            } else {
                Log::error('Erro ao Gerar Fatura (Controller AssasController) de '.$customer.': ' . $body);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Erro ao Gerar Fatura (Controller AssasController) de '.$customer.': ' . $e->getMessage());
            return false;
        }
    }

    public function webhook(Request $request) {

        $jsonData   = $request->json()->all();
        $token      = $jsonData['payment']['id'];

        // if ($jsonData['event'] === 'PAYMENT_CONFIRMED' || $jsonData['event'] === 'PAYMENT_RECEIVED') {
            
        //     $invoice = Invoice::where('payment_token', $token)->whereIn('status', ['pendent', 'canceled'])->first();
        //     if ($invoice) {

        //         $invoice->status = 'paid';
        //         if ($invoice->save()) {
        //             return response()->json(['message' => 'Fatura Aprovada!'], 200);
        //         } else {
        //             return response()->json(['message' => 'Falha ao tentar Aprovar Fatura!'], 400);
        //         }
        //     }

        //     return response()->json(['message' => 'Nenhuma Fatura para o TOKEN!'], 200);
        // };

        if ($jsonData['event'] === 'PAYMENT_OVERDUE') {

            $invoice = Invoice::where('payment_token', $token)->whereIn('status', [0, 2])->first();
            if ($invoice) {

                $invoice->status = 2;
                if ($invoice->save()) {
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
                return response()->json(['message' => 'Fatura deletada via Assas e espelhada no app!'], 200);
            }

            return response()->json(['message' => 'Nenhuma Fatura para o TOKEN!'], 200);
        };

        if ($jsonData['event'] === 'PAYMENT_RESTORED') {

            $invoice = Invoice::withTrashed()->where('payment_token', $token)->first();
            if ($invoice) {
                if ($invoice->trashed()) {
                    $invoice->restore();
                    return response()->json(['message' => 'Fatura restaurada via Assas e espelhada no app!'], 200);
                }

                return response()->json(['message' => 'Fatura já está ativa, nenhuma ação necessária!'], 200);
            }

            return response()->json(['message' => 'Nenhuma Fatura para o TOKEN!'], 200);
        };
        
        return response()->json(['message' => 'Nenhum Evento disponível!'], 200);
    }
}
