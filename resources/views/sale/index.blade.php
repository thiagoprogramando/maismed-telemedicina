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
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
        <link href="{{ asset('Assets/css/sb-admin-2.css') }}" rel="stylesheet">
        <link href="{{ asset('Assets/css/plan-card.css') }}" rel="stylesheet">
        <link href="{{ asset('Assets/css/checkout.css') }}?v={{ @filemtime(public_path('Assets/css/checkout.css')) }}" rel="stylesheet">

    </head>

    <body class="checkout-page">

        <div class="container d-flex align-items-center justify-content-center py-4 py-md-5">
            <div class="ck-shell w-100">

                <div class="ck-brand mb-4 mx-auto" style="max-width: 220px;">
                    <img src="{{ asset('Assets/img/logo.png') }}" class="img-fluid" alt="Logo Mais Med">
                </div>

                @php
                    $hasMembers = $plan->hasSimulator();
                @endphp

                <ol class="ck-steps" id="checkout-steps" aria-label="Etapas da adesão">
                    <li class="ck-step is-active" data-step="details">
                        <i data-lucide="user"></i> Dados
                    </li>
                    @if ($hasMembers)
                        <li class="ck-step" data-step="members">
                            <i data-lucide="users"></i> Dependentes
                        </li>
                    @endif
                    <li class="ck-step" data-step="terms">
                        <i data-lucide="file-text"></i> Contrato
                    </li>
                    <li class="ck-step" data-step="pay">
                        <i data-lucide="wallet"></i> Pagamento
                    </li>
                </ol>

                <div class="card ck-card">
                    <div class="ck-card-body">
                        <div class="mx-auto mb-4" style="max-width: 460px;">
                            @include('sale.plan_card', ['plan' => $plan, 'parent' => $parent, 'cta' => false, 'qty' => $qty, 'dep' => $dep, 'form' => 'sale-form'])
                        </div>

                        <form action="{{ route('created-sale') }}" method="POST" class="mt-2 mb-2" id="sale-form">
                            @csrf
                            <input type="hidden" name="parent_id" value="{{ $parent->uuid ?? null }}">
                            <input type="hidden" name="plan_id" value="{{ $plan->uuid }}">

                            <ul class="nav nav-tabs ck-tabs" id="myTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#details" type="button">
                                        <i data-lucide="user"></i> Dados
                                    </button>
                                </li>
                                @if ($hasMembers)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="members-tab" data-bs-toggle="tab" data-bs-target="#members" type="button">
                                            <i data-lucide="users"></i> Dependentes
                                        </button>
                                    </li>
                                @endif
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="terms-tab" data-bs-toggle="tab" data-bs-target="#terms" type="button">
                                        <i data-lucide="file-text"></i> Contrato
                                    </button>
                                </li>
                            </ul>

                            <div class="tab-content ck-tab-panel">
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
                                                id="birth_date" value="{{ old('birth_date') }}" placeholder="Data de Nascimento" required>
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

                                        <div class="form-group col-12 col-md-12 col-lg-12 mb-0">
                                            <button type="button" class="btn btn-primary btn-user btn-block ck-btn-primary" onclick="changeTab()">
                                                Próximo <i data-lucide="arrow-right"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                @if ($hasMembers)
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
                                                <button type="button" class="btn btn-primary btn-sm ck-btn-primary" id="add-member">
                                                    <i data-lucide="plus"></i> Adicionar
                                                </button>
                                            </div>
                                            <div id="members-empty" class="members-empty text-muted small mb-3">
                                                <i data-lucide="user-plus"></i>
                                                <div>Nenhuma pessoa adicionada ainda.</div>
                                            </div>
                                            <div id="members-list" class="members-list mb-3"></div>
                                            <button type="button" class="btn btn-primary btn-user btn-block ck-btn-primary" onclick="changeTab()">
                                                Próximo <i data-lucide="arrow-right"></i>
                                            </button>
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
                                        <div class="form-group col-12 col-md-6">
                                            <small>Quando deseja pagar?</small>
                                            <select name="pay_timing" id="pay_timing" class="form-control" required>
                                                <option value="now" @selected(old('pay_timing', 'now') === 'now')>Pagar agora</option>
                                                <option value="later" @selected(old('pay_timing') === 'later')>Pagar depois (enviar por e-mail)</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-12 col-md-6">
                                            <small>Forma de Pagamento</small>
                                            <select name="payment_method" id="payment_method" class="form-control" required>
                                                <option value="CREDIT_CARD" @selected(old('payment_method') === 'CREDIT_CARD')>Cartão de Crédito</option>
                                                <option value="PIX" @selected(old('payment_method', 'PIX') === 'PIX')>PIX</option>
                                                <option value="BOLETO" @selected(old('payment_method') === 'BOLETO')>Boleto Bancário</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-12 col-md-6">
                                            <small>Dia de Vencimento das próximas parcelas</small>
                                            <select name="due_date" class="form-control" required>
                                                <option value="5">5</option>
                                                <option value="10" @selected(old('due_date') == 10)>10</option>
                                                <option value="15">15</option>
                                                <option value="20">20</option>
                                            </select>
                                        </div>
                                    </div>

                                    <p class="ck-hint mb-3" id="pay-now-hint">
                                        <i data-lucide="info"></i>
                                        <span>Ao confirmar, vamos para a tela de pagamento (PIX ou cartão) da 1ª parcela.</span>
                                    </p>
                                    <p class="ck-hint mb-3 d-none" id="pay-later-hint">
                                        <i data-lucide="mail"></i>
                                        <span>Ao escolher pagar depois, salvamos a adesão e enviamos o link de pagamento para o seu e-mail.</span>
                                    </p>

                                    <button type="submit" class="btn btn-primary btn-user btn-block ck-btn-primary" id="checkout-submit">
                                        Confirmar adesão e pagar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>

        @if ($hasMembers)
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
                        <button type="button" class="btn btn-primary w-100 ck-btn-primary" id="member-save">Salvar pessoa</button>
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
        @if ($hasMembers)
            <script src="{{ asset('Assets/js/sale-members.js') }}?v={{ @filemtime(public_path('Assets/js/sale-members.js')) }}"></script>
        @endif
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

            function syncCheckoutSteps(activeId) {
                var order = ['details' @if($hasMembers), 'members'@endif, 'terms', 'pay'];
                var idx = order.indexOf(activeId);
                if (idx < 0) idx = 0;
                document.querySelectorAll('#checkout-steps .ck-step').forEach(function (el) {
                    var step = el.getAttribute('data-step');
                    var stepIdx = order.indexOf(step);
                    el.classList.toggle('is-active', step === activeId);
                    el.classList.toggle('is-done', stepIdx > -1 && stepIdx < idx);
                });
                if (window.lucide) {
                    lucide.createIcons();
                }
            }

            function changeTab() {
                @if ($hasMembers)
                    if ($('#details').hasClass('active') || $('#details').hasClass('show')) {
                        $('#members-tab').click();
                        return;
                    }
                @endif
                $('#terms-tab').click();
            }

            $('#myTab button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                var target = (e.target.getAttribute('data-bs-target') || '').replace('#', '');
                syncCheckoutSteps(target || 'details');
            });

            syncCheckoutSteps('details');

            $('#contract-toggle').on('click', function () {
                var $preview = $('#contract-preview');
                var expanded = $preview.toggleClass('is-expanded').hasClass('is-expanded');
                $(this).attr('aria-expanded', expanded ? 'true' : 'false')
                    .text(expanded ? 'Mostrar menos' : 'Leia completo');
            });

            function syncPaymentUi() {
                var payNow = $('#pay_timing').val() === 'now';
                $('#pay-now-hint').toggleClass('d-none', !payNow);
                $('#pay-later-hint').toggleClass('d-none', payNow);
                $('#checkout-submit').text(payNow ? 'Confirmar adesão e pagar' : 'Salvar e enviar por e-mail');
            }

            $('#pay_timing').on('change', syncPaymentUi);
            syncPaymentUi();
        </script>
    </body>
</html>
