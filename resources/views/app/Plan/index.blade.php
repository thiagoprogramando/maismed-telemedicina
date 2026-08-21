@extends('app.layout')
@section('content')

    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet" />

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-primary">PLANOS</h1>
        {{-- <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-graduation-cap fa-sm text-white-50"></i> Aprender</a> --}}
    </div>

    <div class="row">
        <div class="col-12 col-sm-12 col-md-12 col-lg-12">
            <div class="card shadow h-100 py-2">
                <div class="card-body">

                    <div class="btn-group mb-4">
                        @if(Auth::user()->roles == 'admin') <button type="button" class="btn btn-outline-dark text-muted" data-toggle="modal" data-target="#createdModal" title="Novo Plano"><i class="fas fa-plus fa-sm text-muted"></i></button> @endif
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
                                @foreach ($plans as $plan)
                                    <tr>
                                        <td>
                                            <strong>{{ $plan->name }}</strong><br>
                                            <small class="text-muted">{{ $plan->description }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">Beneficiários {{ $plan->max_users }}</span> <span class="badge bg-primary">Preço R$ {{ number_format($plan->price, 2, ',', '.') }}</span> <span class="badge bg-primary">Comissão R$ {{ number_format($plan->commission, 2, ',', '.') }}</span>
                                            <span class="badge bg-primary">Responsável {{ $plan->user()->name ?? 'N/a' }}</span> <span class="badge bg-primary">{{ $plan->statusLabel() }}</span> <span class="badge bg-primary">{{ $plan->timeLabel() }}</span>
                                        </td>
                                        <td>
                                            <form action="{{ route('deleted-plan', $plan->uuid) }}" method="POST" class="btn-group confirm">
                                                @csrf
                                                <button type="button" class="btn btn-outline-dark text-muted" data-toggle="modal" data-target="#detailModal{{ $plan->uuid }}" title="Detalhes"><i class="fas fa-info-circle fa-sm text-muted"></i></button>
                                                @if(Auth::user()->roles == 'admin')
                                                    <a href="{{ route('plan', $plan->uuid) }}" class="btn btn-outline-dark" title="Editar"><i class="fas fa-edit fa-sm text-muted"></i></a>
                                                    <button type="submit" class="btn btn-outline-dark" title="Excluir"><i class="fas fa-trash fa-sm text-muted"></i></button>
                                                @endif
                                            </form>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="detailModal{{ $plan->uuid }}" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="exampleModalLabel">DETALHES DO PLANO</h5>
                                                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">×</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-12 col-md-12 mb-2">
                                                            <div class="form-floating">
                                                                <input type="text" class="form-control" name="name" id="name" placeholder="Título" value="{{ $plan->name }}" readonly>
                                                                <label for="name">Título</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-6 col-md-4 mb-2">
                                                            <div class="form-floating">
                                                                <input type="text" class="form-control money" name="price" id="price" oninput="maskValue(this)" placeholder="Valor (R$)" value="{{ $plan->price }}" readonly>
                                                                <label for="price">Valor (R$)</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-12 col-md-4 mb-2">
                                                            <div class="form-floating">
                                                                <select class="form-select" name="time" id="time" disabled>
                                                                    <option value="month" @selected($plan->time == 'month')>Mensal</option>
                                                                    <option value="semi-annual" @selected($plan->time == 'semi-annual')>Semestral</option>
                                                                    <option value="year" @selected($plan->time == 'year')>Anual</option>
                                                                    <option value="lifetime" @selected($plan->time == 'lifetime')>Vitalício</option>
                                                                </select>
                                                                <label for="time">Vencimento</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-12 col-md-4 mb-2">
                                                            <div class="form-floating">
                                                                <input type="number" class="form-control" name="max_users" id="max_users" placeholder="Beneficiários" value="{{ $plan->max_users }}" readonly>
                                                                <label for="max_users">Beneficiários</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-12 mb-2">
                                                            <div class="form-floating">
                                                                <textarea class="form-control" name="description" id="description" placeholder="Descrição" style="height: 100px" disabled>{{ $plan->description }}</textarea>
                                                                <label for="description"> Descrição </label>
                                                            </div>
                                                        </div>
                                                        <div class="col-12 mb-2">
                                                            <div class="form-floating">
                                                                <textarea class="form-control" name="features" id="features" placeholder="Features" style="height: 100px" disabled>{{ $plan->features }}</textarea>
                                                                <label for="features"> Features </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button class="btn btn-outline-danger" type="button" data-dismiss="modal">Fechar</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
            <form action="{{ route('created-plan') }}" method="POST" class="modal-content" id="form">
                @csrf
                <input type="hidden" name="terms" id="terms">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">CADASTRO DE PLANO</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12 col-md-12 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="name" id="name" placeholder="Título" required>
                                <label for="name">Título</label>
                            </div>
                        </div>
                        <div class="col-6 col-md-6 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="price" id="price" oninput="maskValue(this)" placeholder="Valor (R$)">
                                <label for="price">Valor (R$)</label>
                            </div>
                        </div>
                        <div class="col-6 col-md-6 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="commission" id="commission" oninput="maskValue(this)" placeholder="Comissão (R$)">
                                <label for="commission">Comissão (R$)</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-4 mb-2">
                            <div class="form-floating">
                                <select class="form-select" name="status" id="status" required>
                                    <option value="active" selected>Ativo</option>
                                    <option value="inactive">Inativo</option>
                                </select>
                                <label for="status">Status</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-4 mb-2">
                            <div class="form-floating">
                                <select class="form-select" name="time" id="time" required>
                                    <option value="month" selected>Mensal</option>
                                    <option value="semi-annual">Semestral</option>
                                    <option value="year">Anual</option>
                                    <option value="lifetime">Vitalício</option>
                                </select>
                                <label for="time">Vencimento</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-4 mb-2">
                            <div class="form-floating">
                                <input type="number" class="form-control" name="max_users" id="max_users" placeholder="Máx Usuários">
                                <label for="max_users">Máx Usuários</label>
                            </div>
                        </div>
                        <div class="col-12 mb-2">
                            <div class="form-floating">
                                <textarea class="form-control" name="description" id="description" placeholder="Descrição" style="height: 100px"></textarea>
                                <label for="description"> Descrição </label>
                            </div>
                        </div>
                        <div class="col-12 mb-2">
                            <div class="form-floating">
                                <textarea class="form-control" name="features" id="features" placeholder="Features" style="height: 100px"></textarea>
                                <label for="features"> Features </label>
                            </div>
                        </div>
                        <div class="col-12 mb-2">
                            <div class="full-editor" style="height: 100px;"></div>
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
        <div class="modal-dialog" role="document">
            <form action="{{ route('plans') }}" method="GET" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">PESQUISA DE PLANOS</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12 col-md-12 mb-2">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="name" id="name" placeholder="Título">
                                <label for="name">Título</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 mb-2">
                            <div class="form-floating">
                                <select class="form-select" name="status" id="status">
                                    <option value="active" selected>Ativo</option>
                                    <option value="inactive">Inativo</option>
                                </select>
                                <label for="status">Status</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 mb-2">
                            <div class="form-floating">
                                <select class="form-select" name="time" id="time">
                                    <option value="month" selected>Mensal</option>
                                    <option value="semi-annual">Semestral</option>
                                    <option value="year">Anual</option>
                                    <option value="lifetime">Vitalício</option>
                                </select>
                                <label for="time">Vencimento</label>
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

    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const fullToolbar = [
                [
                    { font: [] },
                    { size: [] }
                ],
                ['bold', 'italic', 'underline', 'strike'],
                    [
                    { color: [] },
                    { background: [] }
                ],
                [
                    { script: 'super' },
                    { script: 'sub' }
                ],
                [
                    { header: '1' },
                    { header: '2' },
                    'blockquote',
                    'code-block'
                ],
                [
                    { list: 'ordered' },
                    { list: 'bullet' },
                    { indent: '-1' },
                    { indent: '+1' }
                ],
                [{ direction: 'rtl' }], ['link'], ['clean']
            ];

            window.editor = new Quill('.full-editor', {
                bounds: '.full-editor',
                placeholder: 'Digite o conteúdo do Termo & Contrato..',
                modules: {
                    toolbar: fullToolbar
                },
                theme: 'snow'
            });
        });

        document.getElementById('form').addEventListener('submit', function (e) {

            if (typeof window.editor === 'undefined') {
                e.preventDefault();
                Swal.fire({ icon: 'error', title: 'Editor não carregado', text: 'Aguarde alguns instantes e tente novamente.' });
                return;
            }

            const editorHTML    = window.editor.root.innerHTML.trim();
            document.getElementById('terms').value = editorHTML;
        });
    </script>
@endsection