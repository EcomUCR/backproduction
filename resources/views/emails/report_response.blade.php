<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Nuevo reporte recibido | TukiShop</title>
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
                📩 Nuevo reporte recibido
              </h1>

              <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#111827;">
                Se ha registrado un nuevo reporte en el sistema de <strong>TukiShop</strong>.  
                Por favor, revisa los detalles a continuación y procede con la gestión correspondiente.
              </p>

              <!-- Detalles del reporte -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border-radius:12px;padding:20px;margin-bottom:24px;">
                <tr>
                  <td>
                    <p style="margin:0;font-weight:bold;color:#111827;">Número de reporte:</p>
                    <p style="margin:4px 0 12px;font-size:15px;color:#000000;">{{ $report->report_number }}</p>

                    @if($report->order_id)
                      <p style="margin:0;font-weight:bold;color:#111827;">Pedido relacionado:</p>
                      <p style="margin:4px 0 12px;font-size:15px;color:#000000;">#{{ $report->order_id }}</p>
                    @endif

                    <p style="margin:0;font-weight:bold;color:#111827;">Estado actual:</p>
                    <p style="margin:4px 0 12px;font-size:15px;color:#000000;">
                      @switch($report->status)
                        @case('PENDING') Pendiente de revisión 🕐 @break
                        @case('IN_REVIEW') En revisión 🔎 @break
                        @case('RESOLVED') Resuelto ✅ @break
                        @case('REJECTED') Rechazado ❌ @break
                        @default Pendiente
                      @endswitch
                    </p>

                    <p style="margin:0;font-weight:bold;color:#111827;">Nombre del usuario:</p>
                    <p style="margin:4px 0 12px;font-size:15px;color:#000000;">{{ $report->name }}</p>

                    <p style="margin:0;font-weight:bold;color:#111827;">Email:</p>
                    <p style="margin:4px 0 12px;font-size:15px;color:#0ea5e9;">{{ $report->email }}</p>

                    <p style="margin:0;font-weight:bold;color:#111827;">Descripción:</p>
                    <p style="margin:4px 0 12px;font-size:14px;line-height:1.5;color:#374151;">
                      {!! nl2br(e(Str::limit($report->description, 300))) !!}
                    </p>

                    @if($report->images && count(json_decode($report->images)) > 0)
                      <p style="margin:0 0 8px;font-weight:bold;color:#111827;">Evidencias:</p>
                      <div style="margin-bottom:16px;display:flex;flex-wrap:wrap;gap:10px;">
                        @foreach(json_decode($report->images) as $img)
                          <a href="{{ $img }}" target="_blank">
                            <img src="{{ $img }}" alt="Evidencia" width="90" style="border-radius:8px;border:1px solid #e5e7eb;">
                          </a>
                        @endforeach
                      </div>
                    @endif

                    <p style="margin:0;font-size:13px;color:#6b7280;">
                      Fecha: {{ $report->created_at->format('d/m/Y, g:i a') }}
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Botón -->
              <p style="margin:0 0 24px;text-align:center;">
                <a href="{{ $adminPanelUrl }}"
                   style="background:linear-gradient(135deg,#FFD027,#FF6F3B);color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:8px;display:inline-block;font-weight:bold;">
                   Ver en panel administrativo
                </a>
              </p>

              <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0;">

              <p style="margin:0;font-size:12px;line-height:1.6;color:#000000;">
                Este mensaje fue generado automáticamente por el sistema de reportes de <strong>TukiShop</strong>.<br>
                No es necesario responder a este correo.
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td align="center" style="padding:16px;background:#f9fafb;color:#6b7280;font-size:12px;">
              © {{ date('Y') }} TukiShop — Plataforma de comercio electrónico y tiendas locales
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
