<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_hero_sections', function (Blueprint $table) {
            $table->id();
            $table->string('navbar_text')->nullable()->default('Offers');
            $table->string('small_label')->nullable()->default('EXCLUSIVE TRAVEL OFFERS');
            $table->string('heading')->nullable()->default('Exclusive Travel Offers');
            $table->text('description')->nullable();
            $table->string('background_image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_hero_sections');
    }
};
