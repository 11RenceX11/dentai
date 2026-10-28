@extends('layouts.app')
@section('title', 'Records')

@section('content')
<h1>Records</h1>
<p class="mt-1 text-slate-600">Patients who are finished with their treatment. Nothing is deleted — you can open or re-open any visit.</p>

<form method="GET" class="mt-6 flex gap-3">
    <input name="q" value="{{ request('q') }}" placeholder="Search by name or phone…" class="input">
    <button class="btn btn-ghost">Search</button>
</form>

<div class="mt-6 space-y-3">
    @forelse ($records as $a)
        <a href="{{ route('appointments.show', $a) }}" class="card flex flex-wrap items-center justify-between gap-3 p-5 transition hover:border-blue-300">
            <div class="min-w-0">
                <p class="text-lg font-semibold text-slate-900">{{ $a->patient->full_name }}</p>
                <p class="truncate text-slate-600">{{ $a->problem_title }}</p>
            </div>
            <div class="text-right text-sm text-slate-500">
                <p>Done {{ $a->completed_at?->format('M j, Y') }}</p>
                <p>{{ $a->dentist?->full_name }}</p>
            </div>
        </a>
    @empty
        <div class="card text-center text-slate-500">No finished visits yet.</div>
    @endforelse
</div>
<div class="mt-6 empty:hidden">{{ $records->links() }}</div>
@endsection
