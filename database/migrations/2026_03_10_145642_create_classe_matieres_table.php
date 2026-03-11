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
        Schema::create('classe_matieres', function (Blueprint $table) {
            $table->id();
              $table->unsignedBigInteger('classe_id');
            $table->foreign('classe_id')->references('id')->on('classes')->cascadeOnDelete();;
            $table->unsignedBigInteger('matiere_id');
            $table->foreign('matiere_id')->references('id')->on('matieres')->cascadeOnDelete();;
            $table->unsignedBigInteger('ecole_id');
            $table->foreign('ecole_id')->references('id')->on('ecoles')->cascadeOnDelete();;
            $table->unique(['classe_id', 'matiere_id','ecole_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classe_matieres');
    }
};
