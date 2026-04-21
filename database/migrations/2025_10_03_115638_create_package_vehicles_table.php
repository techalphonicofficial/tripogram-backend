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
        Schema::create('package_vehicles', function (Blueprint $table) {
            $table->id();
            $table->integer('package_id');
            $table->integer('vehicle_id');
            $table->string('label')->nullable(); // Bus 1, Bus 2
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_vehicles');
    }
};
