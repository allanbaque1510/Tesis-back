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
        Schema::create('dashboards', function (Blueprint $table) {
            $table->id('id_dashboard');
            $table->unsignedBigInteger('id_periodo');
            $table->foreign('id_periodo')->references('id')->on('periodo')->onDelete('cascade')->onUpdate('cascade');

            $table->unsignedBigInteger('id_tipo');
            $table->foreign('id_tipo')->references('id_tipo')->on('tipo_graficos')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('posicion');
            $table->tinyInteger('estado')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dashboards');
    }
};
