<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('logo')->nullable();
            $table->string('tag_line')->nullable();   // e.g. "Preferred Travel Partner"
            $table->string('website_url')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed default partners
        $partners = [
            ['name' => 'MakeMyTrip',        'tag_line' => 'Preferred Travel Partner',        'sort_order' => 1],
            ['name' => 'IndiGo',            'tag_line' => 'Preferred Airline Partner',        'sort_order' => 2],
            ['name' => 'Goods & Services Tax', 'tag_line' => 'GST Registered Company',        'sort_order' => 3],
            ['name' => 'IATO',              'tag_line' => 'Indian Association of Tour Operators', 'sort_order' => 4],
        ];

        foreach ($partners as $p) {
            \Illuminate\Support\Facades\DB::table('partners')->insert([
                'name'       => $p['name'],
                'tag_line'   => $p['tag_line'],
                'sort_order' => $p['sort_order'],
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
