@component('mail::message')

{!! $correo->cabecera_mail !!}
{!! $correo->cuerpo_mail !!}
{!! $correo->pie_mail !!}

@if($correo->url_innovating !== null)
@component('mail::button', ['url' => $correo->url_innovating])
Ir a Innovating.works
@endcomponent
@endif

Gracias,<br>
Equipo innovating.works
@endcomponent

