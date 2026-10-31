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
        <link href="{{ asset('Assets/css/checkout.css') }}?v={{ @filemtime(public_path('Assets/css/checkout.css')) }}" rel="stylesheet">

    </head>

    <body class="thank-page">
        @php $checkout = session('checkout'); @endphp

        <div class="container d-flex align-items-center justify-content-center min-vh-100 py-4">
            <div class="ck-shell-sm w-100">

                <div class="ck-brand mb-4">
                    <img src="{{ asset('Assets/img/logo.png') }}" class="img-fluid" alt="Logo Mais Med">
                </div>

                <div class="card ck-card">
                    <div class="card-body p-4 p-md-5 text-center">
                        <div class="ck-success-icon mx-auto">
                            <i data-lucide="circle-check"></i>
                        </div>

                        <h1 class="h3 ck-title mb-2">Parabéns pela sua escolha!</h1>
                        <p class="mb-4 text-muted">
                            @if (!empty($checkout['plan_name']))
                                Plano <strong>{{ $checkout['plan_name'] }}</strong>
                                @if (!empty($checkout['price']))
                                    — R$ {{ number_format($checkout['price'], 2, ',', '.') }}/mês
                                @endif
                            @else
                                Agora você conta com os benefícios da nossa telemedicina.
                            @endif
                        </p>

                        @if (!empty($checkout['pay_now']) && ($checkout['payment_method'] ?? null) === 'CREDIT_CARD')
                            <div class="alert alert-success text-start">
                                <i data-lucide="credit-card" class="pay-method-icon"></i>
                                Pagamento com cartão enviado.
                                @if (!empty($checkout['card_status']) && in_array($checkout['card_status'], ['CONFIRMED', 'RECEIVED'], true))
                                    A 1ª parcela já foi confirmada.
                                @else
                                    A 1ª parcela está sendo processada. Você receberá a confirmação por e-mail.
                                @endif
                            </div>
                        @elseif (!empty($checkout['pay_now']) && !empty($checkout['invoice_url']))
                            <div class="mb-3">
                                <a href="{{ $checkout['invoice_url'] }}" target="_blank" rel="noopener" class="btn btn-primary btn-lg ck-btn-primary">
                                    <i data-lucide="external-link" class="pay-method-icon"></i>
                                    Abrir fatura / boleto
                                </a>
                            </div>
                        @elseif (!empty($checkout) && empty($checkout['pay_now']))
                            <div class="alert alert-info text-start">
                                <div class="ck-hint mb-0">
                                    <i data-lucide="mail"></i>
                                    <span>
                                        Salvamos sua adesão e enviamos o link de pagamento para
                                        <strong>{{ $checkout['email'] ?? 'seu e-mail' }}</strong>.
                                        @if (!empty($checkout['invoice_url']))
                                            <div class="mt-2">
                                                <a href="{{ $checkout['invoice_url'] }}" target="_blank" rel="noopener">Abrir link de pagamento</a>
                                            </div>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @else
                            <p class="small text-muted mb-0">
                                Em breve, você receberá por <strong>e-mail</strong> as orientações para acessar sua conta.
                                Cuidar da sua saúde ficou mais simples!
                            </p>
                        @endif
                    </div>
                </div>

            </div>
        </div>

        <script src="{{ asset('Assets/vendor/jquery/jquery.min.js') }}"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
        <script src="{{ asset('Assets/vendor/lucide/lucide.min.js') }}"></script>
        <script src="{{ asset('Assets/js/sweet-alert.js') }}"></script>
        <script>
            if (window.lucide) {
                lucide.createIcons();
            }

            @if(session('success'))
                Swal.fire({
                    title: 'Sucesso!',
                    text: '{{ session('success') }}',
                    icon: 'success',
                    timer: 3500
                });
            @endif
        </script>
    </body>
</html>
