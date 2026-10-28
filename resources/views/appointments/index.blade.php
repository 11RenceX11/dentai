@extends('layouts.app')
@section('title', 'Appointments')

@section('content')
<h1>Appointments</h1>
<p class="mt-1 text-slate-600">Finished visits are kept under <a href="{{ route('records.index') }}" class="font-semibold text-blue-600 hover:underline">Records</a>.</p>

<div class="mt-6 flex flex-wrap gap-2">
    @foreach (['all' => 'All open', 'requested' => 'New requests', 'scheduled' => 'Scheduled'] as $key => $label)
        <a href="{{ route('appointments.index', ['tab' => $key, 'q' => request('q')]) }}"
           class="rounded-full px-5 py-2 text-base font-semibold {{ $tab === $key ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-300 hover:bg-slate-100' }}">{{ $label }} <span class="opacity-70">{{ $counts[$key] }}</span></a>
    @endforeach
</div>

<form method="GET" class="mt-4 flex gap-3">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <input name="q" value="{{ request('q') }}" placeholder="Search by name or phone…" class="input">
    <button class="btn btn-ghost">Search</button>
</form>

<div class="mt-6 space-y-3">
    @forelse ($appointments as $a)
        <a href="{{ route('appointments.show', $a) }}" class="card block p-5 transition hover:border-blue-300">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-lg font-semibold text-slate-900">{{ $a->patient->full_name }}</p>
                    <p class="truncate text-slate-600">{{ $a->problem_title }}</p>
                    <p class="mt-1 text-sm text-slate-500">
                        @if ($a->appointment_date) 📅 {{ $a->appointment_date->format('D, M j') }} {{ $a->start_time ? substr($a->start_time, 0, 5) : '' }} @else Not scheduled yet @endif
                        · {{ $a->dentist?->full_name ?? 'No doctor yet' }}
                    </p>
                </div>
                <div class="flex flex-col items-end gap-2">
                    <x-badge :value="$a->urgency" />
                    <x-badge :value="$a->status" />
                </div>
            </div>
        </a>
    @empty
        <div class="card text-center text-slate-500">Nothing here.</div>
    @endforelse
</div>
<div class="mt-6 empty:hidden">{{ $appointments->links() }}</div>
@endsection
