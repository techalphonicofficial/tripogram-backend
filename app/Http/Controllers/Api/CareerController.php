<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Career;
use App\Models\CareerApplication;
use App\Models\CareerBenefit;
use App\Models\CareerFaq;
use App\Models\CareerHeroLabel;
use App\Models\CareerHeroSection;
use App\Models\CareerSetting;
use App\Models\CareerStatistic;
use App\Models\CareerTeamMember;
use App\Models\CareerWhyJoinCard;
use App\Models\CareerWhyJoinSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CareerController extends Controller
{
    // =============================================
    // GET /api/careers
    // Main merged endpoint for Next.js Careers page
    // =============================================

    public function index()
    {
        // 1. Hero Section
        $heroModel = CareerHeroSection::where('is_active', true)->latest('updated_at')->first()
            ?? CareerHeroSection::latest('updated_at')->first();
        $heroData = null;

        if ($heroModel) {
            $labels = CareerHeroLabel::where('is_active', true)
                ->where(function ($q) use ($heroModel) {
                    $q->where('career_hero_section_id', $heroModel->id)
                      ->orWhereNull('career_hero_section_id');
                })
                ->orderBy('sort_order', 'asc')
                ->get()
                ->map(fn ($l) => [
                    'text'     => $l->text,
                    'position' => $l->position,
                ]);

            $heroData = [
                'label'                   => $heroModel->label,
                'eyebrow'                 => $heroModel->label,
                'heading_line_1'          => $heroModel->heading_line_1,
                'heading_line1'           => $heroModel->heading_line_1,
                'heading_line_2'          => $heroModel->heading_line_2,
                'heading_line2'           => $heroModel->heading_line_2,
                'heading_line_3'          => $heroModel->heading_line_3,
                'heading_line3'           => $heroModel->heading_line_3,
                'heading_highlight_color' => $heroModel->heading_highlight_color ?? '#009ED1',
                'description'             => $heroModel->description,
                'sub_text'                => $heroModel->sub_text,
                'button_text'             => $heroModel->button_text,
                'cta_label'               => $heroModel->button_text,
                'button_url'              => $heroModel->button_url,
                'main_image'              => $heroModel->main_image,
                'image_main'              => $heroModel->main_image,
                'secondary_image'         => $heroModel->secondary_image,
                'image_secondary'         => $heroModel->secondary_image,
                'background_image'        => $heroModel->background_image,
                'team_text'               => $heroModel->team_text,
                'labels'                  => $labels,
            ];
        }

        // 2. Team Members
        $teamMembers = CareerTeamMember::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(fn ($m) => [
                'name'        => $m->name,
                'photo'       => $m->photo,
                'designation' => $m->designation,
            ]);

        // 3. Statistics
        $statistics = CareerStatistic::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(fn ($s) => [
                'icon'   => $s->icon,
                'number' => $s->number,
                'title'  => $s->title,
            ]);

        // 4. Why Join Us Section & Cards
        $whyModel = CareerWhyJoinSection::where('is_active', true)->latest()->first();
        $whyJoinData = null;

        if ($whyModel) {
            $cards = CareerWhyJoinCard::where('is_active', true)
                ->where(function ($q) use ($whyModel) {
                    $q->where('career_why_join_section_id', $whyModel->id)
                      ->orWhereNull('career_why_join_section_id');
                })
                ->orderBy('sort_order', 'asc')
                ->get()
                ->map(fn ($c) => [
                    'icon'        => $c->icon,
                    'title'       => $c->title,
                    'description' => $c->description,
                ]);

            $whyJoinData = [
                'small_label'      => $whyModel->small_label,
                'heading'          => $whyModel->heading,
                'highlight_text'   => $whyModel->highlight_text,
                'description'      => $whyModel->description ?? '',
                'background_image' => $whyModel->background_image,
                'cards'            => $cards,
            ];
        }

        // 5. Benefits
        $benefits = CareerBenefit::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(function ($b) {
                return [
                    'id'                => $b->id,
                    'title'             => $b->title,
                    'slug'              => $b->slug,
                    'icon'              => $b->icon,
                    'image'             => $b->image ? url('storage/' . $b->getRawOriginal('image')) : null,
                    'short_description' => $b->short_description,
                    'description'       => $b->description,
                ];
            });

        // 6. Active Jobs
        $jobs = Career::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->select([
                'id', 'title', 'slug', 'department', 'location',
                'job_type', 'experience', 'salary', 'short_description',
                'application_deadline',
            ])
            ->get()
            ->map(function ($j) {
                return [
                    'id'                   => $j->id,
                    'title'                => $j->title,
                    'slug'                 => $j->slug,
                    'department'           => $j->department,
                    'location'             => $j->location,
                    'job_type'             => $j->job_type,
                    'experience'           => $j->experience,
                    'salary'               => $j->salary,
                    'short_description'    => $j->short_description,
                    'application_deadline' => $j->application_deadline
                        ? $j->application_deadline->format('Y-m-d')
                        : null,
                ];
            });

        // 7. FAQs
        $faqs = CareerFaq::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get(['id', 'question', 'answer']);

        return response()->json([
            'success' => true,
            'data'    => [
                'hero'         => $heroData,
                'team_members' => $teamMembers,
                'statistics'   => $statistics,
                'why_join_us'  => $whyJoinData,
                'benefits'     => $benefits,
                'jobs'         => $jobs,
                'faqs'         => $faqs,
            ],
        ]);
    }

    // =============================================
    // GET /api/careers/hero
    // =============================================

    public function hero()
    {
        $heroModel = CareerHeroSection::where('is_active', true)->latest('updated_at')->first()
            ?? CareerHeroSection::latest('updated_at')->first();

        if (!$heroModel) {
            return response()->json(['success' => true, 'data' => null]);
        }

        $labels = CareerHeroLabel::where('is_active', true)
            ->where(function ($q) use ($heroModel) {
                $q->where('career_hero_section_id', $heroModel->id)
                  ->orWhereNull('career_hero_section_id');
            })
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(fn ($l) => [
                'text'     => $l->text,
                'position' => $l->position,
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'label'                   => $heroModel->label,
                'eyebrow'                 => $heroModel->label,
                'heading_line_1'          => $heroModel->heading_line_1,
                'heading_line1'           => $heroModel->heading_line_1,
                'heading_line_2'          => $heroModel->heading_line_2,
                'heading_line2'           => $heroModel->heading_line_2,
                'heading_line_3'          => $heroModel->heading_line_3,
                'heading_line3'           => $heroModel->heading_line_3,
                'heading_highlight_color' => $heroModel->heading_highlight_color ?? '#009ED1',
                'description'             => $heroModel->description,
                'sub_text'                => $heroModel->sub_text,
                'button_text'             => $heroModel->button_text,
                'cta_label'               => $heroModel->button_text,
                'button_url'              => $heroModel->button_url,
                'main_image'              => $heroModel->main_image,
                'image_main'              => $heroModel->main_image,
                'secondary_image'         => $heroModel->secondary_image,
                'image_secondary'         => $heroModel->secondary_image,
                'background_image'        => $heroModel->background_image,
                'team_text'               => $heroModel->team_text,
                'labels'                  => $labels,
            ],
        ]);
    }

    // =============================================
    // GET /api/careers/hero/labels
    // =============================================

    public function heroLabels()
    {
        $labels = CareerHeroLabel::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(fn ($l) => [
                'id'       => $l->id,
                'text'     => $l->text,
                'position' => $l->position,
            ]);

        return response()->json([
            'success' => true,
            'data'    => $labels,
        ]);
    }

    // =============================================
    // GET /api/careers/team-members
    // =============================================

    public function teamMembers()
    {
        $members = CareerTeamMember::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(fn ($m) => [
                'id'          => $m->id,
                'name'        => $m->name,
                'photo'       => $m->photo,
                'designation' => $m->designation,
            ]);

        return response()->json([
            'success' => true,
            'data'    => $members,
        ]);
    }

    // =============================================
    // GET /api/careers/statistics
    // =============================================

    public function statistics()
    {
        $stats = CareerStatistic::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(fn ($s) => [
                'id'     => $s->id,
                'icon'   => $s->icon,
                'number' => $s->number,
                'title'  => $s->title,
            ]);

        return response()->json([
            'success' => true,
            'data'    => $stats,
        ]);
    }

    // =============================================
    // GET /api/careers/why-join-us
    // =============================================

    public function whyJoinUs()
    {
        $whyModel = CareerWhyJoinSection::where('is_active', true)->latest()->first();

        if (!$whyModel) {
            return response()->json(['success' => true, 'data' => null]);
        }

        $cards = CareerWhyJoinCard::where('is_active', true)
            ->where(function ($q) use ($whyModel) {
                $q->where('career_why_join_section_id', $whyModel->id)
                  ->orWhereNull('career_why_join_section_id');
            })
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(fn ($c) => [
                'icon'        => $c->icon,
                'title'       => $c->title,
                'description' => $c->description,
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'small_label'      => $whyModel->small_label,
                'heading'          => $whyModel->heading,
                'highlight_text'   => $whyModel->highlight_text,
                'description'      => $whyModel->description ?? '',
                'background_image' => $whyModel->background_image,
                'cards'            => $cards,
            ],
        ]);
    }

    // =============================================
    // GET /api/careers/why-join-us/cards
    // =============================================

    public function whyJoinCards()
    {
        $cards = CareerWhyJoinCard::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(fn ($c) => [
                'id'          => $c->id,
                'icon'        => $c->icon,
                'title'       => $c->title,
                'description' => $c->description,
            ]);

        return response()->json([
            'success' => true,
            'data'    => $cards,
        ]);
    }

    // =============================================
    // GET /api/careers/jobs
    // List of active jobs
    // =============================================

    public function jobs(Request $request)
    {
        $query = Career::where('is_active', true)
            ->orderBy('sort_order', 'asc');

        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }
        if ($request->filled('location')) {
            $query->where('location', $request->location);
        }
        if ($request->filled('job_type')) {
            $query->where('job_type', $request->job_type);
        }

        $jobs = $query->select([
            'id', 'title', 'slug', 'department', 'location',
            'job_type', 'experience', 'salary', 'short_description',
            'application_deadline',
        ])->get()->map(function ($j) {
            return [
                'id'                   => $j->id,
                'title'                => $j->title,
                'slug'                 => $j->slug,
                'department'           => $j->department,
                'location'             => $j->location,
                'job_type'             => $j->job_type,
                'experience'           => $j->experience,
                'salary'               => $j->salary,
                'short_description'    => $j->short_description,
                'application_deadline' => $j->application_deadline
                    ? $j->application_deadline->format('Y-m-d')
                    : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $jobs,
        ]);
    }

    // =============================================
    // GET /api/careers/jobs/{slug}
    // Single active job by slug
    // =============================================

    public function singleJob(string $slug)
    {
        $job = Career::where('slug', $slug)
            ->where('is_active', true)
            ->select([
                'id', 'title', 'slug', 'department', 'location',
                'job_type', 'experience', 'salary', 'short_description',
                'description', 'requirements', 'skills', 'application_deadline',
                'sort_order',
            ])
            ->first();

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Job not found or is no longer active.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                   => $job->id,
                'title'                => $job->title,
                'slug'                 => $job->slug,
                'department'           => $job->department,
                'location'             => $job->location,
                'job_type'             => $job->job_type,
                'experience'           => $job->experience,
                'salary'               => $job->salary,
                'short_description'    => $job->short_description,
                'description'          => $job->description,
                'requirements'         => $job->requirements,
                'skills'               => $job->skills ?? [],
                'application_deadline' => $job->application_deadline
                    ? $job->application_deadline->format('Y-m-d')
                    : null,
            ],
        ]);
    }

    // =============================================
    // GET /api/careers/benefits
    // =============================================

    public function benefits()
    {
        $benefits = CareerBenefit::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(function ($b) {
                return [
                    'id'                => $b->id,
                    'title'             => $b->title,
                    'slug'              => $b->slug,
                    'icon'              => $b->icon,
                    'image'             => $b->getRawOriginal('image')
                        ? url('storage/' . $b->getRawOriginal('image'))
                        : null,
                    'short_description' => $b->short_description,
                    'description'       => $b->description,
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $benefits,
        ]);
    }

    // =============================================
    // GET /api/careers/faqs
    // =============================================

    public function faqs()
    {
        $faqs = CareerFaq::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get(['id', 'question', 'answer']);

        return response()->json([
            'success' => true,
            'data'    => $faqs,
        ]);
    }

    // =============================================
    // GET /api/careers/settings
    // =============================================

    public function settings()
    {
        $settings = CareerSetting::pluck('value', 'key')->toArray();

        if (!empty($settings['hero_image']) && Storage::disk('public')->exists($settings['hero_image'])) {
            $settings['hero_image'] = url('storage/' . $settings['hero_image']);
        }

        return response()->json([
            'success' => true,
            'data'    => $settings,
        ]);
    }

    // =============================================
    // POST /api/careers/apply
    // =============================================

    public function apply(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|max:255',
            'phone'         => 'nullable|string|max:20',
            'career_id'     => 'required|integer',
            'cover_letter'  => 'nullable|string|max:5000',
            'linkedin_url'  => 'nullable|url|max:500',
            'portfolio_url' => 'nullable|url|max:500',
            'resume'        => 'required|file|mimes:pdf,doc,docx|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $career = Career::where('id', $request->career_id)
            ->where('is_active', true)
            ->first();

        if (!$career) {
            return response()->json([
                'success' => false,
                'message' => 'The selected job position is no longer available.',
            ], 422);
        }

        $resumePath = $request->file('resume')->store('career-resumes', 'local');

        if (!$resumePath) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload resume. Please try again.',
            ], 500);
        }

        CareerApplication::create([
            'career_id'     => $career->id,
            'name'          => strip_tags(trim($request->name)),
            'email'         => strtolower(trim($request->email)),
            'phone'         => strip_tags(trim($request->phone ?? '')),
            'cover_letter'  => strip_tags(trim($request->cover_letter ?? '')),
            'resume'        => $resumePath,
            'linkedin_url'  => $request->linkedin_url,
            'portfolio_url' => $request->portfolio_url,
            'status'        => 'new',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Application submitted successfully.',
        ], 201);
    }
}
