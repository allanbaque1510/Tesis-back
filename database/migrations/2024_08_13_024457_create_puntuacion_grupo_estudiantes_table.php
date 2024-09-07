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
        Schema::create('puntuacion_logro_grupo_estudiante', function (Blueprint $table) {
            $table->id('id_puntuacion_estudiante');
            $table->unsignedBigInteger('id_logros_mat_carr');
            $table->unsignedInteger("pregunta");
            $table->double("puntuacion");
            $table->unsignedBigInteger('id_archivo')->nullable();
            
            
            
            $table->foreign('id_logros_mat_carr')->references('id_logros_mat_carr_per_doc')->on('logros_mat_carr_per_doc')->onUpdate('cascade');
            $table->unsignedInteger("id_estudiante_grupo");
            $table->foreign('id_archivo')->references('id')->on('archivos_subidos')->onUpdate('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('puntuacion_grupo_estudiantes');
    }
};
