<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\PartnershipSection;

class PartnerController extends Controller
{
    // =============================================
    // GET /api/partnerships
    // Returns section heading + all active partners
    // =============================================
    public function index()
    {
        $section = PartnershipSection::where('is_active', true)->latest()->first();

        $partners = Partner::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(fn ($p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'logo'        => $p->logo,
                'tag_line'    => $p->tag_line,
                'website_url' => $p->website_url,
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'section' => $section ? [
                    'small_label'      => $section->small_label,
                    'heading'          => $section->heading,
                    'description'      => $section->description,
                    'background_image' => $section->background_image,
                ] : null,
                'partners' => $partners,
            ],
        ]);
    }

    // =============================================
    // GET /api/partnerships/section
    // Returns only the section heading data
    // =============================================
    public function section()
    {
        $section = PartnershipSection::where('is_active', true)->latest()->first();

        return response()->json([
            'success' => true,
            'data'    => $section ? [
                'small_label'      => $section->small_label,
                'heading'          => $section->heading,
                'description'      => $section->description,
                'background_image' => $section->background_image,
            ] : null,
        ]);
    }

    // =============================================
    // GET /api/partnerships/partners
    // Returns only the partners list
    // =============================================
    public function partners()
    {
        $partners = Partner::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(fn ($p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'logo'        => $p->logo,
                'tag_line'    => $p->tag_line,
                'website_url' => $p->website_url,
            ]);

        return response()->json([
            'success' => true,
            'data'    => $partners,
        ]);
    }
}
