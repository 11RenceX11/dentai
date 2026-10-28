<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Use this on the live site instead of DatabaseSeeder (which creates fake patients and
 * demo logins). Creates the price list, the problem-type drop-down and ONE admin.
 *
 *   ADMIN_EMAIL=you@example.com ADMIN_PASSWORD=a-long-password php artisan db:seed --class=ProductionSeeder
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CatalogueSeeder::class);

        $email = env('ADMIN_EMAIL', 'admin@example.com');
        $password = env('ADMIN_PASSWORD') ?: Str::password(16);

        User::updateOrCreate(
            ['email' => $email],
            ['name' => 'Clinic Admin', 'password' => $password, 'role' => 'admin', 'is_active' => true],
        );

        $this->command?->info("Admin login: {$email}" . (env('ADMIN_PASSWORD') ? '' : "  password: {$password}  (write it down)"));
    }
}
