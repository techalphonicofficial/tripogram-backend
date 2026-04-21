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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            $table->string('full_name');
            $table->string('email');
            $table->string('phone');

            $table->bigInteger('package_id');
            $table->string('package_title');
            $table->string('duration');
            $table->string('pickup');
            $table->string('drop');

            $table->bigInteger('package_date_id')->nullable();
            $table->json('package_date');

            $table->bigInteger('active_cost_id')->nullable();
            $table->json('active_cost');

            $table->enum('payment_mode', ['online', 'cash'])->default('online');
            $table->string('payment_id')->nullable();
            $table->enum('payment_type', ['half', 'full']);
            $table->decimal('final_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2);
            $table->decimal('due_amount', 10, 2);

            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
