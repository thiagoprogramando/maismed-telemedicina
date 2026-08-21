@extends('app.layout')
@section('content')

    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet" />

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-primary">BENEFICIÁRIOS</h1>
        {{-- <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-graduation-cap fa-sm text-white-50"></i> Aprender</a> --}}
    </div>

    <div class="row">
        <div class="col-12 col-sm-12 col-md-12 col-lg-12">
            <div class="card shadow h-100 py-2">
                <div class="card-body">

                    <div class="btn-group mb-4">
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
                                @foreach ($sales as $sale)
                                    <tr>
                                        <td>
                                            <strong>{{ $sale->user->name }}</strong><br>
                                            <small class="text-muted">{{ $sale->user->maskCpfCnpj() }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">Dependentes: {{ $sale->user->children()->count() }}</span>  
                                            <span class="badge bg-primary">Responsável: {{ $sale->user->parent ? $sale->user->parent->name : 'N/a' }}</span> <br>
                                            <span class="badge bg-primary">Planos: {{ $sale->plan->name ?? 'N/a' }}</span>
                                            <span class="badge bg-primary">Faturas: {{ $sale->invoices()->count() }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('sale', ['uuid' => $sale->uuid]) }}" class="btn btn-outline-dark" title="Editar"><i class="fas fa-edit fa-sm text-muted"></i></a>
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

    <div class="modal fade" id="filterModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md" role="document">
            <form action="{{ route('sales') }}" method="GET" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">PESQUISA DE BENEFICIÁRIOS</h5>
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
                        <div class="col-6 col-md-6 mb-2">
                            <div class="form-floating">
                                <input type="date" class="form-control" name="date_start" id="date_start" placeholder="Data Inicial">
                                <label for="date_start">Data Inicial</label>
                            </div>
                        </div>
                        <div class="col-6 col-md-6 mb-2">
                            <div class="form-floating">
                                <input type="date" class="form-control" name="date_end" id="date_end" placeholder="Data Final">
                                <label for="date_end">Data Final</label>
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