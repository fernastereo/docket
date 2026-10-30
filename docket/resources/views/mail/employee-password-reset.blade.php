<x-mail::message>
# Hola, {{ $employeeName }}

Te enviamos este enlace para que elijas una contraseña nueva en **{{ $tenantName }}**:

<x-mail::button :url="$url">
Elegir una contraseña nueva
</x-mail::button>

El enlace sirve una sola vez y vence en {{ $validDays }} días. Tu contraseña actual sigue siendo válida hasta que elijas la nueva. Si no esperabas este correo, ignóralo.

CuraduriAPP
</x-mail::message>
