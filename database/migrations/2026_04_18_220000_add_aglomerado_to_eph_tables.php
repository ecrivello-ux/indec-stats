<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'eph_individual_period', 'eph_individual_edu', 'eph_individual_cat_ocup',
            'eph_individual_gender', 'eph_hogar_period', 'eph_hogar_decil',
            'eph_hogar_vivienda', 'eph_hogar_tenencia',
        ];
        foreach ($tables as $t) {
            Schema::dropIfExists($t);
        }

        Schema::create('eph_individual_period', function (Blueprint $table) {
            $table->integer('ano4');
            $table->tinyInteger('trimestre');
            $table->tinyInteger('region');
            $table->smallInteger('aglomerado');
            $table->bigInteger('total_personas')->default(0);
            $table->bigInteger('ocupados')->default(0);
            $table->bigInteger('desocupados')->default(0);
            $table->bigInteger('inactivos')->default(0);
            $table->decimal('tasa_actividad', 6, 2)->nullable();
            $table->decimal('tasa_empleo', 6, 2)->nullable();
            $table->decimal('tasa_desocupacion', 6, 2)->nullable();
            $table->decimal('tasa_subocupacion', 6, 2)->nullable();
            $table->bigInteger('ingreso_p47t_prom')->nullable();
            $table->bigInteger('ingreso_p21_prom')->nullable();
            $table->decimal('edad_promedio', 5, 1)->nullable();
            $table->bigInteger('varones')->default(0);
            $table->bigInteger('mujeres')->default(0);
            $table->primary(['ano4', 'trimestre', 'region', 'aglomerado']);
        });

        Schema::create('eph_individual_edu', function (Blueprint $table) {
            $table->integer('ano4');
            $table->tinyInteger('trimestre');
            $table->tinyInteger('region');
            $table->smallInteger('aglomerado');
            $table->tinyInteger('nivel_ed');
            $table->bigInteger('total')->default(0);
            $table->primary(['ano4', 'trimestre', 'region', 'aglomerado', 'nivel_ed']);
        });

        Schema::create('eph_individual_cat_ocup', function (Blueprint $table) {
            $table->integer('ano4');
            $table->tinyInteger('trimestre');
            $table->tinyInteger('region');
            $table->smallInteger('aglomerado');
            $table->tinyInteger('cat_ocup');
            $table->bigInteger('total')->default(0);
            $table->primary(['ano4', 'trimestre', 'region', 'aglomerado', 'cat_ocup']);
        });

        Schema::create('eph_individual_gender', function (Blueprint $table) {
            $table->integer('ano4');
            $table->tinyInteger('trimestre');
            $table->tinyInteger('region');
            $table->smallInteger('aglomerado');
            $table->tinyInteger('ch04');
            $table->bigInteger('total')->default(0);
            $table->primary(['ano4', 'trimestre', 'region', 'aglomerado', 'ch04']);
        });

        Schema::create('eph_hogar_period', function (Blueprint $table) {
            $table->integer('ano4');
            $table->tinyInteger('trimestre');
            $table->tinyInteger('region');
            $table->smallInteger('aglomerado');
            $table->bigInteger('total_hogares')->default(0);
            $table->bigInteger('itf_promedio')->nullable();
            $table->bigInteger('ipcf_promedio')->nullable();
            $table->decimal('miembros_promedio', 5, 2)->nullable();
            $table->decimal('pct_hacinamiento', 6, 2)->nullable();
            $table->primary(['ano4', 'trimestre', 'region', 'aglomerado']);
        });

        Schema::create('eph_hogar_decil', function (Blueprint $table) {
            $table->integer('ano4');
            $table->tinyInteger('trimestre');
            $table->tinyInteger('region');
            $table->smallInteger('aglomerado');
            $table->tinyInteger('decifr');
            $table->bigInteger('total')->default(0);
            $table->primary(['ano4', 'trimestre', 'region', 'aglomerado', 'decifr']);
        });

        Schema::create('eph_hogar_vivienda', function (Blueprint $table) {
            $table->integer('ano4');
            $table->tinyInteger('trimestre');
            $table->tinyInteger('region');
            $table->smallInteger('aglomerado');
            $table->tinyInteger('iv1');
            $table->bigInteger('total')->default(0);
            $table->primary(['ano4', 'trimestre', 'region', 'aglomerado', 'iv1']);
        });

        Schema::create('eph_hogar_tenencia', function (Blueprint $table) {
            $table->integer('ano4');
            $table->tinyInteger('trimestre');
            $table->tinyInteger('region');
            $table->smallInteger('aglomerado');
            $table->tinyInteger('ii7');
            $table->bigInteger('total')->default(0);
            $table->primary(['ano4', 'trimestre', 'region', 'aglomerado', 'ii7']);
        });
    }

    public function down(): void
    {
        // Handled by the original migration's down()
    }
};
