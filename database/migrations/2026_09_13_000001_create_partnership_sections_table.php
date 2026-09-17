<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partnership_sections', function (Blueprint $table) {
            $table->id();
            $table->string('small_label')->nullable()->default('TRUSTED BY & RECOGNIZED BY');
            $table->string('heading')->nullable()->default('Partnership & Recognition');
            $table->text('description')->nullable();
            $table->string('background_image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed default section
        \Illuminate\Support\Facades\DB::table('partnership_sections')->insert([
            'small_label' => 'TRUSTED BY & RECOGNIZED BY',
            'heading'     => 'Partnership & Recognition',
            'description' => 'Proud to be associated with trusted travel, government and industry partners.',
            'is_active'   => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('partnership_sections');
    }
};
