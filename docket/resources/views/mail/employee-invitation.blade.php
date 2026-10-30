<x-mail::message>
# Hola, {{ $employeeName }}

**{{ $tenantName }}** te creó una cuenta en CuraduriAPP. Para empezar, elige tu contraseña:

<x-mail::button :url="$url">
Elegir mi contraseña
</x-mail::button>

El enlace sirve una sola vez y vence en {{ $validDays }} días. Si no esperabas este correo, ignóralo: sin la contraseña nadie puede entrar a tu cuenta.

CuraduriAPP
</x-mail::message>
