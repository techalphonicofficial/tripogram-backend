<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Seed Career Hero Section
        $heroId = DB::table('career_hero_sections')->insertGetId([
            'label' => 'CAREERS',
            'heading_line_1' => 'New Places.',
            'heading_line_2' => 'New Challenges.',
            'heading_line_3' => 'Limitless Growth.',
            'heading_highlight_color' => '#009ED1',
            'description' => "At Tripogram, we're a passionate team of explorers, dreamers and doers building meaningful travel experiences for people across India and beyond.",
            'sub_text' => 'Join us and be part of a journey that matters.',
            'button_text' => 'Explore Open Positions',
            'button_url' => '/careers/jobs',
            'team_text' => '40+ amazing people building experiences',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Seed Hero Labels
        DB::table('career_hero_labels')->insert([
            [
                'career_hero_section_id' => $heroId,
                'text' => 'Team exploring',
                'position' => 'top_center',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'career_hero_section_id' => $heroId,
                'text' => 'A journey',
                'position' => 'left_center',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // 3. Seed Team Members
        DB::table('career_team_members')->insert([
            [
                'name' => 'Team Member 1',
                'designation' => 'Explorer',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Team Member 2',
                'designation' => 'Creator',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // 4. Seed Statistics
        DB::table('career_statistics')->insert([
            [
                'icon' => 'users',
                'number' => '70+',
                'title' => 'Team Members',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'icon' => 'location',
                'number' => '25+',
                'title' => 'Destinations',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'icon' => 'briefcase',
                'number' => '8+',
                'title' => 'Years of Journey',
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'icon' => 'star',
                'number' => '4.8 / 5',
                'title' => 'Team Happiness',
                'sort_order' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // 5. Seed Why Join Us Section
        $whyId = DB::table('career_why_join_sections')->insertGetId([
            'small_label' => "WHY YOU'LL LOVE IT HERE",
            'heading' => "More Than a Workplace, It's a Community",
            'highlight_text' => 'Community',
            'description' => 'We believe in empowering individuals, celebrating diversity, and nurturing growth at every step.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 6. Seed Why Join Us Cards
        DB::table('career_why_join_cards')->insert([
            [
                'career_why_join_section_id' => $whyId,
                'icon' => 'users',
                'title' => 'Explore Together',
                'description' => 'Work with passionate travel lovers and create impactful experiences.',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'career_why_join_section_id' => $whyId,
                'icon' => 'chart',
                'title' => 'Grow With Us',
                'description' => 'Continuous learning, mentorship and clear career growth paths.',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'career_why_join_section_id' => $whyId,
                'icon' => 'trophy',
                'title' => 'Recognition & Rewards',
                'description' => 'Celebrate wins and contributions with meaningful recognition.',
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'career_why_join_section_id' => $whyId,
                'icon' => 'globe',
                'title' => 'Flexible Culture',
                'description' => 'Enjoy a supportive, flexible and inclusive workplace.',
                'sort_order' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('career_hero_sections')->truncate();
        DB::table('career_hero_labels')->truncate();
        DB::table('career_team_members')->truncate();
        DB::table('career_statistics')->truncate();
        DB::table('career_why_join_sections')->truncate();
        DB::table('career_why_join_cards')->truncate();
    }
};
