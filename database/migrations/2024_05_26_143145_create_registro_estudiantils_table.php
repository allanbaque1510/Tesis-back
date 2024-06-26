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
        Schema::create('registro_estudiantil', function (Blueprint $table) {
            $table->id('id_registro');
            $table->unsignedBigInteger('id_estudiante');
            $table->unsignedBigInteger('id_periodo');
            $table->unsignedBigInteger('id_carrera');
            $table->unsignedBigInteger('id_habilitado');
            $table->integer('nivel_actual');
            $table->integer('nivel_anterior');
            $table->integer('repetidor');
            $table->tinyInteger('estado')->default(1);
            $table->timestamps();

            $table->foreign('id_estudiante')->references('id')->on('estudiantes')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('id_periodo')->references('id')->on('periodo')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('id_carrera')->references('id')->on('carrera')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('id_habilitado')->references('id')->on('habilitado')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registro_estudiantil');
    }
};
