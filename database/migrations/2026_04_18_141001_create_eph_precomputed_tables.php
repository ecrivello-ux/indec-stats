<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('eph_individual_period', function (Blueprint $table) {
            $table->integer('ano4');
            $table->tinyInteger('trimestre');
            $table->tinyInteger('region');
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
            $table->primary(['ano4', 'trimestre', 'region']);
        });

        Schema::create('eph_individual_edu', function (Blueprint $table) {
            $table->integer('ano4');
            $table->tinyInteger('trimestre');
            $table->tinyInteger('region');
            $table->tinyInteger('nivel_ed');
            $table->bigInteger('total')->default(0);
            $table->primary(['ano4', 'trimestre', 'region', 'nivel_ed']);
        });

        Schema::create('eph_individual_cat_ocup', function (Blueprint $table) {
            $table->integer('ano4');
            $table->tinyInteger('trimestre');
            $table->tinyInteger('region');
            $table->tinyInteger('cat_ocup');
            $table->bigInteger('total')->default(0);
            $table->primary(['ano4', 'trimestre', 'region', 'cat_ocup']);
        });

        Schema::create('eph_individual_gender', function (Blueprint $table) {
            $table->integer('ano4');
            $table->tinyInteger('trimestre');
            $table->tinyInteger('region');
            $table->tinyInteger('ch04');
            $table->bigInteger('total')->default(0);
            $table->primary(['ano4', 'trimestre', 'region', 'ch04']);
        });

        Schema::create('eph_hogar_period', function (Blueprint $table) {
            $table->integer('ano4');
            $table->tinyInteger('trimestre');
            $table->tinyInteger('region');
            $table->bigInteger('total_hogares')->default(0);
            $table->bigInteger('itf_promedio')->nullable();
            $table->bigInteger('ipcf_promedio')->nullable();
            $table->decimal('miembros_promedio', 5, 2)->nullable();
            $table->decimal('pct_hacinamiento', 6, 2)->nullable();
            $table->primary(['ano4', 'trimestre', 'region']);
        });

        Schema::create('eph_hogar_decil', function (Blueprint $table) {
            $table->integer('ano4');
            $table->tinyInteger('trimestre');
            $table->tinyInteger('region');
            $table->tinyInteger('decifr');
            $table->bigInteger('total')->default(0);
            $table->primary(['ano4', 'trimestre', 'region', 'decifr']);
        });

        Schema::create('eph_hogar_vivienda', function (Blueprint $table) {
            $table->integer('ano4');
            $table->tinyInteger('trimestre');
            $table->tinyInteger('region');
            $table->tinyInteger('iv1');
            $table->bigInteger('total')->default(0);
            $table->primary(['ano4', 'trimestre', 'region', 'iv1']);
        });

        Schema::create('eph_hogar_tenencia', function (Blueprint $table) {
            $table->integer('ano4');
            $table->tinyInteger('trimestre');
            $table->tinyInteger('region');
            $table->tinyInteger('ii7');
            $table->bigInteger('total')->default(0);
            $table->primary(['ano4', 'trimestre', 'region', 'ii7']);
        });

        Schema::create('eph_meta', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eph_individual_period');
        Schema::dropIfExists('eph_individual_edu');
        Schema::dropIfExists('eph_individual_cat_ocup');
        Schema::dropIfExists('eph_individual_gender');
        Schema::dropIfExists('eph_hogar_period');
        Schema::dropIfExists('eph_hogar_decil');
        Schema::dropIfExists('eph_hogar_vivienda');
        Schema::dropIfExists('eph_hogar_tenencia');
        Schema::dropIfExists('eph_meta');
    }
};
