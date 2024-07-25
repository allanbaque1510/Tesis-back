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
        Schema::create('logros_aprendizaje', function (Blueprint $table) {
            $table->id("id_logros");
            $table->string("codigo")->index()->nullable();
            $table->string("descripcion");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logros_aprendizaje');
    }
};
