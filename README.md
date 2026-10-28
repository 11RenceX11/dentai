# DentAI — Laravel 13 · Blade · Tailwind · SQLite

Patients book online and describe the problem. Dentists and assistants arrange the schedule, and a
rule-based assistant proposes prioritised actions that a dentist/assistant approves before they are
added to the appointment.

## 1. Install

```bash
laravel new dentai            # choose: No starter kit · SQLite · (Pest or PHPUnit, either works)
cd dentai

# Laravel's default users migration is replaced (adds role + dentist/staff links)
rm database/migrations/0001_01_01_000000_create_users_table.php

# copy this overlay's files on top of the fresh app (from the folder holding dentai-overlay.zip)
unzip -o ../dentai-overlay.zip -d .

npm install && npm run build
php artisan migrate:fresh --seed
php artisan storage:link
composer run dev              # http://localhost:8000
php artisan test              # optional
```

Demo logins (password `password`): `admin@dentai.test`, `dentist@dentai.test`, `assistant@dentai.test`.

## 2. Artisan commands (the equivalent of every file in this overlay)

The overlay already contains the finished files. These are the commands that generate the same stubs.

```bash
# Models + migrations
php artisan make:model Staff -m
php artisan make:model Dentist -m
php artisan make:model Patient -m
php artisan make:model PatientMedicalHistory -m
php artisan make:model Treatment -m
php artisan make:model ProblemType -m
php artisan make:migration create_dentist_treatment_table
php artisan make:model Appointment -m
php artisan make:model AppointmentTreatment -m
php artisan make:model AppointmentSuggestion -m
php artisan make:model Prescription -m
php artisan make:model XrayImage -m
php artisan make:model Billing -m
php artisan make:model Payment -m

# Controllers
php artisan make:controller BookingController
php artisan make:controller AuthController
php artisan make:controller DashboardController --invokable
php artisan make:controller AppointmentController
php artisan make:controller SuggestionController
php artisan make:controller PatientController
php artisan make:controller PrescriptionController
php artisan make:controller TreatmentController
php artisan make:controller BillingController

# Requests, middleware, service, seeder, test
php artisan make:request StoreBookingRequest
php artisan make:request ScheduleAppointmentRequest
php artisan make:middleware EnsureRole
php artisan make:class Services/TriageService
php artisan make:seeder DatabaseSeeder
php artisan make:test BookingTriageTest

# Run
php artisan migrate:fresh --seed
php artisan storage:link
php artisan route:list
```

## 3. How it works

| Who | What they can do |
|---|---|
| Patient (no login) | `/book` — name, phone, gender (required), problem-type drop-down, problem name, how it feels, pain 0–10 |
| Assistant | Dashboard queue, arrange schedule, approve/dismiss suggestions, patients, billing |
| Dentist | Same clinical access + treatment notes and prescriptions (no billing) |
| Admin | Everything + treatment catalogue |

**Assistant suggestions** (`app/Services/TriageService.php`): on every booking it scores the problem
type baseline + pain level + warning words ("swelling", "throbbing", "fever", …) → `low / normal / high /
emergency`, then creates *pending* suggestions (add a treatment, add an X-ray, call the patient today,
check allergies first, book by a target date). Approving one writes it into the appointment.
It is rule-based, not an LLM; you can swap `generate()` for Laravel's AI SDK later.

## 4. Changes to your data structure

- **Removed the stray `Information` table and the odd `Payments.payment_id ?<? payment_method` ref.**
  Name, phone and gender now live on `patients` (your booking flow needs them); `staff` also gets names.
- **`appointments`** gained `dentist_id`, `problem_type_id`, `problem_title`, `description`, `pain_level`,
  `urgency`, `preferred_date`, and a `requested` status (a patient request has no time yet, so
  start/end are nullable `time` columns).
- **New tables:** `problem_types` (the drop-down) and `appointment_suggestions` (the approval queue).
- `appointment_treatments.performed_by` is nullable (a dentist isn't assigned when a suggestion is approved).
- `users` log in with **email** (Laravel's default) instead of `username`; `linked_dentist_id/linked_staff_id`
  became `dentist_id/staff_id`. `payments.bill_id` → `billing_id` to follow Eloquent naming.
- Table/column names follow Laravel conventions (plural snake_case, `created_at/updated_at`).
- The users migration replaces Laravel's default so it can reference `dentists`/`staff`; SQLite allows that
  ordering. On MySQL/Postgres, move those two foreign keys into a later migration.

## 5. Design note

The landing page follows the structure and copy of dentai.ai (dark theme, hero, bento cards, 4 steps, FAQ, CTA).
Colors, fonts and the logo are approximations (placeholder wordmark in `resources/views/components/logo.blade.php`);
Pricing and FAQ answers are placeholders. Replace them before going public.
