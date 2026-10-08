@extends('app.layout')
@section('content')

    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet" />
    <link href="{{ asset('Assets/css/plan-card.css') }}" rel="stylesheet">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-primary">{{ $plan->name }}</h1>
        {{-- <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-graduation-cap fa-sm text-white-50"></i> Aprender</a> --}}
    </div>

    <div class="row">
        <div class="col-12 col-lg-7 mb-4">
            <div class="card shadow py-2">
                <form action="{{ route('updated-plan', ['uuid' => $plan->uuid]) }}" method="POST" class="card-body" id="form">
                    @csrf
                    <input type="hidden" name="terms" id="terms">
                    @include('app.Plan.form', ['plan' => $plan])
                    <div class="text-center">
                        <a href="{{ route('plans') }}" class="btn btn-outline-danger">Sair</a>
                        <button class="btn btn-success" type="submit">Atualizar</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-12 col-lg-5 mb-4">
            <div class="text-xs font-weight-bold text-dark text-uppercase mb-2"><i data-lucide="eye" style="width: 14px; height: 14px;"></i> Como o cliente vê</div>
            @include('sale.plan_card', ['plan' => $plan, 'parent' => null, 'cta' => false, 'qty' => null, 'dep' => null])
            <small class="text-muted d-block mt-2">A prévia reflete os dados salvos. Clique em Atualizar para ver as alterações.</small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
    <script src="{{ asset('Assets/js/plan-card.js') }}"></script>
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