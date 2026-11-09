<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>{{ $data['subject'] ?? 'Transferência de beneficiário' }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.5;">
    <p>Olá,</p>

    <p>
        O administrador <strong>{{ $data['admin_name'] }}</strong> transferiu um beneficiário entre consultores
        em <strong>{{ $data['transferred_at'] }}</strong>.
    </p>

    <p>
        Beneficiário: <strong>{{ $data['buyer_name'] }}</strong> ({{ $data['buyer_document'] }})<br>
        Plano: <strong>{{ $data['plan_name'] }}</strong><br>
        De: <strong>{{ $data['from_name'] }}</strong><br>
        Para: <strong>{{ $data['to_name'] }}</strong>
    </p>

    <p style="font-size: 12px; color: #6b7280;">
        Este e-mail é apenas para transparência da operação. Não responda automaticamente.
    </p>
</body>
</html>
