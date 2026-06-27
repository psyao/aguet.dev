<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Restores a SQL dump (as produced by {@see DatabaseDumper}) over the live
 * database. Destructive: the dump's DROP/CREATE statements wipe existing tables.
 *
 * To avoid locking the admin out, the current admin row is re-asserted after
 * the import — so the login you used to trigger the restore always still works,
 * regardless of what password hash the dump carried.
 *
 * ponytail: no surrounding transaction — MySQL DDL (DROP/CREATE) can't roll
 * back anyway. The confirmation modal + admin re-assert are the safety net.
 */
class DatabaseRestorer
{
    public function restore(string $sql, User $admin): void
    {
        DB::unprepared('SET FOREIGN_KEY_CHECKS=0;'.$sql.'SET FOREIGN_KEY_CHECKS=1;');

        // Write the stored hash raw (DB::table, not Eloquent) so the model's
        // 'hashed' cast doesn't re-hash an already-hashed value.
        DB::table('users')->updateOrInsert(
            ['email' => $admin->email],
            [
                'name' => $admin->name,
                'password' => $admin->password,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }
}
