<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Respuesta a tu mensaje | TukiShop</title>
    <style>
        body {
            background: #f6f7fb;
            font-family: Arial, sans-serif;
            color: #333;
        }

        .container {
            background: #fff;
            border-radius: 10px;
            margin: 30px auto;
            max-width: 600px;
            padding: 24px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        h2 {
            color: #ff6f3b;
        }

        .message {
            background: #f8f8f8;
            border-left: 4px solid #ff6f3b;
            padding: 12px;
            margin: 20px 0;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Hola {{ $userName }},</h2>
        <p>Hemos recibido tu mensaje y te respondemos a continuación:</p>

        <div class="message">
            {!! nl2br(e($replyMessage)) !!}
        </div>

        <p><strong>Tu mensaje original:</strong></p>
        <blockquote style="font-size:13px;color:#555;border-left:3px solid #ddd;padding-left:10px;">
            {!! nl2br(e($originalMessage)) !!}
        </blockquote>

        <p>Gracias por contactarte con <strong>{{ $companyName }}</strong> 💛</p>
        <p>— El equipo de atención de {{ $companyName }}</p>
    </div>
</body>

</html>