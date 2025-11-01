<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Respuesta a tu mensaje | TukiShop</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
</head>
<body style="margin:0;padding:0;background:#f6f7fb;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f7fb;padding:24px 0;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0"
          style="background:#ffffff;border-radius:12px;overflow:hidden;font-family:Arial,Helvetica,sans-serif;">
          
          <!-- Header con degradado -->
          <tr>
            <td align="center" style="padding:24px;background:linear-gradient(135deg,#FFD027,#FF6F3B,#5B2A86);">
              <img src="https://res.cloudinary.com/dpbghs8ep/image/upload/v1760314036/Tuki_vitzem.png"
                   alt="TukiShop" width="60" style="display:block;">
            </td>
          </tr>

          <!-- Contenido principal -->
          <tr>
            <td style="padding:32px 28px;">
              <h1 style="margin:0 0 12px;font-size:22px;line-height:1.3;color:#111827;">
                ¡Hola {{ $userName }}!
              </h1>

              <p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#111827;">
                Hemos recibido tu reporte y nuestro equipo de soporte te ha respondido a continuación
              </p>

              <!-- Mensaje de respuesta -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                style="background:#f9fafb;border-radius:12px;padding:20px;margin-bottom:24px;">
                <tr>
                  <td>
                    <p style="margin:0;font-weight:bold;color:#111827;">Respuesta del equipo de soporte:</p>
                    <p style="margin:8px 0 0;font-size:15px;line-height:1.5;color:#000000;">
                      {!! nl2br(e($replyMessage)) !!}
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Mensaje original -->
              <p style="margin:0 0 8px;font-weight:bold;color:#111827;">Tu mensaje original:</p>
              <blockquote style="background:#f9fafb;border-left:4px solid #FFD027;padding:12px 16px;margin:0 0 24px;border-radius:8px;">
                <p style="margin:0;font-size:14px;line-height:1.5;color:#374151;">
                  {!! nl2br(e($originalMessage)) !!}
                </p>
              </blockquote>

              <!-- Firma -->
              <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#111827;">
                Gracias por contactarte con <strong>TukiShop</strong>.
              </p>

              <p style="margin:0 0 24px;font-size:15px;color:#111827;">
                — El equipo de atención de TukiShop
              </p>

              <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0;">

              <p style="margin:0;font-size:12px;line-height:1.6;color:#000000;">
                Este mensaje fue enviado automáticamente por el sistema de soporte de <strong>TukiShop</strong>.<br>
                Si necesitas más ayuda, contáctanos en 
                <a href="mailto:ecomucr2025@gmail.com" style="color:#0ea5e9;text-decoration:none;">ecomucr2025@gmail.com</a>.
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td align="center" style="padding:16px;background:#f9fafb;color:#6b7280;font-size:12px;">
              © {{ date('Y') }} TukiShop — Plataforma de tiendas y comercio electrónico
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
