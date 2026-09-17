<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('popup_slides', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();         // e.g. "Your Next Adventure"
            $table->text('description')->nullable();    // e.g. "Handpicked experiences, made simple by Tripogram."
            $table->string('image')->nullable();          // slide background image
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed initial default slide
        DB::table('popup_slides')->insert([
            'title'       => 'Your Next Adventure',
            'description' => 'Handpicked experiences, made simple by Tripogram.',
            'image'       => null,
            'sort_order'  => 1,
            'is_active'   => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('popup_slides');
    }
};
