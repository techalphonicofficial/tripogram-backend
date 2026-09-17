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
        Schema::create('career_hero_sections', function (Blueprint $table) {
            $table->id();
            $table->string('label')->nullable()->default('CAREERS');
            $table->string('heading_line_1')->nullable();
            $table->string('heading_line_2')->nullable();
            $table->string('heading_line_3')->nullable();
            $table->string('heading_highlight_color')->default('#009ED1');
            $table->text('description')->nullable();
            $table->text('sub_text')->nullable();
            $table->string('button_text')->nullable();
            $table->string('button_url')->nullable();
            $table->string('main_image')->nullable();
            $table->string('secondary_image')->nullable();
            $table->string('background_image')->nullable();
            $table->string('team_text')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('career_hero_sections');
    }
};
