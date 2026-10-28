@extends('layouts.marketing')
@section('title', 'Book a visit · DentAI')

@section('content')
<section class="mx-auto max-w-2xl px-6 py-12">
    <h1 class="text-center">Book a visit</h1>
    <p class="mt-2 text-center text-slate-600">Fill in the two boxes below. We’ll contact you to confirm a time.</p>

    <form method="POST" action="{{ route('booking.store') }}" class="mt-8 space-y-6">
        @csrf

        <div class="card space-y-5">
            <h2 class="flex items-center gap-3"><span class="step">1</span> About you</h2>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="label" for="first_name">First name</label>
                    <input id="first_name" name="first_name" value="{{ old('first_name') }}" class="input" required autocomplete="given-name">
                    @error('first_name')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="last_name">Last name</label>
                    <input id="last_name" name="last_name" value="{{ old('last_name') }}" class="input" required autocomplete="family-name">
                    @error('last_name')<p class="error">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="label" for="phone_number">Mobile number</label>
                    <input id="phone_number" name="phone_number" value="{{ old('phone_number') }}" class="input" required inputmode="tel" autocomplete="tel" placeholder="0917 123 4567">
                    @error('phone_number')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="gender">Gender</label>
                    <select id="gender" name="gender" class="input" required>
                        <option value="">Choose…</option>
                        @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other', 'prefer_not_to_say' => 'Prefer not to say'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('gender') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('gender')<p class="error">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="card space-y-5">
            <h2 class="flex items-center gap-3"><span class="step">2</span> What’s bothering you?</h2>
            <div>
                <label class="label" for="problem_type_id">What kind of problem is it?</label>
                <select id="problem_type_id" name="problem_type_id" class="input" required>
                    <option value="">Choose the closest one…</option>
                    @foreach ($problemTypes as $type)
                        <option value="{{ $type->id }}" data-desc="{{ $type->description }}" @selected((string) old('problem_type_id') === (string) $type->id)>{{ $type->name }}</option>
                    @endforeach
                </select>
                <p id="type-hint" class="hint mt-1.5"></p>
                @error('problem_type_id')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="problem_title">Give it a short name</label>
                <input id="problem_title" name="problem_title" value="{{ old('problem_title') }}" class="input" required maxlength="120" placeholder="e.g. Upper left tooth hurts when I chew">
                @error('problem_title')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="description">How does it feel?</label>
                <textarea id="description" name="description" rows="4" class="input" required maxlength="2000" placeholder="When did it start? Does it hurt all the time or only sometimes? Any swelling or bleeding?">{{ old('description') }}</textarea>
                @error('description')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="pain_level">How much does it hurt? <span id="pain-out" class="ml-1 rounded-lg bg-blue-50 px-2 py-0.5 text-blue-700"></span></label>
                <input id="pain_level" type="range" min="0" max="10" name="pain_level" value="{{ old('pain_level', 0) }}" class="w-full accent-blue-600">
                <div class="flex justify-between text-sm text-slate-500"><span>No pain</span><span>Worst pain</span></div>
                @error('pain_level')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="preferred_date">Day you’d like to come <span class="font-normal text-slate-500">(optional)</span></label>
                <input id="preferred_date" type="date" name="preferred_date" min="{{ today()->toDateString() }}" value="{{ old('preferred_date') }}" class="input">
                @error('preferred_date')<p class="error">{{ $message }}</p>@enderror
            </div>
        </div>

        <button class="btn btn-primary w-full py-4 text-lg">Send my request</button>
        <p class="text-center text-sm text-slate-500">Trouble breathing or swallowing, or swelling spreading across your face? Go to an emergency room right away.</p>
    </form>
</section>

<script>
    const pain = document.getElementById('pain_level'), out = document.getElementById('pain-out');
    const words = v => v == 0 ? 'No pain' : v <= 3 ? 'Mild' : v <= 6 ? 'Moderate' : 'Severe';
    const showPain = () => out.textContent = pain.value + ' / 10 · ' + words(pain.value);
    pain.addEventListener('input', showPain); showPain();

    const type = document.getElementById('problem_type_id'), hint = document.getElementById('type-hint');
    const showHint = () => hint.textContent = type.selectedOptions[0]?.dataset.desc ?? '';
    type.addEventListener('change', showHint); showHint();
</script>
@endsection
