<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScheduleAppointmentRequest;
use App\Models\Appointment;
use App\Models\AppointmentTreatment;
use App\Models\Dentist;
use App\Models\Prescription;
use App\Models\Treatment;
use App\Services\PatientAccountService;
use App\Services\TriageService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    /** Open appointments only. Finished ones live under Records. */
    public function index(Request $request)
    {
        $tab = in_array($request->tab, ['requested', 'scheduled'], true) ? $request->tab : 'all';

        $appointments = Appointment::with(['patient', 'dentist', 'problemType'])
            ->withCount(['suggestions as pending_suggestions_count' => fn ($q) => $q->where('status', 'pending')])
            ->open()
            ->when($tab !== 'all', fn ($q) => $q->where('status', $tab))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->q . '%';
                $q->whereHas('patient', fn ($p) => $p->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)->orWhere('phone_number', 'like', $term));
            })
            ->orderByRaw(Appointment::urgencyOrderSql())
            ->orderByRaw('appointment_date IS NULL DESC, appointment_date ASC, start_time ASC')
            ->paginate(12)
            ->withQueryString();

        return view('appointments.index', [
            'appointments' => $appointments,
            'tab' => $tab,
            'counts' => [
                'all' => Appointment::open()->count(),
                'requested' => Appointment::where('status', 'requested')->count(),
                'scheduled' => Appointment::where('status', 'scheduled')->count(),
            ],
        ]);
    }

    public function show(Appointment $appointment)
    {
        $appointment->load([
            'patient.medicalHistories', 'patient.user', 'dentist', 'problemType', 'suggestions.treatment',
            'treatments.treatment', 'prescriptions.prescriber', 'billing',
        ]);

        $minutes = max(30, (int) $appointment->treatments->sum(fn ($t) => $t->treatment->est_duration_minutes));
        $dentists = Dentist::where('is_active', true)->with('timeOffs')->orderBy('last_name')->get();

        // dentist id => list of [start, end] days off, used to hide unavailable doctors for the chosen date
        $timeOff = $dentists->mapWithKeys(fn ($d) => [
            $d->id => $d->timeOffs->map(fn ($t) => [$t->start_date->toDateString(), $t->end_date->toDateString()])->values(),
        ]);

        return view('appointments.show', [
            'appointment' => $appointment,
            'dentists' => $dentists,
            'timeOff' => $timeOff,
            'treatments' => Treatment::orderBy('treatment_name')->get(),
            'pending' => $appointment->suggestions->where('status', 'pending')
                ->sortBy(fn ($s) => array_search($s->priority, ['emergency', 'high', 'normal', 'low'])),
            'defaultMinutes' => $minutes,
            'timeSlots' => Prescription::TIME_SLOTS,
        ]);
    }

    /** Arrange the visit: doctor, date, start/end time. */
    public function update(ScheduleAppointmentRequest $request, Appointment $appointment)
    {
        $data = $request->validated();
        $scheduled = ! empty($data['appointment_date']) && ! empty($data['start_time']);

        if (! empty($data['dentist_id']) && ! empty($data['appointment_date'])) {
            $dentist = Dentist::findOrFail($data['dentist_id']);

            if (! $dentist->isAvailableOn($data['appointment_date'])) {
                throw ValidationException::withMessages([
                    'dentist_id' => "{$dentist->full_name} is not available on that day. Please pick another doctor or date.",
                ]);
            }
        }

        if ($scheduled && ! empty($data['dentist_id'])) {
            $clash = Appointment::where('dentist_id', $data['dentist_id'])
                ->whereDate('appointment_date', $data['appointment_date'])
                ->where('status', 'scheduled')
                ->where('id', '!=', $appointment->id)
                ->where('start_time', '<', $data['end_time'] . ':00')
                ->where('end_time', '>', $data['start_time'] . ':00')
                ->exists();

            if ($clash) {
                throw ValidationException::withMessages([
                    'start_time' => 'This doctor already has a visit at that time.',
                ]);
            }
        }

        foreach (['start_time', 'end_time'] as $field) {
            if (! empty($data[$field])) {
                $data[$field] .= ':00';
            }
        }

        if ($scheduled && $appointment->status === 'requested') {
            $data['status'] = 'scheduled';
        }

        $appointment->update($data);

        return back()->with('status', $scheduled ? 'Visit scheduled.' : 'Saved.');
    }

    /** The patient is cured / treatment finished → archive it under Records. */
    public function complete(Appointment $appointment)
    {
        $appointment->update(['status' => 'completed', 'completed_at' => now()]);

        return redirect()->route('records.index')->with('status', 'Marked as done and moved to Records.');
    }

    public function reopen(Appointment $appointment)
    {
        $appointment->update([
            'status' => $appointment->isScheduled() ? 'scheduled' : 'requested',
            'completed_at' => null,
        ]);

        return redirect()->route('appointments.show', $appointment)->with('status', 'Visit re-opened.');
    }

    public function cancel(Appointment $appointment)
    {
        $appointment->update(['status' => 'cancelled']);

        return redirect()->route('appointments.index')->with('status', 'Visit cancelled.');
    }

    public function regenerate(Appointment $appointment, TriageService $triage)
    {
        $triage->generate($appointment);

        return back()->with('status', 'Suggestions refreshed.');
    }

    /** Printable slip: visit details, patient login and medicine instructions. */
    public function slip(Appointment $appointment, PatientAccountService $accounts)
    {
        $appointment->load(['patient', 'dentist', 'treatments.treatment', 'prescriptions', 'billing']);
        $user = $accounts->ensureFor($appointment->patient);

        return view('appointments.slip', ['appointment' => $appointment, 'user' => $user]);
    }

    public function addTreatment(Request $request, Appointment $appointment)
    {
        $data = $request->validate([
            'treatment_id' => ['required', 'exists:treatments,id'],
            'tooth_number' => ['nullable', 'string', 'max:10'],
        ]);

        $treatment = Treatment::findOrFail($data['treatment_id']);

        AppointmentTreatment::firstOrCreate(
            ['appointment_id' => $appointment->id, 'treatment_id' => $treatment->id, 'tooth_number' => $data['tooth_number'] ?? ''],
            ['performed_by' => $appointment->dentist_id, 'cost_at_time' => $treatment->base_cost, 'status' => 'planned'],
        );

        return back()->with('status', 'Treatment added.');
    }

    public function removeTreatment(Appointment $appointment, AppointmentTreatment $line)
    {
        abort_unless($line->appointment_id === $appointment->id, 404);
        $line->delete();

        return back()->with('status', 'Treatment removed.');
    }
}
