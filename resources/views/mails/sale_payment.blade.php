<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>{{ $data['subject'] ?? 'Pagamento' }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.5;">
    <p>Olá, <strong>{{ $data['customer_name'] }}</strong>!</p>

    <p>Recebemos sua adesão ao plano <strong>{{ $data['plan_name'] }}</strong>.</p>

    <p>
        Valor da mensalidade: <strong>R$ {{ number_format($data['price'], 2, ',', '.') }}</strong><br>
        Forma de pagamento: <strong>{{ $data['payment_method_label'] }}</strong>
    </p>

    @if (!empty($data['payment_url']))
        <p>
            Para concluir o pagamento da primeira parcela, acesse o link abaixo:
        </p>
        <p>
            <a href="{{ $data['payment_url'] }}" style="display:inline-block;background:#0b1b5c;color:#fff;padding:12px 18px;border-radius:8px;text-decoration:none;">
                Pagar agora
            </a>
        </p>
        <p style="font-size: 12px; color: #6b7280;">
            Se o botão não funcionar, copie e cole no navegador:<br>
            {{ $data['payment_url'] }}
        </p>
    @endif

    <p>Qualquer dúvida, fale com o seu consultor ou com o suporte {{ config('app.name') }}.</p>
</body>
</html>
