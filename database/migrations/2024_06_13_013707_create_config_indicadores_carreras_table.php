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
        Schema::create('config_indicadores_carreras', function (Blueprint $table) {
            $table->id('id_configuracion');
            $table->unsignedBigInteger('id_carrera');
            $table->foreign('id_carrera')->references('id')->on('carrera')->onDelete('cascade')->onUpdate('cascade');
            $table->tinyInteger('periodos_desercion');
            $table->tinyInteger('total_periodos');
            $table->tinyInteger('periodos_gracia');
            $table->tinyInteger('estado')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config_indicadores_carreras');
    }
};