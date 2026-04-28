<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function getConnection(): string
    {
        return 'sqlite';
    }

    public function up(): void
    {
        DB::connection('sqlite')->statement('
            CREATE TABLE IF NOT EXISTS eph_cross_hogar (
                ano4      INTEGER NOT NULL,
                trimestre INTEGER NOT NULL,
                region    INTEGER NOT NULL,
                aglomerado INTEGER NOT NULL,
                dim_key   TEXT    NOT NULL,
                dim_val   INTEGER NOT NULL,
                total     INTEGER NOT NULL DEFAULT 0,
                PRIMARY KEY (ano4, trimestre, region, aglomerado, dim_key, dim_val)
            )
        ');

        $db = DB::connection('sqlite');

        $dims = ['ii7', 'iv1', 'iv3', 'hacinamiento', 'decifr', 'v17'];
        foreach ($dims as $dim) {
            $db->statement("
                INSERT OR IGNORE INTO eph_cross_hogar (ano4, trimestre, region, aglomerado, dim_key, dim_val, total)
                SELECT ano4, trimestre, region, aglomerado, '{$dim}', {$dim}, SUM(total)
                FROM eph_cross
                WHERE {$dim} > 0
                GROUP BY ano4, trimestre, region, aglomerado, {$dim}
            ");
        }
    }

    public function down(): void
    {
        DB::connection('sqlite')->statement('DROP TABLE IF EXISTS eph_cross_hogar');
    }
};
