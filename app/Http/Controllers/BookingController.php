<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\ProblemType;
use App\Services\PatientAccountService;
use App\Services\TriageService;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function create()
    {
        return view('book', ['problemTypes' => ProblemType::orderBy('name')->get()]);
    }

    public function store(StoreBookingRequest $request, TriageService $triage, PatientAccountService $accounts)
    {
        $data = $request->validated();

        $appointment = DB::transaction(function () use ($data, $triage, $accounts) {
            // Same name + phone = same patient, so repeat bookings share one record.
            $patient = Patient::firstOrCreate(
                [
                    'phone_number' => $data['phone_number'],
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                ],
                [
                    'gender' => $data['gender'],
                    'email' => $data['email'] ?? null,
                    'date_registered' => today(),
                ],
            );

            // Automatic account: phone number + access code (staff hand the code over in person).
            $accounts->ensureFor($patient);

            $appointment = $patient->appointments()->create([
                'problem_type_id' => $data['problem_type_id'],
                'problem_title' => $data['problem_title'],
                'description' => $data['description'],
                'pain_level' => $data['pain_level'],
                'preferred_date' => $data['preferred_date'] ?? null,
                'status' => 'requested',
            ]);

            $triage->generate($appointment);

            return $appointment;
        });

        return redirect()->route('booking.success')->with('booking', [
            'reference' => 'DA-' . str_pad((string) $appointment->id, 5, '0', STR_PAD_LEFT),
            'name' => $appointment->patient->first_name,
            'priority' => in_array($appointment->urgency, ['high', 'emergency'], true),
        ]);
    }

    public function success()
    {
        abort_unless(session()->has('booking'), 404);

        return view('booking-success', ['booking' => session('booking')]);
    }
}
