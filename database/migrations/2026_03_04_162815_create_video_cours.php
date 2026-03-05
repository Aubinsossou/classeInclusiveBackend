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
        Schema::create('video_cours', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cours_id')
                ->constrained('cours')
                ->onDelete('cascade');

            $table->string('titre');
            $table->string('path');
            $table->string('type');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_cours');
    }
};
