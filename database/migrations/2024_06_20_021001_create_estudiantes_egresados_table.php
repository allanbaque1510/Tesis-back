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
        Schema::create('estudiantes_egresados', function (Blueprint $table) {
            $table->id('id_estudiantes_egresados');
            $table->unsignedBigInteger('id_estudiante');
            $table->unsignedBigInteger('id_periodo');
            $table->unsignedBigInteger('id_periodo_relacionado');
            $table->unsignedBigInteger('id_carrera');
            $table->double('prom_materia');
            $table->double('prom_titulacion');
            $table->double('prom_general');
            $table->tinyInteger('estado')->default(1);

            $table->foreign('id_estudiante')->references('id')->on('estudiantes')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('id_periodo')->references('id')->on('periodo')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('id_periodo_relacionado')->references('id')->on('periodo')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('id_carrera')->references('id')->on('carrera')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estudiantes_egresados');
    }
};
