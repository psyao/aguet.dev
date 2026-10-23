<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Until the connection ran in UTC, MySQL and MariaDB read each date Laravel handed them in the server's own zone
     * (CEST in production) and stored it shifted by that zone's offset. This shifts every TIMESTAMP column back, once,
     * under the new UTC session: each value becomes what Laravel wrote. DATETIME columns were never converted.
     */
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        // Run under the old session zone, it would shift the dates a second time instead.
        if (DB::selectOne('SELECT @@session.time_zone AS zone')->zone !== '+00:00') {
            throw new RuntimeException("Set the connection's 'timezone' to '+00:00' before converting its dates.");
        }

        $columns = collect(DB::select(
            "SELECT table_name AS name, column_name AS col FROM information_schema.columns WHERE table_schema = DATABASE() AND data_type = 'timestamp'"
        ))->groupBy('name');

        // One UPDATE per table, every column set at once, so no ON UPDATE CURRENT_TIMESTAMP column overwrites itself.
        foreach ($columns as $table => $cols) {
            DB::update("UPDATE `{$table}` SET ".$cols->map(fn (object $c): string => "`{$c->col}` = CONVERT_TZ(`{$c->col}`, '+00:00', @@global.time_zone)")->implode(', '));
        }
    }

    public function down(): void
    {
        // One-way: the shifted dates aren't worth restoring.
    }
};
