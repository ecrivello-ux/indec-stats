<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eph_individual_informalidad', function (Blueprint $table) {
            $table->integer('ano4');
            $table->tinyInteger('trimestre');
            $table->tinyInteger('region');
            $table->smallInteger('aglomerado');
            $table->bigInteger('total_asalariados')->default(0);
            $table->bigInteger('no_registrados')->default(0);
            $table->primary(['ano4', 'trimestre', 'region', 'aglomerado']);
        });

        Schema::create('eph_individual_intensidad', function (Blueprint $table) {
            $table->integer('ano4');
            $table->tinyInteger('trimestre');
            $table->tinyInteger('region');
            $table->smallInteger('aglomerado');
            $table->bigInteger('sobreocupados')->default(0);
            $table->bigInteger('subocu_demandante')->default(0);
            $table->bigInteger('subocu_no_demandante')->default(0);
            $table->primary(['ano4', 'trimestre', 'region', 'aglomerado']);
        });

        Schema::create('eph_individual_salud', function (Blueprint $table) {
            $table->integer('ano4');
            $table->tinyInteger('trimestre');
            $table->tinyInteger('region');
            $table->smallInteger('aglomerado');
            $table->tinyInteger('ch10');
            $table->bigInteger('total')->default(0);
            $table->primary(['ano4', 'trimestre', 'region', 'aglomerado', 'ch10']);
        });

        Schema::create('eph_individual_ingreso_genero', function (Blueprint $table) {
            $table->integer('ano4');
            $table->tinyInteger('trimestre');
            $table->tinyInteger('region');
            $table->smallInteger('aglomerado');
            $table->tinyInteger('ch04');
            $table->bigInteger('ingreso_prom')->nullable();
            $table->bigInteger('total')->default(0);
            $table->primary(['ano4', 'trimestre', 'region', 'aglomerado', 'ch04']);
        });

        Schema::create('eph_individual_decindr', function (Blueprint $table) {
            $table->integer('ano4');
            $table->tinyInteger('trimestre');
            $table->tinyInteger('region');
            $table->smallInteger('aglomerado');
            $table->tinyInteger('decindr');
            $table->bigInteger('total')->default(0);
            $table->primary(['ano4', 'trimestre', 'region', 'aglomerado', 'decindr']);
        });

        Schema::create('eph_individual_cat_inac', function (Blueprint $table) {
            $table->integer('ano4');
            $table->tinyInteger('trimestre');
            $table->tinyInteger('region');
            $table->smallInteger('aglomerado');
            $table->tinyInteger('cat_inac');
            $table->bigInteger('total')->default(0);
            $table->primary(['ano4', 'trimestre', 'region', 'aglomerado', 'cat_inac']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eph_individual_informalidad');
        Schema::dropIfExists('eph_individual_intensidad');
        Schema::dropIfExists('eph_individual_salud');
        Schema::dropIfExists('eph_individual_ingreso_genero');
        Schema::dropIfExists('eph_individual_decindr');
        Schema::dropIfExists('eph_individual_cat_inac');
    }
};
