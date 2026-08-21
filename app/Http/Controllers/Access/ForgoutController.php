<?php

namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

use Carbon\Carbon;
use App\Mail\Forgout;

class ForgoutController extends Controller {
    
    public function index ($code = null) {
        return view('forgout', [
            'code' => $code,
        ]);
    }

    public function forgoutPassword (Request $request) {

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return redirect()->back()->with('error', 'Email não encontrado! Verifique os dados e tente novamente.');
        }

        $token = str_shuffle(Str::upper(Str::random(3)) . str_pad(mt_rand(0, 999), 3, '0', STR_PAD_LEFT));

        DB::table('password_reset_tokens')->updateOrInsert(
            [
                'email' => $user->email
            ],
            [
                'token'         => $token,
                'created_at'    => Carbon::now(),
            ]
        );
        
        Mail::to($user->email, $user->name)->send(new Forgout([
            'fromName'  => env('MAIL_FROM_NAME'),
            'fromEmail' => env('MAIL_FROM_ADDRESS'),
            'toName'    => $user->name,
            'toEmail'   => $user->email,
            'token'     => $token,  
        ]));

        return redirect()->back()->with('success', 'Verifique sua caixa de E-mail, enviamos às instruções para você!');
    }

    public function recoverPassword (Request $request, $code) {

        $token = DB::table('password_reset_tokens')->where('token', $code)->first();
        if (!$token) {
            return redirect()->back()->with('error', 'Código inválido! Verifique os dados e tente novamente.');
        }

        $user = User::where('email', $token->email)->first();
        if (!$user) {
            return redirect()->back()->with('error', 'E-mail não válidado! Verifique os dados e tente novamente.');
        }

        if ($request->password !== $request->password_confirmed) {
            return redirect()->back()->with('infor', 'As senhas não conferem!');
        }

        $user->password = bcrypt($request->password);
        if ($user->save()) {
            DB::table('password_reset_tokens')->where('email', $token->email)->delete();
            return redirect()->route('login')->with('success', 'Senha alterada com sucesso! Você já pode acessar sua conta.');
        }

        return redirect()->route('forgout')->with('error', 'Não foi possível alterar a senha! Verifique os dados e tente novamente.');	
       
    }
}
