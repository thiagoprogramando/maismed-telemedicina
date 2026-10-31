<?php

namespace App\Http\Controllers\Gateway;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\AssasConfig;
use App\Support\MailConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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

            'smtpHost'           => Setting::getValue('smtp_host', ''),
            'smtpPort'           => Setting::getValue('smtp_port', '587'),
            'smtpUsername'       => Setting::getValue('smtp_username', ''),
            'hasSmtpPassword'    => (bool) Setting::getSecret('smtp_password'),
            'smtpEncryption'     => Setting::getValue('smtp_encryption', 'tls'),
            'smtpFromAddress'    => Setting::getValue('smtp_from_address', ''),
            'smtpFromName'       => Setting::getValue('smtp_from_name', ''),
            'smtpBcc'            => Setting::getValue('smtp_bcc', ''),
            'mailSource'         => MailConfig::source(),
            'mailConfigured'     => MailConfig::isConfigured(),
            'envMailHostSet'     => !empty(config('mail.mailers.smtp.host')),
            'envMailUserSet'     => !empty(config('mail.mailers.smtp.username')),
            'envMailPasswordSet' => !empty(config('mail.mailers.smtp.password')),
            'envMailFromSet'     => !empty(config('mail.from.address')),
            'envMailBccSet'      => !empty(config('mail.bcc')),
        ]);
    }

    public function update(Request $request) {
        if (Auth::user()->roles !== 'admin') {
            return redirect()->route('app')->with('infor', 'Acesso restrito ao administrador.');
        }

        $validated = $request->validate([
            'asaas_api_url'   => ['nullable', 'url', 'max:255'],
            'asaas_api_token' => ['nullable', 'string', 'max:500'],

            'smtp_host'         => ['nullable', 'string', 'max:255'],
            'smtp_port'         => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_username'     => ['nullable', 'string', 'max:255'],
            'smtp_password'     => ['nullable', 'string', 'max:500'],
            'smtp_encryption'   => ['nullable', 'string', Rule::in(['', 'tls', 'ssl'])],
            'smtp_from_address' => ['nullable', 'email', 'max:255'],
            'smtp_from_name'    => ['nullable', 'string', 'max:255'],
            'smtp_bcc'          => ['nullable', 'email', 'max:255'],
        ], [
            'asaas_api_url.url'         => 'Informe uma URL válida da API Asaas.',
            'smtp_from_address.email'   => 'Informe um e-mail remetente válido.',
            'smtp_bcc.email'            => 'Informe um e-mail BCC válido.',
            'smtp_port.integer'         => 'A porta SMTP deve ser um número.',
        ]);

        $url = isset($validated['asaas_api_url']) ? rtrim(trim($validated['asaas_api_url']), '/') . '/' : null;
        Setting::putValue('asaas_api_url', $url ?: null);

        // Token em branco não sobrescreve o que já está salvo.
        if (!empty($validated['asaas_api_token'])) {
            Setting::putSecret('asaas_api_token', $validated['asaas_api_token']);
        }

        Setting::putValue('smtp_host', $this->nullableTrim($validated['smtp_host'] ?? null));
        Setting::putValue(
            'smtp_port',
            isset($validated['smtp_port']) && $validated['smtp_port'] !== null
                ? (string) $validated['smtp_port']
                : null
        );
        Setting::putValue('smtp_username', $this->nullableTrim($validated['smtp_username'] ?? null));
        Setting::putValue(
            'smtp_encryption',
            $this->nullableTrim($validated['smtp_encryption'] ?? null)
        );
        Setting::putValue('smtp_from_address', $this->nullableTrim($validated['smtp_from_address'] ?? null));
        Setting::putValue('smtp_from_name', $this->nullableTrim($validated['smtp_from_name'] ?? null));
        Setting::putValue('smtp_bcc', $this->nullableTrim($validated['smtp_bcc'] ?? null));

        // Senha em branco não sobrescreve a que já está salva.
        if (!empty($validated['smtp_password'])) {
            Setting::putSecret('smtp_password', $validated['smtp_password']);
        }

        return redirect()->route('gateway')->with('success', 'Configuração do gateway salva com sucesso!');
    }

    private function nullableTrim(?string $value): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
