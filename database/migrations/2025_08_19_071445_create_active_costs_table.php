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
        Schema::create('active_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')
                ->constrained('packages')
                ->onDelete('cascade');
            $table->string('activity')->comment('Example: River Rafting, Paragliding');
            $table->decimal('cost', 10, 2)->comment('Base cost of activity');
            $table->decimal('discount_percent', 5, 2)->default(0)->comment('Discount in %');
            $table->decimal('gst_percent', 5, 2)->default(0)->comment('GST in %');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('active_costs');
    }
};
