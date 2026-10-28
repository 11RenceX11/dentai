<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Billing;
use App\Models\MedicationLog;
use App\Models\Patient;
use App\Models\Prescription;
use App\Services\PaymongoService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;

/** Everything a patient sees after logging in. */
class PortalController extends Controller
{
    private function patientId(): int
    {
        return (int) auth()->user()->patient_id;
    }

    public function home()
    {
        $pid = $this->patientId();

        $visits = Appointment::with(['dentist', 'problemType', 'prescriptions', 'billing'])
            ->where('patient_id', $pid)->latest()->get();

        return view('portal.home', [
            'patient' => Patient::findOrFail($pid),
            'upcoming' => $visits->whereNotIn('status', ['completed', 'cancelled']),
            'past' => $visits->where('status', 'completed'),
            'todayDoses' => $this->dosesFor(now(), $pid),
            'bills' => Billing::with('payments')
                ->whereHas('appointment', fn ($q) => $q->where('patient_id', $pid))
                ->where('payment_status', '!=', 'paid')->get(),
        ]);
    }

    /** Month calendar + the list of doses for the chosen day. */
    public function medicines(Request $request)
    {
        $pid = $this->patientId();

        $month = preg_match('/^\d{4}-\d{2}$/', (string) $request->query('month'))
            ? Carbon::createFromFormat('Y-m-d', $request->query('month') . '-01')->startOfDay()
            : now()->startOfMonth();

        $selected = $request->filled('date') ? Carbon::parse($request->query('date'))->startOfDay() : now()->startOfDay();

        $gridStart = $month->copy()->startOfWeek(Carbon::SUNDAY);
        $gridEnd = $month->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        $prescriptions = $this->prescriptions($pid)->get();

        $days = [];
        for ($d = $gridStart->copy(); $d->lte($gridEnd); $d->addDay()) {
            $doses = $this->dosesFor($d, $pid, $prescriptions);
            $days[] = [
                'date' => $d->copy(),
                'inMonth' => $d->month === $month->month,
                'total' => $doses->count(),
                'taken' => $doses->where('taken', true)->count(),
            ];
        }

        return view('portal.medicines', [
            'month' => $month,
            'days' => $days,
            'selected' => $selected,
            'selectedDoses' => $this->dosesFor($selected, $pid, $prescriptions),
            'activePrescriptions' => $prescriptions->filter(fn ($rx) => $rx->endDate()->gte(today())),
        ]);
    }

    /** Tick / untick "I took this dose". */
    public function toggleDose(Request $request)
    {
        $data = $request->validate([
            'prescription_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
            'time' => ['required', 'regex:/^\d{2}:\d{2}$/'],
        ]);

        $rx = $this->prescriptions($this->patientId())->findOrFail($data['prescription_id']);
        $day = Carbon::parse($data['date']);

        abort_unless($rx->activeOn($day) && in_array($data['time'], $rx->times, true), 422);

        $existing = MedicationLog::where('prescription_id', $rx->id)
            ->whereDate('dose_date', $day)->where('dose_time', $data['time'])->first();

        $existing
            ? $existing->delete()
            : MedicationLog::create([
                'prescription_id' => $rx->id, 'dose_date' => $day->toDateString(),
                'dose_time' => $data['time'], 'taken_at' => now(),
            ]);

        return back();
    }

    /** Downloadable calendar file: opens in Google/Apple/Outlook calendar with a repeating alarm for every dose. */
    public function reminders(Prescription $prescription)
    {
        $rx = $this->prescriptions($this->patientId())->findOrFail($prescription->id);

        $esc = fn (string $t) => str_replace(["\\", ';', ',', "\n"], ["\\\\", '\;', '\,', '\n'], $t);

        $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//DentAI//Medicine reminders//EN', 'CALSCALE:GREGORIAN'];

        foreach ($rx->times as $time) {
            [$h, $m] = array_map('intval', explode(':', $time));
            $start = $rx->start_date->copy()->setTime($h, $m);

            array_push($lines,
                'BEGIN:VEVENT',
                "UID:rx{$rx->id}-" . str_replace(':', '', $time) . '@dentai',
                'DTSTAMP:' . now()->utc()->format('Ymd\THis\Z'),
                'DTSTART:' . $start->format('Ymd\THis'),
                'DTEND:' . $start->copy()->addMinutes(15)->format('Ymd\THis'),
                'RRULE:FREQ=DAILY;COUNT=' . max(1, $rx->duration_days),
                'SUMMARY:' . $esc("Take {$rx->medication_name}"),
                'DESCRIPTION:' . $esc(trim("{$rx->dosage}. {$rx->instructions}")),
                'BEGIN:VALARM', 'ACTION:DISPLAY', 'DESCRIPTION:Time to take your medicine', 'TRIGGER:PT0M', 'END:VALARM',
                'END:VEVENT',
            );
        }
        $lines[] = 'END:VCALENDAR';

        return response(implode("\r\n", $lines) . "\r\n", 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="medicine-reminders.ics"',
        ]);
    }

    public function bill(Billing $billing, PaymongoService $paymongo)
    {
        $this->authorizeBill($billing);
        $billing->load(['appointment.treatments.treatment', 'payments']);

        return view('portal.bill', ['billing' => $billing, 'paymongoReady' => $paymongo->enabled()]);
    }

    public function pay(Billing $billing, PaymongoService $paymongo)
    {
        $this->authorizeBill($billing);

        abort_unless($billing->allow_online && $paymongo->enabled() && $billing->balance() > 0, 403);

        try {
            $checkout = $paymongo->createCheckout(
                $billing,
                route('portal.bills.show', [$billing, 'paid' => 1]),
                route('portal.bills.show', $billing),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['pay' => 'Online payment could not be started: ' . $e->getMessage()]);
        }

        $billing->update(['paymongo_checkout_id' => $checkout['id']]);

        return redirect()->away($checkout['url']);
    }

    private function authorizeBill(Billing $billing): void
    {
        abort_unless($billing->appointment->patient_id === $this->patientId(), 404);
    }

    private function prescriptions(int $patientId)
    {
        return Prescription::with(['logs', 'appointment'])
            ->whereHas('appointment', fn ($q) => $q->where('patient_id', $patientId));
    }

    /** @return Collection<int, array{prescription:Prescription,time:string,taken:bool}> */
    private function dosesFor(Carbon $day, int $patientId, ?Collection $prescriptions = null): Collection
    {
        $prescriptions ??= $this->prescriptions($patientId)->get();
        $doses = collect();

        foreach ($prescriptions as $rx) {
            if (! $rx->activeOn($day)) {
                continue;
            }
            foreach ($rx->times as $time) {
                $doses->push([
                    'prescription' => $rx,
                    'time' => $time,
                    'taken' => $rx->logs->contains(fn ($l) => $l->dose_time === $time && $l->dose_date->isSameDay($day)),
                ]);
            }
        }

        return $doses->sortBy('time')->values();
    }
}
