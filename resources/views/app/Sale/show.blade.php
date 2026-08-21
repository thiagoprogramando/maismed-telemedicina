@extends('app.layout')
@section('content')

    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet" />

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-primary">BENEFICIÁRIO {{ $sale->user->name }}</h1>
        {{-- <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-graduation-cap fa-sm text-white-50"></i> Aprender</a> --}}
    </div>

    <div class="row">
        <div class="col-12 col-sm-12 col-md-7 col-lg-7 mb-2">
            <div class="card shadow h-100">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-dark text-uppercase mb-1">DADOS DO TITULAR</div>
                    <form action="{{ route('updated-user', $sale->user->uuid) }}" method="POST" class="row">
                        @csrf
                        <div class="col-12 col-md-12 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="name" id="name" placeholder="Nome" value="{{ $sale->user->name }}">
                                <label for="name">Nome</label>
                            </div>
                        </div>
                        <div class="col-4 col-md-6 col-lg-6 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control cpfcnpj" name="document" id="document" oninput="maskCpfCnpj(this)" placeholder="CPF/CNPJ" value="{{ $sale->user->document }}" readonly>
                                <label for="document">CPF/CNPJ</label>
                            </div>
                        </div>
                        <div class="col-4 col-md-6 col-lg-6 mb-2">
                            <div class="form-floating">
                                <input type="date" class="form-control" name="birth_date" id="birth_date" placeholder="Data de Nascimento" value="{{ $sale->user->birth_date }}" readonly>
                                <label for="birth_date">Data de Nascimento</label>
                            </div>
                        </div>
                        <div class="col-4 col-md-6 col-lg-6 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="email" id="email" placeholder="E-mail" value="{{ $sale->user->email }}">
                                <label for="email">E-mail</label>
                            </div>
                        </div>
                        <div class="col-4 col-md-6 col-lg-6 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control phone" name="phone" id="phone" oninput="maskPhone(this)" placeholder="Telefone" value="{{ $sale->user->phone }}">
                                <label for="phone">Telefone</label>
                            </div>
                        </div>
                        <div class="col-4 col-md-3 col-lg-3 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control cep" name="postal_code" id="postal_code" onblur="consultAddress(this)" placeholder="CEP" value="{{ $sale->user->postal_code }}">
                                <label for="postal_code">CEP</label>
                            </div>
                        </div>
                        <div class="col-4 col-md-3 col-lg-3 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="address_number" id="address_number" placeholder="N" value="{{ $sale->user->address_number }}">
                                <label for="address_number">N</label>
                            </div>
                        </div>
                        <div class="col-4 col-md-6 col-lg-6 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="address" id="address" placeholder="Endereço" value="{{ $sale->user->address }}">
                                <label for="address">Endereço</label>
                            </div>
                        </div>
                        <div class="col-4 col-md-6 col-lg-6 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="address_city" id="address_city" placeholder="Endereço" value="{{ $sale->user->address_city }}">
                                <label for="address_city">Cidade</label>
                            </div>
                        </div>
                        <div class="col-4 col-md-6 col-lg-6 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="address_provincy" id="address_provincy" placeholder="Endereço" value="{{ $sale->user->address_provincy }}">
                                <label for="address_provincy">Estado</label>
                            </div>
                        </div>
                        <div class="form-group col-12 col-md-12 col-lg-12">
                            <button type="submit" class="btn btn-primary btn-user btn-block"> Atualizar </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-sm-12 col-md-5 col-lg-5 mb-2">
            <div class="card shadow h-100">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-dark text-uppercase mb-1">DEPENDENTES</div>
                    <div class="btn-group mb-4">
                        <button type="button" class="btn btn-outline-dark text-muted" data-toggle="modal" data-target="#createdModal" title="Novo Dependente"><i class="fas fa-plus fa-sm text-muted"></i></button>
                        <button type="button" class="btn btn-outline-dark" title="Recarregar" onClick="location.reload()"><i class="fas fa-sync-alt fa-sm text-muted"></i></button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Beneficiário</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sale->user->children as $user)
                                    <tr>
                                        <td>
                                            <strong>{{ $user->name }}</strong> <br>
                                            <small class="text-muted">{{ $user->maskCpfCnpj() }}</small>
                                        </td>
                                        <td>
                                            <form action="{{ route('deleted-user', ['uuid' => $user->uuid]) }}" method="POST" class="btn-group confirm">
                                                @csrf
                                                <a href="{{ route('user', ['uuid' => $user->uuid]) }}" class="btn btn-outline-dark" title="Editar"><i class="fas fa-edit fa-sm text-muted"></i></a>
                                                <button type="submit" class="btn btn-outline-dark" title="Excluir"><i class="fas fa-trash fa-sm text-muted"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div> 
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-12 col-md-12 col-lg-12 mb-2">
            <div class="card shadow h-100">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-dark text-uppercase mb-1">FATURAS DO BENEFICIÁRIO</div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Fatura</th>
                                    <th class="text-center">Vencimento</th>
                                    <th class="text-center">Valor</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sale->invoices as $invoice)
                                    <tr>
                                        <td>
                                            <strong>{{ $invoice->name }}</strong> <br>
                                            <small class="text-muted">{{ $invoice->description }}</small>
                                        </td>
                                        <td class="text-center">
                                            <small class="text-muted">{{ \Carbon\Carbon::parse($invoice->payment_due_date)->format('d/m/Y') }}</small>
                                        </td>
                                        <td class="text-center"><small class="text-muted">R$ {{ number_format($invoice->price, 2, ',', '.') }}</small></td>
                                        <td class="text-center"><small class="text-muted">{!! $invoice->labelStatus() !!}</small></td>
                                        <td class="text-center">
                                            <a href="{{ $invoice->payment_url }}" target="_blank" class="btn btn-outline-dark" title="Editar"><i class="fas fa-copy fa-sm text-muted"></i></a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createdModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form action="{{ route('created-user') }}" method="POST" class="modal-content" id="form">
                @csrf
                <input type="hidden" name="sale_uuid" value="{{ $sale->uuid }}">
                <input type="hidden" name="parent_id" id="parent_id" value="{{ $sale->user->id }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">CADASTRO DE DEPENDENTE</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12 col-md-12 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="name" id="name" placeholder="Nome">
                                <label for="name">Nome</label>
                            </div>
                        </div>
                        <div class="col-4 col-md-6 col-lg-6 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control cpfcnpj" name="document" id="document" oninput="maskCpfCnpj(this)" placeholder="CPF/CNPJ">
                                <label for="document">CPF/CNPJ</label>
                            </div>
                        </div>
                        <div class="col-4 col-md-6 col-lg-6 mb-2">
                            <div class="form-floating">
                                <input type="date" class="form-control" name="birth_date" id="birth_date" placeholder="Data de Nascimento">
                                <label for="birth_date">Data de Nascimento</label>
                            </div>
                        </div>
                        <div class="col-4 col-md-6 col-lg-6 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="email" id="email" placeholder="E-mail">
                                <label for="email">E-mail</label>
                            </div>
                        </div>
                        <div class="col-4 col-md-6 col-lg-6 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control phone" name="phone" id="phone" oninput="maskPhone(this)" placeholder="Telefone">
                                <label for="phone">Telefone</label>
                            </div>
                        </div>
                        <div class="col-4 col-md-3 col-lg-3 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control cep" name="postal_code" id="postal_code" onblur="consultAddress(this)" placeholder="CEP">
                                <label for="postal_code">CEP</label>
                            </div>
                        </div>
                        <div class="col-4 col-md-3 col-lg-3 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="address_number" id="address_number" placeholder="N">
                                <label for="address_number">N</label>
                            </div>
                        </div>
                        <div class="col-4 col-md-6 col-lg-6 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="address" id="address" placeholder="Endereço">
                                <label for="address">Endereço</label>
                            </div>
                        </div>
                        <div class="col-4 col-md-6 col-lg-6 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="address_city" id="address_city" placeholder="Endereço">
                                <label for="address_city">Cidade</label>
                            </div>
                        </div>
                        <div class="col-4 col-md-6 col-lg-6 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="address_provincy" id="address_provincy" placeholder="Endereço">
                                <label for="address_provincy">Estado</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-danger" type="button" data-dismiss="modal">Cancelar</button>
                    <button class="btn btn-success" type="submit">Confirmar</button>
                </div>
            </form>
        </div>
    </div>
@endsection