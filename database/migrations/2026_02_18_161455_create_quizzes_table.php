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
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->unsignedBigInteger('cours_id');
            $table->foreign('cours_id')->references('id')->on('cours');
             $table->unsignedBigInteger('enseignant_id');
            $table->foreign('enseignant_id')->references('id')->on('enseignants');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
