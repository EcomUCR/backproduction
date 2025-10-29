<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Actualización de tu tienda | TukiShop</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
</head>
<body style="margin:0;padding:0;background:#f6f7fb;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f7fb;padding:24px 0;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0"
          style="background:#ffffff;border-radius:12px;overflow:hidden;font-family:Arial,Helvetica,sans-serif;">

          <!-- Header -->
          <tr>
            <td align="center" style="padding:24px;background:linear-gradient(135deg,#FFD027,#FF6F3B,#5B2A86);">
              <img src="https://res.cloudinary.com/dpbghs8ep/image/upload/v1760314036/Tuki_vitzem.png"
                   alt="TukiShop" width="55" style="display:block;">
            </td>
          </tr>

          <!-- Contenido -->
          <tr>
            <td style="padding:32px 28px;">
              <h1 style="margin:0 0 12px;font-size:22px;color:#111827;">⚙️ Actualización en tu tienda</h1>

              <p style="margin:0 0 16px;font-size:15px;color:#111827;">
                Hola <strong>{{ $owner_name }}</strong>, te informamos que un administrador de
                <strong>TukiShop</strong> ha realizado modificaciones en tu tienda <strong>{{ $store_name }}</strong>.
              </p>

              <p style="margin:0 0 16px;font-size:15px;color:#111827;">
                Si tú solicitaste esta acción, no necesitas hacer nada más. Si no reconoces esta actualización,
                por favor comunícate con nuestro equipo de soporte.
              </p>

              <p style="text-align:center;margin:20px 0;">
                <a href="{{ $dashboard_url }}"
                   style="background:linear-gradient(135deg,#FFD027,#FF6F3B);color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:8px;display:inline-block;font-weight:bold;">
                   Ver mi tienda
                </a>
              </p>

              <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0;">
              <p style="margin:0;font-size:12px;color:#6b7280;">
                Este correo fue generado automáticamente por el sistema de administración de TukiShop.
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td align="center" style="padding:16px;background:#f9fafb;color:#6b7280;font-size:12px;">
              © {{ date('Y') }} TukiShop — Tu plataforma de ventas local 💛
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
