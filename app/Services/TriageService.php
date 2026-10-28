<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentSuggestion;
use App\Models\AppointmentTreatment;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Rule-based "assistant" that reads what the patient reported (problem type, pain level,
 * their own words) and proposes prioritised actions. Nothing is applied until a dentist
 * or assistant approves it.
 */
class TriageService
{
    private const BASE = ['low' => 0, 'normal' => 2, 'high' => 4, 'emergency' => 7];

    private const EMERGENCY_WORDS = [
        'swelling', 'swollen', 'fever', 'pus', 'abscess', 'trouble swallowing',
        'difficulty swallowing', 'knocked out', 'knocked-out', 'trauma', 'accident',
    ];

    private const HIGH_WORDS = [
        'throbbing', 'severe', 'unbearable', 'excruciating', "can't sleep", 'cannot sleep',
        "couldn't sleep", 'broken', 'cracked', 'bleeding', 'sharp pain', 'very painful',
    ];

    /** @return array{urgency:string, score:int, reasons:array<int,string>} */
    public function assess(Appointment $appointment): array
    {
        $text = Str::lower($appointment->problem_title . ' ' . $appointment->description);
        $base = $appointment->problemType?->default_urgency ?? 'normal';
        $score = self::BASE[$base];
        $reasons = ["Problem type baseline: {$base}"];

        $pain = (int) $appointment->pain_level;
        if ($pain >= 8) {
            $score += 3;
            $reasons[] = "Reported pain {$pain}/10 (severe)";
        } elseif ($pain >= 6) {
            $score += 2;
            $reasons[] = "Reported pain {$pain}/10 (significant)";
        } elseif ($pain >= 4) {
            $score += 1;
            $reasons[] = "Reported pain {$pain}/10 (moderate)";
        }

        $emergency = array_values(array_filter(self::EMERGENCY_WORDS, fn ($w) => str_contains($text, $w)));
        if ($emergency) {
            $score += 4;
            $reasons[] = 'Mentions possible infection/trauma: ' . implode(', ', $emergency);
        }

        $high = array_values(array_filter(self::HIGH_WORDS, fn ($w) => str_contains($text, $w)));
        if ($high) {
            $score += 2;
            $reasons[] = 'Mentions: ' . implode(', ', $high);
        }

        $urgency = match (true) {
            $score >= 7 => 'emergency',
            $score >= 4 => 'high',
            $score >= 2 => 'normal',
            default => 'low',
        };

        return ['urgency' => $urgency, 'score' => $score, 'reasons' => $reasons];
    }

    /** Set the appointment's urgency and (re)build the pending suggestions. */
    public function generate(Appointment $appointment): void
    {
        $appointment->loadMissing('problemType.suggestedTreatment');
        $result = $this->assess($appointment);
        $urgency = $result['urgency'];
        $why = implode('; ', $result['reasons']);

        DB::transaction(function () use ($appointment, $urgency, $why) {
            $appointment->update(['urgency' => $urgency]);
            $appointment->suggestions()->where('status', 'pending')->delete();

            $minutes = 0;
            $treatments = [];

            if ($main = $appointment->problemType?->suggestedTreatment) {
                $treatments[] = $main;
            }
            if (in_array($urgency, ['high', 'emergency'], true)) {
                $xray = Treatment::where('treatment_name', 'Dental X-Ray')->first();
                if ($xray && ! collect($treatments)->contains('id', $xray->id)) {
                    $treatments[] = $xray;
                }
            }

            foreach ($treatments as $t) {
                $minutes += $t->est_duration_minutes;
                $appointment->suggestions()->create([
                    'type' => 'treatment',
                    'treatment_id' => $t->id,
                    'title' => "Add treatment: {$t->treatment_name}",
                    'detail' => "Suggested because: {$why}",
                    'priority' => $urgency,
                ]);
            }

            if ($urgency === 'emergency') {
                $appointment->suggestions()->create([
                    'type' => 'note',
                    'title' => 'Call the patient today to confirm',
                    'detail' => 'Possible emergency. Contact the patient the same day; the dentist decides on pain relief or antibiotics.',
                    'priority' => 'emergency',
                ]);
            }

            if ((int) $appointment->pain_level >= 6) {
                $appointment->suggestions()->create([
                    'type' => 'note',
                    'title' => 'Review medical history & allergies first',
                    'detail' => 'Patient reports significant pain. Check allergies and current medication before any prescription.',
                    'priority' => 'high',
                ]);
            }

            $target = match ($urgency) {
                'emergency' => today(),
                'high' => today()->addDays(2),
                'normal' => $appointment->preferred_date ?? today()->addDays(7),
                default => $appointment->preferred_date ?? today()->addDays(14),
            };
            $minutes = max($minutes, 30);

            $appointment->suggestions()->create([
                'type' => 'schedule',
                'title' => 'Book by ' . $target->format('D, M j'),
                'detail' => "Hold {$minutes} minutes with a qualified dentist ({$urgency} priority).",
                'target_date' => $target,
                'priority' => $urgency,
            ]);
        });
    }

    /** Apply an approved suggestion to its appointment. */
    public function approve(AppointmentSuggestion $suggestion, User $by): void
    {
        DB::transaction(function () use ($suggestion, $by) {
            $appointment = $suggestion->appointment;

            switch ($suggestion->type) {
                case 'treatment':
                    if ($treatment = $suggestion->treatment) {
                        AppointmentTreatment::firstOrCreate(
                            ['appointment_id' => $appointment->id, 'treatment_id' => $treatment->id, 'tooth_number' => ''],
                            ['cost_at_time' => $treatment->base_cost, 'status' => 'planned'],
                        );
                    }
                    break;

                case 'schedule':
                    if (! $appointment->appointment_date && $suggestion->target_date) {
                        $appointment->update(['appointment_date' => $suggestion->target_date]);
                    }
                    $this->appendNote($appointment, "{$suggestion->title} — {$suggestion->detail}");
                    break;

                default: // note
                    $this->appendNote($appointment, "{$suggestion->title} — {$suggestion->detail}");
            }

            $suggestion->update(['status' => 'approved', 'decided_by' => $by->id, 'decided_at' => now()]);
        });
    }

    public function dismiss(AppointmentSuggestion $suggestion, User $by): void
    {
        $suggestion->update(['status' => 'dismissed', 'decided_by' => $by->id, 'decided_at' => now()]);
    }

    private function appendNote(Appointment $appointment, string $line): void
    {
        $appointment->update(['notes' => trim(($appointment->notes ? $appointment->notes . "\n" : '') . '• ' . $line)]);
    }
}
