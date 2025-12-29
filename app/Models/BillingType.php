<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillingType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'amount',
        'applies_to_all',
        'resident_category_id', // ✅ TAMBAHAN BARU
        'is_active',
    ];

    protected $casts = [
        'amount' => 'integer',
        'applies_to_all' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relations
    public function dorms(): BelongsToMany
    {
        return $this->belongsToMany(Dorm::class, 'billing_type_dorm')->withTimestamps();
    }

    public function residentCategory(): BelongsTo
    {
        return $this->belongsTo(ResidentCategory::class);
    }

    // Helper: cek apakah billing type berlaku untuk user tertentu
    public function appliesTo(User $user, ?int $dormId = null): bool
    {
        // Jika applies_to_all, langsung true
        if ($this->applies_to_all) {
            return true;
        }

        $profile = $user->residentProfile;
        if (!$profile) {
            return false;
        }

        // Cek kategori penghuni
        if ($this->resident_category_id && $this->resident_category_id !== $profile->resident_category_id) {
            return false;
        }

        // Cek cabang (jika dormId disediakan)
        if ($dormId) {
            return $this->dorms()->where('dorms.id', $dormId)->exists();
        }

        return true;
    }
}