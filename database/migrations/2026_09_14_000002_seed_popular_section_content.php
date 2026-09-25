<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')
            ->whereNull('popular_title')
            ->orWhereNull('popular_description')
            ->update([
                'popular_title' => 'Most Popular Tour',
                'popular_description' => 'Discover the world\'s most popular tours with Tripogramclub - where every journey is crafted for unforgettable experiences.',
            ]);
    }

    public function down(): void
    {
        DB::table('settings')->update([
            'popular_title' => null,
            'popular_description' => null,
        ]);
    }
};
