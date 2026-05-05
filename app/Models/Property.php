<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        // ── Champs de base (migration 000002) ──────────────────
        'owner_id',
        'title',
        'description',
        'type',
        'price',
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
        'area',
        'max_guests',
        'status',
        'is_featured',
        'is_approved',
        'rating',
        'reviews_count',
        'views_count',

        // ── Champs ajoutés (migration 000001 — types étendus) ──
        'capacity',
        'floor',

        // ── Champs ajoutés (migration 000005 — extra fields) ───
        'deposit',
        'contact_phone',
        'contact_email',
        'view_type',
        'rules',
        'workstations',
        'terrain_type',
        'land_title',

        // ── Champs ajoutés (migration 000010 — durée horaire) ──
        'duration_hours',
    ];

    protected $casts = [
        'is_featured'    => 'boolean',
        'is_approved'    => 'boolean',
        'latitude'       => 'float',
        'longitude'      => 'float',
        'price'          => 'float',
        'rating'         => 'float',
        'deposit'        => 'float',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function images()
    {
        return $this->hasMany(PropertyImage::class)->orderBy('sort_order');
    }

    public function primaryImage()
    {
        return $this->hasOne(PropertyImage::class)->where('is_primary', true);
    }

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

    /**
     * Grille tarifaire multi-périodes (heure, jour, nuit, semaine, mois, an).
     * Ordre logique : heure → jour → nuit → semaine → mois → an
     */
    public function pricingGrids()
    {
        return $this->hasMany(PropertyPricingGrid::class)
                    ->where('is_active', true)
                    ->orderByRaw("FIELD(period, 'heure','jour','nuit','semaine','mois','an')");
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($q)
    {
        return $q->where('status', 'disponible')->where('is_approved', true);
    }

    public function scopeFeatured($q)
    {
        return $q->where('is_featured', true);
    }

    // ── Méthodes ──────────────────────────────────────────────────────────────

    public function updateRating(): void
    {
        $avg   = $this->reviews()->where('is_visible', true)->avg('rating') ?? 0;
        $count = $this->reviews()->where('is_visible', true)->count();
        $this->update(['rating' => round($avg, 2), 'reviews_count' => $count]);
    }

    // ── Accesseurs ────────────────────────────────────────────────────────────

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 0, ',', ' ') . ' ' . $this->currency;
    }

    public function getPricePeriodLabelAttribute(): string
    {
        return match($this->price_period) {
            'heure'   => 'Par heure',
            'nuit'    => 'Par nuit',
            'jour'    => 'Par jour',
            'semaine' => 'Par semaine',
            'mois'    => 'Par mois',
            'an'      => 'Par an',
            'total'   => 'Prix total',
            default   => $this->price_period,
        ];
    }
}
