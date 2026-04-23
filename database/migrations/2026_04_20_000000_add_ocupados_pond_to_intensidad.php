<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('sqlite')->table('eph_individual_intensidad', function (Blueprint $table) {
            $table->bigInteger('ocupados_pond')->default(0)->after('subocu_no_demandante');
        });
    }

    public function down(): void
    {
        Schema::connection('sqlite')->table('eph_individual_intensidad', function (Blueprint $table) {
            $table->dropColumn('ocupados_pond');
        });
    }
};
