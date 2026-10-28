@extends('layouts.marketing')
@section('title', 'Log in · DentAI')

@section('content')
<section class="mx-auto max-w-md px-6 py-16">
    <h1 class="text-center">Log in</h1>
    <p class="mt-2 text-center text-slate-600">Patients: use your phone number and the code we gave you.<br>Clinic staff: use your email.</p>

    <form method="POST" action="{{ route('login') }}" class="card mt-8 space-y-5">
        @csrf
        <div>
            <label class="label" for="login">Phone number or email</label>
            <input id="login" name="login" value="{{ old('login') }}" class="input" required autofocus autocomplete="username">
            @error('login')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="password">Password or access code</label>
            <input id="password" type="password" name="password" class="input" required autocomplete="current-password">
        </div>
        <label class="flex items-center gap-2 text-slate-600"><input type="checkbox" name="remember" class="size-4 rounded border-slate-300"> Keep me logged in</label>
        <button class="btn btn-primary w-full">Log in</button>
    </form>

    @if (app()->isLocal())
        <div class="mt-6 rounded-xl bg-slate-100 p-4 text-sm text-slate-600">
            <p class="font-semibold">Demo logins (local only) — password: <code>password</code></p>
            <p>admin@dentai.test · dentist@dentai.test · assistant@dentai.test</p>
            <p class="mt-1">Demo patient: phone <code>09175550200</code> — the 6-digit code is on the visit’s slip.</p>
        </div>
    @endif
</section>
@endsection
