@extends('layouts.app')
@section('title', 'Today')

@section('content')
<h1>Hello, {{ explode(' ', str_replace('Dr. ', '', auth()->user()->name))[0] }} 👋</h1>
<p class="mt-1 text-slate-600">{{ now()->format('l, F j') }}</p>

<div class="mt-8 grid gap-4 sm:grid-cols-3">
    <a href="{{ route('appointments.index', ['tab' => 'requested']) }}" class="card block transition hover:border-blue-300">
        <p class="hint">New requests</p><p class="mt-1 text-4xl font-bold text-amber-600">{{ $newCount }}</p>
    </a>
    <div class="card"><p class="hint">Visits today</p><p class="mt-1 text-4xl font-bold text-blue-600">{{ $today->count() }}</p></div>
    <div class="card"><p class="hint">Painful / urgent cases</p><p class="mt-1 text-4xl font-bold text-red-600">{{ $urgentCount }}</p></div>
</div>

<h2 class="mt-10">Needs your attention</h2>
<p class="hint">Most urgent first. Open one to arrange the visit.</p>
<div class="mt-4 space-y-3">
    @forelse ($requests as $a)
        <a href="{{ route('appointments.show', $a) }}" class="card flex flex-wrap items-center justify-between gap-3 p-5 transition hover:border-blue-300">
            <div class="min-w-0">
                <p class="text-lg font-semibold text-slate-900">{{ $a->patient->full_name }}</p>
                <p class="truncate text-slate-600">{{ $a->problem_title }}@if($a->pain_level !== null) · pain {{ $a->pain_level }}/10 @endif</p>
            </div>
            <div class="flex items-center gap-2">
                @if ($a->pending_suggestions_count)<span class="text-sm font-medium text-amber-700">{{ $a->pending_suggestions_count }} suggestions</span>@endif
                <x-badge :value="$a->urgency" />
            </div>
        </a>
    @empty
        <div class="card text-center text-slate-500">All caught up — no new requests. 🎉</div>
    @endforelse
</div>

<h2 class="mt-10">Today’s visits</h2>
<div class="mt-4 space-y-3">
    @forelse ($today as $a)
        <a href="{{ route('appointments.show', $a) }}" class="card flex items-center gap-4 p-5 transition hover:border-blue-300">
            <span class="w-16 text-lg font-bold text-blue-600">{{ $a->start_time ? substr($a->start_time, 0, 5) : '—' }}</span>
            <span class="min-w-0 flex-1">
                <span class="block text-lg font-semibold text-slate-900">{{ $a->patient->full_name }}</span>
                <span class="block truncate text-slate-600">{{ $a->problem_title }} · {{ $a->dentist?->full_name ?? 'No doctor yet' }}</span>
            </span>
        </a>
    @empty
        <div class="card text-center text-slate-500">Nothing scheduled for today.</div>
    @endforelse
</div>
@endsection
