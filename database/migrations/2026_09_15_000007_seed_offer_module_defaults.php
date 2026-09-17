<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Seed Hero Section
        DB::table('offer_hero_sections')->insert([
            'navbar_text'       => 'Offers',
            'small_label'       => 'EXCLUSIVE TRAVEL OFFERS',
            'heading'          => 'Exclusive Travel Offers',
            'description'      => 'Unlock best deals & exclusive travel offers across top destinations for your next trip.',
            'is_active'        => true,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        // 2. Seed 4 Highlight Cards
        $cards = [
            ['title' => 'Limited Time Drive Savings',        'icon' => 'percent',      'sort_order' => 1],
            ['title' => 'Book at Just 999/- Only',           'icon' => 'tag',          'sort_order' => 2],
            ['title' => 'Exclusive Student Discount Offer', 'icon' => 'academic-cap', 'sort_order' => 3],
            ['title' => 'Unbeatable Group Deals',            'icon' => 'user-group',   'sort_order' => 4],
        ];
        foreach ($cards as $c) {
            DB::table('offer_cards')->insert([
                'title'      => $c['title'],
                'icon'       => $c['icon'],
                'sort_order' => $c['sort_order'],
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Seed Travel Spots Section
        DB::table('offer_travel_spots_sections')->insert([
            'small_label' => 'POPULAR TRAVEL SPOTS',
            'heading'     => 'Handpicked destinations for your next unforgettable journey.',
            'is_active'   => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // 4. Seed 6 Popular Travel Spots
        $spots = [
            ['title' => 'Himachal',    'url' => '/destinations/himachal',    'sort_order' => 1],
            ['title' => 'Uttarakhand', 'url' => '/destinations/uttarakhand', 'sort_order' => 2],
            ['title' => 'Rajasthan',   'url' => '/destinations/rajasthan',   'sort_order' => 3],
            ['title' => 'Meghalaya',   'url' => '/destinations/meghalaya',   'sort_order' => 4],
            ['title' => 'Kashmir',     'url' => '/destinations/kashmir',     'sort_order' => 5],
            ['title' => 'Spiti',       'url' => '/destinations/spiti',       'sort_order' => 6],
        ];
        foreach ($spots as $s) {
            DB::table('offer_travel_spots')->insert([
                'title'      => $s['title'],
                'url'        => $s['url'],
                'sort_order' => $s['sort_order'],
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 5. Seed FAQ Section
        DB::table('offer_faq_sections')->insert([
            'heading'    => 'Grand Travel Sale FAQs',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 6. Seed 6 FAQs
        $faqs = [
            [
                'question' => 'How can I get the offer during the Grand Travel Sale?',
                'answer'   => 'You can book directly on our platform or contact our travel experts during the sale period to avail the best deals.',
                'sort_order' => 1,
            ],
            [
                'question' => 'How much do I have to pay the remaining amount of the trip booked during the Grand Travel Sale?',
                'answer'   => 'The remaining balance can be paid as per the payment schedule before the departure date.',
                'sort_order' => 2,
            ],
            [
                'question' => 'What if I have to change or modify my trip later?',
                'answer'   => 'Trip modifications are subject to availability and terms of the specific package.',
                'sort_order' => 3,
            ],
            [
                'question' => 'What is the refund policy for the Grand Travel Sale bookings?',
                'answer'   => 'Refunds are processed according to our cancellation policy for sale bookings.',
                'sort_order' => 4,
            ],
            [
                'question' => 'Can I avail this offer with a group trip?',
                'answer'   => 'Yes, group deals are specially applicable during the Grand Travel Sale.',
                'sort_order' => 5,
            ],
            [
                'question' => 'I have a query regarding the Grand Travel Sale, how can I get help?',
                'answer'   => 'You can reach out to our 24/7 support team via phone or email.',
                'sort_order' => 6,
            ],
        ];
        foreach ($faqs as $f) {
            DB::table('offer_faqs')->insert([
                'question'   => $f['question'],
                'answer'     => $f['answer'],
                'sort_order' => $f['sort_order'],
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('offer_faqs')->truncate();
        DB::table('offer_faq_sections')->truncate();
        DB::table('offer_travel_spots')->truncate();
        DB::table('offer_travel_spots_sections')->truncate();
        DB::table('offer_cards')->truncate();
        DB::table('offer_hero_sections')->truncate();
    }
};
