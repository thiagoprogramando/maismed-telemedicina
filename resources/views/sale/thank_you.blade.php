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
                                            <small class="text-white"> Obrigado por escolher {{ env('APP_NAME').' - '.env('APP_DESCRIPTION') }} </small>
                                        </div>
                                        <div class="mt-2 mb-5 text-center">
                                            <h1 class="h1 text-primary">Parabéns pela sua escolha! 🎉</h1>
                                            <small> 
                                                Agora você conta com os benefícios da nossa telemedicina, com acesso a atendimento de forma prática, segura e conveniente. 
                                                Em breve, você receberá por <b>E-MAIL</b> as orientações para acessar sua conta e começar a utilizar nossos serviços. <strong>Cuidar da sua saúde ficou mais simples!</strong>
                                            </small>
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
        </script>
    </body>
</html>