<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DentistController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PaymongoWebhookController;
use App\Http\Controllers\Portal\PortalController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\RecordsController;
use App\Http\Controllers\SuggestionController;
use App\Http\Controllers\TreatmentController;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

/* ---------- Public ---------- */
Route::view('/', 'home')->name('home');
Route::get('/book', [BookingController::class, 'create'])->name('booking.create');
Route::post('/book', [BookingController::class, 'store'])->middleware('throttle:10,1')->name('booking.store');
Route::get('/book/success', [BookingController::class, 'success'])->name('booking.success');

// PayMongo → us. No login and no CSRF token (PayMongo can't send one); the signed header is the protection.
Route::post('/webhooks/paymongo', PaymongoWebhookController::class)
    ->withoutMiddleware([
        \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
        \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
    ])
    ->name('webhooks.paymongo');

/* ---------- Login ---------- */
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

/* ---------- Patient account ---------- */
Route::middleware(['auth', EnsureRole::class . ':patient'])->prefix('my')->name('portal.')->group(function () {
    Route::get('/', [PortalController::class, 'home'])->name('home');
    Route::get('/medicines', [PortalController::class, 'medicines'])->name('medicines');
    Route::post('/doses', [PortalController::class, 'toggleDose'])->name('doses.toggle');
    Route::get('/medicines/{prescription}/reminders.ics', [PortalController::class, 'reminders'])->name('reminders');
    Route::get('/bills/{billing}', [PortalController::class, 'bill'])->name('bills.show');
    Route::post('/bills/{billing}/pay', [PortalController::class, 'pay'])->name('bills.pay');
});

/* ---------- Clinic (admin / dentist / assistant) ---------- */
Route::middleware(['auth', EnsureRole::class . ':admin,dentist,staff'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Appointments
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/appointments/{appointment}', [AppointmentController::class, 'show'])->name('appointments.show');
    Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
    Route::get('/appointments/{appointment}/slip', [AppointmentController::class, 'slip'])->name('appointments.slip');
    Route::post('/appointments/{appointment}/done', [AppointmentController::class, 'complete'])->name('appointments.done');
    Route::post('/appointments/{appointment}/reopen', [AppointmentController::class, 'reopen'])->name('appointments.reopen');
    Route::post('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::post('/appointments/{appointment}/regenerate', [AppointmentController::class, 'regenerate'])->name('appointments.regenerate');

    // Assistant suggestions
    Route::post('/appointments/{appointment}/suggestions/approve-all', [SuggestionController::class, 'approveAll'])->name('suggestions.approve-all');
    Route::post('/suggestions/{suggestion}/approve', [SuggestionController::class, 'approve'])->name('suggestions.approve');
    Route::post('/suggestions/{suggestion}/dismiss', [SuggestionController::class, 'dismiss'])->name('suggestions.dismiss');

    // Treatments on a visit
    Route::post('/appointments/{appointment}/treatments', [AppointmentController::class, 'addTreatment'])->name('appointments.treatments.store');
    Route::delete('/appointments/{appointment}/treatments/{line}', [AppointmentController::class, 'removeTreatment'])->name('appointments.treatments.destroy');

    // Records (finished visits)
    Route::get('/records', [RecordsController::class, 'index'])->name('records.index');

    // Doctors' days off
    Route::get('/doctors', [DentistController::class, 'index'])->name('dentists.index');
    Route::post('/doctors/{dentist}/time-off', [DentistController::class, 'storeTimeOff'])->name('dentists.time-off.store');
    Route::delete('/doctors/{dentist}/time-off/{timeOff}', [DentistController::class, 'destroyTimeOff'])->name('dentists.time-off.destroy');

    // Patients
    Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
    Route::get('/patients/{patient}', [PatientController::class, 'show'])->name('patients.show');
    Route::put('/patients/{patient}', [PatientController::class, 'update'])->name('patients.update');
    Route::post('/patients/{patient}/reset-code', [PatientController::class, 'resetCode'])->name('patients.reset-code');
    Route::post('/patients/{patient}/history', [PatientController::class, 'storeHistory'])->name('patients.history.store');
    Route::delete('/patients/{patient}/history/{history}', [PatientController::class, 'destroyHistory'])->name('patients.history.destroy');
    Route::post('/patients/{patient}/images', [PatientController::class, 'storeImage'])->name('patients.images.store');

    // Medicine: dentists only
    Route::middleware(EnsureRole::class . ':dentist')->group(function () {
        Route::post('/appointments/{appointment}/prescriptions', [PrescriptionController::class, 'store'])->name('prescriptions.store');
        Route::delete('/appointments/{appointment}/prescriptions/{prescription}', [PrescriptionController::class, 'destroy'])->name('prescriptions.destroy');
    });

    // Billing: admin + assistant
    Route::middleware(EnsureRole::class . ':admin,staff')->group(function () {
        Route::get('/billings', [BillingController::class, 'index'])->name('billings.index');
        Route::post('/appointments/{appointment}/billing', [BillingController::class, 'store'])->name('billings.store');
        Route::get('/billings/{billing}', [BillingController::class, 'show'])->name('billings.show');
        Route::post('/billings/{billing}/online', [BillingController::class, 'toggleOnline'])->name('billings.online');
        Route::post('/billings/{billing}/payments', [BillingController::class, 'storePayment'])->name('payments.store');
    });

    // Price list: admin only
    Route::middleware(EnsureRole::class . ':admin')->group(function () {
        Route::get('/treatments', [TreatmentController::class, 'index'])->name('treatments.index');
        Route::post('/treatments', [TreatmentController::class, 'store'])->name('treatments.store');
        Route::put('/treatments/{treatment}', [TreatmentController::class, 'update'])->name('treatments.update');
        Route::delete('/treatments/{treatment}', [TreatmentController::class, 'destroy'])->name('treatments.destroy');
    });

    Route::get('/sqlite-test', function () {
        return DB::select("sqlite_version() as version");
    });
});
