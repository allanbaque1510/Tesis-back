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
        Schema::create('estudiantes_reprobados', function (Blueprint $table) {
            $table->id('id_estudiantes_reprobados');
            
            $table->unsignedBigInteger('id_periodo');
            $table->foreign('id_periodo')->references('id')->on('periodo')->onUpdate('cascade');
            
            $table->unsignedBigInteger('id_carrera');
            $table->foreign('id_carrera')->references('id')->on('carrera')->onUpdate('cascade');
            
            $table->unsignedBigInteger('id_materia');
            $table->foreign('id_materia')->references('id_materia')->on('materias')->onUpdate('cascade');
            
            $table->unsignedBigInteger('id_grupo');
            $table->foreign('id_grupo')->references('id_grupo')->on('grupo_estudiantes')->onUpdate('cascade');
            
            $table->unsignedBigInteger('id_estudiante');
            $table->foreign('id_estudiante')->references('id')->on('estudiantes')->onUpdate('cascade');
            
            $table->double('asistencia');
            $table->double('promedio');
            $table->tinyInteger('reprobado_asistencia')->index();
            $table->tinyInteger('reprobado_nota')->index();
            
            $table->unsignedBigInteger('id_archivo');
            $table->foreign('id_archivo')->references('id')->on('archivos_subidos')->onUpdate('cascade')->onDelete("cascade");


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estudiantes_reprobados');
    }
};
