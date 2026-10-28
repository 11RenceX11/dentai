<?php

namespace App\Http\Controllers;

use App\Models\Dentist;
use App\Models\Patient;
use App\Models\PatientMedicalHistory;
use App\Models\XrayImage;
use App\Services\PatientAccountService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $patients = Patient::withCount('appointments')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->q . '%';
                $q->where(fn ($w) => $w->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)->orWhere('phone_number', 'like', $term));
            })
            ->latest()->paginate(15)->withQueryString();

        return view('patients.index', compact('patients'));
    }

    public function show(Patient $patient)
    {
        $patient->load(['medicalHistories.recorder', 'xrayImages.dentist', 'user']);

        return view('patients.show', [
            'patient' => $patient,
            'appointments' => $patient->appointments()->with(['dentist', 'problemType'])->latest()->get(),
            'dentists' => Dentist::where('is_active', true)->orderBy('last_name')->get(),
        ]);
    }

    public function update(Request $request, Patient $patient)
    {
        $patient->update($request->validate([
            'email' => ['nullable', 'email'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'address_line1' => ['nullable', 'string', 'max:150'],
            'city' => ['nullable', 'string', 'max:80'],
            'state_province' => ['nullable', 'string', 'max:80'],
        ]));

        return back()->with('status', 'Patient details saved.');
    }

    /** Forgot the access code? Staff can make a new one and print it on the slip. */
    public function resetCode(Patient $patient, PatientAccountService $accounts)
    {
        $accounts->resetCode($patient);

        return back()->with('status', 'New access code created. Open the slip to see it.');
    }

    public function storeHistory(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'history_type' => ['required', Rule::in(PatientMedicalHistory::TYPES)],
            'description' => ['required', 'string', 'max:255'],
        ]);

        $patient->medicalHistories()->create($data + ['recorded_date' => today(), 'recorded_by' => $request->user()->id]);

        return back()->with('status', 'Medical history entry added.');
    }

    public function destroyHistory(Patient $patient, PatientMedicalHistory $history)
    {
        abort_unless($history->patient_id === $patient->id, 404);
        $history->delete();

        return back()->with('status', 'Entry removed.');
    }

    public function storeImage(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'image' => ['required', 'image', 'max:10240'],
            'image_type' => ['required', Rule::in(['xray', 'panoramic', 'intraoral_photo', 'ct_scan', 'other'])],
            'taken_by' => ['nullable', 'exists:dentists,id'],
        ]);

        XrayImage::create([
            'patient_id' => $patient->id,
            'taken_by' => $data['taken_by'] ?? null,
            'uploaded_by' => $request->user()->id,
            'image_path' => $request->file('image')->store("patients/{$patient->id}", 'public'),
            'image_type' => $data['image_type'],
            'upload_date' => today(),
        ]);

        return back()->with('status', 'Image uploaded.');
    }
}
