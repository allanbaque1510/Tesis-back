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
        Schema::create('logros_mat_carr_per_doc', function (Blueprint $table) {
            $table->id('id_logros_mat_carr_per_doc');
            $table->unsignedBigInteger('id_carrera_docente_materia');
            $table->unsignedBigInteger("id_logros");
            $table->foreign('id_logros')->references('id_logros')->on('logros_aprendizaje')->onUpdate('cascade');
            $table->foreign('id_carrera_docente_materia')->references('id_carrera_docente_materia')->on('carrera_docente_materias')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logros_materia_periodo_docentes');
    }
};
