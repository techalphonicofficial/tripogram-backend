<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Packages extends Model
{

    protected $fillable = [
        'trip_id',
        'sort_order',
        'destination_id',
        'vehicle_id',
        'banner',
        'map_image',
        'package_code',
        'day',
        'thumbnail',
        'title',
        'slug',
        'duration',
        'starting_price',
        'pickup',
        'drop',
        'age_group_min',
        'age_group_max',
        'description',
        'itinerary',
        'age_group',
        'itinerary_pdf',
        'inclusion',
        'exclusion',
        'note',
        'things_to_pack',
        'gallery',
        'testimonials',
        'faqs',
        'related_insta_video',
        'related_youtube_video',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'is_active',
        'is_trending',
        'season',
        'slot',
        'is_land_package',
        'booking_amount',
      'show_book_no_button',
    ];
    
    protected $casts = [
        'itinerary' => 'array',
        'gallery' => 'array',
        'testimonials' => 'array',
        'faqs' => 'array',
      
        'related_insta_video' => 'array',
        'related_youtube_video' => 'array',
    ];
    
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }
    
    public function activeCosts()
    {
        return $this->hasMany(ActiveCosts::class, 'package_id');
    }
    
    public function bookings()
    {
        return $this->hasMany(Bookings::class, 'package_id', 'id');
    }
    
    public function packageDates()
    {
        return $this->hasMany(PackageDates::class, 'package_id');
    }
    
    public function addonSchema()
    {
        return $this->hasMany(PackageAddonSchemas::class, 'package_id');
    }
    
    public function trips()
    {
        return $this->belongsToMany(Trips::class, 'package_map_with_trips', 'package_id', 'trip_id')
                    ->withPivot('mapping_date', 'status')
                    ->withTimestamps();
    }
    
    public function trippu()
    {
        return $this->hasMany(PackageMapWithTrip::class, 'package_id');
    }
    
    public function destination()
    {
        return $this->belongsTo(Destinations::class, 'destination_id');
    }
    
    public function trip()
    {
        return $this->belongsTo(Trips::class, 'trip_id');
    }
    
    public function packageVehicles()
    {
        return $this->hasMany(PackageVehicle::class, 'package_id', 'id');
    }
    
    // ✅ AUTO-DELETE IMAGES WHEN UPDATING
    protected static function booted()
    {
        // ✅ When updating package
        static::updating(function ($package) {
            // Single image fields
            $imageFields = ['thumbnail', 'banner', 'map_image', 'itinerary_pdf'];
            
            foreach ($imageFields as $field) {
                if ($package->isDirty($field)) {
                    $oldValue = $package->getOriginal($field);
                    if ($oldValue && Storage::disk('public')->exists($oldValue)) {
                        Storage::disk('public')->delete($oldValue);
                        \Log::info("Deleted old {$field}: {$oldValue}");
                    }
                }
            }
            
            // ✅ Handle gallery images removal
            if ($package->isDirty('gallery')) {
                $oldGallery = $package->getOriginal('gallery');
                $newGallery = $package->gallery;
                
                if (is_string($oldGallery)) {
                    $oldGallery = json_decode($oldGallery, true);
                }
                if (is_string($newGallery)) {
                    $newGallery = json_decode($newGallery, true);
                }
                
                if (is_array($oldGallery) && is_array($newGallery)) {
                    $oldImages = collect($oldGallery)->pluck('image')->filter()->toArray();
                    $newImages = collect($newGallery)->pluck('image')->filter()->toArray();
                    $removedImages = array_diff($oldImages, $newImages);
                    
                    foreach ($removedImages as $imagePath) {
                        if (Storage::disk('public')->exists($imagePath)) {
                            Storage::disk('public')->delete($imagePath);
                            \Log::info("Deleted old gallery image: {$imagePath}");
                        }
                    }
                }
            }
            
            // ✅ Handle testimonials images removal
            if ($package->isDirty('testimonials')) {
                $oldTestimonials = $package->getOriginal('testimonials');
                $newTestimonials = $package->testimonials;
                
                if (is_string($oldTestimonials)) {
                    $oldTestimonials = json_decode($oldTestimonials, true);
                }
                if (is_string($newTestimonials)) {
                    $newTestimonials = json_decode($newTestimonials, true);
                }
                
                if (is_array($oldTestimonials) && is_array($newTestimonials)) {
                    $oldImages = collect($oldTestimonials)->pluck('image')->filter()->toArray();
                    $newImages = collect($newTestimonials)->pluck('image')->filter()->toArray();
                    $removedImages = array_diff($oldImages, $newImages);
                    
                    foreach ($removedImages as $imagePath) {
                        if (Storage::disk('public')->exists($imagePath)) {
                            Storage::disk('public')->delete($imagePath);
                            \Log::info("Deleted old testimonial image: {$imagePath}");
                        }
                    }
                }
            }
            
            // ✅ Handle Instagram video thumbnails removal
            if ($package->isDirty('related_insta_video')) {
                $oldVideos = $package->getOriginal('related_insta_video');
                $newVideos = $package->related_insta_video;
                
                if (is_string($oldVideos)) {
                    $oldVideos = json_decode($oldVideos, true);
                }
                if (is_string($newVideos)) {
                    $newVideos = json_decode($newVideos, true);
                }
                
                if (is_array($oldVideos) && is_array($newVideos)) {
                    $oldThumbnails = collect($oldVideos)->pluck('thumbnail')->filter()->toArray();
                    $newThumbnails = collect($newVideos)->pluck('thumbnail')->filter()->toArray();
                    $removedThumbnails = array_diff($oldThumbnails, $newThumbnails);
                    
                    foreach ($removedThumbnails as $thumbnailPath) {
                        if (Storage::disk('public')->exists($thumbnailPath)) {
                            Storage::disk('public')->delete($thumbnailPath);
                            \Log::info("Deleted old insta video thumbnail: {$thumbnailPath}");
                        }
                    }
                }
            }
            
            // ✅ Handle YouTube video thumbnails removal
            if ($package->isDirty('related_youtube_video')) {
                $oldVideos = $package->getOriginal('related_youtube_video');
                $newVideos = $package->related_youtube_video;
                
                if (is_string($oldVideos)) {
                    $oldVideos = json_decode($oldVideos, true);
                }
                if (is_string($newVideos)) {
                    $newVideos = json_decode($newVideos, true);
                }
                
                if (is_array($oldVideos) && is_array($newVideos)) {
                    $oldThumbnails = collect($oldVideos)->pluck('thumbnail')->filter()->toArray();
                    $newThumbnails = collect($newVideos)->pluck('thumbnail')->filter()->toArray();
                    $removedThumbnails = array_diff($oldThumbnails, $newThumbnails);
                    
                    foreach ($removedThumbnails as $thumbnailPath) {
                        if (Storage::disk('public')->exists($thumbnailPath)) {
                            Storage::disk('public')->delete($thumbnailPath);
                            \Log::info("Deleted old youtube video thumbnail: {$thumbnailPath}");
                        }
                    }
                }
            }
        });
        
        // ✅ When deleting entire package
        static::deleting(function ($package) {
            // Delete single image fields
            $imageFields = ['thumbnail', 'banner', 'map_image', 'itinerary_pdf'];
            
            foreach ($imageFields as $field) {
                if ($package->$field && Storage::disk('public')->exists($package->$field)) {
                    Storage::disk('public')->delete($package->$field);
                    \Log::info("Deleted package {$field}: {$package->$field}");
                }
            }
            
            // Delete gallery images
            if ($package->gallery && is_array($package->gallery)) {
                foreach ($package->gallery as $galleryItem) {
                    if (!empty($galleryItem['image']) && Storage::disk('public')->exists($galleryItem['image'])) {
                        Storage::disk('public')->delete($galleryItem['image']);
                        \Log::info("Deleted gallery image: {$galleryItem['image']}");
                    }
                }
            }
            
            // Delete testimonial images
            if ($package->testimonials && is_array($package->testimonials)) {
                foreach ($package->testimonials as $testimonial) {
                    if (!empty($testimonial['image']) && Storage::disk('public')->exists($testimonial['image'])) {
                        Storage::disk('public')->delete($testimonial['image']);
                        \Log::info("Deleted testimonial image: {$testimonial['image']}");
                    }
                }
            }
            
            // Delete Instagram video thumbnails
            if ($package->related_insta_video && is_array($package->related_insta_video)) {
                foreach ($package->related_insta_video as $video) {
                    if (!empty($video['thumbnail']) && Storage::disk('public')->exists($video['thumbnail'])) {
                        Storage::disk('public')->delete($video['thumbnail']);
                        \Log::info("Deleted insta thumbnail: {$video['thumbnail']}");
                    }
                }
            }
            
            // Delete YouTube video thumbnails
            if ($package->related_youtube_video && is_array($package->related_youtube_video)) {
                foreach ($package->related_youtube_video as $video) {
                    if (!empty($video['thumbnail']) && Storage::disk('public')->exists($video['thumbnail'])) {
                        Storage::disk('public')->delete($video['thumbnail']);
                        \Log::info("Deleted youtube thumbnail: {$video['thumbnail']}");
                    }
                }
            }
            
            // ✅ Delete related records
            $package->activeCosts()->delete();
            $package->packageDates()->delete();
        });
    }
    
    // ✅ API URL helpers
    public function getThumbnailAttribute($value)
    {
        if (request()->is('api/*')) {
            return $value ? url('storage/' . $value) : null;
        }
        return $value;
    }
    
    public function getBannerAttribute($value)
    {
        if (request()->is('api/*')) {
            return $value ? url('storage/' . $value) : null;
        }
        return $value;
    }
    
    public function getItineraryPdfAttribute($value)
    {
        if (request()->is('api/*')) {
            return $value ? url('storage/' . $value) : null;
        }
        return $value;
    }
    
    public function getMapImageAttribute($value)
    {
        if (request()->is('api/*')) {
            return $value ? url('storage/' . $value) : null;
        }
        return $value;
    }
}