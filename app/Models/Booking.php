<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Booking extends Model
{
    protected $fillable = [
        'reference',
        'user_id',
        'property_id',
        'check_in',
        'check_out',
        'nights',
        'guests',
        'base_amount',
        'fees_amount',
        'total_amount',
        'currency',
        'status',
        'notes',
        'cancel_reason',
        'cancelled_at',
    ];

    protected $casts = [
        'check_in'     => 'datetime',  // ← FIX : était 'date'
        'check_out'    => 'datetime',  // ← FIX : était 'date'
        'cancelled_at' => 'datetime',
        'base_amount'  => 'float',
        'fees_amount'  => 'float',
        'total_amount' => 'float',
    ];

    protected static function booted(): void
    {
        static::creating(function ($booking) {
            if (empty($booking->reference)) {
                $booking->reference = 'BK-' . date('Y') . '-' . strtoupper(Str::random(6));
            }
        });
    }

    // ── Relations ────────────────────────────────────────────────
    public function user()     { return $this->belongsTo(User::class); }
    public function property() { return $this->belongsTo(Property::class); }
    public function payment()  { return $this->hasOne(Payment::class); }
    public function review()   { return $this->hasOne(Review::class); }

    // ── Commission Tholad ────────────────────────────────────────
    public function getCommissionRateAttribute(): float
    {
        return (float) ($this->property?->owner?->ownerProfile?->commission_rate ?? 0);
    }

    public function getCommissionAmountAttribute(): float
    {
        if ($this->commission_rate <= 0) return 0;
        return round($this->total_amount * $this->commission_rate / 100);
    }

    public function getOwnerAmountAttribute(): float
    {
        return $this->total_amount - $this->commission_amount;
    }

    // ── Statuts ──────────────────────────────────────────────────
    public function isPending()   { return $this->status === 'en_attente'; }
    public function isConfirmed() { return $this->status === 'confirmé'; }
    public function isCancelled() { return $this->status === 'annulé'; }
    public function isCompleted() { return $this->status === 'terminé'; }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'en_attente' => 'En attente',
            'confirmé'   => 'Confirmé',
            'annulé'     => 'Annulé',
            'terminé'    => 'Terminé',
            default      => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'confirmé'   => '#10B981',
            'en_attente' => '#EA580C',
            'annulé'     => '#EF4444',
            'terminé'    => '#6B7280',
            default      => '#9E9E9E',
        };
    }
}
