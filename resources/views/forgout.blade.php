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
        <link href="{{ asset('Assets/css/sb-admin-2.css') }}" rel="stylesheet">
    </head>

    <body class="bg-gradient-primary">

        <div class="container d-flex align-items-center justify-content-center min-vh-100">
            <div class="row justify-content-center w-100">
                <div class="col-xl-10 col-lg-12 col-md-9">

                    <div class="card o-hidden border-0 shadow-lg my-5">
                        <div class="card-body p-0">
                            <div class="row">
                                <div class="col-lg-6 d-none d-lg-block bg-password-image"></div>
                                <div class="col-lg-6">
                                    <div class="p-5">
                                        <div class="text-center">
                                            <h1 class="h4 text-gray-900 mb-2">Esqueceu algo?</h1>
                                            <p class="mb-4">Vamos recuperar o seu acesso ao {{ env('APP_NAME') }}!</p>
                                        </div>
                                        @if (empty($code))
                                            <form class="user" action="{{ route('forgout-password') }}" method="POST">
                                                @csrf
                                                <div class="form-group">
                                                    <input type="email" name="email" class="form-control form-control-user" id="email" placeholder="Qual o E-mail da sua conta?">
                                                </div>
                                                <button type="submit" class="btn btn-primary btn-user btn-block"> Recuperar Conta </button>
                                            </form>
                                        @else
                                            <form class="user" action="{{ route('recover-password', ['code' => $code]) }}" method="POST">
                                                @csrf
                                                <div class="form-group">
                                                    <input type="password" name="password" class="form-control form-control-user" id="password" placeholder="Nova Senha:">
                                                </div>
                                                <div class="form-group">
                                                    <input type="password" name="password_confirmed" class="form-control form-control-user" id="password_confirmed" placeholder="Confirme a senha:">
                                                </div>
                                                <button type="submit" class="btn btn-primary btn-user btn-block"> Alterar Senha </button>
                                            </form>
                                        @endif
                                        <hr>
                                        <div class="text-center">
                                            <a class="small" href="{{ route('register') }}">Conheça nossos serviços</a>
                                        </div>
                                        <div class="text-center">
                                            <a class="small" href="{{ route('login') }}">Já tem uma conta? Acesse!</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <script src="{{ asset('Assets/vendor/jquery/jquery.min.js') }}"></script>
        <script src="{{ asset('Assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
        <script src="{{ asset('Assets/vendor/jquery-easing/jquery.easing.min.js') }}"></script>
        <script src="{{ asset('Assets/js/sb-admin-2.min.js') }}"></script>
        <script src="{{ asset('assets/js/sweetalert.js') }}"></script>
        <script>
            @if(session('error'))
                Swal.fire({
                    title: 'Erro!',
                    text: '{{ session('error') }}',
                    icon: 'error',
                    timer: 2000
                })
            @endif

            @if(session('infor'))
                Swal.fire({
                    title: 'Atenção!',
                    text: '{{ session('infor') }}',
                    icon: 'info',
                    timer: 2000
                })
            @endif

            @if(session('success'))
                Swal.fire({
                    title: 'Sucesso!',
                    text: '{{ session('success') }}',
                    icon: 'success',
                    timer: 2000
                })
            @endif
        </script>
    </body>
</html>