
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitación al Sistema ERP</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2563eb; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f8fafc; padding: 30px; border-radius: 0 0 8px 8px; }
        .info-box { background: white; padding: 20px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #2563eb; }
        .button { display: inline-block; background: #16a34a; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
        .warning { background: #fef3c7; padding: 15px; border-radius: 6px; border-left: 4px solid #f59e0b; margin: 20px 0; }
        .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <h1>¡Bienvenido al Sistema ERP!</h1>
        <p>{{ $companyName }}</p>
    </div>

    <div class="content">
        <h2>Hola {{ $invitation->first_name }},</h2>
        
        <p><strong>{{ $invitedByName }}</strong> te ha invitado a unirte al sistema ERP de <strong>{{ $companyName }}</strong>.</p>

        <div class="info-box">
            <h3>📋 Detalles de tu Invitación:</h3>
            <ul>
                <li><strong>Nombre:</strong> {{ $invitation->first_name }} {{ $invitation->last_name }}</li>
                <li><strong>Email:</strong> {{ $invitation->email }}</li>
                <li><strong>Empresa:</strong> {{ $companyName }}</li>
                <li><strong>Sucursal:</strong> {{ $branchName }}</li>
                @if($invitation->position)
                <li><strong>Cargo:</strong> {{ $invitation->position }}</li>
                @endif
                <li><strong>Rol asignado:</strong> {{ $invitation->role_name }}</li>
            </ul>
        </div>

        <div class="info-box">
            <h3>🔐 Credenciales de Acceso Temporal:</h3>
            <p><strong>Email:</strong> {{ $invitation->email }}</p>
            <p><strong>Contraseña temporal:</strong> <code style="background: #e5e7eb; padding: 4px 8px; border-radius: 4px; font-family: monospace;">{{ $temporaryPassword }}</code></p>
        </div>

        <p>Para completar el proceso de activación de tu cuenta:</p>
        
        <ol>
            <li>Haz clic en el botón de activación</li>
            <li>Crea tu nueva contraseña segura</li>
            <li>Acepta los términos y condiciones</li>
            <li>¡Comienza a usar el sistema!</li>
        </ol>

        <div style="text-align: center;">
            <a href="{{ $activationUrl }}" class="button">🚀 Activar mi Cuenta</a>
        </div>

        <div class="warning">
            <h4>⚠️ Información Importante:</h4>
            <ul>
                <li>Esta invitación expira el <strong>{{ $expiresAt->format('d/m/Y H:i') }}</strong></li>
                <li>Solo puedes usar este enlace una vez</li>
                <li>Después de activar, deberás crear una nueva contraseña</li>
                <li>Si no solicitaste esta invitación, puedes ignorar este mensaje</li>
            </ul>
        </div>

        <p>Si tienes problemas con el enlace de activación, también puedes copiarlo y pegarlo en tu navegador:</p>
        <p style="word-break: break-all; font-family: monospace; background: #e5e7eb; padding: 10px; border-radius: 4px;">{{ $activationUrl }}</p>

        <div class="footer">
            <p>Este email fue enviado por el sistema ERP de {{ $companyName }}</p>
            <p>Si tienes preguntas, contacta con {{ $invitedByName }} o con el administrador del sistema.</p>
            <hr>
            <p>© {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>