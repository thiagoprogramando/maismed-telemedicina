@extends('app.layout')
@section('content')

    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet" />

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-primary">PERFIL {{ $user->name }}</h1>
    </div>

    <div class="row">
        <div class="col-12 col-sm-12 col-md-12 col-lg-12 mb-2">
            <div class="card shadow h-100">
                <div class="card-body">
                    <form action="{{ route('updated-user', $user->uuid) }}" method="POST" class="row">
                        @csrf
                        <div class="col-12 col-md-12 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="name" id="name" placeholder="Nome" value="{{ $user->name }}">
                                <label for="name">Nome</label>
                            </div>
                        </div>
                        <div class="col-4 col-md-6 col-lg-6 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control cpfcnpj" name="document" id="document" oninput="maskCpfCnpj(this)" placeholder="CPF/CNPJ" value="{{ $user->document }}" readonly>
                                <label for="document">CPF/CNPJ</label>
                            </div>
                        </div>
                        <div class="col-4 col-md-6 col-lg-6 mb-2">
                            <div class="form-floating">
                                <input type="date" class="form-control" name="birth_date" id="birth_date" placeholder="Data de Nascimento" value="{{ $user->birth_date }}" readonly>
                                <label for="birth_date">Data de Nascimento</label>
                            </div>
                        </div>
                        <div class="col-4 col-md-6 col-lg-6 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="email" id="email" placeholder="E-mail" value="{{ $user->email }}">
                                <label for="email">E-mail</label>
                            </div>
                        </div>
                        <div class="col-4 col-md-6 col-lg-6 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control phone" name="phone" id="phone" oninput="maskPhone(this)" placeholder="Telefone" value="{{ $user->phone }}">
                                <label for="phone">Telefone</label>
                            </div>
                        </div>
                        <div class="col-4 col-md-3 col-lg-3 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control cep" name="postal_code" id="postal_code" onblur="consultAddress(this)" placeholder="CEP" value="{{ $user->postal_code }}">
                                <label for="postal_code">CEP</label>
                            </div>
                        </div>
                        <div class="col-4 col-md-3 col-lg-3 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="address_number" id="address_number" placeholder="N" value="{{ $user->address_number }}">
                                <label for="address_number">N</label>
                            </div>
                        </div>
                        <div class="col-4 col-md-6 col-lg-6 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="address" id="address" placeholder="Endereço" value="{{ $user->address }}">
                                <label for="address">Endereço</label>
                            </div>
                        </div>
                        <div class="col-4 col-md-6 col-lg-6 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="address_city" id="address_city" placeholder="Endereço" value="{{ $user->address_city }}">
                                <label for="address_city">Cidade</label>
                            </div>
                        </div>
                        <div class="col-4 col-md-6 col-lg-6 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="address_provincy" id="address_provincy" placeholder="Endereço" value="{{ $user->address_provincy }}">
                                <label for="address_provincy">Estado</label>
                            </div>
                        </div>
                        @if (Auth::user()->roles == 'admin')
                            <div class="col-12 col-md-6 col-lg-6 mb-2">
                                <div class="form-floating">
                                    <select class="form-select" name="roles" id="roles">
                                        <option value="admin" @selected($user->roles == 'admin')>Administrador</option>
                                        <option value="user" @selected($user->roles == 'user')>Usuário</option>
                                        <option value="collaborator" @selected($user->roles == 'collaborator')>Colaborador</option>
                                    </select>
                                    <label for="roles">Função</label>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-6 mb-2">
                                <div class="form-floating">
                                    <input type="text" class="form-control" name="password" id="password" placeholder="Senha">
                                    <label for="password">Senha</label>
                                </div>
                            </div>
                        @endif
                        <div class="form-group col-12 col-md-12 col-lg-12">
                            <button type="submit" class="btn btn-primary btn-user btn-block"> Atualizar </button>
                            <button type="button" class="btn btn-secondary btn-user btn-block" onclick="window.history.back()"> Voltar </button>
                        </div>
                    </form>
                    @if (Auth::user()->roles == 'admin')
                        <form action="{{ route('deleted-user', ['uuid' => $user->uuid]) }}" method="POST" class="row">
                            @csrf
                            <div class="form-group col-12 col-md-12 col-lg-12">
                                <button type="submit" class="btn btn-danger btn-user btn-block"> Excluir </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection