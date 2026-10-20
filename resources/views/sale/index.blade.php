<!DOCTYPE html>
<html lang="pt-br">
    <head>

        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="description" content="">
        <meta name="author" content="">

        <title>{{ env('APP_NAME') }} - {{ env('APP_DESCRIPTION') }}</title>

        <link rel="icon" href="{{ asset('Assets/img/logo.png') }}" type="image/png">
        <link href="{{ asset('Assets/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet" type="text/css">
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
        <link href="{{ asset('Assets/css/sb-admin-2.css') }}" rel="stylesheet">
        <link href="{{ asset('Assets/css/plan-card.css') }}" rel="stylesheet">
        
    </head>

    <body class="bg-gradient-light">

        <div class="container d-flex align-items-center justify-content-center min-vh-100">
            <div class="row justify-content-center w-100">
                <div class="col-xl-10 col-lg-10 col-md-10">

                    <div class="card o-hidden border-0 shadow-lg my-5">
                        <div class="card-body p-0">
                            
                            <div class="row">
                                <div class="col-12 col-md-12 col-lg-12">
                                    <div class="p-3">
                                        <div class="text-center bg-primary">
                                            <img src="{{ asset('Assets/img/logo.png') }}" class="img-fluid w-25" alt="Logo Mais Med">
                                        </div>
                                        <div class="mx-auto my-3" style="max-width: 460px;">
                                            @include('sale.plan_card', ['plan' => $plan, 'parent' => $parent, 'cta' => false, 'qty' => $qty, 'dep' => $dep, 'form' => 'sale-form'])
                                        </div>
                                        <form action="{{ route('created-sale') }}" method="POST" class="mt-2 mb-5" id="sale-form">
                                            @csrf
                                            <input type="hidden" name="parent_id" value="{{ $parent->uuid ?? null }}">
                                            <input type="hidden" name="plan_id" value="{{ $plan->uuid }}">
                                            <ul class="nav nav-tabs" id="myTab" role="tablist">
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#details" type="button">
                                                        Dados
                                                    </button>
                                                </li>
                                                @if ($plan->hasSimulator())
                                                    <li class="nav-item" role="presentation">
                                                        <button class="nav-link" id="members-tab" data-bs-toggle="tab" data-bs-target="#members" type="button">
                                                            Dependentes
                                                        </button>
                                                    </li>
                                                @endif
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link" id="terms-tab" data-bs-toggle="tab" data-bs-target="#terms" type="button">
                                                        Contrato
                                                    </button>
                                                </li>
                                            </ul>
                                            <div class="tab-content border border-top-0 p-3">
                                                <div class="tab-pane fade show active" id="details">
                                                    <div class="row">
                                                        <div class="form-group col-12 col-md-6 col-lg-6">
                                                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                                                id="name" value="{{ old('name') }}" placeholder="Nome Completo">
                                                            @error('name')
                                                                <span class="invalid-feedback d-block">{{ $message }}</span>
                                                            @enderror
                                                        </div>

                                                        <div class="form-group col-12 col-md-3 col-lg-3">
                                                            <input type="text" name="document" class="form-control @error('document') is-invalid @enderror"
                                                                id="document" value="{{ old('document') }}" oninput="maskCpfCnpj(this)" placeholder="CPF/CNPJ">
                                                            @error('document')
                                                                <span class="invalid-feedback d-block">{{ $message }}</span>
                                                            @enderror
                                                        </div>

                                                        <div class="form-group col-12 col-md-3 col-lg-3">
                                                            <input type="date" name="birth_date" class="form-control @error('birth_date') is-invalid @enderror"
                                                                id="birth_date" value="{{ old('birth_date') }}" placeholder="Data de Nascimento">
                                                            @error('birth_date')
                                                                <span class="invalid-feedback d-block">{{ $message }}</span>
                                                            @enderror
                                                        </div>

                                                        <div class="form-group col-12 col-md-6 col-lg-6">
                                                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                                                id="email" value="{{ old('email') }}" placeholder="E-mail">
                                                            @error('email')
                                                                <span class="invalid-feedback d-block">{{ $message }}</span>
                                                            @enderror
                                                        </div>

                                                        <div class="form-group col-12 col-md-3 col-lg-3">
                                                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                                                id="phone" value="{{ old('phone') }}" oninput="maskPhone(this)" placeholder="Celular">
                                                            @error('phone')
                                                                <span class="invalid-feedback d-block">{{ $message }}</span>
                                                            @enderror
                                                        </div>

                                                        <div class="form-group col-12 col-md-3 col-lg-3">
                                                            <input type="text" name="postal_code" class="form-control @error('postal_code') is-invalid @enderror"
                                                                id="postal_code" value="{{ old('postal_code') }}" onblur="consultAddress(this)" placeholder="CEP">
                                                            @error('postal_code')
                                                                <span class="invalid-feedback d-block">{{ $message }}</span>
                                                            @enderror
                                                        </div>

                                                        <div class="form-group col-12 col-md-4 col-lg-4">
                                                            <input type="text" name="address" class="form-control @error('address') is-invalid @enderror"
                                                                id="address" value="{{ old('address') }}" placeholder="Endereço">
                                                            @error('address')
                                                                <span class="invalid-feedback d-block">{{ $message }}</span>
                                                            @enderror
                                                        </div>

                                                        <div class="form-group col-12 col-md-2 col-lg-2">
                                                            <input type="text" name="address_number" class="form-control @error('address_number') is-invalid @enderror"
                                                                id="address_number" value="{{ old('address_number') }}" placeholder="Número">
                                                            @error('address_number')
                                                                <span class="invalid-feedback d-block">{{ $message }}</span>
                                                            @enderror
                                                        </div>

                                                        <div class="form-group col-12 col-md-3 col-lg-3">
                                                            <input type="text" name="address_city" class="form-control" id="address_city"
                                                                value="{{ old('address_city') }}" placeholder="Cidade" readonly>
                                                        </div>

                                                        <div class="form-group col-12 col-md-3 col-lg-3">
                                                            <input type="text" name="address_provincy" class="form-control" id="address_provincy"
                                                                value="{{ old('address_provincy') }}" placeholder="Estado" readonly>
                                                        </div>

                                                        <div class="form-group col-12 col-md-12 col-lg-12">
                                                            <button type="button" class="btn btn-primary btn-user btn-block" onclick="changeTab()"> Próximo </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                @if ($plan->hasSimulator())
                                                    <div class="tab-pane fade" id="members">
                                                        <div id="members-root"
                                                            data-business="{{ $plan->isBusiness() ? 1 : 0 }}"
                                                            data-kinships='@json($kinships)'
                                                            data-employee-keys='@json(\App\Support\Kinship::BUSINESS_EMPLOYEES)'
                                                            data-old-members='@json(old('members', []))'>
                                                            <div class="d-flex justify-content-between align-items-start mb-3 gap-2">
                                                                <div>
                                                                    <p class="text-muted small mb-1">
                                                                        Opcional: cadastre agora os {{ $plan->isBusiness() ? 'funcionários e dependentes' : 'dependentes' }}.
                                                                        Você também pode incluir mais depois.
                                                                    </p>
                                                                    <small class="text-primary font-weight-bold">
                                                                        <span id="members-count">0</span> cadastrados · <span id="members-slots">—</span>
                                                                    </small>
                                                                </div>
                                                                <button type="button" class="btn btn-primary btn-sm" id="add-member">
                                                                    + Adicionar
                                                                </button>
                                                            </div>
                                                            <div id="members-empty" class="members-empty text-muted small mb-3">
                                                                Nenhuma pessoa adicionada ainda.
                                                            </div>
                                                            <div id="members-list" class="members-list mb-3"></div>
                                                            <button type="button" class="btn btn-primary btn-user btn-block" onclick="changeTab()"> Próximo </button>
                                                        </div>
                                                    </div>
                                                @endif
                                                <div class="tab-pane fade" id="terms">
                                                    <div class="contract-wrap mb-3">
                                                        <div class="editor contract-preview" id="contract-preview">
                                                            {!! $plan->terms !!}
                                                        </div>
                                                        <button type="button" class="btn btn-link btn-sm px-0" id="contract-toggle" aria-expanded="false">
                                                            Leia completo
                                                        </button>
                                                    </div>
                                                    
                                                    <div class="row">
                                                        <div class="form-group col-12 col-md-6 col-lg-6">
                                                            <small>Forma de Pagamento</small>
                                                            <select name="payment_method" class="form-control" required>
                                                                <option value="CREDIT_CARD">Cartão de Crédito</option>
                                                                <option value="BOLETO">Boleto Bancário</option>
                                                                <option value="PIX">PIX</option>
                                                            </select>
                                                        </div>
                                                        <div class="form-group col-12 col-md-6 col-lg-6">
                                                            <small>Dia de Vencimento</small>
                                                            <select name="due_date" class="form-control" required>
                                                                <option value="5">5</option>
                                                                <option value="10">10</option>
                                                                <option value="15">15</option>
                                                                <option value="20">20</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    
                                                    <button type="submit" class="btn btn-primary btn-user btn-block"> Confirmar e continuar </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        @if ($plan->hasSimulator())
            <div class="offcanvas offcanvas-end" tabindex="-1" id="memberOffcanvas" aria-labelledby="member-offcanvas-title">
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title" id="member-offcanvas-title">Adicionar pessoa</h5>
                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
                </div>
                <div class="offcanvas-body">
                    <form id="member-form" onsubmit="return false;">
                        <div class="mb-3">
                            <label for="member-name" class="form-label">Nome completo</label>
                            <input type="text" class="form-control" id="member-name" placeholder="Nome completo" autocomplete="name">
                        </div>
                        <div class="mb-3">
                            <label for="member-document" class="form-label">CPF</label>
                            <input type="text" class="form-control" id="member-document" oninput="maskCpf(this)" placeholder="000.000.000-00" inputmode="numeric" maxlength="14" autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <label for="member-kinship" class="form-label">{{ $plan->isBusiness() ? 'Vínculo / Cargo' : 'Grau de parentesco' }}</label>
                            <select class="form-select" id="member-kinship">
                                <option value="">Selecione</option>
                                @foreach ($kinships as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="button" class="btn btn-primary w-100" id="member-save">Salvar pessoa</button>
                    </form>
                </div>
            </div>
        @endif

        <script src="{{ asset('Assets/vendor/jquery/jquery.min.js') }}"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
        <script src="{{ asset('Assets/vendor/jquery-easing/jquery.easing.min.js') }}"></script>
        <script src="{{ asset('Assets/js/sb-admin-2.min.js') }}"></script>
        <script src="{{ asset('Assets/js/sweet-alert.js') }}"></script>
        <script src="{{ asset('Assets/js/mask.js') }}?v={{ @filemtime(public_path('Assets/js/mask.js')) }}"></script>
        <script src="{{ asset('Assets/vendor/lucide/lucide.min.js') }}"></script>
        <script src="{{ asset('Assets/js/plan-card.js') }}?v={{ @filemtime(public_path('Assets/js/plan-card.js')) }}"></script>
        @if ($plan->hasSimulator())
            <script src="{{ asset('Assets/js/sale-members.js') }}?v={{ @filemtime(public_path('Assets/js/sale-members.js')) }}"></script>
        @endif
        <style>
            .contract-preview {
                max-height: 140px;
                overflow: hidden;
                position: relative;
                transition: max-height 0.25s ease;
            }
            .contract-preview.is-expanded {
                max-height: none;
            }
            .contract-preview:not(.is-expanded)::after {
                content: '';
                position: absolute;
                left: 0;
                right: 0;
                bottom: 0;
                height: 48px;
                background: linear-gradient(transparent, #fff);
                pointer-events: none;
            }
            .members-list {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            .member-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                padding: 12px 14px;
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                background: #f8fafc;
            }
            .member-item-main {
                min-width: 0;
            }
            .member-item-main strong {
                display: block;
                font-size: 14px;
                color: #111827;
            }
            .member-item-main small {
                display: block;
                color: #6b7280;
                font-size: 12px;
            }
            .member-item-actions {
                display: flex;
                gap: 6px;
                flex-shrink: 0;
            }
            .members-empty {
                padding: 16px;
                border: 1px dashed #d1d5db;
                border-radius: 12px;
                text-align: center;
            }
        </style>
        <script>
            lucide.createIcons();

            @if(session('error'))
                Swal.fire({
                    title: 'Atenção!',
                    text: '{{ session('error') }}',
                    icon: 'info',
                    timer: 3500
                })
            @endif

            @if($errors->any())
                Swal.fire({
                    title: 'Atenção!',
                    html: `{!! implode('<br>', array_map('addslashes', $errors->all())) !!}`,
                    icon: 'warning',
                    timer: 3500
                });
            @endif

            @if(session('infor'))
                Swal.fire({
                    title: 'Atenção!',
                    text: '{{ session('infor') }}',
                    icon: 'info',
                    timer: 3500
                })
            @endif

            @if(session('success'))
                Swal.fire({
                    title: 'Sucesso!',
                    text: '{{ session('success') }}',
                    icon: 'success',
                    timer: 3500
                })
            @endif

            function changeTab() {
                @if ($plan->hasSimulator())
                    if ($('#details').hasClass('active') || $('#details').hasClass('show')) {
                        $('#members-tab').click();
                        return;
                    }
                @endif
                $('#terms-tab').click();
            }

            $('#contract-toggle').on('click', function () {
                var $preview = $('#contract-preview');
                var expanded = $preview.toggleClass('is-expanded').hasClass('is-expanded');
                $(this).attr('aria-expanded', expanded ? 'true' : 'false')
                    .text(expanded ? 'Mostrar menos' : 'Leia completo');
            });
        </script>
    </body>
</html>