@extends('app.layout')
@section('content')
<link href="{{ asset('Assets/css/dashboard.css') }}?v={{ @filemtime(public_path('Assets/css/dashboard.css')) }}" rel="stylesheet">

@php
    $hora = (int) now()->format('G');
    $saudacao = $hora < 12 ? 'Bom dia' : ($hora < 18 ? 'Boa tarde' : 'Boa noite');
    $primeiroNome = explode(' ', trim(Auth::user()->name))[0] ?: Auth::user()->name;
    $diasSemana = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
    $mesesPt = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
    $dataExtenso = $diasSemana[(int) now()->format('w')] . ', ' . (int) now()->format('j') . ' de ' . $mesesPt[(int) now()->format('n') - 1] . ' de ' . now()->format('Y');
    $period = $period ?? 30;
@endphp

@if (Auth::user()->roles == 'collaborator' || Auth::user()->roles == 'admin')
    <div class="mm-dashboard">
        <header class="dash-header">
            <div class="dash-title">
                <h1>{{ $saudacao }}, {{ $primeiroNome }}</h1>
                <p>
                    {{ $dataExtenso }}
                    <span class="dash-sep">·</span>
                    visão dos últimos {{ $period }} dias
                </p>
                @if (!empty($metrics))
                    <div class="dash-chips" aria-label="Resumo rápido">
                        @if (($metrics['unpaid_invoices'] ?? 0) > 0)
                            <span class="dash-chip is-warn">
                                <i data-lucide="clock"></i> {{ $metrics['unpaid_invoices'] }} fatura(s) em aberto
                            </span>
                        @endif
                        @if (($metrics['due_soon'] ?? 0) > 0)
                            <span class="dash-chip is-danger">
                                <i data-lucide="alert-circle"></i> {{ $metrics['due_soon'] }} a vencer no período
                            </span>
                        @endif
                        @if (($metrics['unpaid_invoices'] ?? 0) === 0 && ($metrics['due_soon'] ?? 0) === 0)
                            <span class="dash-chip is-ok">
                                <i data-lucide="circle-check"></i> Nenhuma pendência urgente
                            </span>
                        @endif
                    </div>
                @endif
            </div>
            <div class="dash-toolbar">
                <a href="{{ route('app', ['period' => $period]) }}" class="dash-refresh" title="Atualizar">
                    <i data-lucide="refresh-cw"></i>
                </a>
                <div class="dash-period" role="group" aria-label="Período">
                    @foreach ([7 => '7 dias', 15 => '15 dias', 30 => '30 dias'] as $p => $label)
                        <a href="{{ route('app', ['period' => $p]) }}" class="{{ $period == $p ? 'is-active' : '' }}">{{ $label }}</a>
                    @endforeach
                </div>
            </div>
        </header>

        @if (!empty($metrics))
            <section class="kpi-strip" aria-label="Indicadores">
                <article class="kpi-card is-ok">
                    <div class="kpi-icon"><i data-lucide="user-check"></i></div>
                    <div class="kpi-body">
                        <small>Usuários ativos</small>
                        <strong>{{ $metrics['active_users'] }}</strong>
                    </div>
                </article>
                <article class="kpi-card is-primary">
                    <div class="kpi-icon"><i data-lucide="badge-check"></i></div>
                    <div class="kpi-body">
                        <small>Vendas ativas</small>
                        <strong>{{ $metrics['active_sales'] }}</strong>
                        <span>Pendentes: {{ $metrics['pending_sales'] }}</span>
                    </div>
                </article>
                <article class="kpi-card is-info">
                    <div class="kpi-icon"><i data-lucide="circle-dollar-sign"></i></div>
                    <div class="kpi-body">
                        <small>Pagaram ({{ $period }}d)</small>
                        <strong>{{ $metrics['paid_invoices'] }}</strong>
                        <span>Novas vendas: {{ $metrics['new_sales'] }}</span>
                    </div>
                </article>
                <article class="kpi-card is-warn">
                    <div class="kpi-icon"><i data-lucide="wallet"></i></div>
                    <div class="kpi-body">
                        <small>Não pagaram</small>
                        <strong>{{ $metrics['unpaid_invoices'] }}</strong>
                        <span>A vencer: {{ $metrics['due_soon'] }}</span>
                    </div>
                </article>
            </section>

            <section class="charts-grid" aria-label="Gráficos">
                <div class="panel">
                    <h2 class="panel__title">Situação das vendas</h2>
                    <p class="panel__hint">Ativas versus pendentes de pagamento.</p>
                    <div class="chart-box"><canvas id="chartSalesStatus"></canvas></div>
                </div>
                <div class="panel">
                    <h2 class="panel__title">Pagamentos no período</h2>
                    <p class="panel__hint">Últimos {{ $period }} dias · faturas e novas adesões.</p>
                    <div class="chart-box"><canvas id="chartPayments"></canvas></div>
                </div>
            </section>
        @endif

        <section class="main-grid">
            <div class="panel">
                <h2 class="panel__title">Links de adesão</h2>
                <p class="panel__hint">Clique para copiar o link com o seu UUID de consultor.</p>
                <div class="link-list">
                    <button type="button" class="link-btn" onclick="onClip('{{ route('showcase', ['parent' => Auth::user()->uuid]) }}')">
                        <i data-lucide="layout-grid"></i> Vitrine de planos
                    </button>
                    @foreach ($plans as $plan)
                        <button type="button" class="link-btn" onclick="onClip('{{ route('create-sale', ['plan' => $plan->slug, 'parent' => Auth::user()->uuid]) }}')">
                            <i data-lucide="copy"></i> {{ $plan->name }}
                        </button>
                    @endforeach
                </div>
                <div class="quick-nav">
                    <a href="{{ route('sales') }}"><i data-lucide="users"></i> Beneficiários</a>
                    <a href="{{ route('plans') }}"><i data-lucide="clipboard-list"></i> Planos</a>
                </div>
            </div>

            <div class="panel">
                <h2 class="panel__title">Últimos beneficiários</h2>
                <div class="table-responsive">
                    <table class="table table-sm benef-table">
                        <thead>
                            <tr>
                                <th>Beneficiário</th>
                                <th class="text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sales as $sale)
                                <tr>
                                    <td>
                                        <strong>{{ $sale->user->name ?? 'Beneficiário removido' }}</strong><br>
                                        <small class="text-muted">{{ $sale->user ? $sale->user->maskCpfCnpj() : '—' }}</small>
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('sale', ['uuid' => $sale->uuid]) }}" class="btn btn-sm btn-outline-secondary" title="Abrir">
                                            <i data-lucide="pencil" style="width:14px;height:14px;"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-muted text-center py-4">Nenhum beneficiário ainda.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <a href="{{ route('sales') }}" class="benef-more">Ver todos</a>
            </div>
        </section>

        <p class="foot-hint">Atualizado em {{ now()->format('d/m/Y H:i') }}. Filtro de período afeta os indicadores de pagamento.</p>
    </div>

    @if (!empty($metrics))
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
            (function () {
                var m = @json($metrics);
                if (!window.Chart) return;
                var brand = '#0b1b5c';
                new Chart(document.getElementById('chartSalesStatus'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Ativas', 'Pendentes'],
                        datasets: [{
                            data: [m.active_sales, m.pending_sales],
                            backgroundColor: ['#10b981', '#f59e0b'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { family: 'Nunito' } } } }
                    }
                });
                new Chart(document.getElementById('chartPayments'), {
                    type: 'bar',
                    data: {
                        labels: ['Pagaram', 'Em aberto', 'A vencer', 'Novas vendas'],
                        datasets: [{
                            data: [m.paid_invoices, m.unpaid_invoices, m.due_soon, m.new_sales],
                            backgroundColor: ['#0ea5e9', '#ef4444', '#f59e0b', brand],
                            borderRadius: 6,
                            maxBarThickness: 42
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f1f5f9' } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            })();
        </script>
    @endif
@else
    <div class="mm-dashboard">
        <header class="dash-header">
            <div class="dash-title">
                <h1>{{ $saudacao }}, {{ $primeiroNome }}</h1>
                <p>Acompanhe seus dados, débitos e dependentes por aqui.</p>
            </div>
        </header>
        <div class="main-grid">
            <div class="panel">
                <h2 class="panel__title">Atalhos</h2>
                <div class="quick-nav">
                    <a href="https://maismed.rapidoc.tech/login" target="_blank" rel="noopener"><i data-lucide="stethoscope"></i> Atendimento</a>
                    <a href="{{ route('user', ['uuid' => Auth::user()->uuid]) }}"><i data-lucide="user"></i> Meus dados</a>
                </div>
            </div>
            <div class="panel">
                <h2 class="panel__title">Meus contratos</h2>
                <div class="table-responsive">
                    <table class="table table-sm benef-table">
                        <thead>
                            <tr>
                                <th>Titular</th>
                                <th class="text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($contracts as $contract)
                                <tr>
                                    <td>
                                        <strong>{{ $contract->user->name ?? 'Titular removido' }}</strong><br>
                                        <small class="text-muted">{{ $contract->user ? $contract->user->maskCpfCnpj() : '—' }}</small>
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('sale', ['uuid' => $contract->uuid]) }}" class="btn btn-sm btn-outline-secondary">
                                            <i data-lucide="pencil" style="width:14px;height:14px;"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-muted text-center py-4">Nenhum contrato encontrado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endif

<script>
    if (window.lucide) { lucide.createIcons(); }
</script>
@endsection
