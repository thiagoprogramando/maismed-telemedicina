<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

use App\Models\User;
use App\Models\Sale;

class UserController extends Controller {
    
    public function index() {
        
    }

    public function show($uuid) {
        
        $user = User::where('uuid', $uuid)->first();
        if (!$user) {
            return redirect()->back()->with('error', 'Perfil não encontrado, verifique os dados e tente novamente!');
        }

        return view('app.User.show', [
            'user' => $user
        ]);
    }

    public function store (Request $request) {

        if ($request->sale_uuid) {
            $validatedPlan = $this->validatedPlan($request->sale_uuid);
            if ($validatedPlan !== true) {
                return $validatedPlan;
            }
        }

        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'document'       => ['required', 'string', 'unique:users,document'],
            'phone'          => ['required', 'string'],
            'email'          => ['required', 'email'],
        ], [
            'name.required'           => 'Informe seu nome completo.',
            'name.string'             => 'O nome informado é inválido.',
            'name.max'                => 'O nome não pode ter mais que :max caracteres.',
            'document.required'       => 'Informe seu CPF ou CNPJ.',
            'document.string'         => 'O documento informado é inválido.',
            'document.unique'         => 'Já existe um usuário cadastrado com este documento.',
            'phone.required'          => 'Informe um telefone para contato.',
            'phone.string'            => 'O telefone informado é inválido.',
            'email.required'          => 'Informe seu e-mail.',
            'email.email'             => 'Informe um e-mail válido.',
        ]);

        $user               = new User();
        $user->parent_id    = $request->parent_id ?? Auth::user()->id;
        $user->uuid         = Str::uuid();
        $user->name         = $request->name;
        $user->document     = preg_replace('/\D/', '', $request->document);
        $user->password     = bcrypt(substr(preg_replace('/\D/', '', $request->document), 0, 4));
        $user->email        = $request->email;
        $user->phone        = preg_replace('/\D/', '', $request->phone);
        $user->birth_date   = $request->birth_date;
        $user->postal_code          = $request->postal_code;
        $user->address              = $request->address;
        $user->address_number       = $request->address_number;
        $user->address_city         = $request->address_city;
        $user->address_provincy     = $request->address_provincy;
        if ($user->save()) {
            return redirect()->back()->with('success', 'Perfil cadastrado com sucesso!');
        }

        return redirect()->back()->with('error', 'Erro ao cadastrar Perfil, verifique os dados e tente novamente!');
    }

    public function update (Request $request, $uuid) {
        
        $user = User::where('uuid', $uuid)->first();
        if (!$user) {
            return redirect()->back()->with('error', 'Perfil não encontrado, verifique os dados e tente novamente!');
        }

        if (!empty($request->name)) {
            $user->name = $request->name;
        }
        if (!empty($request->email)) {
            $user->email = $request->email;
        }
        if (!empty($request->phone)) {
            $user->phone = preg_replace('/\D/', '', $request->phone);
        }
        if (!empty($request->postal_code)) {
            $user->postal_code = $request->postal_code;
        }
        if (!empty($request->address)) {
            $user->address = $request->address;
        }
        if (!empty($request->address_number)) {
            $user->address_number = $request->address_number;
        }
        if (!empty($request->address_complement)) {
            $user->address_complement = $request->address_complement;
        }
        if (!empty($request->address_city)) {
            $user->address_city = $request->address_city;
        }
        if (!empty($request->address_provincy)) {
            $user->address_provincy = $request->address_provincy;
        }
        if (Auth::user()->roles == 'admin' && !empty($request->roles)) {
            $user->roles    = $request->roles;
            $user->password = bcrypt($request->password);
        }
        
        if ($user->save()) {
            return redirect()->back()->with('success', 'Perfil atualizado com sucesso!');
        } 

        return redirect()->back()->with('error', 'Erro ao atualizar perfil, verifique os dados e tente novamente!');
    }

    public function destroy (Request $request,$uuid) {

        if (!Auth::user()->roles == 'admin') {
            return redirect()->back()->with('error', 'Você não tem permissão para excluir este perfil!');
        }

        if (Hash::check($request->password, Auth::user()->password)) {

            $user = User::where('uuid', $uuid)->first();
            if ($user && $user->delete()) {
                return redirect()->back()->with('success', 'Perfil excluído com sucesso!');
            }

            return redirect()->back()->with('error', 'Erro ao excluir perfil, verifique os dados e tente novamente!');
        }

        return redirect()->back()->with('error', 'Autenticação falhou, verifique os dados e tente novamente!');
    }

    private function validatedPlan($saleUuid) {

        $sale = Sale::with(['plan', 'user.children'])->where('uuid', $saleUuid)->first();
        if (!$sale) {
            return redirect()->back()->with('error', 'Plano não encontrado, verifique os dados e tente novamente!');
        }

        if (!$sale->plan) {
            return redirect()->back()->with('error', 'O plano vinculado a esta venda não está disponível!');
        }

        if (!$sale->user) {
            return redirect()->back()->with('error', 'O titular deste plano não foi encontrado!');
        }

        $maxUsers       = $sale->plan->max_users;
        $dependents     = $sale->user->children->count();
        $currentUsers   = 1 + $dependents;

        if ($maxUsers !== null && $currentUsers >= $maxUsers) {
            return redirect()->back()->with('error', 'O limite de usuários deste plano foi atingido!');
        }

        return true;
    }
}
