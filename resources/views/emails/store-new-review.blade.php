<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Nueva reseña en tu tienda | TukiShop</title>
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
                   alt="TukiShop" width="50" style="display:block;">
            </td>
          </tr>

          <!-- Contenido principal -->
          <tr>
            <td style="padding:32px 28px;">
              <h1 style="margin:0 0 12px;font-size:22px;line-height:1.3;color:#111827;">
                ¡Has recibido una nueva reseña en tu tienda!
              </h1>

              <p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#111827;">
                <strong>{{ $reviewer_name }}</strong> ha dejado una nueva reseña sobre tu tienda <strong>{{ $store_name }}</strong>.
              </p>

              <!-- Componente tipo LargeReviewComponent -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                style="background:#f9fafb;border-radius:12px;padding:20px;margin-bottom:24px;">
                <tr>
                  <td style="vertical-align:top;padding-right:12px;width:60px;">
                    <img src="{{ $reviewer_image ?? 'https://res.cloudinary.com/dpbghs8ep/image/upload/v1760650000/user_default_tukishop.png' }}"
                         alt="Foto de perfil de {{ $reviewer_name }}"
                         width="48" height="48"
                         style="border-radius:50%;object-fit:cover;border:2px solid #FFD027;">
                  </td>
                  <td style="vertical-align:top;">
                    <p style="margin:0;font-weight:bold;color:#111827;">{{ $reviewer_name }}</p>

                    <!-- Estrellas de calificación (HTML estático porque los correos no renderizan JS) -->
                    <p style="margin:4px 0 12px;">
                      @for ($i = 1; $i <= 5; $i++)
                        @if ($i <= $rating)
                          ⭐
                        @else
                          ☆
                        @endif
                      @endfor
                    </p>

                    <p style="margin:0 0 10px;font-size:15px;line-height:1.5;color:#000000;">
                      "{{ $comment }}"
                    </p>

                    <p style="margin:0;font-size:13px;color:#6b7280;">
                      {{ $date }}
                    </p>
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 24px;text-align:center;">
                <a href="{{ $store_dashboard_url }}"
                  style="background:linear-gradient(135deg,#FFD027,#FF6F3B);color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:8px;display:inline-block;font-weight:bold;">
                  Ver reseña en tu panel
                </a>
              </p>

              <p style="margin:0 0 12px;font-size:13px;line-height:1.6;color:#000000;">
                Ingresa a tu panel de vendedor para responder o revisar todas tus reseñas.
              </p>

              <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0;">

              <p style="margin:0;font-size:12px;line-height:1.6;color:#000000;">
                Si el botón no funciona, copia y pega esta URL en tu navegador:<br>
                <a href="{{ $store_dashboard_url }}" style="color:#0ea5e9;word-break:break-all;">{{ $store_dashboard_url }}</a>
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
