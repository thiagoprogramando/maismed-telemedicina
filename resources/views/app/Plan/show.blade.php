@extends('app.layout')
@section('content')

    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet" />

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-primary">{{ $plan->name }}</h1>
        {{-- <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-graduation-cap fa-sm text-white-50"></i> Aprender</a> --}}
    </div>

    <div class="row">
        <div class="col-12 col-sm-12 col-md-12 col-lg-12">
            <div class="card shadow h-100 py-2">
                <form action="{{ route('updated-plan', ['uuid' => $plan->uuid]) }}" method="POST" class="card-body row" id="form">
                    @csrf
                    <input type="hidden" name="terms" id="terms">
                    <div class="col-12 col-md-9 mb-2">
                        <div class="form-floating">
                            <input type="text" class="form-control" name="name" id="name" placeholder="Título" value="{{ $plan->name }}">
                            <label for="name">Título</label>
                        </div>
                    </div>
                    <div class="col-12 col-md-3 mb-2">
                        <div class="form-floating">
                            <select class="form-select" name="status" id="status" required>
                                <option value="active" @selected($plan->status == 'active')>Ativo</option>
                                <option value="inactive" @selected($plan->status == 'inactive')>Inativo</option>
                            </select>
                            <label for="status">Status</label>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <div class="form-floating">
                            <input type="text" class="form-control money" name="price" id="price" oninput="maskValue(this)"  value="{{ $plan->price }}" placeholder="Valor (R$)">
                            <label for="price">Valor (R$)</label>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <div class="form-floating">
                            <input type="text" class="form-control money" name="commission" id="commission" oninput="maskValue(this)" value="{{ $plan->commission }}" placeholder="Comissão (R$)">
                            <label for="commission">Comissão (R$)</label>
                        </div>
                    </div>
                    <div class="col-12 col-md-3 mb-2">
                        <div class="form-floating">
                            <select class="form-select" name="time" id="time" required>
                                <option value="month" @selected($plan->time == 'month')>Mensal</option>
                                <option value="semi-annual" @selected($plan->time == 'semi-annual')>Semestral</option>
                                <option value="year" @selected($plan->time == 'year')>Anual</option>
                                <option value="lifetime" @selected($plan->time == 'lifetime')>Vitalício</option>
                            </select>
                            <label for="time">Vencimento</label>
                        </div>
                    </div>
                    <div class="col-12 col-md-3 mb-2">
                        <div class="form-floating">
                            <input type="number" class="form-control" name="max_users" id="max_users" value="{{ $plan->max_users }}" placeholder="Máx Usuários">
                            <label for="max_users">Máx Usuários</label>
                        </div>
                    </div>
                    <div class="col-12 mb-2">
                        <div class="form-floating">
                            <textarea class="form-control" name="description" id="description" placeholder="Descrição" style="height: 100px">{{ $plan->description }}</textarea>
                            <label for="description"> Descrição </label>
                        </div>
                    </div>
                    <div class="col-12 mb-2">
                        <div class="form-floating">
                            <textarea class="form-control" name="features" id="features" placeholder="Features" style="height: 100px">{{ $plan->features }}</textarea>
                            <label for="features"> Features </label>
                        </div>
                    </div>
                    <div class="col-12 mb-2">
                            <div class="full-editor" style="height: 100px;">
                                {!! $plan->terms !!}
                            </div>
                        </div>
                    <div class="col-12 text-center">
                        <a href="{{ route('plans') }}" class="btn btn-outline-danger">Sair</a>
                        <button class="btn btn-success" type="submit">Atualizar</button>
                    </div>
                </form>
            </div>
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