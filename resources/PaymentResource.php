<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'reference'       => $this->reference,
            'booking_id'      => $this->booking_id,
            'booking_ref'     => $this->booking?->reference ?? "BK-{$this->booking_id}",

            // ✅ FIX : noms de colonnes réels en DB
            'amount'          => $this->amount,
            'currency'        => $this->currency ?? 'XAF',
            'amount_formatted'=> number_format((float) $this->amount, 0, ',', ' ') . ' ' . ($this->currency ?? 'XAF'),

            'method'          => $this->method,           // ← colonne réelle (pas payment_method)
            'method_label'    => $this->method_label,
            'method_emoji'    => $this->method_emoji,

            'phone'           => $this->phone,            // ← colonne réelle (pas phone_number)
            'provider_ref'    => $this->provider_ref,     // ← colonne réelle (pas transaction_id)

            // ✅ FIX : proof_image avec accesseur qui gère Cloudinary + local
            'proof_image_url' => $this->proof_image_url,

            'status'          => $this->status,
            'status_label'    => $this->status_label,
            'admin_note'      => $this->admin_note,
            'verified_at'     => $this->verified_at?->format('d/m/Y H:i'),
            'paid_at'         => $this->paid_at?->format('d/m/Y H:i'),
            'created_at'      => $this->created_at->format('d/m/Y H:i'),

            // Relations conditionnelles
            'booking' => $this->whenLoaded('booking', fn() => [
                'id'            => $this->booking->id,
                'reference'     => $this->booking->reference,
                'check_in'      => $this->booking->check_in?->format('d/m/Y'),
                'check_out'     => $this->booking->check_out?->format('d/m/Y'),
                'base_amount'   => $this->booking->base_amount,
                'fees_amount'   => $this->booking->fees_amount,
                'total_amount'  => $this->booking->total_amount,
                'status'        => $this->booking->status,
                'property_name' => $this->booking->property?->title ?? 'N/A',
            ]),

            'verified_by' => $this->whenLoaded('verifiedBy', fn() => [
                'id'   => $this->verifiedBy->id,
                'name' => $this->verifiedBy->name,
            ]),
        ];
    }
}
