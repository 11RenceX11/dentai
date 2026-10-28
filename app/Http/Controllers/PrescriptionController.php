<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PrescriptionController extends Controller
{
    public function store(Request $request, Appointment $appointment)
    {
        $data = $request->validate([
            'medication_name' => ['required', 'string', 'max:120'],
            'dosage' => ['required', 'string', 'max:60'],
            'times' => ['required', 'array', 'min:1'],
            'times.*' => [Rule::in(array_keys(Prescription::TIME_SLOTS))],
            'duration_days' => ['required', 'integer', 'between:1,90'],
            'start_date' => ['nullable', 'date'],
            'instructions' => ['nullable', 'string', 'max:500'],
        ], ['times.required' => 'Pick at least one time of day.']);

        $appointment->prescriptions()->create([
            'medication_name' => $data['medication_name'],
            'dosage' => $data['dosage'],
            'times' => collect($data['times'])->sort()->values()->all(),
            'duration_days' => $data['duration_days'],
            'start_date' => $data['start_date'] ?? today(),
            'instructions' => $data['instructions'] ?? null,
            'prescribed_by' => $request->user()->dentist_id,
            'prescribed_date' => today(),
        ]);

        return back()->with('status', 'Medicine added. The patient can see it in their account.');
    }

    public function destroy(Appointment $appointment, Prescription $prescription)
    {
        abort_unless($prescription->appointment_id === $appointment->id, 404);
        $prescription->delete();

        return back()->with('status', 'Medicine removed.');
    }
}
