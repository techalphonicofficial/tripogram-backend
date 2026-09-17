<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OfferCard;
use App\Models\OfferFaq;
use App\Models\OfferFaqSection;
use App\Models\OfferHeroSection;
use App\Models\OfferTravelSpot;
use App\Models\OfferTravelSpotsSection;

class OfferController extends Controller
{
    // =============================================
    // GET /api/offers
    // Full merged payload for the /offers page
    // =============================================
    public function index()
    {
        $hero = OfferHeroSection::latest()->first();
        $isVisible = $hero ? (bool) $hero->is_active : false;

        $cards = $isVisible ? OfferCard::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(fn ($card) => [
                'id'          => $card->id,
                'title'       => $card->title,
                'description' => $card->description,
                'icon'        => $card->icon,
                'icon_image'  => $card->icon_image,
            ]) : [];

        $spotsSection = $isVisible ? OfferTravelSpotsSection::where('is_active', true)->latest()->first() : null;

        $spots = $isVisible ? OfferTravelSpot::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(fn ($spot) => $this->formatSpot($spot)) : [];

        $faqSection = $isVisible ? OfferFaqSection::where('is_active', true)->latest()->first() : null;

        $faqs = $isVisible ? OfferFaq::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(fn ($faq) => [
                'id'       => $faq->id,
                'question' => $faq->question,
                'answer'   => $faq->answer,
            ]) : [];

        return response()->json([
            'success' => true,
            'data'    => [
                'is_visible'  => $isVisible,
                'show_offers' => $isVisible,
                'hero' => ($hero && $hero->is_active) ? [
                    'navbar_text'      => $hero->navbar_text,
                    'small_label'      => $hero->small_label,
                    'heading'          => $hero->heading,
                    'description'      => $hero->description,
                    'background_image' => $hero->background_image,
                ] : null,
                'cards' => $cards,
                'travel_spots_section' => $spotsSection ? [
                    'small_label' => $spotsSection->small_label,
                    'heading'     => $spotsSection->heading,
                    'description' => $spotsSection->description,
                ] : null,
                'travel_spots' => $spots,
                'faq_section' => $faqSection ? [
                    'heading'     => $faqSection->heading,
                    'description' => $faqSection->description,
                ] : null,
                'faqs' => $faqs,
            ],
        ]);
    }

    // =============================================
    // GET /api/offers/hero
    // =============================================
    public function hero()
    {
        $hero = OfferHeroSection::latest()->first();
        $isVisible = $hero ? (bool) $hero->is_active : false;

        return response()->json([
            'success' => true,
            'data'    => [
                'is_visible'  => $isVisible,
                'show_offers' => $isVisible,
                'hero' => ($hero && $hero->is_active) ? [
                    'navbar_text'      => $hero->navbar_text,
                    'small_label'      => $hero->small_label,
                    'heading'          => $hero->heading,
                    'description'      => $hero->description,
                    'background_image' => $hero->background_image,
                ] : null,
            ],
        ]);
    }

    // =============================================
    // GET /api/offers/cards
    // =============================================
    public function cards()
    {
        $cards = OfferCard::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(fn ($card) => [
                'id'          => $card->id,
                'title'       => $card->title,
                'description' => $card->description,
                'icon'        => $card->icon,
                'icon_image'  => $card->icon_image,
            ]);

        return response()->json([
            'success' => true,
            'data'    => $cards,
        ]);
    }

    // =============================================
    // GET /api/offers/travel-spots
    // =============================================
    public function travelSpots()
    {
        $spotsSection = OfferTravelSpotsSection::where('is_active', true)->latest()->first();

        $spots = OfferTravelSpot::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(fn ($spot) => $this->formatSpot($spot));

        return response()->json([
            'success' => true,
            'data'    => [
                'section' => $spotsSection ? [
                    'small_label' => $spotsSection->small_label,
                    'heading'     => $spotsSection->heading,
                    'description' => $spotsSection->description,
                ] : null,
                'spots' => $spots,
            ],
        ]);
    }

    private function formatSpot($spot)
    {
        $type = 'custom';
        $slug = null;
        $package = null;
        $destination = null;

        if ($spot->url && str_contains($spot->url, '/packages/')) {
            $type = 'package';
            $slug = last(explode('/', trim($spot->url, '/')));
            $pkg = \App\Models\Packages::where('slug', $slug)->first();
            if ($pkg) {
                $package = [
                    'id'             => $pkg->id,
                    'title'          => $pkg->title,
                    'slug'           => $pkg->slug,
                    'starting_price' => $pkg->starting_price,
                    'duration'       => $pkg->duration,
                    'thumbnail'      => $pkg->thumbnail,
                    'banner'         => $pkg->banner,
                    'details_api'    => url('/api/packages/single/' . $pkg->slug),
                ];
            }
        } elseif ($spot->url && str_contains($spot->url, '/destinations/')) {
            $type = 'destination';
            $slug = last(explode('/', trim($spot->url, '/')));
            $dest = \App\Models\Destinations::where('slug', $slug)->first();
            if ($dest) {
                $destination = [
                    'id'          => $dest->id,
                    'name'        => $dest->name,
                    'slug'        => $dest->slug,
                    'thumbnail'   => $dest->thumbnail,
                    'banner'      => $dest->banner,
                    'details_api' => url('/api/destinations/' . $dest->slug),
                ];
            }
        }

        return [
            'id'          => $spot->id,
            'title'       => $spot->title,
            'subtitle'    => $spot->subtitle,
            'image'       => $spot->image,
            'url'         => $spot->url,
            'type'        => $type,
            'slug'        => $slug,
            'package'     => $package,
            'destination' => $destination,
        ];
    }

    // =============================================
    // GET /api/offers/faqs
    // =============================================
    public function faqs()
    {
        $faqSection = OfferFaqSection::where('is_active', true)->latest()->first();

        $faqs = OfferFaq::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(fn ($faq) => [
                'id'       => $faq->id,
                'question' => $faq->question,
                'answer'   => $faq->answer,
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'section' => $faqSection ? [
                    'heading'     => $faqSection->heading,
                    'description' => $faqSection->description,
                ] : null,
                'faqs' => $faqs,
            ],
        ]);
    }
}
