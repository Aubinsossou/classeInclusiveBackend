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
        Schema::create('eleves', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("prenom");
            $table->integer("code");
            $table->string('dateOfNaissance');
            $table->boolean('is_connect')->nullable()->default(false);
            $table->unsignedBigInteger('handicap_id');
            $table->foreign('handicap_id')->references('id')->on('handicaps')->cascadeOnDelete();
            $table->unsignedBigInteger('classe_id');
            $table->foreign('classe_id')->references('id')->on('classes')->cascadeOnDelete();
            $table->string("numeroParent");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eleves');
    }
};
