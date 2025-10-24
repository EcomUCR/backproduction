<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>¡Tu tienda ha sido verificada! | TukiShop</title>
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
                   alt="TukiShop" width="60" style="display:block;">
            </td>
          </tr>

          <!-- Contenido principal -->
          <tr>
            <td style="padding:32px 28px;">
              <h1 style="margin:0 0 12px;font-size:22px;line-height:1.3;color:#111827;">
                🎉 ¡Tu tienda ha sido verificada!
              </h1>

              <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#111827;">
                ¡Felicidades, <strong>{{ $owner_name }}</strong>!<br>
                Nos complace informarte que tu tienda <strong>{{ $store_name }}</strong> ha sido revisada y verificada oficialmente en <strong>TukiShop</strong>.
              </p>

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                style="background:#f9fafb;border-radius:8px;padding:16px;margin-bottom:20px;">
                <tr>
                  <td style="font-size:14px;color:#111827;padding-bottom:6px;">
                    <strong>Nombre de la tienda:</strong> {{ $store_name }}
                  </td>
                </tr>
                <tr>
                  <td style="font-size:14px;color:#111827;padding-bottom:6px;">
                    <strong>Propietario:</strong> {{ $owner_name }}
                  </td>
                </tr>
                <tr>
                  <td style="font-size:14px;color:#111827;">
                    <strong>Fecha de verificación:</strong> {{ $verification_date }}
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 24px;text-align:center;">
                <a href="{{ $dashboard_url }}"
                  style="background:linear-gradient(135deg,#FFD027,#FF6F3B);color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:8px;display:inline-block;font-weight:bold;">
                  Ir a mi panel de tienda
                </a>
              </p>

              <p style="margin:0 0 12px;font-size:13px;line-height:1.6;color:#000000;">
                A partir de ahora, tus productos y perfil mostrarán el distintivo de verificación ✅, brindando mayor confianza a tus clientes.
              </p>

              <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0;">

              <p style="margin:0;font-size:12px;line-height:1.6;color:#000000;">
                Si el botón no funciona, copia y pega esta URL en tu navegador:<br>
                <a href="{{ $dashboard_url }}" style="color:#0ea5e9;word-break:break-all;">{{ $dashboard_url }}</a>
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td align="center" style="padding:16px;background:#f9fafb;color:#6b7280;font-size:12px;">
              © {{ date('Y') }} TukiShop — Sistema de gestión de tiendas
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
