<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\User;

/**
 * Every patient gets a login automatically when they book:
 *   username = their phone number, password = a 6-digit access code.
 * Staff read the code out (or print it on the slip) after the visit is arranged.
 */
class PatientAccountService
{
    public function ensureFor(Patient $patient): User
    {
        if ($patient->user) {
            return $patient->user;
        }

        $code = $this->newCode();
        $patient->update(['access_code' => $code]);

        $base = preg_replace('/\D+/', '', $patient->phone_number) ?: 'p' . $patient->id;
        $username = User::where('username', $base)->exists() ? "{$base}-{$patient->id}" : $base;

        $user = User::create([
            'name' => $patient->full_name,
            'username' => $username,
            'password' => $code,
            'role' => 'patient',
            'patient_id' => $patient->id,
        ]);

        $patient->setRelation('user', $user);

        return $user;
    }

    public function resetCode(Patient $patient): string
    {
        $user = $this->ensureFor($patient);
        $code = $this->newCode();

        $patient->update(['access_code' => $code]);
        $user->update(['password' => $code]);

        return $code;
    }

    private function newCode(): string
    {
        return (string) random_int(100000, 999999);
    }
}
