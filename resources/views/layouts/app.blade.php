<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'DentAI') · DentAI</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
@php
    $user = auth()->user();
    // [label, route, active-pattern, roles]
    $links = $user->isPatient()
        ? [['Home', 'portal.home', 'portal.home', ['patient']], ['My medicine', 'portal.medicines', 'portal.medicines', ['patient']]]
        : [
            ['Today', 'dashboard', 'dashboard', ['admin', 'dentist', 'staff']],
            ['Appointments', 'appointments.index', 'appointments.*', ['admin', 'dentist', 'staff']],
            ['Patients', 'patients.index', 'patients.*', ['admin', 'dentist', 'staff']],
            ['Doctors', 'dentists.index', 'dentists.*', ['admin', 'dentist', 'staff']],
            ['Records', 'records.index', 'records.*', ['admin', 'dentist', 'staff']],
            ['Billing', 'billings.index', 'billings.*', ['admin', 'staff']],
            ['Prices', 'treatments.index', 'treatments.*', ['admin']],
        ];
@endphp
    <header class="no-print sticky top-0 z-40 border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-3">
            <div class="flex items-center gap-6">
                <x-logo />
                <nav class="hidden items-center gap-1 lg:flex">
                    @foreach ($links as [$label, $route, $pattern, $roles])
                        @if ($user->hasRole(...$roles))
                            <a href="{{ route($route) }}" class="rounded-lg px-3.5 py-2 text-base font-medium transition {{ request()->routeIs($pattern) ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-100' }}">{{ $label }}</a>
                        @endif
                    @endforeach
                </nav>
            </div>
            <div class="flex items-center gap-3">
                <span class="hidden text-sm text-slate-600 sm:block">{{ $user->name }}</span>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-ghost btn-sm">Log out</button></form>
            </div>
        </div>
        <nav class="flex gap-1 overflow-x-auto border-t border-slate-100 px-4 py-2 lg:hidden">
            @foreach ($links as [$label, $route, $pattern, $roles])
                @if ($user->hasRole(...$roles))
                    <a href="{{ route($route) }}" class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-medium {{ request()->routeIs($pattern) ? 'bg-blue-50 text-blue-700' : 'text-slate-600' }}">{{ $label }}</a>
                @endif
            @endforeach
        </nav>
    </header>

    <main class="mx-auto max-w-4xl px-6 py-10">
        @if (session('status'))
            <div class="no-print mb-6 rounded-xl bg-emerald-50 px-5 py-4 text-base font-medium text-emerald-800">✓ {{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="no-print mb-6 rounded-xl bg-red-50 px-5 py-4 text-base text-red-800">
                @foreach ($errors->all() as $e)<p>• {{ $e }}</p>@endforeach
            </div>
        @endif
        @yield('content')
    </main>
</body>
</html>
