@extends('app.layout')
@section('content')

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-primary">Incluir beneficiário</h1>
        <a href="{{ route('sales') }}" class="btn btn-outline-secondary btn-sm">Voltar</a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow">
        <div class="card-body">
            <p class="text-muted small mb-4">
                Preencha os dados do titular e o plano. Ao finalizar, o sistema gera um link para o cliente
                <strong>aceitar o contrato</strong> e <strong>realizar o pagamento</strong>.
            </p>

            <form action="{{ route('sales-store') }}" method="POST" class="row">
                @csrf

                <div class="col-12 col-md-6 mb-3">
                    <label class="small text-muted" for="plan_id">Plano</label>
                    <select name="plan_id" id="plan_id" class="form-control @error('plan_id') is-invalid @enderror" required>
                        <option value="">Selecione…</option>
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->uuid }}" @selected(old('plan_id') === $plan->uuid)>
                                {{ $plan->name }} — R$ {{ number_format((float) $plan->price, 2, ',', '.') }}
                            </option>
                        @endforeach
                    </select>
                    @error('plan_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                @if(Auth::user()->roles === 'admin' && ($consultants ?? collect())->isNotEmpty())
                    <div class="col-12 col-md-6 mb-3">
                        <label class="small text-muted" for="seller_uuid">Consultor responsável</label>
                        <select name="seller_uuid" id="seller_uuid" class="form-control">
                            <option value="{{ Auth::user()->uuid }}">Eu ({{ Auth::user()->name }})</option>
                            @foreach ($consultants as $consultant)
                                @if ($consultant->id !== Auth::user()->id)
                                    <option value="{{ $consultant->uuid }}" @selected(old('seller_uuid') === $consultant->uuid)>
                                        {{ $consultant->name }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-12"><hr class="my-2"><div class="text-xs font-weight-bold text-uppercase text-muted mb-2">Dados do titular</div></div>

                <div class="col-12 col-md-6 mb-3">
                    <label class="small text-muted" for="name">Nome completo</label>
                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-3 mb-3">
                    <label class="small text-muted" for="document">CPF/CNPJ</label>
                    <input type="text" name="document" id="document" class="form-control @error('document') is-invalid @enderror" value="{{ old('document') }}" oninput="maskCpfCnpj(this)" required>
                    @error('document')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-3 mb-3">
                    <label class="small text-muted" for="birth_date">Nascimento</label>
                    <input type="date" name="birth_date" id="birth_date" class="form-control @error('birth_date') is-invalid @enderror" value="{{ old('birth_date') }}" required>
                    @error('birth_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-6 mb-3">
                    <label class="small text-muted" for="email">E-mail</label>
                    <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                    @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-6 mb-3">
                    <label class="small text-muted" for="phone">Telefone</label>
                    <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" oninput="maskPhone(this)" required>
                    @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-md-3 mb-3">
                    <label class="small text-muted" for="postal_code">CEP</label>
                    <input type="text" name="postal_code" id="postal_code" class="form-control" value="{{ old('postal_code') }}" onblur="consultAddress(this)">
                </div>
                <div class="col-12 col-md-6 mb-3">
                    <label class="small text-muted" for="address">Endereço</label>
                    <input type="text" name="address" id="address" class="form-control" value="{{ old('address') }}">
                </div>
                <div class="col-12 col-md-3 mb-3">
                    <label class="small text-muted" for="address_number">Nº</label>
                    <input type="text" name="address_number" id="address_number" class="form-control" value="{{ old('address_number') }}">
                </div>
                <div class="col-12 col-md-6 mb-3">
                    <label class="small text-muted" for="address_city">Cidade</label>
                    <input type="text" name="address_city" id="address_city" class="form-control" value="{{ old('address_city') }}" readonly>
                </div>
                <div class="col-12 col-md-6 mb-3">
                    <label class="small text-muted" for="address_provincy">Estado</label>
                    <input type="text" name="address_provincy" id="address_provincy" class="form-control" value="{{ old('address_provincy') }}" readonly>
                </div>

                <div class="col-12"><hr class="my-2"><div class="text-xs font-weight-bold text-uppercase text-muted mb-2">Pagamento</div></div>

                <div class="col-12 col-md-6 mb-3">
                    <label class="small text-muted" for="payment_method">Forma preferencial</label>
                    <select name="payment_method" id="payment_method" class="form-control" required>
                        <option value="PIX" @selected(old('payment_method', 'PIX') === 'PIX')>PIX</option>
                        <option value="CREDIT_CARD" @selected(old('payment_method') === 'CREDIT_CARD')>Cartão de crédito</option>
                        <option value="BOLETO" @selected(old('payment_method') === 'BOLETO')>Boleto</option>
                    </select>
                </div>
                <div class="col-12 col-md-6 mb-3">
                    <label class="small text-muted" for="due_date">Dia de vencimento das próximas parcelas</label>
                    <select name="due_date" id="due_date" class="form-control" required>
                        @foreach ([5, 10, 15, 20] as $day)
                            <option value="{{ $day }}" @selected((int) old('due_date', 10) === $day)>{{ $day }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 mt-2">
                    <button type="submit" class="btn btn-primary">
                        Gerar link de contrato e pagamento
                    </button>
                    <a href="{{ route('sales') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>

@endsection
