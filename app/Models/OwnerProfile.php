<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OwnerProfile extends Model
{
    protected $fillable = [
        'user_id','company_name','siret','legal_form','contact_person',
        'contact_phone','contact_email','address','city','country',
        'id_document_type','id_document_number','id_document_file',
        'kbis_file','id_document_expiry','bank_name','bank_account',
        'mobile_money_number','preferred_payment','commission_rate',
        'status','is_verified','notes','verified_at',
    ];

    protected $casts = [
        'is_verified'        => 'boolean',
        'verified_at'        => 'datetime',
        'id_document_expiry' => 'date',
        'commission_rate'    => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->company_name ?? $this->user->name ?? '—';
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'vérifié'    => '<span class="badge-status actif">✓ Vérifié</span>',
            'suspendu'   => '<span class="badge-status annulé">Suspendu</span>',
            default      => '<span class="badge-status en_attente">En attente</span>',
        };
    }

    public function scopeVerified($q) { return $q->where('status', 'vérifié'); }
    public function scopePending($q)  { return $q->where('status', 'en_attente'); }
}
