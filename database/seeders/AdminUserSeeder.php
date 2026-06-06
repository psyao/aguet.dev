<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * The single Filament admin user, taken from env (never hard-coded).
     * Set ADMIN_NAME / ADMIN_EMAIL / ADMIN_PASSWORD in your .env.
     */
    public function run(): void
    {
        $email = config('aguet.admin.email');
        $password = config('aguet.admin.password');

        if (! $email || ! $password) {
            $this->command?->warn('AdminUserSeeder skipped: set ADMIN_EMAIL and ADMIN_PASSWORD in .env.');

            return;
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => config('aguet.admin.name', 'Admin'),
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );
    }
}
