<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Visit slip · {{ $appointment->patient->full_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white">
<div class="mx-auto max-w-2xl px-6 py-8">
    <div class="no-print mb-6 flex gap-3">
        <button onclick="window.print()" class="btn btn-primary">🖨 Print</button>
        <a href="{{ route('appointments.show', $appointment) }}" class="btn btn-ghost">Back</a>
    </div>

    <div class="flex items-center justify-between border-b border-slate-300 pb-4">
        <x-logo />
        <p class="text-right text-sm text-slate-600">Ref DA-{{ str_pad($appointment->id, 5, '0', STR_PAD_LEFT) }}<br>{{ now()->format('M j, Y') }}</p>
    </div>

    <h1 class="mt-6 text-2xl">{{ $appointment->patient->full_name }}</h1>

    <h2 class="mt-6 text-lg">Your visit</h2>
    @if ($appointment->appointment_date && $appointment->start_time)
        <p class="mt-1 text-xl font-semibold">{{ $appointment->appointment_date->format('l, F j, Y') }} at {{ \Illuminate\Support\Carbon::parse($appointment->start_time)->format('g:i A') }}</p>
        <p class="text-slate-600">with {{ $appointment->dentist?->full_name ?? 'our dentist' }}</p>
    @else
        <p class="mt-1 text-slate-600">We will contact you to confirm the day and time.</p>
    @endif

    <h2 class="mt-6 text-lg">Your account</h2>
    <div class="mt-2 rounded-xl border-2 border-slate-800 p-4">
        <p>Log in at <strong>{{ url('/login') }}</strong></p>
        <p class="mt-2">Phone number: <strong class="text-xl tracking-wide">{{ $user->username }}</strong></p>
        <p>Access code: <strong class="text-xl tracking-widest">{{ $appointment->patient->access_code }}</strong></p>
        <p class="mt-2 text-sm text-slate-600">See your medicine calendar, set reminders and pay online (if available).</p>
    </div>

    @if ($appointment->prescriptions->isNotEmpty())
        <h2 class="mt-6 text-lg">Your medicine</h2>
        <ul class="mt-2 space-y-3">
            @foreach ($appointment->prescriptions as $rx)
                <li class="rounded-xl border border-slate-300 p-4">
                    <p class="text-lg font-semibold">{{ $rx->medication_name }}</p>
                    <p>{{ $rx->summary() }}</p>
                    <p class="text-sm text-slate-600">From {{ $rx->start_date->format('M j') }} to {{ $rx->endDate()->format('M j') }}@if($rx->instructions) · {{ $rx->instructions }}@endif</p>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($appointment->treatments->isNotEmpty())
        <h2 class="mt-6 text-lg">Treatment &amp; cost</h2>
        <table class="table mt-2">
            @foreach ($appointment->treatments as $line)
                <tr><td>{{ $line->treatment->treatment_name }}</td><td class="text-right"><x-money :value="$line->cost_at_time" /></td></tr>
            @endforeach
            <tr><td class="font-bold">Total</td><td class="text-right font-bold"><x-money :value="$appointment->treatments->sum('cost_at_time')" /></td></tr>
        </table>
        <p class="mt-2 text-sm text-slate-600">
            @if ($appointment->billing?->allow_online) You can pay online from your account, or at the clinic. @else Please pay at the clinic. @endif
        </p>
    @endif
</div>
</body>
</html>
