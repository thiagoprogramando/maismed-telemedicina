@extends('app.layout')
@section('content')

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div class="page-heading">
            <i data-lucide="handshake"></i>
            <h1 class="h3 text-primary">Consultores</h1>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('consultants') }}" class="form-inline mb-3">
                <input type="text" name="search" class="form-control mr-2 mb-2" placeholder="Nome, e-mail ou documento"
                    value="{{ $filters['search'] ?? '' }}">
                <button type="submit" class="btn btn-outline-primary mb-2">Filtrar</button>
            </form>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Consultor</th>
                            <th>Papel</th>
                            <th>Status</th>
                            <th>Clientes</th>
                            <th>Link</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($consultants as $consultant)
                            <tr>
                                <td>
                                    <strong>{{ $consultant->name }}</strong><br>
                                    <small class="text-muted">{{ $consultant->email }}</small>
                                </td>
                                <td><span class="badge bg-dark">{{ $consultant->roles }}</span></td>
                                <td>{!! $consultant->labelStatus() !!}</td>
                                <td>{{ $consultant->sales_as_seller_count }}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                        onclick="onClip('{{ route('showcase', ['parent' => $consultant->uuid]) }}')">
                                        <i data-lucide="copy" style="width:14px;height:14px;"></i> Vitrine
                                    </button>
                                </td>
                                <td>
                                    <a href="{{ route('consultant', $consultant->uuid) }}" class="btn btn-sm btn-outline-dark" title="Ver">
                                        <i data-lucide="eye" style="width:14px;height:14px;"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-muted text-center">Nenhum consultor encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $consultants->links() }}
        </div>
    </div>

@endsection
