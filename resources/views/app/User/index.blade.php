@extends('app.layout')
@section('content')

    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet" />

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-primary">PESSOAS</h1>
        {{-- <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-graduation-cap fa-sm text-white-50"></i> Aprender</a> --}}
    </div>

    <div class="row">
        <div class="col-12 col-sm-12 col-md-12 col-lg-12">
            <div class="card shadow h-100 py-2">
                <div class="card-body">

                    <div class="btn-group mb-4">
                        <button type="button" class="btn btn-outline-dark text-muted" data-toggle="modal" data-target="#createdModal" title="Novo Plano"><i class="fas fa-plus fa-sm text-muted"></i></button>
                        <button type="button" class="btn btn-outline-dark text-muted" data-toggle="modal" data-target="#filterModal" title="Filtrar"><i class="fas fa-filter fa-sm text-muted"></i></button>
                        <button type="button" class="btn btn-outline-dark" title="Recarregar" onClick="location.reload()"><i class="fas fa-sync-alt fa-sm text-muted"></i></button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>DETALHES</th>
                                    <th>INFORMAÇÕES</th>
                                    <th>AÇÕES</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $user)
                                    <tr>
                                        <td>
                                            <strong>{{ $user->name }}</strong><br>
                                            <small class="text-muted">{{ $user->maskCpfCnpj() }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-dark">{!! $user->labelRoles() !!}</span>
                                            <span class="badge bg-primary">Dependentes: {{ $user->children()->count() }}</span>  
                                            <span class="badge bg-primary">Responsável: {{ $user->parent ? $user->parent->name : 'N/a' }}</span> <br>
                                            {!! $user->labelStatus() !!}
                                            <span class="badge bg-primary">Faturas: {{ $user->invoices()->count() }}</span>
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
    </div>

    <div class="modal fade" id="createdModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form action="{{ route('created-user') }}" method="POST" class="modal-content" id="form">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">CADASTRO DE PESSOA/USUÁRIO</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12 col-md-12 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="name" id="name" placeholder="Nome" required>
                                <label for="name">Nome</label>
                            </div>
                        </div>
                        <div class="col-4 col-md-6 col-lg-6 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control cpfcnpj" name="document" id="document" oninput="maskCpfCnpj(this)" placeholder="CPF/CNPJ" required>
                                <label for="document">CPF/CNPJ</label>
                            </div>
                        </div>
                        <div class="col-4 col-md-6 col-lg-6 mb-2">
                            <div class="form-floating">
                                <input type="date" class="form-control" name="birth_date" id="birth_date" placeholder="Data de Nascimento" required>
                                <label for="birth_date">Data de Nascimento</label>
                            </div>
                        </div>
                        <div class="col-4 col-md-12 col-lg-12 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="email" id="email" placeholder="E-mail" required>
                                <label for="email">E-mail</label>
                            </div>
                        </div>
                        <div class="col-4 col-md-6 col-lg-6 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control phone" name="phone" id="phone" oninput="maskPhone(this)" placeholder="Telefone" required>
                                <label for="phone">Telefone</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-6 mb-2">
                            <div class="form-floating">
                                <select class="form-select" name="roles" id="roles">
                                    <option value="admin">Administrador</option>
                                    <option value="user">Usuário</option>
                                    <option value="collaborator">Colaborador</option>
                                </select>
                                <label for="roles">Função</label>
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

    <div class="modal fade" id="filterModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md" role="document">
            <form action="{{ route('users') }}" method="GET" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">PESQUISA DE PESSOA/USUÁRIO</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12 col-md-12 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="search" id="name" placeholder="Nome">
                                <label for="name">Nome</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-12 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="document" id="document" oninput="maskCpfCnpj(this)" placeholder="CPF/CNPJ">
                                <label for="document">CPF/CNPJ</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-danger" type="button" data-dismiss="modal">Cancelar</button>
                    <button class="btn btn-success" type="submit">Filtrar</button>
                </div>
            </form>
        </div>
    </div>
@endsection