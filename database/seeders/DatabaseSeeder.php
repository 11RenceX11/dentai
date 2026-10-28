<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\ProblemType;
use App\Models\Staff;
use App\Models\Treatment;
use App\Models\User;
use App\Services\PatientAccountService;
use App\Services\TriageService;
use App\Models\Billing;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CatalogueSeeder::class);
        $t = fn (string $n) => Treatment::where('treatment_name', $n)->value('id');

        // ---- People ----
        $maria = Dentist::create([
            'first_name' => 'Maria', 'last_name' => 'Santos', 'specialization' => 'General Dentistry',
            'license_number' => 'LIC-1001', 'email' => 'maria@dentai.test', 'phone_number' => '0917 000 1001', 'hire_date' => '2022-01-10',
        ]);
        $john = Dentist::create([
            'first_name' => 'John', 'last_name' => 'Reyes', 'specialization' => 'Endodontics',
            'license_number' => 'LIC-1002', 'email' => 'john@dentai.test', 'phone_number' => '0917 000 1002', 'hire_date' => '2023-03-01',
        ]);

        // dentist <-> treatment certifications (M:M)
        $maria->treatments()->attach(collect(['Check-up & Cleaning', 'Emergency Examination', 'Dental X-Ray', 'Filling', 'Gum Treatment', 'Tooth Extraction', 'Crown'])
            ->mapWithKeys(fn ($n) => [$t($n) => ['certified_date' => '2022-02-01', 'proficiency_level' => 'proficient']])->all());
        $john->treatments()->attach(collect(['Emergency Examination', 'Dental X-Ray', 'Root Canal Therapy', 'Tooth Extraction', 'Crown'])
            ->mapWithKeys(fn ($n) => [$t($n) => ['certified_date' => '2023-04-01', 'proficiency_level' => 'expert']])->all());

        $assistant = Staff::create(['first_name' => 'Ana', 'last_name' => 'Cruz', 'job_title' => 'Dental Assistant', 'hire_date' => '2024-05-01']);

        User::create(['name' => 'Clinic Admin', 'email' => 'admin@dentai.test', 'password' => 'password', 'role' => 'admin']);
        User::create(['name' => 'Dr. Maria Santos', 'email' => 'dentist@dentai.test', 'password' => 'password', 'role' => 'dentist', 'dentist_id' => $maria->id]);
        User::create(['name' => 'Dr. John Reyes', 'email' => 'john@dentai.test', 'password' => 'password', 'role' => 'dentist', 'dentist_id' => $john->id]);
        User::create(['name' => 'Ana Cruz', 'email' => 'assistant@dentai.test', 'password' => 'password', 'role' => 'staff', 'staff_id' => $assistant->id]);

        // ---- Demo patient requests (run through the same triage the booking form uses) ----
        $triage = app(TriageService::class);
        $accounts = app(PatientAccountService::class);
        $samples = [
            ['Lena', 'Garcia', 'female', '0917 555 0101', 'Toothache', 'Lower right molar pulsing', "It has been throbbing since last night and I couldn't sleep. My cheek is swollen too.", 9],
            ['Paolo', 'Mendoza', 'male', '0917 555 0102', 'Tooth sensitivity', 'Cold drinks hurt', 'A sharp zing when I drink iced water, it goes away after a few seconds.', 3],
            ['Grace', 'Tan', 'female', '0917 555 0103', 'Cleaning / check-up', 'Routine cleaning', 'Just due for my regular cleaning and check-up.', 0],
            ['Ben', 'Aquino', 'male', '0917 555 0104', 'Chipped or broken tooth', 'Front tooth chipped', 'I bit something hard and a piece of my front tooth broke off. It feels rough.', 5],
        ];
        foreach ($samples as [$first, $last, $gender, $phone, $problem, $title, $desc, $pain]) {
            $patient = Patient::create([
                'first_name' => $first, 'last_name' => $last, 'gender' => $gender,
                'phone_number' => $phone, 'date_registered' => today(),
            ]);
            $appointment = Appointment::create([
                'patient_id' => $patient->id,
                'problem_type_id' => ProblemType::where('name', $problem)->value('id'),
                'problem_title' => $title, 'description' => $desc, 'pain_level' => $pain, 'status' => 'requested',
            ]);
            $triage->generate($appointment);
            $accounts->ensureFor($patient);
        }

        // ---- A scheduled visit with medicine + a bill that can be paid online ----
        $maya = Patient::create(['first_name' => 'Maya', 'last_name' => 'Lim', 'gender' => 'female', 'phone_number' => '0917 555 0200', 'date_registered' => today()]);
        $accounts->ensureFor($maya);
        $visit = Appointment::create([
            'patient_id' => $maya->id, 'dentist_id' => $maria->id,
            'problem_type_id' => ProblemType::where('name', 'Cavity or hole in tooth')->value('id'),
            'problem_title' => 'Cavity on lower left tooth', 'description' => 'A small hole that catches food.',
            'pain_level' => 2, 'urgency' => 'normal', 'status' => 'scheduled',
            'appointment_date' => today()->addDay(), 'start_time' => '10:00:00', 'end_time' => '10:45:00',
        ]);
        $visit->treatments()->create(['treatment_id' => $t('Filling'), 'performed_by' => $maria->id, 'cost_at_time' => 120, 'status' => 'planned']);
        $visit->prescriptions()->create([
            'medication_name' => 'Pain reliever (demo)', 'dosage' => '1 tablet', 'times' => ['08:00', '21:00'],
            'start_date' => today(), 'duration_days' => 5, 'instructions' => 'Take after eating. Stop and call us if you feel unwell.',
            'prescribed_by' => $maria->id, 'prescribed_date' => today(),
        ]);
        Billing::create(['appointment_id' => $visit->id, 'total_amount' => 120, 'issued_date' => today(), 'due_date' => today()->addDays(14), 'allow_online' => true]);

        // ---- Dr. Reyes is off next Friday: he will not be offered for that date ----
        $john->timeOffs()->create(['start_date' => today()->next('Friday'), 'end_date' => today()->next('Friday'), 'reason' => 'Seminar']);

        // ---- One finished visit so the Records page isn't empty ----
        $sam = Patient::create(['first_name' => 'Sam', 'last_name' => 'Ortega', 'gender' => 'male', 'phone_number' => '0917 555 0300', 'date_registered' => today()->subMonth()]);
        $accounts->ensureFor($sam);
        Appointment::create([
            'patient_id' => $sam->id, 'dentist_id' => $maria->id,
            'problem_type_id' => ProblemType::where('name', 'Cleaning / check-up')->value('id'),
            'problem_title' => 'Routine cleaning', 'description' => 'Regular check-up and cleaning.',
            'pain_level' => 0, 'urgency' => 'low', 'status' => 'completed', 'completed_at' => now()->subWeeks(2),
            'appointment_date' => today()->subWeeks(2), 'start_time' => '09:00:00', 'end_time' => '09:30:00',
        ]);
    }
}
