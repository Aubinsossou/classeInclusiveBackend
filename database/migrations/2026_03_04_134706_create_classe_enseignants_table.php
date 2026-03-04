<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('classe_enseignants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('classe_id');
            $table->foreign('classe_id')->references('id')->on('classes');
            $table->unsignedBigInteger('enseignant_id');
            $table->foreign('enseignant_id')->references('id')->on('enseignants');
            $table->unique(['classe_id', 'enseignant_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classe_enseignants');
    }
};
