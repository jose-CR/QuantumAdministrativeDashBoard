<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixSequences extends Command
{
    protected $signature = 'db:fix-sequences';

    protected $description = 'Resincroniza las secuencias de PostgreSQL (id) con el MAX(id) de cada tabla';

    public function handle(): int
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->error('Este comando solo funciona con PostgreSQL.');

            return self::FAILURE;
        }

        $tables = DB::select("
            SELECT t.table_name
            FROM information_schema.tables t
            JOIN information_schema.columns c
              ON c.table_schema = t.table_schema AND c.table_name = t.table_name
            WHERE t.table_schema = 'public'
              AND t.table_type = 'BASE TABLE'
              AND c.column_name = 'id'
            ORDER BY t.table_name
        ");

        foreach ($tables as $row) {
            $table = $row->table_name;
            $seq = DB::selectOne("SELECT pg_get_serial_sequence(?, 'id') AS s", ['"public"."' . $table . '"'])->s;

            if (! $seq) {
                $this->line("SKIP  {$table} (sin secuencia)");

                continue;
            }

            DB::statement(
                "SELECT setval(?, COALESCE((SELECT MAX(id) FROM \"{$table}\"), 1), (SELECT MAX(id) IS NOT NULL FROM \"{$table}\"))",
                [$seq]
            );

            $this->info("OK    {$table}");
        }

        return self::SUCCESS;
    }
}