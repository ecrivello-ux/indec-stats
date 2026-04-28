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
        $db = DB::connection('sqlite');
        $db->statement('CREATE INDEX IF NOT EXISTS idx_cross_estado   ON eph_cross(ano4, estado,   ii7, iv1, iv3, hacinamiento, decifr, v17)');
        $db->statement('CREATE INDEX IF NOT EXISTS idx_cross_cat_inac ON eph_cross(ano4, cat_inac, ii7, iv1, iv3, hacinamiento, decifr, v17)');
        $db->statement('CREATE INDEX IF NOT EXISTS idx_cross_cat_ocup ON eph_cross(ano4, cat_ocup, ii7, iv1, iv3, hacinamiento, decifr, v17)');
        $db->statement('CREATE INDEX IF NOT EXISTS idx_cross_ch04     ON eph_cross(ano4, ch04,     ii7, iv1, iv3, hacinamiento, decifr, v17)');
        $db->statement('CREATE INDEX IF NOT EXISTS idx_cross_nivel_ed ON eph_cross(ano4, nivel_ed, ii7, iv1, iv3, hacinamiento, decifr, v17)');
        $db->statement('CREATE INDEX IF NOT EXISTS idx_cross_ch08     ON eph_cross(ano4, ch08,     ii7, iv1, iv3, hacinamiento, decifr, v17)');
        $db->statement('CREATE INDEX IF NOT EXISTS idx_cross_pp07h    ON eph_cross(ano4, pp07h,    ii7, iv1, iv3, hacinamiento, decifr, v17)');
    }

    public function down(): void
    {
        $db = DB::connection('sqlite');
        foreach (['idx_cross_estado','idx_cross_cat_inac','idx_cross_cat_ocup','idx_cross_ch04','idx_cross_nivel_ed','idx_cross_ch08','idx_cross_pp07h'] as $idx) {
            $db->statement("DROP INDEX IF EXISTS {$idx}");
        }
    }
};
