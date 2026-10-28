@extends('layouts.app')
@section('title', $appointment->patient->full_name)

@section('content')
@php
    $user = auth()->user();
    $done = $appointment->isDone();
    $patient = $appointment->patient;
@endphp

<a href="{{ $done ? route('records.index') : route('appointments.index') }}" class="text-slate-500 hover:text-slate-800">← Back</a>

<div class="mt-3 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1>{{ $patient->full_name }}</h1>
        <p class="mt-1 text-slate-600">{{ ucfirst(str_replace('_', ' ', $patient->gender)) }} · {{ $patient->phone_number }} · Ref DA-{{ str_pad($appointment->id, 5, '0', STR_PAD_LEFT) }}</p>
        <div class="mt-3 flex gap-2"><x-badge :value="$appointment->urgency" /><x-badge :value="$appointment->status" /></div>
    </div>
    @unless ($done || $appointment->status === 'cancelled')
        <form method="POST" action="{{ route('appointments.done', $appointment) }}" onsubmit="return confirm('Mark this patient as done? The visit will move to Records.')">
            @csrf<button class="btn btn-green">✓ Mark as done</button>
        </form>
    @endunless
</div>

@if ($done)
    <div class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-emerald-50 px-5 py-4 text-emerald-900">
        <span>✓ This visit is done and saved in Records ({{ $appointment->completed_at?->format('M j, Y') }}).</span>
        <form method="POST" action="{{ route('appointments.reopen', $appointment) }}">@csrf<button class="btn btn-ghost btn-sm">Re-open</button></form>
    </div>
@endif

<div class="mt-8 space-y-6">

    {{-- What the patient said --}}
    <div class="card">
        <h2>What the patient said</h2>
        <p class="mt-3 text-lg font-semibold text-slate-900">{{ $appointment->problem_title }}</p>
        <p class="hint">{{ $appointment->problemType?->name }} · Pain {{ $appointment->pain_level ?? '—' }}/10@if($appointment->preferred_date) · Prefers {{ $appointment->preferred_date->format('D, M j') }}@endif</p>
        <p class="mt-3 whitespace-pre-line text-slate-700">{{ $appointment->description }}</p>
        @if ($patient->medicalHistories->isNotEmpty())
            <div class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-amber-900">
                <p class="font-semibold">Medical notes</p>
                @foreach ($patient->medicalHistories as $h)<p>• {{ ucfirst(str_replace('_', ' ', $h->history_type)) }}: {{ $h->description }}</p>@endforeach
            </div>
        @endif
    </div>

    @unless ($done)
    {{-- Step 1: suggestions --}}
    @if ($pending->isNotEmpty())
        <div class="card border-blue-200 bg-blue-50/40">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="flex items-center gap-3"><span class="step">1</span> Suggested next steps</h2>
                @if ($pending->count() > 1)
                    <form method="POST" action="{{ route('suggestions.approve-all', $appointment) }}">@csrf<button class="btn btn-primary btn-sm">Approve all</button></form>
                @endif
            </div>
            <p class="hint mt-1">Based on what the patient wrote. Nothing is added until you approve it.</p>
            <ul class="mt-4 space-y-3">
                @foreach ($pending as $s)
                    <li class="rounded-xl border border-slate-200 bg-white p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <x-badge :value="$s->priority" />
                                <p class="mt-2 font-semibold text-slate-900">{{ $s->title }}</p>
                                @if ($s->detail)<p class="text-slate-600">{{ $s->detail }}</p>@endif
                            </div>
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('suggestions.approve', $s) }}">@csrf<button class="btn btn-primary btn-sm">Approve</button></form>
                                <form method="POST" action="{{ route('suggestions.dismiss', $s) }}">@csrf<button class="btn btn-ghost btn-sm">Skip</button></form>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Step 2: schedule --}}
    <form method="POST" action="{{ route('appointments.update', $appointment) }}" class="card space-y-5">
        @csrf @method('PUT')
        <h2 class="flex items-center gap-3"><span class="step">{{ $pending->isNotEmpty() ? 2 : 1 }}</span> Set the visit</h2>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="label" for="appointment_date">Day</label>
                <input id="appointment_date" type="date" name="appointment_date" value="{{ old('appointment_date', $appointment->appointment_date?->format('Y-m-d')) }}" class="input">
            </div>
            <div>
                <label class="label" for="dentist_id">Doctor</label>
                <select id="dentist_id" name="dentist_id" class="input">
                    <option value="">Choose a doctor…</option>
                    @foreach ($dentists as $d)
                        <option value="{{ $d->id }}" @selected(old('dentist_id', $appointment->dentist_id) == $d->id)>{{ $d->full_name }}@if($d->specialization) — {{ $d->specialization }}@endif</option>
                    @endforeach
                </select>
                <p id="off-note" class="mt-1.5 text-sm font-medium text-amber-700"></p>
            </div>
            <div>
                <label class="label" for="start_time">From</label>
                <input id="start_time" type="time" name="start_time" value="{{ old('start_time', $appointment->start_time ? substr($appointment->start_time, 0, 5) : '') }}" class="input">
            </div>
            <div>
                <label class="label" for="end_time">Until</label>
                <input id="end_time" type="time" name="end_time" value="{{ old('end_time', $appointment->end_time ? substr($appointment->end_time, 0, 5) : '') }}" class="input">
            </div>
        </div>
        <p class="hint -mt-2">Suggested length: {{ $defaultMinutes }} minutes (the “until” time fills in for you).</p>

        <details class="rounded-xl bg-slate-50 px-4 py-3">
            <summary class="cursor-pointer font-semibold text-slate-700">More options (priority, notes)</summary>
            <div class="mt-4 space-y-4">
                <div>
                    <label class="label" for="urgency">Priority</label>
                    <select id="urgency" name="urgency" class="input">@foreach (\App\Models\Appointment::URGENCIES as $u)<option value="{{ $u }}" @selected($appointment->urgency === $u)>{{ ['emergency' => 'Urgent', 'high' => 'High priority', 'normal' => 'Normal', 'low' => 'Low'][$u] }}</option>@endforeach</select>
                </div>
                <div>
                    <label class="label" for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="4" class="input">{{ old('notes', $appointment->notes) }}</textarea>
                </div>
            </div>
        </details>

        <button class="btn btn-primary">Save visit</button>
    </form>
    @endunless

    {{-- Treatments --}}
    <div class="card">
        <h2 class="flex items-center gap-3">@unless($done)<span class="step">{{ $pending->isNotEmpty() ? 3 : 2 }}</span>@endunless Treatment</h2>
        <ul class="mt-4 divide-y divide-slate-100">
            @forelse ($appointment->treatments as $line)
                <li class="flex items-center justify-between gap-3 py-3">
                    <span class="font-medium text-slate-900">{{ $line->treatment->treatment_name }}</span>
                    <span class="flex items-center gap-4">
                        <span class="text-slate-600"><x-money :value="$line->cost_at_time" /></span>
                        @unless ($done)
                            <form method="POST" action="{{ route('appointments.treatments.destroy', [$appointment, $line]) }}" onsubmit="return confirm('Remove this treatment?')">@csrf @method('DELETE')<button class="text-sm font-semibold text-red-600 hover:underline">Remove</button></form>
                        @endunless
                    </span>
                </li>
            @empty
                <li class="py-3 text-slate-500">No treatment added yet.</li>
            @endforelse
        </ul>
        @unless ($done)
            <form method="POST" action="{{ route('appointments.treatments.store', $appointment) }}" class="mt-4 flex gap-3">
                @csrf
                <select name="treatment_id" class="input" required>
                    <option value="">Add a treatment…</option>
                    @foreach ($treatments as $t)<option value="{{ $t->id }}">{{ $t->treatment_name }} — ₱{{ number_format($t->base_cost, 0) }}</option>@endforeach
                </select>
                <button class="btn btn-ghost">Add</button>
            </form>
        @endunless
    </div>

    {{-- Medicine --}}
    <div class="card">
        <h2>Medicine</h2>
        <p class="hint">The patient sees this in their account, with a calendar and reminders.</p>
        <ul class="mt-4 space-y-3">
            @forelse ($appointment->prescriptions as $rx)
                <li class="flex items-start justify-between gap-3 rounded-xl bg-slate-50 px-4 py-3">
                    <div>
                        <p class="font-semibold text-slate-900">💊 {{ $rx->medication_name }}</p>
                        <p class="text-slate-600">{{ $rx->summary() }}</p>
                        @if ($rx->instructions)<p class="text-slate-500">{{ $rx->instructions }}</p>@endif
                    </div>
                    @if ($user->hasRole('dentist'))
                        <form method="POST" action="{{ route('prescriptions.destroy', [$appointment, $rx]) }}" onsubmit="return confirm('Remove this medicine?')">@csrf @method('DELETE')<button class="text-sm font-semibold text-red-600 hover:underline">Remove</button></form>
                    @endif
                </li>
            @empty
                <li class="text-slate-500">No medicine prescribed.</li>
            @endforelse
        </ul>

        @if ($user->hasRole('dentist'))
            <details class="mt-4 rounded-xl border border-slate-200 px-4 py-3" @if($errors->has('times') || $errors->has('medication_name')) open @endif>
                <summary class="cursor-pointer font-semibold text-blue-700">+ Add medicine</summary>
                <form method="POST" action="{{ route('prescriptions.store', $appointment) }}" class="mt-4 space-y-4">
                    @csrf
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="label">Medicine</label><input name="medication_name" class="input" required placeholder="e.g. Pain reliever"></div>
                        <div><label class="label">How much each time</label><input name="dosage" class="input" required placeholder="e.g. 1 tablet"></div>
                    </div>
                    <div>
                        <label class="label">When to take it</label>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($timeSlots as $time => $label)
                                <label class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-3"><input type="checkbox" name="times[]" value="{{ $time }}" class="size-5 rounded border-slate-300"> {{ $label }}</label>
                            @endforeach
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="label">For how many days</label><input type="number" name="duration_days" min="1" max="90" value="5" class="input" required></div>
                        <div><label class="label">Starting</label><input type="date" name="start_date" value="{{ today()->toDateString() }}" class="input"></div>
                    </div>
                    <div><label class="label">Instructions <span class="font-normal text-slate-500">(optional)</span></label><input name="instructions" class="input" placeholder="e.g. Take after eating"></div>
                    <button class="btn btn-primary">Save medicine</button>
                </form>
            </details>
        @endif
    </div>

    {{-- Payment --}}
    @if ($user->hasRole('admin', 'staff'))
        <div class="card">
            <h2>Payment</h2>
            @if ($appointment->billing)
                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-lg"><x-money :value="$appointment->billing->total_amount" /> <x-badge :value="$appointment->billing->payment_status" class="ml-2" /></p>
                    <a href="{{ route('billings.show', $appointment->billing) }}" class="btn btn-ghost btn-sm">Open bill</a>
                </div>
            @else
                <form method="POST" action="{{ route('billings.store', $appointment) }}" class="mt-3 space-y-3">
                    @csrf
                    <label class="flex items-center gap-3 text-slate-700"><input type="checkbox" name="allow_online" value="1" class="size-5 rounded border-slate-300"> Let the patient pay online</label>
                    <button class="btn btn-ghost">Create bill from treatments</button>
                </form>
            @endif
        </div>
    @endif

    {{-- Patient login --}}
    <div class="card">
        <h2>Patient account &amp; printout</h2>
        @if ($patient->user)
            <p class="mt-2 text-slate-600">An account was created automatically. Give these details to the patient, or print the slip for them.</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl bg-slate-50 px-4 py-3"><p class="hint">Username (phone)</p><p class="text-xl font-bold tracking-wide">{{ $patient->user->username }}</p></div>
                <div class="rounded-xl bg-slate-50 px-4 py-3"><p class="hint">Access code</p><p class="text-xl font-bold tracking-widest">{{ $patient->access_code }}</p></div>
            </div>
        @endif
        <div class="mt-4 flex flex-wrap gap-3">
            <a href="{{ route('appointments.slip', $appointment) }}" target="_blank" class="btn btn-primary btn-sm">🖨 Print slip</a>
            <form method="POST" action="{{ route('patients.reset-code', $patient) }}" onsubmit="return confirm('Make a new access code? The old one will stop working.')">@csrf<button class="btn btn-ghost btn-sm">New code</button></form>
            <a href="{{ route('patients.show', $patient) }}" class="btn btn-ghost btn-sm">Patient profile</a>
        </div>
    </div>

    @unless ($done || $appointment->status === 'cancelled')
        <div class="text-center">
            <form method="POST" action="{{ route('appointments.cancel', $appointment) }}" onsubmit="return confirm('Cancel this visit?')">@csrf<button class="text-sm font-semibold text-red-600 hover:underline">Cancel this visit</button></form>
        </div>
    @endunless
</div>

<script>
    // Hide doctors who are off on the chosen day.
    const timeOff = @json($timeOff);
    const dateEl = document.getElementById('appointment_date');
    const doc = document.getElementById('dentist_id');
    const note = document.getElementById('off-note');
    const start = document.getElementById('start_time'), end = document.getElementById('end_time');

    function filterDoctors() {
        if (!dateEl || !doc) return;
        const day = dateEl.value, off = [];
        [...doc.options].forEach(o => {
            if (!o.value) return;
            const away = day && (timeOff[o.value] || []).some(([a, b]) => day >= a && day <= b);
            o.hidden = away; o.disabled = away;
            if (away) off.push(o.textContent.split(' — ')[0]);
        });
        if (doc.selectedOptions[0]?.disabled) doc.value = '';
        note.textContent = off.length ? 'Not available that day: ' + off.join(', ') : '';
    }
    dateEl?.addEventListener('change', filterDoctors); filterDoctors();

    start?.addEventListener('change', () => {
        if (!start.value || end.value) return;
        const [h, m] = start.value.split(':').map(Number), t = h * 60 + m + {{ $defaultMinutes }};
        end.value = String(Math.floor(t / 60) % 24).padStart(2, '0') + ':' + String(t % 60).padStart(2, '0');
    });
</script>
@endsection
