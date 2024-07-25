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
        Schema::create('puntuacion_logros', function (Blueprint $table) {
            
            $table->id('id_puntuacion_logros');

            $table->integer('pregunta');
            $table->double('puntuacion');

            $table->unsignedBigInteger('id_archivo');
            $table->foreign('id_archivo')->references('id')->on('archivos_subidos')->onUpdate('cascade');

            $table->unsignedBigInteger('id_logros_mat_carr_per_doc');
            $table->foreign('id_logros_mat_carr_per_doc')->references('id_logros_mat_carr_per_doc')->on('logros_mat_carr_per_doc')->onUpdate('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('puntuacion_logros');
    }
};
