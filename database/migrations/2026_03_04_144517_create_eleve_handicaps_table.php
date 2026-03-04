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
        Schema::create('eleve_handicaps', function (Blueprint $table) {
            $table->id();
             $table->unsignedBigInteger('eleve_id');
            $table->foreign('eleve_id')->references('id')->on('eleves');
            $table->unsignedBigInteger('handicap_id');
            $table->foreign('handicap_id')->references('id')->on('handicaps');
            $table->unique(['eleve_id', 'handicap_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eleve_handicaps');
    }
};
