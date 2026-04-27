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
            CREATE TABLE eph_cross_new (
                ano4         INTEGER NOT NULL,
                trimestre    INTEGER NOT NULL,
                region       INTEGER NOT NULL,
                aglomerado   INTEGER NOT NULL,
                estado       INTEGER NOT NULL DEFAULT 0,
                cat_inac     INTEGER NOT NULL DEFAULT 0,
                cat_ocup     INTEGER NOT NULL DEFAULT 0,
                ch04         INTEGER NOT NULL DEFAULT 0,
                nivel_ed     INTEGER NOT NULL DEFAULT 0,
                ch08         INTEGER NOT NULL DEFAULT 0,
                pp07h        INTEGER NOT NULL DEFAULT 0,
                iv1          INTEGER NOT NULL DEFAULT 0,
                iv3          INTEGER NOT NULL DEFAULT 0,
                ii7          INTEGER NOT NULL DEFAULT 0,
                hacinamiento INTEGER NOT NULL DEFAULT 0,
                decifr       INTEGER NOT NULL DEFAULT 0,
                v17          INTEGER NOT NULL DEFAULT 0,
                total        INTEGER NOT NULL DEFAULT 0,
                PRIMARY KEY (ano4, trimestre, region, aglomerado,
                             estado, cat_inac, cat_ocup, ch04, nivel_ed, ch08, pp07h,
                             iv1, iv3, ii7, hacinamiento, decifr, v17)
            )
        ');

        DB::connection('sqlite')->statement('
            INSERT INTO eph_cross_new
                (ano4, trimestre, region, aglomerado, estado, cat_inac, cat_ocup,
                 ch04, nivel_ed, ch08, pp07h, iv1, iv3, ii7, hacinamiento, decifr, v17, total)
            SELECT ano4, trimestre, region, aglomerado, estado, cat_inac, cat_ocup,
                   ch04, nivel_ed, ch08, pp07h, iv1, iv3, ii7, hacinamiento, decifr, 0, total
            FROM eph_cross
        ');

        DB::connection('sqlite')->statement('DROP TABLE eph_cross');
        DB::connection('sqlite')->statement('ALTER TABLE eph_cross_new RENAME TO eph_cross');
    }

    public function down(): void
    {
        DB::connection('sqlite')->statement('
            CREATE TABLE eph_cross_new (
                ano4         INTEGER NOT NULL,
                trimestre    INTEGER NOT NULL,
                region       INTEGER NOT NULL,
                aglomerado   INTEGER NOT NULL,
                estado       INTEGER NOT NULL DEFAULT 0,
                cat_inac     INTEGER NOT NULL DEFAULT 0,
                cat_ocup     INTEGER NOT NULL DEFAULT 0,
                ch04         INTEGER NOT NULL DEFAULT 0,
                nivel_ed     INTEGER NOT NULL DEFAULT 0,
                ch08         INTEGER NOT NULL DEFAULT 0,
                pp07h        INTEGER NOT NULL DEFAULT 0,
                iv1          INTEGER NOT NULL DEFAULT 0,
                iv3          INTEGER NOT NULL DEFAULT 0,
                ii7          INTEGER NOT NULL DEFAULT 0,
                hacinamiento INTEGER NOT NULL DEFAULT 0,
                decifr       INTEGER NOT NULL DEFAULT 0,
                total        INTEGER NOT NULL DEFAULT 0,
                PRIMARY KEY (ano4, trimestre, region, aglomerado,
                             estado, cat_inac, cat_ocup, ch04, nivel_ed, ch08, pp07h,
                             iv1, iv3, ii7, hacinamiento, decifr)
            )
        ');

        DB::connection('sqlite')->statement('
            INSERT INTO eph_cross_new
                (ano4, trimestre, region, aglomerado, estado, cat_inac, cat_ocup,
                 ch04, nivel_ed, ch08, pp07h, iv1, iv3, ii7, hacinamiento, decifr, total)
            SELECT ano4, trimestre, region, aglomerado, estado, cat_inac, cat_ocup,
                   ch04, nivel_ed, ch08, pp07h, iv1, iv3, ii7, hacinamiento, decifr, SUM(total)
            FROM eph_cross
            GROUP BY ano4, trimestre, region, aglomerado, estado, cat_inac, cat_ocup,
                     ch04, nivel_ed, ch08, pp07h, iv1, iv3, ii7, hacinamiento, decifr
        ');

        DB::connection('sqlite')->statement('DROP TABLE eph_cross');
        DB::connection('sqlite')->statement('ALTER TABLE eph_cross_new RENAME TO eph_cross');
    }
};
