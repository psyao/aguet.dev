<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Pure-PHP MySQL dump: no mysqldump binary required, so it works on shared
 * hosting (Infomaniak) where the CLI tool is not on PATH. The whole dump is
 * built in memory — fine for this site's small content tables.
 *
 * ponytail: in-memory, no chunking/gzip. Add row-streaming if a table grows huge.
 */
class DatabaseDumper
{
    public function dump(): string
    {
        $pdo = DB::connection()->getPdo();

        $sql = "-- aguet.dev database dump\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($this->tables() as $table) {
            $create = DB::selectOne("SHOW CREATE TABLE `{$table}`");
            // SHOW CREATE TABLE returns the statement under "Create Table".
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= ((array) $create)['Create Table'].";\n\n";

            foreach (DB::table($table)->cursor() as $row) {
                $row = (array) $row;
                $cols = '`'.implode('`, `', array_keys($row)).'`';
                $vals = implode(', ', array_map(
                    fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v),
                    array_values($row),
                ));
                $sql .= "INSERT INTO `{$table}` ({$cols}) VALUES ({$vals});\n";
            }
            $sql .= "\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        return $sql;
    }

    /** @return list<string> */
    private function tables(): array
    {
        return array_map(
            fn ($row) => array_values((array) $row)[0],
            DB::select('SHOW TABLES'),
        );
    }
}
