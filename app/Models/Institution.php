<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class Institution extends Model
{
    use HasFactory;

    protected $fillable = [
        'legal_number',
        'institution_name',
        'dormitory_name',
        'address',
        'phone',
        'email',
        'website',
        'logo_path',
        'about_content',
        'landing_headline',
        'landing_description',
        'landing_stats',
    ];

    protected $casts = [
        'landing_stats' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($institution) {
            // Hapus logo dari storage jika ada
            if ($institution->logo_path && Storage::disk('public')->exists($institution->logo_path)) {
                Storage::disk('public')->delete($institution->logo_path);
            }
        });

        static::saved(function ($institution) {
            cache()->forget('institution_data');
        });
    }

    public function getLogoUrlAttribute(): ?string
    {
        if ($this->logo_path) {
            return Storage::disk('public')->url($this->logo_path);
        }

        return null;
    }

    public function getLogoFullPathAttribute(): ?string
    {
        if ($this->logo_path) {
            return Storage::disk('public')->path($this->logo_path);
        }
        return null;
    }
}
