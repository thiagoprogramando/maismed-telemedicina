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
        
    </head>

    <body class="bg-gradient-light">
        @php $checkout = session('checkout'); @endphp

        <div class="container d-flex align-items-center justify-content-center min-vh-100">
            <div class="row justify-content-center w-100">
                <div class="col-xl-8 col-lg-9 col-md-10">

                    <div class="card o-hidden border-0 shadow-lg my-5">
                        <div class="card-body p-0">
                            
                            <div class="row">
                                <div class="col-12">
                                    <div class="p-4">
                                        <div class="text-center bg-primary rounded mb-3 py-3">
                                            <img src="{{ asset('Assets/img/logo.png') }}" class="img-fluid" style="max-width: 160px;" alt="Logo Mais Med">
                                        </div>

                                        <div class="text-center mb-4">
                                            <h1 class="h3 text-primary mb-2">Parabéns pela sua escolha!</h1>
                                            <p class="mb-0">
                                                @if (!empty($checkout['plan_name']))
                                                    Plano <strong>{{ $checkout['plan_name'] }}</strong>
                                                    @if (!empty($checkout['price']))
                                                        — R$ {{ number_format($checkout['price'], 2, ',', '.') }}/mês
                                                    @endif
                                                @else
                                                    Agora você conta com os benefícios da nossa telemedicina.
                                                @endif
                                            </p>
                                        </div>

                                        @if (!empty($checkout['pay_now']) && ($checkout['payment_method'] ?? null) === 'PIX' && !empty($checkout['pix']['payload']))
                                            <div class="border rounded p-3 mb-3 text-center">
                                                <h2 class="h5 text-primary">Pague com PIX agora</h2>
                                                <p class="small text-muted">Escaneie o QR Code ou copie o código para pagar a 1ª parcela nesta tela.</p>
                                                @if (!empty($checkout['pix']['encodedImage']))
                                                    <img src="data:image/png;base64,{{ $checkout['pix']['encodedImage'] }}" alt="QR Code PIX" class="img-fluid mb-3" style="max-width: 220px;">
                                                @endif
                                                <div class="input-group mb-2">
                                                    <input type="text" class="form-control" id="pix-payload" value="{{ $checkout['pix']['payload'] }}" readonly>
                                                    <button type="button" class="btn btn-primary" id="copy-pix">Copiar</button>
                                                </div>
                                            </div>
                                        @elseif (!empty($checkout['pay_now']) && ($checkout['payment_method'] ?? null) === 'CREDIT_CARD')
                                            <div class="alert alert-success">
                                                Cartão cadastrado para recorrência.
                                                @if (!empty($checkout['card_status']) && in_array($checkout['card_status'], ['CONFIRMED', 'RECEIVED'], true))
                                                    A 1ª parcela já foi confirmada.
                                                @else
                                                    A 1ª parcela está sendo processada. Você receberá a confirmação por e-mail.
                                                @endif
                                            </div>
                                        @elseif (!empty($checkout['pay_now']) && !empty($checkout['invoice_url']))
                                            <div class="text-center mb-3">
                                                <a href="{{ $checkout['invoice_url'] }}" target="_blank" rel="noopener" class="btn btn-primary btn-lg">
                                                    Abrir fatura / boleto
                                                </a>
                                            </div>
                                        @elseif (!empty($checkout) && empty($checkout['pay_now']))
                                            <div class="alert alert-info">
                                                Salvamos sua adesão e enviamos o link de pagamento para
                                                <strong>{{ $checkout['email'] ?? 'seu e-mail' }}</strong>.
                                                @if (!empty($checkout['invoice_url']))
                                                    <div class="mt-2">
                                                        <a href="{{ $checkout['invoice_url'] }}" target="_blank" rel="noopener">Abrir link de pagamento</a>
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <div class="text-center">
                                                <small>
                                                    Em breve, você receberá por <b>E-MAIL</b> as orientações para acessar sua conta.
                                                    <strong>Cuidar da sua saúde ficou mais simples!</strong>
                                                </small>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <script src="{{ asset('Assets/vendor/jquery/jquery.min.js') }}"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
        <script src="{{ asset('Assets/js/sweet-alert.js') }}"></script>
        <script>
            @if(session('success'))
                Swal.fire({
                    title: 'Sucesso!',
                    text: '{{ session('success') }}',
                    icon: 'success',
                    timer: 3500
                });
            @endif

            $('#copy-pix').on('click', function () {
                var input = document.getElementById('pix-payload');
                if (!input) return;
                navigator.clipboard.writeText(input.value).then(function () {
                    Swal.fire({ title: 'Copiado!', text: 'Código PIX copiado.', icon: 'success', timer: 1800 });
                });
            });
        </script>
    </body>
</html>
