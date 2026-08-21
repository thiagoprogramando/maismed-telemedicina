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
        <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
        <link href="{{ asset('Assets/css/sb-admin-2.css') }}" rel="stylesheet">
        
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
                                            <small class="text-white">{{ $plan->name }} R$ {{ number_format($plan->price, 2, ',', '.').'/Mês' }} </small>
                                        </div>
                                        <form action="{{ route('created-sale') }}" method="POST" class="mt-2 mb-5">
                                            @csrf
                                            <input type="hidden" name="parent_id" value="{{ $parent->uuid ?? null }}">
                                            <input type="hidden" name="plan_id" value="{{ $plan->uuid }}">
                                            <ul class="nav nav-tabs" id="myTab" role="tablist">
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#details" type="button">
                                                        Dados
                                                    </button>
                                                </li>

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
                                                <div class="tab-pane fade" id="terms">
                                                    <div class="editor">
                                                        {!! $plan->terms !!}
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

        <script src="{{ asset('Assets/vendor/jquery/jquery.min.js') }}"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
        <script src="{{ asset('Assets/vendor/jquery-easing/jquery.easing.min.js') }}"></script>
        <script src="{{ asset('Assets/js/sb-admin-2.min.js') }}"></script>
        <script src="{{ asset('Assets/js/sweet-alert.js') }}"></script>
        <script src="{{ asset('Assets/js/mask.js') }}"></script>
        <script>
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
                $('#terms-tab').click();
            }
        </script>
    </body>
</html>