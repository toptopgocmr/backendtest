<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyPricingGrid extends Model
{
    protected $table = 'property_pricing_grids';

    protected $fillable = [
        'property_id',
        'period',
        'price',
        'min_duration',
        'is_active',
    ];

    protected $casts = [
        'price'        => 'integer',
        'min_duration' => 'integer',
        'is_active'    => 'boolean',
    ];

    // Labels affichés côté mobile
    public static array $periodLabels = [
        'heure'   => 'Par heure',
        'jour'    => 'Par jour',
        'nuit'    => 'Par nuit',
        'semaine' => 'Par semaine',
        'mois'    => 'Par mois',
        'an'      => 'Par an',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Calcule le prix total pour une durée donnée.
     * Ex: period=heure, price=5000, duration=3 → 15000 XAF
     */
    public function calculateTotal(int $duration): int
    {
        return $this->price * max($duration, $this->min_duration);
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 0, ',', ' ')
            . ' XAF / '
            . $this->period;
    }

    public function getPeriodLabelAttribute(): string
    {
        return self::$periodLabels[$this->period] ?? $this->period;
    }
}
