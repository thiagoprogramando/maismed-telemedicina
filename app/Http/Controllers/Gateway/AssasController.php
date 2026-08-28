<?php

namespace App\Http\Controllers\Gateway;

use App\Http\Controllers\Controller;
use App\Models\Extract;
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
            if ($existingUser && !empty($existingUser->token)) {
                Log::warning("Tentativa de recriar cliente já existente", [
                    'document' => $cpfcnpj,
                    'email'    => $email,
                ]);
                return [
                    'user' => $existingUser,
                    'id'   => $existingUser->token,
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

                if ($existingUser) {
                    $user = $existingUser;
                } else {
                    $user             = new User();
                    $user->uuid       = Str::uuid();
                    $user->name       = $name;
                    $user->document   = $cpfcnpj;
                    $user->phone      = $mobilePhone;
                    $user->email      = $email;
                    $user->password   = bcrypt(substr(preg_replace('/\D/', '', $cpfcnpj), 0, 4));
                    $user->birth_date = $birth_date;
                }
                
                $user->token = $data['id'];
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

    public function canceledCharge ($token) {
        
        try {
            $client = new Client();
            $options = [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'access_token'  => env('API_TOKEN_ASSAS'),
                    'User-Agent'    => env('APP_NAME')
                ],
                'verify' => false
            ];

            $response = $client->delete(env('API_URL_ASSAS') . 'v3/payments/' . $token, $options);
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

    public function webhook(Request $request) {

        $jsonData   = $request->json()->all();
        $token      = $jsonData['payment']['id'];

        if ($jsonData['event'] === 'PAYMENT_CONFIRMED' || $jsonData['event'] === 'PAYMENT_RECEIVED') {
            
            $invoice = Invoice::where('payment_token', $token)->whereIn('status', ['pendent', 'canceled'])->first();
            if ($invoice) {

                $invoice->status = 'paid';
                if ($invoice->save()) {
                    
                    $user = $invoice->user;
                    if ($user) {
                        $user->status = 'active';
                        $user->save();
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
