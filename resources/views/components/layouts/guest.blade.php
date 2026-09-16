@props([
    'title' => app_name(),
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · {{ app_name() }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-ink text-white">
    <div class="absolute inset-x-0 top-0 h-0.5 bg-brand"></div>

    <main class="relative mx-auto flex min-h-screen w-full max-w-md items-center px-4 py-10">
        {{ $slot }}
    </main>
</body>
</html>
