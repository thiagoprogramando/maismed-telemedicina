<?php

namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller {
    
    public function index () {

        if (Auth::check()) {
            return redirect()->route('app');
        }

        return view('login');
    }

    public function logon (Request $request) {

        $request->validate([
            'email'     => 'required|email|exists:users,email',
            'password'  => 'required',
        ], [
            'email.exists' => 'Você ainda não possui cadastro! <a href="'.route('register').'">Cadastre-se aqui</a> para ter sua Conta.',
        ]);

        $credentials = $request->only(['email', 'password']);
        if (Auth::attempt($credentials)) {
            return redirect()->route('app');
        } else {
            return redirect()->back()->withInput($request->only('email'))->with('error', 'Credenciais inválidas!');
        }
    }

    public function logout () {

        Auth::logout();
        return redirect()->route('login');
    }
}
