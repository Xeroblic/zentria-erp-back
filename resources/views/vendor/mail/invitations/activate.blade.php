@component('mail::message')

@php($logoUrl = asset('storage/logo192.png'))
<p style="text-align:center;margin:0 0 16px;">
    <img src="{{ $logoUrl }}" alt="{{ config('app.name') }}" width="96" height="96" style="display:inline-block;border-radius:8px;">
</p>

@if(!empty($fullName))
<p style="margin:0 0 8px;">Hola {{ $fullName }},</p>
@endif

# ¡Te dieron acceso al ERP!

Has sido invitado como **{{ $role }}**@if(!empty($branchName)) para la sucursal **{{ $branchName }}**@endif.

@component('mail::panel')
¿Qué sigue?
Haz clic en el botón para activar tu cuenta y definir tu contraseña.
@endcomponent

@component('mail::button', ['url' => $activationUrl])
Activar tu cuenta
@endcomponent

@if(!empty($expiresAt))
> • Vence: {{ \Carbon\Carbon::parse($expiresAt)->tz(config('app.timezone'))->isoFormat('D [de] MMMM, HH:mm') }}
@endif

Si no solicitaste este acceso, puedes ignorar este correo.

Gracias,
El equipo de **{{ config('app.name') }}**
@endcomponent

