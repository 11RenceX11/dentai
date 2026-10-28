<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'DentAI')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white">
    <header class="border-b border-slate-200">
        <div class="mx-auto flex h-20 max-w-5xl items-center justify-between px-6">
            <x-logo />
            <div class="flex items-center gap-3">
                @auth
                    <a href="{{ route(auth()->user()->homeRoute()) }}" class="btn btn-primary btn-sm">My account</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-ghost btn-sm">Log in</a>
                    <a href="{{ route('booking.create') }}" class="btn btn-primary btn-sm">Book a visit</a>
                @endauth
            </div>
        </div>
    </header>

    <main>@yield('content')</main>

    <footer class="border-t border-slate-200 py-8 text-center text-sm text-slate-500">&copy; {{ date('Y') }} DentAI</footer>
</body>
</html>
