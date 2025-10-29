<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>¡Bienvenido a TukiShop!</title>
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
                   alt="TukiShop" width="55" style="display:block;">
            </td>
          </tr>

          <!-- Contenido principal -->
          <tr>
            <td style="padding:32px 28px;">
              <h1 style="margin:0 0 12px;font-size:22px;line-height:1.3;color:#111827;">
                ¡Bienvenido a <strong>TukiShop</strong>! 🎉
              </h1>

              <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#111827;">
                Hola <strong>{{ $name }}</strong>, gracias por registrarte en <strong>TukiShop</strong>.
              </p>

              @if($role === 'SELLER')
                <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#111827;">
                  Tu cuenta de vendedor está lista para comenzar. Puedes ingresar al panel para configurar tu tienda, añadir productos y empezar a vender hoy mismo. 🚀
                </p>
              @elseif($role === 'CUSTOMER')
                <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#111827;">
                  ¡Empieza a explorar miles de productos únicos de tiendas locales y disfruta una experiencia de compra diferente! 🛍️
                </p>
              @else
                <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#111827;">
                  Tu cuenta ha sido creada exitosamente. ¡Ya puedes acceder al panel de control de TukiShop!
                </p>
              @endif

              <p style="margin:0 0 24px;text-align:center;">
                <a href="{{ $login_url }}"
                  style="background:linear-gradient(135deg,#FFD027,#FF6F3B);color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:8px;display:inline-block;font-weight:bold;">
                  Ir al panel
                </a>
              </p>

              <p style="margin:0 0 12px;font-size:14px;line-height:1.6;color:#111827;">
                Si tienes alguna duda, recuerda que nuestro equipo de soporte está listo para ayudarte.
              </p>

              <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0;">

              <p style="margin:0;font-size:12px;line-height:1.6;color:#000000;">
                Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
                <a href="{{ $login_url }}" style="color:#0ea5e9;word-break:break-all;">{{ $login_url }}</a>
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
