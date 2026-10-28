# DentAI — setup

Laravel 13 · Blade · Tailwind · SQLite

## 1. Install

```bash
laravel new dentai            # choose: No starter kit · SQLite · Pest or PHPUnit
cd dentai

rm database/migrations/0001_01_01_000000_create_users_table.php   # replaced by ours
unzip -o ../dentai-overlay.zip -d .                                # copy this overlay on top

npm install && npm run build
php artisan migrate:fresh --seed
php artisan storage:link
composer run dev                                                   # http://localhost:8000
php artisan test
```

Demo logins (password `password`): `admin@dentai.test`, `dentist@dentai.test`, `assistant@dentai.test`.
Demo patient: phone `09175550200` — open that visit's **Print slip** to see the 6-digit code.

## 2. Add to `.env` (PayMongo — see docs/PAYMONGO.md)

```
PAYMONGO_SECRET_KEY=sk_test_xxxxxxxx
PAYMONGO_WEBHOOK_SECRET=whsk_xxxxxxxx
PAYMONGO_METHODS=gcash,paymaya,card,qrph
```

## 3. How people use it

| Who | What happens |
|---|---|
| **Patient** | Books at `/book` (name, phone, gender required; problem type, name, how it feels, pain 0–10). An account is created automatically. |
| **Assistant / Dentist** | Open the request → approve the suggested steps → pick day, doctor, time → give the patient the login (or print the slip). |
| **Patient (after)** | Logs in with phone + 6-digit code → sees the visit, medicine calendar (tick doses, reminders), bills, and Pay online if the clinic turned it on. |
| **Dentist** | Adds medicine (what, how much, which times of day, how many days). |
| **Assistant / Admin** | Creates the bill, switches **Online payment** ON/OFF, records money received at the clinic. |
| **Anyone at the clinic** | **Doctors** page → add a day off; that doctor disappears from the picker on those days. **Mark as done** archives the visit under **Records**. |

## 4. Artisan commands (equivalents of the files in this overlay)

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
php artisan make:model MedicationLog -m
php artisan make:model DentistTimeOff -m
php artisan make:model XrayImage -m
php artisan make:model Billing -m
php artisan make:model Payment -m

# Controllers
php artisan make:controller BookingController
php artisan make:controller AuthController
php artisan make:controller DashboardController --invokable
php artisan make:controller AppointmentController
php artisan make:controller SuggestionController
php artisan make:controller PrescriptionController
php artisan make:controller RecordsController
php artisan make:controller DentistController
php artisan make:controller PatientController
php artisan make:controller TreatmentController
php artisan make:controller BillingController
php artisan make:controller PaymongoWebhookController --invokable
php artisan make:controller Portal/PortalController

# Requests, middleware, services, seeders, test
php artisan make:request StoreBookingRequest
php artisan make:request ScheduleAppointmentRequest
php artisan make:middleware EnsureRole
php artisan make:class Services/TriageService
php artisan make:class Services/PatientAccountService
php artisan make:class Services/PaymongoService
php artisan make:seeder CatalogueSeeder
php artisan make:seeder ProductionSeeder
php artisan make:test DentAiTest

php artisan migrate:fresh --seed
php artisan route:list
```

## 5. What changed from your data structure

- Name / phone / gender live on `patients` (the stray `Information` table is gone).
- `appointments`: + `dentist_id`, `problem_type_id`, `problem_title`, `description`, `pain_level`, `urgency`,
  `preferred_date`, `completed_at`. Statuses simplified to **requested → scheduled → completed / cancelled**.
- New tables: `problem_types`, `appointment_suggestions`, `medication_logs`, `dentist_time_offs`.
- `prescriptions` now belong to the **appointment** and carry a schedule (`times`, `start_date`, `duration_days`).
- `users`: patients are users too (`role = patient`, `username` = phone, `patient_id`); staff log in by email.
- `billings.allow_online` is the staff switch; `payments.provider_reference` stops a PayMongo payment being counted twice.
- Money is shown in ₱ (PayMongo charges in PHP).

## 6. Good to know

- Patient reminders: the page pops up notifications **while it is open**; for phone alarms the patient downloads the
  calendar file (“Add alarms to my phone calendar”). Real SMS/push needs an extra service.
- An access code is tied to a phone number + name. Anyone who types another person's exact name and number when booking
  would attach a request to that account, so staff only hand the code over in person.
- The suggestion engine is rule-based (pain level + words like “swelling”), not an AI model, and never decides on its own.
