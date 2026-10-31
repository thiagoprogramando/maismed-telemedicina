@extends('app.layout')
@section('content')

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div class="page-heading">
            <i data-lucide="cable"></i>
            <h1 class="h3 text-primary">Gateway</h1>
        </div>
    </div>

    <form action="{{ route('updated-gateway') }}" method="POST">
        @csrf

        <div class="row">
            <div class="col-12 col-lg-8">
                <div class="card gw-card mb-4">
                    <div class="card-header py-3">
                        <h6 class="gw-section-title">
                            <i data-lucide="credit-card"></i>
                            Asaas
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            O sistema usa primeiro as variáveis do arquivo <code>.env</code>
                            <code>API_URL_ASSAS</code> e <code>API_TOKEN_ASSAS</code>.
                            Se estiverem vazias, usa os valores salvos abaixo.
                        </p>

                        <div class="mb-3">
                            @if ($configured)
                                <span class="badge badge-success">Gateway configurado</span>
                            @else
                                <span class="badge badge-danger">Gateway sem URL/token</span>
                            @endif

                            @if ($source === 'env')
                                <span class="badge badge-info">Fonte: .env</span>
                            @elseif ($source === 'database')
                                <span class="badge badge-info">Fonte: banco de dados</span>
                            @elseif ($source === 'mixed')
                                <span class="badge badge-warning">Fonte: mista (.env + banco)</span>
                            @else
                                <span class="badge badge-secondary">Fonte: nenhuma</span>
                            @endif
                        </div>

                        @if ($envUrlSet || $envTokenSet)
                            <div class="alert alert-warning small">
                                Há valor no <code>.env</code>
                                (@if($envUrlSet) URL @endif
                                @if($envUrlSet && $envTokenSet) e @endif
                                @if($envTokenSet) token @endif).
                                Ele tem prioridade sobre o formulário abaixo.
                            </div>
                        @endif

                        <div class="form-group">
                            <label for="asaas_api_url">URL da API Asaas</label>
                            <input type="url" name="asaas_api_url" id="asaas_api_url"
                                class="form-control @error('asaas_api_url') is-invalid @enderror"
                                value="{{ old('asaas_api_url', $apiUrl) }}"
                                placeholder="https://api.asaas.com/ ou https://sandbox.asaas.com/api/">
                            @error('asaas_api_url')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                            <small class="form-text text-muted">Sandbox: <code>https://sandbox.asaas.com/api/</code> — Produção: <code>https://api.asaas.com/</code></small>
                        </div>

                        <div class="form-group">
                            <label for="asaas_api_token">Token da API Asaas</label>
                            <input type="password" name="asaas_api_token" id="asaas_api_token"
                                class="form-control @error('asaas_api_token') is-invalid @enderror"
                                value=""
                                placeholder="{{ $hasToken ? 'Token já salvo — deixe em branco para manter' : 'Cole o access_token do Asaas' }}"
                                autocomplete="new-password">
                            @error('asaas_api_token')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                            @if ($hasToken)
                                <small class="form-text text-muted">Já existe um token no banco. Preencha só se quiser trocar.</small>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card gw-card mb-4">
                    <div class="card-header py-3">
                        <h6 class="gw-section-title">
                            <i data-lucide="mail"></i>
                            E-mail / SMTP
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            O sistema usa primeiro as variáveis do <code>.env</code>
                            <code>MAIL_HOST</code>, <code>MAIL_PORT</code>, <code>MAIL_USERNAME</code>,
                            <code>MAIL_PASSWORD</code>, <code>MAIL_SCHEME</code>,
                            <code>MAIL_FROM_ADDRESS</code>, <code>MAIL_FROM_NAME</code> e opcionalmente <code>MAIL_BCC</code>.
                            Se estiverem vazias, usa os valores salvos abaixo.
                        </p>

                        <div class="mb-3">
                            @if ($mailConfigured)
                                <span class="badge badge-success">SMTP configurado</span>
                            @else
                                <span class="badge badge-danger">SMTP incompleto</span>
                            @endif

                            @if ($mailSource === 'env')
                                <span class="badge badge-info">Fonte: .env</span>
                            @elseif ($mailSource === 'database')
                                <span class="badge badge-info">Fonte: banco de dados</span>
                            @elseif ($mailSource === 'mixed')
                                <span class="badge badge-warning">Fonte: mista (.env + banco)</span>
                            @else
                                <span class="badge badge-secondary">Fonte: nenhuma</span>
                            @endif
                        </div>

                        @if ($envMailHostSet || $envMailUserSet || $envMailPasswordSet || $envMailFromSet || $envMailBccSet)
                            <div class="alert alert-warning small">
                                Há valor de e-mail no <code>.env</code>
                                (@if($envMailHostSet) host @endif
                                @if($envMailUserSet){{ $envMailHostSet ? ',' : '' }} usuário @endif
                                @if($envMailPasswordSet){{ ($envMailHostSet || $envMailUserSet) ? ',' : '' }} senha @endif
                                @if($envMailFromSet){{ ($envMailHostSet || $envMailUserSet || $envMailPasswordSet) ? ',' : '' }} remetente @endif
                                @if($envMailBccSet){{ ($envMailHostSet || $envMailUserSet || $envMailPasswordSet || $envMailFromSet) ? ',' : '' }} BCC @endif).
                                Ele tem prioridade sobre o formulário abaixo.
                            </div>
                        @endif

                        <div class="form-row">
                            <div class="form-group col-md-8">
                                <label for="smtp_host">Host SMTP</label>
                                <input type="text" name="smtp_host" id="smtp_host"
                                    class="form-control @error('smtp_host') is-invalid @enderror"
                                    value="{{ old('smtp_host', $smtpHost) }}"
                                    placeholder="smtp.seudominio.com">
                                @error('smtp_host')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="form-group col-md-4">
                                <label for="smtp_port">Porta</label>
                                <input type="number" name="smtp_port" id="smtp_port"
                                    class="form-control @error('smtp_port') is-invalid @enderror"
                                    value="{{ old('smtp_port', $smtpPort) }}"
                                    placeholder="587" min="1" max="65535">
                                @error('smtp_port')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-8">
                                <label for="smtp_username">Usuário</label>
                                <input type="text" name="smtp_username" id="smtp_username"
                                    class="form-control @error('smtp_username') is-invalid @enderror"
                                    value="{{ old('smtp_username', $smtpUsername) }}"
                                    autocomplete="off">
                                @error('smtp_username')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="form-group col-md-4">
                                <label for="smtp_encryption">Criptografia</label>
                                <select name="smtp_encryption" id="smtp_encryption"
                                    class="form-control @error('smtp_encryption') is-invalid @enderror">
                                    @php $enc = old('smtp_encryption', $smtpEncryption); @endphp
                                    <option value="tls" @selected($enc === 'tls')>TLS</option>
                                    <option value="ssl" @selected($enc === 'ssl')>SSL</option>
                                    <option value="" @selected($enc === '' || $enc === null)>Nenhuma</option>
                                </select>
                                @error('smtp_encryption')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="smtp_password">Senha SMTP</label>
                            <input type="password" name="smtp_password" id="smtp_password"
                                class="form-control @error('smtp_password') is-invalid @enderror"
                                value=""
                                placeholder="{{ $hasSmtpPassword ? 'Senha já salva — deixe em branco para manter' : 'Senha do SMTP' }}"
                                autocomplete="new-password">
                            @error('smtp_password')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                            @if ($hasSmtpPassword)
                                <small class="form-text text-muted">Já existe uma senha no banco. Preencha só se quiser trocar.</small>
                            @endif
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="smtp_from_address">E-mail remetente</label>
                                <input type="email" name="smtp_from_address" id="smtp_from_address"
                                    class="form-control @error('smtp_from_address') is-invalid @enderror"
                                    value="{{ old('smtp_from_address', $smtpFromAddress) }}"
                                    placeholder="noreply@seudominio.com">
                                @error('smtp_from_address')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label for="smtp_from_name">Nome remetente</label>
                                <input type="text" name="smtp_from_name" id="smtp_from_name"
                                    class="form-control @error('smtp_from_name') is-invalid @enderror"
                                    value="{{ old('smtp_from_name', $smtpFromName) }}"
                                    placeholder="{{ config('app.name') }}">
                                @error('smtp_from_name')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="smtp_bcc">BCC opcional</label>
                            <input type="email" name="smtp_bcc" id="smtp_bcc"
                                class="form-control @error('smtp_bcc') is-invalid @enderror"
                                value="{{ old('smtp_bcc', $smtpBcc) }}"
                                placeholder="copia@seudominio.com">
                            @error('smtp_bcc')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                            <small class="form-text text-muted">Recebe cópia oculta dos e-mails de adesão/pagamento.</small>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mb-4">
                    <i data-lucide="save" style="width: 16px; height: 16px; vertical-align: -0.2rem;"></i>
                    Salvar configuração
                </button>
            </div>
        </div>
    </form>

@endsection
