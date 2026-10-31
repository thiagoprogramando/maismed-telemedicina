<!DOCTYPE html>
<html lang="pt-br">
    <head>

        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

        <title>{{ env('APP_NAME') }} - Planos</title>

        <link rel="icon" href="{{ asset('Assets/img/logo.png') }}" type="image/png">
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
        <link href="{{ asset('Assets/css/sb-admin-2.css') }}" rel="stylesheet">
        <link href="{{ asset('Assets/css/plan-card.css') }}" rel="stylesheet">
        <link href="{{ asset('Assets/css/checkout.css') }}?v={{ @filemtime(public_path('Assets/css/checkout.css')) }}" rel="stylesheet">

    </head>

    <body class="showcase-page">

        <div class="container py-5">

            <div class="ck-brand mb-4 mx-auto" style="max-width: 220px;">
                <img src="{{ asset('Assets/img/logo.png') }}" class="img-fluid" alt="Logo Mais Med">
            </div>

            <div class="ck-hero">
                <h1 class="h2 mb-1">Simule seu plano em segundos</h1>
                <p class="ck-lead mb-0">Ajuste a quantidade de pessoas e veja na hora quanto fica o seu plano.</p>
            </div>

            @if ($plans->isEmpty())
                <div class="showcase-empty">
                    <i data-lucide="package-open"></i>
                    <p class="mt-3 mb-0">Nenhum plano disponível no momento. Fale com um de nossos consultores.</p>
                </div>
            @else
                <div class="pc-grid">
                    @foreach ($plans as $plan)
                        @include('sale.plan_card', ['plan' => $plan, 'parent' => $parent, 'cta' => true, 'qty' => null, 'dep' => null])
                    @endforeach
                </div>
            @endif

        </div>

        <script src="{{ asset('Assets/vendor/lucide/lucide.min.js') }}"></script>
        <script src="{{ asset('Assets/js/plan-card.js') }}?v={{ @filemtime(public_path('Assets/js/plan-card.js')) }}"></script>
        <script>
            lucide.createIcons();
        </script>
    </body>
</html>
