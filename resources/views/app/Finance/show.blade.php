@extends('app.layout')
@section('content')

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-primary">CARTEIRA</h1>
        {{-- <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-graduation-cap fa-sm text-white-50"></i> Aprender</a> --}}
    </div>
    <div class="row">
        <div class="col-12 col-sm-12 col-md-5 col-lg-5 col-xl-4 col-xxl-4">

            <div class="btn-group mb-4">
                <a href="{{ route('users') }}" class="btn btn-outline-dark text-muted"title="Voltar"><i class="fas fa-chevron-left fa-sm text-muted"></i></a>
                <button type="button" class="btn btn-outline-dark" title="Recarregar" onClick="location.reload()"><i class="fas fa-sync-alt fa-sm text-muted"></i></button>
            </div>

            <div class="card border-left-success shadow py-2 mb-3">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">DISPONÍVEL</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">R$ {{ number_format($user->wallet, 2, ',', '.') }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="btn-group w-100 mb-4">
                <button type="button" class="btn btn-outline-dark d-flex flex-column align-items-center justify-content-center p-3 text-muted" title="Recarregar" title="Beneficiários">
                    <i class="fas fa-credit-card fa-lg text-muted"></i>
                    TRANSFERIR
                </button>
                <button type="button" class="btn btn-outline-dark d-flex flex-column align-items-center justify-content-center p-3 text-muted" title="Recarregar" title="Beneficiários">
                    <i class="fas fa-filter fa-lg text-muted"></i>
                    FILTRAR
                </button>
            </div>
        </div>

        <div class="col-12 col-sm-12 col-md-7 col-lg-7 col-xl-8 col-xxl-8">
            <div class="card shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-dark text-uppercase mb-1">EXTRATO</div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Informações</th>
                                    <th>Detalhes</th>
                                    <th>Valores</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($extracts as $extract)
                                    <tr>
                                        <td>
                                            <strong>{{ $extract->title }}</strong><br>
                                            <small class="text-muted">{{ $extract->description }}</small>
                                        </td>
                                        <td>
                                            {!! $extract->labelStatus() !!} {!! $extract->labelType() !!}
                                            <small class="text-muted">{{ $extract->payment_date->format('d/m/Y') }}</small>
                                        </td>
                                        <td>
                                            <strong>R$ {{ number_format($extract->value, 2, ',', '.') }}</strong><br>
                                            <small class="text-muted">{{ $extract->payment_date->format('d/m/Y') }}</small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="text-center">
                            <a href="">Ver mais</a>
                        </div>
                    </div> 
                </div>
            </div>
        </div>
    </div>
    
@endsection