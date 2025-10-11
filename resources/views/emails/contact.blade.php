<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo mensaje de contacto</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fb;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            overflow: hidden;
            border: 1px solid #eee;
        }
        .header {
            background: linear-gradient(135deg, #0165B0, #00AEEF);
            color: #fff;
            padding: 24px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
        }
        .content {
            padding: 24px;
            line-height: 1.6;
        }
        .content p {
            margin-bottom: 12px;
        }
        .content strong {
            color: #0165B0;
        }
        .message-box {
            background-color: #f2f7fb;
            border-left: 4px solid #00AEEF;
            padding: 16px;
            border-radius: 8px;
            white-space: pre-line;
        }
        .footer {
            text-align: center;
            padding: 16px;
            font-size: 13px;
            color: #777;
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Nuevo mensaje de contacto 📩</h1>
        </div>

        <div class="content">
            <p><strong>Nombre:</strong> {{ $name }}</p>
            <p><strong>Email:</strong> {{ $email }}</p>

            @if ($subject)
                <p><strong>Asunto:</strong> {{ $subject }}</p>
            @endif

            <p><strong>Mensaje:</strong></p>
            <div class="message-box">
                {{ $messageContent }}
            </div>
        </div>

        <div class="footer">
            <p>Este mensaje fue enviado desde el formulario de contacto de tu sitio web.</p>
        </div>
    </div>
</body>
</html>
