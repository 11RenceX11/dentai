@extends('layouts.app')
@section('title', $patient->full_name)

@section('content')
<a href="{{ route('patients.index') }}" class="text-slate-500 hover:text-slate-800">← All patients</a>
<h1 class="mt-3">{{ $patient->full_name }}</h1>
<p class="mt-1 text-slate-600">{{ ucfirst(str_replace('_', ' ', $patient->gender)) }} · {{ $patient->phone_number }}</p>

<div class="mt-8 space-y-6">
    <div class="card">
        <h2>Visits</h2>
        <ul class="mt-3 divide-y divide-slate-100">
            @forelse ($appointments as $a)
                <li><a href="{{ route('appointments.show', $a) }}" class="flex items-center justify-between gap-3 py-3 hover:text-blue-700">
                    <span><span class="font-semibold">{{ $a->problem_title }}</span><br><span class="text-sm text-slate-500">{{ $a->appointment_date?->format('M j, Y') ?? 'Not scheduled' }}</span></span>
                    <x-badge :value="$a->status" />
                </a></li>
            @empty
                <li class="py-3 text-slate-500">No visits yet.</li>
            @endforelse
        </ul>
    </div>

    <div class="card">
        <h2>Medical notes</h2>
        <p class="hint">Allergies, conditions, medicines they already take.</p>
        <ul class="mt-3 space-y-2">
            @forelse ($patient->medicalHistories as $h)
                <li class="flex items-start justify-between gap-3 rounded-xl bg-slate-50 px-4 py-3">
                    <span><strong class="{{ $h->history_type === 'allergy' ? 'text-red-600' : '' }}">{{ ucfirst(str_replace('_', ' ', $h->history_type)) }}:</strong> {{ $h->description }}</span>
                    <form method="POST" action="{{ route('patients.history.destroy', [$patient, $h]) }}">@csrf @method('DELETE')<button class="text-sm font-semibold text-red-600 hover:underline">Remove</button></form>
                </li>
            @empty
                <li class="text-slate-500">Nothing recorded.</li>
            @endforelse
        </ul>
        <form method="POST" action="{{ route('patients.history.store', $patient) }}" class="mt-4 grid gap-3 sm:grid-cols-3">
            @csrf
            <select name="history_type" class="input">@foreach (\App\Models\PatientMedicalHistory::TYPES as $t)<option value="{{ $t }}">{{ ucfirst(str_replace('_', ' ', $t)) }}</option>@endforeach</select>
            <input name="description" class="input" placeholder="e.g. Allergic to penicillin" required>
            <button class="btn btn-ghost">Add</button>
        </form>
    </div>

    <details class="card">
        <summary class="cursor-pointer text-xl font-semibold text-slate-900">More details</summary>
        <form method="POST" action="{{ route('patients.update', $patient) }}" class="mt-5 grid gap-4 sm:grid-cols-2">
            @csrf @method('PUT')
            <div><label class="label">Email</label><input type="email" name="email" value="{{ old('email', $patient->email) }}" class="input"></div>
            <div><label class="label">Date of birth</label><input type="date" name="date_of_birth" value="{{ old('date_of_birth', $patient->date_of_birth?->format('Y-m-d')) }}" class="input"></div>
            <div class="sm:col-span-2"><label class="label">Address</label><input name="address_line1" value="{{ old('address_line1', $patient->address_line1) }}" class="input"></div>
            <div><label class="label">City</label><input name="city" value="{{ old('city', $patient->city) }}" class="input"></div>
            <div><label class="label">Province</label><input name="state_province" value="{{ old('state_province', $patient->state_province) }}" class="input"></div>
            <div class="sm:col-span-2"><button class="btn btn-ghost">Save</button></div>
        </form>

        <h3 class="mt-8 text-lg font-semibold">X-rays &amp; photos</h3>
        <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
            @forelse ($patient->xrayImages as $img)
                <a href="{{ asset('storage/' . $img->image_path) }}" target="_blank" class="overflow-hidden rounded-xl border border-slate-200">
                    <img src="{{ asset('storage/' . $img->image_path) }}" alt="{{ $img->image_type }}" class="aspect-square w-full object-cover">
                    <p class="px-3 py-2 text-sm text-slate-500">{{ ucfirst(str_replace('_', ' ', $img->image_type)) }} · {{ $img->upload_date->format('M j, Y') }}</p>
                </a>
            @empty
                <p class="col-span-full text-slate-500">None uploaded.</p>
            @endforelse
        </div>
        <form method="POST" action="{{ route('patients.images.store', $patient) }}" enctype="multipart/form-data" class="mt-4 grid gap-3 sm:grid-cols-3">
            @csrf
            <input type="file" name="image" accept="image/*" class="input" required>
            <select name="image_type" class="input">@foreach (['xray', 'panoramic', 'intraoral_photo', 'ct_scan', 'other'] as $t)<option value="{{ $t }}">{{ ucfirst(str_replace('_', ' ', $t)) }}</option>@endforeach</select>
            <button class="btn btn-ghost">Upload</button>
        </form>
    </details>
</div>
@endsection
