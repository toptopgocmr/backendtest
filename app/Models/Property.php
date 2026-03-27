<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * FIX #4, #5, #6 — Model aligné avec la migration 2025_01_01_000002
 * Migration utilise : owner_id, price, price_period, type, is_approved, rating, reviews_count
 */
class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',       // FIX: était 'user_id'
        'title',
        'description',
        'type',           // FIX: enum 'appartement','villa','studio','maison','chambre','bureau','terrain'
        'price',          // FIX: était 'price_per_night'
        'price_period',
        'currency',
        'address',
        'city',
        'district',
        'country',
        'latitude',
        'longitude',
        'bedrooms',
        'bathrooms',
        'area',           // FIX: était 'area_m2'
        'max_guests',
        'status',
        'is_featured',
        'is_approved',
        'rating',         // FIX: était 'rating_avg'
        'reviews_count',  // FIX: était 'rating_count'
        'views_count',
    ];

    protected $casts = [
        'is_featured'  => 'boolean',
        'is_approved'  => 'boolean',
        'latitude'     => 'float',
        'longitude'    => 'float',
        'price'        => 'float',   // FIX
        'rating'       => 'float',   // FIX
    ];

    // ── Relations ────────────────────────────────────────────────
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id'); // FIX: clé étrangère correcte
    }

    public function images()
    {
        return $this->hasMany(PropertyImage::class)->orderBy('sort_order');
    }

    // FIX #6 — is_primary (migration) au lieu de is_cover
    public function primaryImage()
    {
        return $this->hasOne(PropertyImage::class)->where('is_primary', true);
    }

    // Alias pour rétrocompatibilité
    public function coverImage()
    {
        return $this->primaryImage();
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function amenities()
    {
        return $this->hasMany(PropertyAmenity::class);
    }

    public function availability()
    {
        return $this->hasMany(PropertyAvailability::class);
    }

    // ── Méthodes ─────────────────────────────────────────────────
    public function updateRating(): void
    {
        $avg   = $this->reviews()->where('is_visible', true)->avg('rating') ?? 0;
        $count = $this->reviews()->where('is_visible', true)->count();
        $this->update(['rating' => round($avg, 2), 'reviews_count' => $count]);
    }

    // FIX #5 — enum migration est 'disponible', pas 'active'
    public function scopeActive($q)
    {
        return $q->where('status', 'disponible')->where('is_approved', true);
    }

    public function scopeFeatured($q)
    {
        return $q->where('is_featured', true);
    }

    // Accesseur prix formaté
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 0, ',', ' ') . ' ' . $this->currency;
    }
}
