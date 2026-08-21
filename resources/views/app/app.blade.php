@extends('app.layout')
@section('content')

    @if (Auth::user()->roles == 'collaborator' || Auth::user()->roles == 'admin')
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-primary">DASHBOARD</h1>
            {{-- <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-graduation-cap fa-sm text-white-50"></i> Aprender</a> --}}
        </div>
        <div class="row">
            <div class="col-12 col-sm-12 col-md-5 col-lg-5">
                <div class="card border-left-primary shadow py-2 mb-3">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="text-xs font-weight-bold text-dark text-uppercase mb-1">LINKS DE REVENDA (CLICK PARA COPIAR)</div>
                            @foreach ($plans as $plan)
                                <button type="button" class="btn btn-outline-info mb-2" onClick="onClip('{{ env('APP_URL').'create-sale/'.$plan->slug.'/'.Auth::user()->uuid }}')"><i class="fas fa-copy fa-sm"></i> Plano {{ $plan->name }}</button>
                                </br>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="btn-group w-100 mb-4">
                    <a href="{{ route('sales') }}" class="btn btn-outline-dark d-flex flex-column align-items-center justify-content-center p-3 text-muted" title="Recarregar" title="Beneficiários">
                        <i class="fas fa-users fa-lg text-muted"></i>
                        Beneficiários
                    </a>
                    <a href="{{ route('plans') }}" class="btn btn-outline-dark d-flex flex-column align-items-center justify-content-center p-3 text-muted" title="Recarregar" title="Beneficiários">
                        <i class="fas fa-shopping-cart fa-lg text-muted"></i>
                        Planos
                    </a>
                </div>
            </div>

            <div class="col-12 col-sm-12 col-md-7 col-lg-7">
                <div class="card shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-dark text-uppercase mb-1">ÚLTIMOS BENEFICIÁRIOS</div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Beneficiário</th>
                                        <th>Ações</th>
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
                                                <a href="{{ route('sale', ['uuid' => $sale->uuid]) }}" class="btn btn-outline-dark" title="Editar"><i class="fas fa-edit fa-sm text-muted"></i></a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="text-center">
                                <a href="{{ route('sales') }}">Ver mais</a>
                            </div>
                        </div> 
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="row">
            <div class="col-12 col-sm-12 col-md-12 col-lg-12 mb-2">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-center">
                            <h1 class="h4 text-gray-900">Bem-vindo(a) {{ Auth::user()->name }}!</h1>
                            <p class="text-muted">Você pode obter acesso aos seus débitos, dados e dependentes por aqui!</p>
                        </div>

                        <div class="btn-group w-100 mb-4">
                            <a href="https://maismed.rapidoc.tech/login" target="_blank" class="btn btn-outline-dark d-flex flex-column align-items-center justify-content-center p-3 text-muted" title="Recarregar" title="Beneficiários">
                                <i class="fas fa-user-md fa-lg text-muted"></i>
                                Atendimento
                            </a>
                            <a href="{{ route('user', ['uuid' => Auth::user()->uuid]) }}" class="btn btn-outline-dark d-flex flex-column align-items-center justify-content-center p-3 text-muted" title="Recarregar" title="Beneficiários">
                                <i class="fas fa-user fa-lg text-muted"></i>
                                Meus Dados
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-12 col-md-12 col-lg-12 mb-5">
                <div class="card shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-dark text-uppercase mb-1">MEUS CONTRATOS</div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Títular</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($contracts as $contract)
                                        <tr>
                                            <td>
                                                <strong>{{ $contract->user->name }}</strong><br>
                                                <small class="text-muted">{{ $contract->user->maskCpfCnpj() }}</small>
                                            </td>
                                            <td>
                                                <a href="{{ route('sale', ['uuid' => $contract->uuid]) }}" class="btn btn-outline-dark" title="Editar"><i class="fas fa-edit fa-sm text-muted"></i></a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="text-center">
                                <a href="{{ route('sales') }}">Ver mais</a>
                            </div>
                        </div> 
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection