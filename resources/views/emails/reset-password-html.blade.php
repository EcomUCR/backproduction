<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Restablecer contraseña</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
</head>
<body style="margin:0;padding:0;background:#f6f7fb;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f7fb;padding:24px 0;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:linear-gradient(135deg,#FFD027,#FF6F3B,#5B2A86);border-radius:12px;overflow:hidden;font-family:Arial,Helvetica,sans-serif;">
          <tr>
            <td align="center" style="padding:24px;background:#111827;">
              <img src="https://res.cloudinary.com/dpbghs8ep/image/upload/v1760314036/Tuki_vitzem.png" alt="TukiShop" width="120" style="display:block;">
            </td>
          </tr>
          <tr>
            <td style="padding:32px 28px;color:#111827;">
              <h1 style="margin:0 0 12px;font-size:22px;line-height:1.3;">¡Hola!</h1>
              <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
                Recibiste este correo porque solicitaste restablecer tu contraseña.
              </p>
              <p style="margin:0 0 24px;text-align:center;">
                <a href="{{ $url }}" style="background:#0ea5e9;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:8px;display:inline-block;font-weight:bold;">
                  Restablecer contraseña
                </a>
              </p>
              <p style="margin:0 0 12px;font-size:13px;line-height:1.6;color:#374151;">
                Si no solicitaste esto, ignora el correo.
              </p>
              <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0;">
              <p style="margin:0;font-size:12px;line-height:1.6;color:#6b7280;">
                Si tienes problemas con el botón, copia y pega esta URL en tu navegador:<br>
                <a href="{{ $url }}" style="color:#0ea5e9;word-break:break-all;">{{ $url }}</a>
              </p>
            </td>
          </tr>
          <tr>
            <td align="center" style="padding:16px;background:#f9fafb;color:#6b7280;font-size:12px;">
              Regards, TukiShop
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
