<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('sqlite')->create('eph_cross', function (Blueprint $table) {
            $table->integer('ano4');
            $table->tinyInteger('trimestre');
            $table->tinyInteger('region');
            $table->smallInteger('aglomerado');
            // Individual dimensions
            $table->tinyInteger('estado')->default(0);
            $table->tinyInteger('cat_inac')->default(0);
            $table->tinyInteger('cat_ocup')->default(0);
            $table->tinyInteger('ch04')->default(0);
            $table->tinyInteger('nivel_ed')->default(0);
            $table->tinyInteger('ch10')->default(0);
            $table->tinyInteger('pp07h')->default(0);
            // Hogar dimensions
            $table->tinyInteger('iv1')->default(0);
            $table->tinyInteger('iv3')->default(0);
            $table->tinyInteger('ii7')->default(0);
            $table->tinyInteger('hacinamiento')->default(0);
            $table->tinyInteger('decifr')->default(0);
            // Weight
            $table->bigInteger('total')->default(0);

            $table->primary([
                'ano4', 'trimestre', 'region', 'aglomerado',
                'estado', 'cat_inac', 'cat_ocup', 'ch04', 'nivel_ed', 'ch10', 'pp07h',
                'iv1', 'iv3', 'ii7', 'hacinamiento', 'decifr',
            ]);
        });
    }

    public function down(): void
    {
        Schema::connection('sqlite')->dropIfExists('eph_cross');
    }
};
