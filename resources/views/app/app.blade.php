@extends('app.layout')
@section('content')

    @if (Auth::user()->roles == 'collaborator' || Auth::user()->roles == 'admin')
        <div class="d-sm-flex align-items-center justify-content-between mb-4 flex-wrap">
            <h1 class="h3 mb-0 text-primary">DASHBOARD</h1>
            <div class="btn-group btn-group-sm mt-2 mt-sm-0" role="group" aria-label="Período">
                @foreach ([7, 15, 30] as $p)
                    <a href="{{ route('app', ['period' => $p]) }}"
                       class="btn {{ ($period ?? 30) == $p ? 'btn-primary' : 'btn-outline-primary' }}">
                        {{ $p }} dias
                    </a>
                @endforeach
            </div>
        </div>

        @if (!empty($metrics))
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Usuários ativos</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">{{ $metrics['active_users'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Vendas ativas</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">{{ $metrics['active_sales'] }}</div>
                            <small class="text-muted">Pendentes: {{ $metrics['pending_sales'] }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Pagaram ({{ $metrics['period'] }}d)</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">{{ $metrics['paid_invoices'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Não pagaram / a vencer</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">{{ $metrics['unpaid_invoices'] }}</div>
                            <small class="text-muted">A vencer em {{ $metrics['period'] }}d: {{ $metrics['due_soon'] }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-lg-6 mb-3">
                    <div class="card shadow h-100">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-uppercase mb-3">Vendas × faturas (visão)</div>
                            <canvas id="chartSalesStatus" height="180"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-3">
                    <div class="card shadow h-100">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-uppercase mb-3">Pagamentos no período</div>
                            <canvas id="chartPayments" height="180"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="row">
            <div class="col-12 col-sm-12 col-md-5 col-lg-5">
                <div class="card border-left-primary shadow py-2 mb-3">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="text-xs font-weight-bold text-dark text-uppercase mb-1">LINKS DE REVENDA (CLICK PARA COPIAR)</div>
                            <button type="button" class="btn btn-outline-primary mb-2" onClick="onClip('{{ route('showcase', ['parent' => Auth::user()->uuid]) }}')"><i data-lucide="layout-grid" style="width: 14px; height: 14px;"></i> Vitrine de planos</button>
                            </br>
                            @foreach ($plans as $plan)
                                <button type="button" class="btn btn-outline-info mb-2" onClick="onClip('{{ route('create-sale', ['plan' => $plan->slug, 'parent' => Auth::user()->uuid]) }}')"><i data-lucide="copy" style="width:14px;height:14px;"></i> Plano {{ $plan->name }}</button>
                                </br>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="btn-group w-100 mb-4">
                    <a href="{{ route('sales') }}" class="btn btn-outline-dark d-flex flex-column align-items-center justify-content-center p-3 text-muted" title="Beneficiários">
                        <i data-lucide="users"></i>
                        Beneficiários
                    </a>
                    <a href="{{ route('plans') }}" class="btn btn-outline-dark d-flex flex-column align-items-center justify-content-center p-3 text-muted" title="Planos">
                        <i data-lucide="clipboard-list"></i>
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
                                                <strong>{{ $sale->user->name ?? 'Beneficiário removido' }}</strong><br>
                                                <small class="text-muted">{{ $sale->user ? $sale->user->maskCpfCnpj() : '—' }}</small>
                                            </td>
                                            <td>
                                                <a href="{{ route('sale', ['uuid' => $sale->uuid]) }}" class="btn btn-outline-dark" title="Editar"><i data-lucide="pencil" style="width:14px;height:14px;"></i></a>
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

        @if (!empty($metrics))
            <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
            <script>
                (function () {
                    var m = @json($metrics);
                    if (window.Chart) {
                        new Chart(document.getElementById('chartSalesStatus'), {
                            type: 'doughnut',
                            data: {
                                labels: ['Ativas', 'Pendentes'],
                                datasets: [{ data: [m.active_sales, m.pending_sales], backgroundColor: ['#1cc88a', '#f6c23e'] }]
                            },
                            options: { plugins: { legend: { position: 'bottom' } } }
                        });
                        new Chart(document.getElementById('chartPayments'), {
                            type: 'bar',
                            data: {
                                labels: ['Pagaram', 'Em aberto', 'A vencer', 'Novas vendas'],
                                datasets: [{
                                    label: 'Qtd',
                                    data: [m.paid_invoices, m.unpaid_invoices, m.due_soon, m.new_sales],
                                    backgroundColor: ['#36b9cc', '#e74a3b', '#f6c23e', '#4e73df']
                                }]
                            },
                            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
                        });
                    }
                })();
            </script>
        @endif
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
                                                <strong>{{ $contract->user->name ?? 'Titular removido' }}</strong><br>
                                                <small class="text-muted">{{ $contract->user ? $contract->user->maskCpfCnpj() : '—' }}</small>
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