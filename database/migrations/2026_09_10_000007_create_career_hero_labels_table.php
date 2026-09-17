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
        Schema::create('career_hero_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_hero_section_id')->nullable()->constrained('career_hero_sections')->onDelete('cascade');
            $table->string('text');
            $table->string('position')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('career_hero_labels');
    }
};
