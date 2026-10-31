<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    {{-- Aplica el tema guardado antes de pintar (sin destello). Lleva el nonce de la CSP. --}}
    <script nonce="{{ Vite::cspNonce() }}">
        (function () {
            try {
                var t = localStorage.getItem('theme');
                if (t === 'dark' || ((t === null || t === 'system') && matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
</head>
<body>
    <div id="app" data-context="{{ $context }}" @if ($tenantName) data-tenant-name="{{ $tenantName }}" @endif></div>
</body>
</html>
