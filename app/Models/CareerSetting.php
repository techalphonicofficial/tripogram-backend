<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CareerSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    // =============================================
    // STATIC HELPERS
    // =============================================

    /**
     * Get a single career setting value by key.
     */
    public static function getValue(string $key, ?string $default = null): ?string
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    /**
     * Get all career settings as key => value associative array.
     */
    public static function getAllAsArray(): array
    {
        return static::pluck('value', 'key')->toArray();
    }

    /**
     * Set (upsert) a value by key.
     */
    public static function setValue(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    // =============================================
    // IMAGE URL helper for API
    // =============================================

    /**
     * If a setting value is an image path, return full storage URL.
     */
    public static function getImageUrl(string $key): ?string
    {
        $value = static::getValue($key);
        if ($value && Storage::disk('public')->exists($value)) {
            return url('storage/' . $value);
        }
        return $value;
    }
}
