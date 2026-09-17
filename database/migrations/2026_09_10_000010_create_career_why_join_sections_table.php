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
        Schema::create('career_why_join_sections', function (Blueprint $table) {
            $table->id();
            $table->string('small_label')->nullable();
            $table->string('heading')->nullable();
            $table->string('highlight_text')->nullable();
            $table->text('description')->nullable();
            $table->string('background_image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('career_why_join_sections');
    }
};
