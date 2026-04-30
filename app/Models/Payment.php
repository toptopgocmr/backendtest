<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;

    // ── Champs fillable alignés avec la migration create_payments_table ───────
    // + add_proof_to_payments_table
    // Colonnes réelles en DB :
    //   reference, booking_id, user_id, amount, currency,
    //   method, provider_ref, phone, status,
    //   proof_image (ajouté par migration 2026_04_23),
    //   refund_reason, paid_at, refunded_at, gateway_response
    protected $fillable = [
        'reference',        // ← OBLIGATOIRE (NOT NULL UNIQUE en migration)
        'booking_id',
        'user_id',
        'amount',
        'currency',
        'method',           // colonne réelle (pas payment_method)
        'provider_ref',     // colonne réelle (pas transaction_id)
        'phone',            // colonne réelle (pas phone_number)
        'proof_image',      // ajouté par migration 2026_04_23
        'status',
        'admin_note',
        'verified_by',
        'verified_at',
        'refund_reason',
        'paid_at',
        'refunded_at',
        'gateway_response',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'verified_at'      => 'datetime',
        'paid_at'          => 'datetime',
        'refunded_at'      => 'datetime',
        'gateway_response' => 'array',
    ];

    // ── Auto-génération de la référence ───────────────────────────────────────
    protected static function booted(): void
    {
        static::creating(function ($payment) {
            if (empty($payment->reference)) {
                $payment->reference = 'PAY-' . strtoupper(Str::random(10));
            }
        });
    }

    // ── Statuts ───────────────────────────────────────────────────────────────
    const STATUS_EN_ATTENTE              = 'en_attente';
    const STATUS_EN_ATTENTE_CONFIRMATION = 'en_attente_confirmation';
    const STATUS_SUCCES                  = 'succès';
    const STATUS_ECHOUE                  = 'échoué';
    const STATUS_REMBOURSE               = 'remboursé';

    // ── Relations ─────────────────────────────────────────────────────────────
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    /**
     * URL de la preuve image.
     * Supporte Cloudinary (URL http) et storage local.
     */
    public function getProofImageUrlAttribute(): ?string
    {
        if (!$this->proof_image) {
            return null;
        }
        // Si c'est déjà une URL complète (Cloudinary)
        if (str_starts_with($this->proof_image, 'http')) {
            return $this->proof_image;
        }
        // Storage local
        return asset('storage/' . $this->proof_image);
    }

    /** Alias proof_url utilisé dans la vue Blade admin */
    public function getProofUrlAttribute(): ?string
    {
        return $this->proof_image_url;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_EN_ATTENTE              => 'En attente de paiement',
            self::STATUS_EN_ATTENTE_CONFIRMATION => 'En attente de confirmation',
            self::STATUS_SUCCES                  => 'Paiement validé',
            self::STATUS_ECHOUE                  => 'Paiement refusé',
            self::STATUS_REMBOURSE               => 'Remboursé',
            default                              => 'Inconnu',
        };
    }

    public function getMethodLabelAttribute(): string
    {
        return match ($this->method) {
            'mtn_momo'     => 'MTN MoMo',
            'airtel_money' => 'Airtel Money',
            'orange_money' => 'Orange Money',
            'wave'         => 'Wave',
            'carte'        => 'Carte bancaire',
            'virement'     => 'Virement',
            default        => ucfirst($this->method ?? '—'),
        };
    }

    public function getMethodEmojiAttribute(): string
    {
        return match ($this->method) {
            'mtn_momo'     => '🟡',
            'airtel_money' => '🔴',
            'orange_money' => '🟠',
            'wave'         => '🔵',
            'carte'        => '💳',
            'virement'     => '🏦',
            default        => '💰',
        };
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format((float) $this->amount, 0, ',', ' ') . ' ' . ($this->currency ?? 'XAF');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCES;
    }

    public function isPending(): bool
    {
        return in_array($this->status, [
            self::STATUS_EN_ATTENTE,
            self::STATUS_EN_ATTENTE_CONFIRMATION,
        ]);
    }
}
