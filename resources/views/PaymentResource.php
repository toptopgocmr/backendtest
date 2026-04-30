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
            'booking_id'      => $this->booking_id,
            'booking_ref'     => $this->booking?->reference ?? "BK-{$this->booking_id}",
            'amount'          => $this->amount,
            'currency'        => $this->currency,
            'amount_formatted'=> number_format($this->amount, 0, ',', ' ') . ' ' . $this->currency,
            'payment_method'  => $this->payment_method,
            'phone_number'    => $this->phone_number,
            'transaction_id'  => $this->transaction_id,
            'proof_image_url' => $this->proof_image_url,
            'status'          => $this->status,
            'status_label'    => $this->status_label,
            'admin_note'      => $this->admin_note,
            'verified_at'     => $this->verified_at?->format('d/m/Y H:i'),
            'created_at'      => $this->created_at->format('d/m/Y H:i'),

            // Relations conditionnelles
            'booking'         => $this->whenLoaded('booking', fn() => [
                'id'            => $this->booking->id,
                'reference'     => $this->booking->reference ?? "BK-{$this->booking->id}",
                'check_in'      => $this->booking->check_in,
                'check_out'     => $this->booking->check_out,
                'status'        => $this->booking->status,
                'payment_status'=> $this->booking->payment_status,
                'property_name' => $this->booking->property?->name ?? 'N/A',
            ]),
            'verified_by'     => $this->whenLoaded('verifiedBy', fn() => [
                'id'   => $this->verifiedBy->id,
                'name' => $this->verifiedBy->name,
            ]),
        ];
    }
}
