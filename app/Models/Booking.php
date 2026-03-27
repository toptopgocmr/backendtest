<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * FIX #7 — Model aligné avec la migration 2025_01_01_000003
 * Migration utilise : base_amount, fees_amount, notes, cancel_reason, status en FR
 */
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
        'base_amount',     // FIX: était 'subtotal' + 'price_per_night'
        'fees_amount',     // FIX: était 'service_fee'
        'total_amount',
        'currency',
        'status',
        'notes',           // FIX: était 'special_requests'
        'cancel_reason',   // FIX: était 'cancellation_reason'
        'cancelled_at',
    ];

    protected $casts = [
        'check_in'     => 'date',
        'check_out'    => 'date',
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

    // ── Statuts (alignés avec l'enum migration) ──────────────────
    public function isPending()   { return $this->status === 'en_attente'; }
    public function isConfirmed() { return $this->status === 'confirmé'; }
    public function isCancelled() { return $this->status === 'annulé'; }
    public function isCompleted() { return $this->status === 'terminé'; }

    // Label de statut pour les vues
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
