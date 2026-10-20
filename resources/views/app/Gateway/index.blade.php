@extends('app.layout')
@section('content')

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-primary">GATEWAY DE PAGAMENTO</h1>
    </div>

    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Asaas</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        O sistema usa primeiro as variáveis do arquivo <code>.env</code>
                        (<code>API_URL_ASSAS</code> e <code>API_TOKEN_ASSAS</code>.
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

                    <form action="{{ route('updated-gateway') }}" method="POST">
                        @csrf

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

                        <button type="submit" class="btn btn-primary">Salvar configuração</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
