<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>{{ $data['subject'] ?? 'Cópia do acordo' }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.5;">
    <p>Olá, <strong>{{ $data['customer_name'] }}</strong>!</p>

    <p>Segue a cópia do seu acordo de adesão ao plano <strong>{{ $data['plan_name'] }}</strong>.</p>

    <p>
        Valor da mensalidade: <strong>R$ {{ number_format((float) ($data['price'] ?? 0), 2, ',', '.') }}</strong><br>
        Forma de pagamento: <strong>{{ $data['payment_method_label'] ?? '—' }}</strong>
        @if (!empty($data['agreement_date']))
            <br>Data: <strong>{{ $data['agreement_date'] }}</strong>
        @endif
    </p>

    @if (!empty($data['dependents_summary']))
        <p>{{ $data['dependents_summary'] }}</p>
    @endif

    @if (!empty($data['payment_url']))
        <p>
            Para acessar o pagamento ou o recibo, use o link abaixo:
        </p>
        <p>
            <a href="{{ $data['payment_url'] }}" style="display:inline-block;background:#0b1b5c;color:#fff;padding:12px 18px;border-radius:8px;text-decoration:none;">
                Abrir link de pagamento
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
