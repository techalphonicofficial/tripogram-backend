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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            // Basic Info
            $table->bigInteger('trip_id');
            $table->bigInteger('destination_id');
            $table->string('banner');
            $table->string('thumbnail');
            $table->string('title');
            $table->string('slug');
            $table->string('duration');
            $table->decimal('starting_price', 10, 2);
            $table->string('pickup');
            $table->string('drop');
            $table->unsignedTinyInteger('age_group_min');
            $table->unsignedTinyInteger('age_group_max');

            // Sections / Tabs
            $table->longText('description')->nullable();
            $table->json('itinerary')->nullable();
            $table->longText('itinerary_pdf')->nullable();
            $table->longText('inclusion')->nullable();
            $table->longText('exclusion')->nullable();
            $table->longText('note')->nullable();
            $table->longText('things_to_pack')->nullable();
            $table->json('gallery')->nullable();
            // SEO Fields (optional, but good practice)
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_trending')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
