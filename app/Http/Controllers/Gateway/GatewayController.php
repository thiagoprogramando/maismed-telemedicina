<?php

namespace App\Http\Controllers\Gateway;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\AssasConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GatewayController extends Controller {

    public function index() {
        if (Auth::user()->roles !== 'admin') {
            return redirect()->route('app')->with('infor', 'Acesso restrito ao administrador.');
        }

        return view('app.Gateway.index', [
            'apiUrl'      => Setting::getValue('asaas_api_url', ''),
            'hasToken'    => (bool) Setting::getSecret('asaas_api_token'),
            'source'      => AssasConfig::source(),
            'envUrlSet'   => !empty(config('services.asaas.url')),
            'envTokenSet' => !empty(config('services.asaas.token')),
            'configured'  => AssasConfig::isConfigured(),
        ]);
    }

    public function update(Request $request) {
        if (Auth::user()->roles !== 'admin') {
            return redirect()->route('app')->with('infor', 'Acesso restrito ao administrador.');
        }

        $validated = $request->validate([
            'asaas_api_url'   => ['nullable', 'url', 'max:255'],
            'asaas_api_token' => ['nullable', 'string', 'max:500'],
        ], [
            'asaas_api_url.url' => 'Informe uma URL válida da API Asaas.',
        ]);

        $url = isset($validated['asaas_api_url']) ? rtrim(trim($validated['asaas_api_url']), '/') . '/' : null;
        Setting::putValue('asaas_api_url', $url ?: null);

        // Token em branco não sobrescreve o que já está salvo.
        if (!empty($validated['asaas_api_token'])) {
            Setting::putSecret('asaas_api_token', $validated['asaas_api_token']);
        }

        return redirect()->route('gateway')->with('success', 'Configuração do gateway salva com sucesso!');
    }
}
