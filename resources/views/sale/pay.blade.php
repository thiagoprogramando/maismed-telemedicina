<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title>{{ env('APP_NAME') }} - Pagamento</title>

        <link rel="icon" href="{{ asset('Assets/img/logo.png') }}" type="image/png">
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
        <link href="{{ asset('Assets/css/sb-admin-2.css') }}" rel="stylesheet">
        <style>
            :root {
                --mm-navy: #0b1b5c;
            }
            body.pay-page {
                background: linear-gradient(160deg, #e8eef8 0%, #f7f9fc 45%, #ffffff 100%);
                min-height: 100vh;
                font-family: Nunito, system-ui, sans-serif;
            }
            .pay-shell {
                max-width: 520px;
            }
            .pay-brand {
                background: var(--mm-navy);
                border-radius: 0.75rem;
            }
            .pay-brand img {
                max-width: 140px;
            }
            .pay-card {
                border: 0;
                border-radius: 1rem;
                box-shadow: 0 12px 40px rgba(11, 27, 92, 0.08);
            }
            .pay-amount {
                font-size: 1.75rem;
                font-weight: 800;
                color: var(--mm-navy);
            }
            .pay-method-icon {
                width: 1.25rem;
                height: 1.25rem;
                vertical-align: -0.2rem;
            }
        </style>
    </head>
    <body class="pay-page">
        <div class="container d-flex align-items-center justify-content-center py-5">
            <div class="pay-shell w-100">
                <div class="text-center pay-brand py-3 mb-4">
                    <img src="{{ asset('Assets/img/logo.png') }}" class="img-fluid" alt="Logo Mais Med">
                </div>

                <div class="card pay-card">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <h1 class="h4 text-primary mb-1">Finalize o pagamento</h1>
                            <p class="mb-0 text-muted">
                                Plano <strong>{{ $plan->name }}</strong>
                            </p>
                            <p class="pay-amount mb-0 mt-2">R$ {{ number_format((float) $sale->price, 2, ',', '.') }}</p>
                            <small class="text-muted">1ª parcela</small>
                        </div>

                        @if ($error)
                            <div class="alert alert-danger">{{ $error }}</div>
                        @endif

                        @if ($method === 'PIX')
                            <div class="text-center mb-3">
                                <h2 class="h5 text-primary">
                                    <i data-lucide="qr-code" class="pay-method-icon"></i>
                                    Pague com PIX
                                </h2>
                                <p class="small text-muted">Escaneie o QR Code ou copie o código para pagar a 1ª parcela.</p>
                            </div>

                            @if (!empty($pix['encodedImage']))
                                <div class="text-center mb-3">
                                    <img src="data:image/png;base64,{{ $pix['encodedImage'] }}" alt="QR Code PIX" class="img-fluid" style="max-width: 220px;">
                                </div>
                            @endif

                            @if (!empty($pix['payload']))
                                <div class="input-group mb-3">
                                    <input type="text" class="form-control" id="pix-payload" value="{{ $pix['payload'] }}" readonly>
                                    <button type="button" class="btn btn-primary" id="copy-pix">Copiar</button>
                                </div>
                            @endif

                            @if (!empty($invoiceUrl))
                                <div class="text-center">
                                    <a href="{{ $invoiceUrl }}" target="_blank" rel="noopener" class="small">Abrir fatura no Asaas</a>
                                </div>
                            @endif
                        @elseif ($method === 'CREDIT_CARD')
                            <div class="mb-3">
                                <h2 class="h5 text-primary">
                                    <i data-lucide="credit-card" class="pay-method-icon"></i>
                                    Cartão de crédito
                                </h2>
                                <p class="small text-muted mb-0">Informe os dados do cartão para pagar a 1ª parcela.</p>
                            </div>

                            <form action="{{ route('pay-card', $sale->uuid) }}" method="POST" id="pay-card-form">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label small" for="card_holder">Nome impresso no cartão</label>
                                    <input type="text" name="card_holder" id="card_holder" class="form-control @error('card_holder') is-invalid @enderror" value="{{ old('card_holder') }}" autocomplete="cc-name" required>
                                    @error('card_holder')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small" for="card_number">Número do cartão</label>
                                    <input type="text" name="card_number" id="card_number" class="form-control @error('card_number') is-invalid @enderror" value="{{ old('card_number') }}" inputmode="numeric" autocomplete="cc-number" maxlength="19" required>
                                    @error('card_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <label class="form-label small" for="card_expiry">Validade</label>
                                        <input type="text" name="card_expiry" id="card_expiry" class="form-control @error('card_expiry') is-invalid @enderror" value="{{ old('card_expiry') }}" placeholder="MM/AA" inputmode="numeric" autocomplete="cc-exp" maxlength="5" required>
                                        @error('card_expiry')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="form-label small" for="card_cvv">CVV</label>
                                        <input type="text" name="card_cvv" id="card_cvv" class="form-control @error('card_cvv') is-invalid @enderror" value="{{ old('card_cvv') }}" inputmode="numeric" autocomplete="cc-csc" maxlength="4" required>
                                        @error('card_cvv')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <label class="form-label small" for="postal_code">CEP</label>
                                        <input type="text" name="postal_code" id="postal_code" class="form-control" value="{{ old('postal_code') }}" inputmode="numeric" maxlength="9" placeholder="00000-000">
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="form-label small" for="address_number">Nº</label>
                                        <input type="text" name="address_number" id="address_number" class="form-control" value="{{ old('address_number') }}" maxlength="20" placeholder="S/N">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary btn-user btn-block w-100">Pagar 1ª parcela</button>
                            </form>
                        @else
                            <div class="text-center">
                                <h2 class="h5 text-primary mb-3">
                                    <i data-lucide="file-text" class="pay-method-icon"></i>
                                    Boleto bancário
                                </h2>
                                @if (!empty($invoiceUrl))
                                    <a href="{{ $invoiceUrl }}" target="_blank" rel="noopener" class="btn btn-primary btn-lg">
                                        Abrir boleto / fatura
                                    </a>
                                @else
                                    <p class="text-muted mb-0">Aguarde enquanto preparamos sua fatura.</p>
                                @endif
                            </div>
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
                Swal.fire({ title: 'Sucesso!', text: '{{ session('success') }}', icon: 'success', timer: 3500 });
            @endif
            @if(session('error'))
                Swal.fire({ title: 'Atenção', text: '{{ session('error') }}', icon: 'error' });
            @endif

            $('#copy-pix').on('click', function () {
                var input = document.getElementById('pix-payload');
                if (!input) return;
                navigator.clipboard.writeText(input.value).then(function () {
                    Swal.fire({ title: 'Copiado!', text: 'Código PIX copiado.', icon: 'success', timer: 1800 });
                });
            });

            $('#card_number').on('input', function () {
                var digits = this.value.replace(/\D/g, '').slice(0, 16);
                this.value = digits.replace(/(\d{4})(?=\d)/g, '$1 ').trim();
            });

            $('#card_expiry').on('input', function () {
                var digits = this.value.replace(/\D/g, '').slice(0, 4);
                if (digits.length >= 3) {
                    digits = digits.slice(0, 2) + '/' + digits.slice(2);
                }
                this.value = digits;
            });

            $('#card_cvv').on('input', function () {
                this.value = this.value.replace(/\D/g, '').slice(0, 4);
            });
        </script>
    </body>
</html>
