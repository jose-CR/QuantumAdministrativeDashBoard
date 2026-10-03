#!/usr/bin/env bash
# Resincroniza las secuencias de PostgreSQL (id) con MAX(id) de cada tabla.
# asi se usa  ./scripts/fix-sequences.sh
set -euo pipefail
cd "$(dirname "$0")/.."

read -r -d '' PHP <<'EOF' || true
$tables = DB::select("SELECT t.table_name FROM information_schema.tables t JOIN information_schema.columns c ON c.table_schema = t.table_schema AND c.table_name = t.table_name WHERE t.table_schema = 'public' AND t.table_type = 'BASE TABLE' AND c.column_name = 'id'");
foreach ($tables as $r) {
    $t = $r->table_name;
    $seq = DB::selectOne("SELECT pg_get_serial_sequence(?, 'id') AS s", ['"public"."' . $t . '"'])->s;
    if (! $seq) { echo "SKIP $t (sin secuencia)\n"; continue; }
    DB::statement("SELECT setval(?, COALESCE((SELECT MAX(id) FROM \"$t\"), 1), (SELECT MAX(id) IS NOT NULL FROM \"$t\"))", [$seq]);
    echo "OK   $t\n";
}
EOF

php artisan tinker --execute="$PHP"
