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
        Schema::create('archivos_subidos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_periodo');
            $table->foreign('id_periodo')->references('id')->on('periodo')->onDelete('cascade')->onUpdate('cascade');
            
            $table->unsignedBigInteger('id_carrera');
            $table->foreign('id_carrera')->references('id')->on('carrera')->onDelete('cascade')->onUpdate('cascade');
            
            $table->unsignedBigInteger('id_indicador');
            $table->foreign('id_indicador')->references('id_indicador')->on('indicadores')->onDelete('cascade')->onUpdate('cascade');
            
            $table->tinyInteger('estado')->default(1);
            $table->string('file_name');
            $table->string('file_hash')->unique();
            $table->string('file_path');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archivos_subidos');
    }
};
