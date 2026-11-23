<x-mail::message>
# Hola, {{ $userName }}

Recibimos una solicitud para restablecer tu contraseña del panel de CuraduriAPP:

<x-mail::button :url="$url">
Elegir una contraseña nueva
</x-mail::button>

El enlace sirve una sola vez y vence en {{ $validMinutes }} minutos. Tu contraseña actual sigue siendo válida hasta que elijas la nueva, y tu verificación en dos pasos no cambia. Si no fuiste tú, ignora este correo.

CuraduriAPP
</x-mail::message>
