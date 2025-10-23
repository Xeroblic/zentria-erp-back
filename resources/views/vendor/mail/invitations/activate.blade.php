@component('mail::message')

@php($logoUrl = asset('storage/logo192.png'))
<div style="max-width: 520px; margin: 0 auto 14px;">
  <div class="hero-band">
    Invitación de acceso a {{ config('app.name') }}
    <br>
    <small style="opacity:.9; font-weight:400;">Seguridad y desempeño, con diseño Material</small>
    <div style="margin-top:10px;">
      <img src="{{ $logoUrl }}" alt="{{ config('app.name') }}" width="72" height="72" style="display:inline-block;border-radius:12px;border:2px solid rgba(255,255,255,0.3);">
    </div>
  </div>
</div>

@if(!empty($fullName))
<p style="margin:16px 0 8px;">Hola {{ $fullName }},</p>
@endif

# ¡Tienes acceso al ERP!

Has sido invitado como **{{ $role }}**@if(!empty($branchName)) para la sucursal **{{ $branchName }}**@endif.

@component('mail::panel')
¿Qué sigue?
Haz clic en el botón para activar tu cuenta y definir tu contraseña.
@endcomponent

@component('mail::button', ['url' => $activationUrl, 'color' => 'success'])
Activar cuenta ahora
@endcomponent

<div style="height:16px; line-height:16px;">&nbsp;</div>

@if(!empty($expiresAt))
> • Vence: {{ \Carbon\Carbon::parse($expiresAt)->tz(config('app.timezone'))->isoFormat('D [de] MMMM, HH:mm') }}
@endif

<p style="color:#64748b;margin-top:8px;">
Si no solicitaste este acceso, puedes ignorar este correo.
</p>

Gracias,
El equipo de **{{ config('app.name') }}**
@endcomponent
