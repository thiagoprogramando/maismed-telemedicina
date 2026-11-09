@extends('app.layout')
@section('content')

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div class="page-heading">
            <i data-lucide="handshake"></i>
            <h1 class="h3 text-primary">{{ $consultant->name }}</h1>
        </div>
        <a href="{{ route('consultants') }}" class="btn btn-outline-secondary btn-sm">Voltar</a>
    </div>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-uppercase mb-2">Dados</div>
                    <p class="mb-1"><strong>{{ $consultant->name }}</strong></p>
                    <p class="mb-1 small text-muted">{{ $consultant->email }}</p>
                    <p class="mb-1 small text-muted">{{ $consultant->maskCpfCnpj() }}</p>
                    <p class="mb-3">{!! $consultant->labelStatus() !!} <span class="badge bg-dark">{{ $consultant->roles }}</span></p>

                    <div class="text-xs font-weight-bold text-uppercase mb-2">Links de adesão</div>
                    <button type="button" class="btn btn-outline-primary btn-block btn-sm mb-2"
                        onclick="onClip('{{ route('showcase', ['parent' => $consultant->uuid]) }}')">
                        Vitrine de planos
                    </button>
                    @foreach ($plans as $plan)
                        <button type="button" class="btn btn-outline-info btn-block btn-sm mb-2"
                            onclick="onClip('{{ route('create-sale', ['plan' => $plan->slug, 'parent' => $consultant->uuid]) }}')">
                            {{ $plan->name }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-uppercase mb-3">Clientes deste consultor</div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Beneficiário</th>
                                    <th>Plano</th>
                                    <th>Status</th>
                                    <th>Transferir</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sales as $sale)
                                    <tr>
                                        <td>
                                            <a href="{{ route('sale', $sale->uuid) }}">
                                                <strong>{{ $sale->user->name ?? 'Removido' }}</strong>
                                            </a><br>
                                            <small class="text-muted">{{ $sale->user?->maskCpfCnpj() ?? '—' }}</small>
                                        </td>
                                        <td>{{ $sale->plan->name ?? $sale->name }}</td>
                                        <td>{!! $sale->labelStatus() !!}</td>
                                        <td>
                                            @if ($otherConsultants->isNotEmpty())
                                                <form action="{{ route('transfer-sale') }}" method="POST" class="form-inline">
                                                    @csrf
                                                    <input type="hidden" name="sale_uuid" value="{{ $sale->uuid }}">
                                                    <select name="to_consultant_uuid" class="form-control form-control-sm mr-1" required>
                                                        <option value="">Consultor…</option>
                                                        @foreach ($otherConsultants as $other)
                                                            <option value="{{ $other->uuid }}">{{ $other->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="btn btn-sm btn-warning"
                                                        onclick="return confirm('Transferir este beneficiário? Os consultores e o admin receberão e-mail.');">
                                                        Transferir
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-muted small">Sem outro consultor</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-muted text-center">Nenhum cliente neste consultor.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $sales->links() }}
                </div>
            </div>
        </div>
    </div>

@endsection
