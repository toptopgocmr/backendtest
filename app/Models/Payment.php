<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * FIX #8 — Model aligné avec migration 2025_01_01_000004
 * Migration utilise : method, provider_ref, phone, status en FR ('en_attente','succès','échoué','remboursé')
 * Ajout de method_label accessor (manquait, utilisé dans les vues)
 */
class Payment extends Model
{
    protected $fillable = [
        'reference',
        'booking_id',
        'user_id',
        'amount',
        'currency',
        'method',          // FIX: était 'gateway'
        'provider_ref',    // FIX: était 'gateway_ref'
        'phone',           // FIX: était 'phone_number'
        'status',
        'refund_reason',
        'gateway_response',
        'paid_at',
        'refunded_at',
    ];

    protected $casts = [
        'gateway_response' => 'array',
        'paid_at'          => 'datetime',
        'refunded_at'      => 'datetime',
        'amount'           => 'float',
    ];

    protected static function booted(): void
    {
        static::creating(function ($pay) {
            if (empty($pay->reference)) {
                $pay->reference = 'PAY-' . date('Y') . '-' . strtoupper(Str::random(8));
            }
        });
    }

    // ── Relations ────────────────────────────────────────────────
    public function booking() { return $this->belongsTo(Booking::class); }
    public function user()    { return $this->belongsTo(User::class); }

    // ── Statuts (alignés avec l'enum migration) ──────────────────
    public function isSuccess()    { return $this->status === 'succès'; }
    public function isPending()    { return $this->status === 'en_attente'; }
    public function isFailed()     { return $this->status === 'échoué'; }
    public function isRefunded()   { return $this->status === 'remboursé'; }

    // FIX #11 — Accessor method_label manquant (utilisé dans dashboard/views)
    public function getMethodLabelAttribute(): string
    {
        return match($this->method) {
            'mtn_momo'     => 'MTN MoMo',
            'airtel_money' => 'Airtel Money',
            'orange_money' => 'Orange Money',
            'wave'         => 'Wave',
            'carte'        => 'Carte bancaire',
            'virement'     => 'Virement bancaire',
            default        => ucfirst($this->method ?? 'Inconnu'),
        };
    }

    public function getMethodEmojiAttribute(): string
    {
        return match($this->method) {
            'mtn_momo'     => '📱',
            'airtel_money' => '📲',
            'orange_money' => '🍊',
            'wave'         => '🌊',
            'carte'        => '💳',
            'virement'     => '🏦',
            default        => '💰',
        };
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 0, ',', ' ') . ' ' . $this->currency;
    }
}
