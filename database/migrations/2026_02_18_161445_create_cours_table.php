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
        Schema::create('cours', function (Blueprint $table) {
            $table->id();
            $table->string("titre");
            $table->string("description");
            $table->string("contenu");
            $table->unsignedBigInteger('enseignant_id');
            $table->foreign('enseignant_id')->references('id')->on('enseignants');
            $table->unsignedBigInteger('matiere_id');
            $table->foreign('matiere_id')->references('id')->on('matieres');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cours');
    }
};
