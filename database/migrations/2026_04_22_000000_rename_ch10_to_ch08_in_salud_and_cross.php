<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function getConnection(): string
    {
        return 'sqlite';
    }

    public function up(): void
    {
        DB::connection('sqlite')->statement('ALTER TABLE eph_individual_salud RENAME COLUMN ch10 TO ch08');
        DB::connection('sqlite')->statement('ALTER TABLE eph_cross RENAME COLUMN ch10 TO ch08');
    }

    public function down(): void
    {
        DB::connection('sqlite')->statement('ALTER TABLE eph_individual_salud RENAME COLUMN ch08 TO ch10');
        DB::connection('sqlite')->statement('ALTER TABLE eph_cross RENAME COLUMN ch08 TO ch10');
    }
};
