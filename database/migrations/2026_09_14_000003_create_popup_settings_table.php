<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('popup_settings', function (Blueprint $table) {
            $table->id();
            $table->string('form_heading')->nullable()->default('Plan your Next Trip');
            $table->string('submit_button_text')->nullable()->default('Submit');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed default row
        DB::table('popup_settings')->insert([
            'form_heading'       => 'Plan your Next Trip',
            'submit_button_text' => 'Submit',
            'is_active'          => true,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('popup_settings');
    }
};
