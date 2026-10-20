<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Restores a SQL dump (as produced by {@see DatabaseDumper}) over the live
 * database. Destructive: every table is dropped before the import, so the
 * database holds exactly the dump.
 *
 * The migrations then run, bringing a dump taken before a schema change up to
 * the code's schema (a pre-Velox dump gets Velox's tables and the Role and MFA
 * columns). Last, the current admin row is re-asserted whole (name, password,
 * Role, MFA secret and recovery codes), so the Account that triggered the
 * restore always still signs in, whatever the dump carried. Both run even when
 * the import fails, and the re-assert even when the migrations fail, so a
 * broken dump locks the Admin only if it leaves no `users` table behind.
 *
 * ponytail: no surrounding transaction — MySQL DDL (DROP/CREATE) can't roll
 * back anyway. The confirmation modal + admin re-assert are the safety net.
 */
class DatabaseRestorer
{
    public function restore(string $sql, User $admin): void
    {
        Schema::dropAllTables();

        try {
            Schema::withoutForeignKeyConstraints(fn (): bool => DB::unprepared($sql));
        } finally {
            try {
                Artisan::call('migrate', ['--force' => true]);
            } finally {
                $this->reassertAdmin($admin);
            }
        }
    }

    /**
     * Writes raw values (DB::table, not Eloquent): the stored hash and the
     * encrypted MFA columns go back as they are, not re-hashed or re-encrypted.
     */
    private function reassertAdmin(User $admin): void
    {
        DB::table($admin->getTable())->updateOrInsert(
            ['email' => $admin->getRawOriginal('email')],
            [
                ...collect(['name', 'password', 'role', 'app_authentication_secret', 'app_authentication_recovery_codes'])
                    ->mapWithKeys(fn (string $column): array => [$column => $admin->getRawOriginal($column)]),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }
}
