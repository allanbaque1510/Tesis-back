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
        Schema::create('carrera_docente_materias', function (Blueprint $table) {
            $table->id('id_carrera_docente_materia');
            $table->unsignedBigInteger('id_periodo');
            $table->unsignedBigInteger('id_carrera');
            $table->unsignedBigInteger('id_materia');
            $table->unsignedBigInteger('id_grupo');
            $table->unsignedBigInteger('id_docente');
            
            $table->foreign('id_periodo')->references('id')->on('periodo')->onUpdate('cascade');
            $table->foreign('id_carrera')->references('id')->on('carrera')->onUpdate('cascade');
            $table->foreign('id_materia')->references('id_materia')->on('materias')->onUpdate('cascade');
            $table->foreign('id_grupo')->references('id_grupo')->on('grupo_estudiantes')->onUpdate('cascade');
            $table->foreign('id_docente')->references('id_docente')->on('docentes')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carrera_docente_materias');
    }
};
