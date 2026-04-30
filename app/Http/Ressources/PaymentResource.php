<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'reference'        => $this->reference ?? "PAY-{$this->id}",
            'booking_id'       => $this->booking_id,
            'booking_ref'      => $this->booking?->reference ?? "BK-{$this->booking_id}",
            'amount'           => $this->amount,
            'currency'         => $this->currency ?? 'XAF',
            'amount_formatted' => number_format((float) $this->amount, 0, ',', ' ')
                                  . ' ' . ($this->currency ?? 'XAF'),
            'method'           => $this->method,
            'phone'            => $this->phone,
            'provider_ref'     => $this->provider_ref,
            'proof_image_url'  => $this->proof_image_url ?? null,
            'status'           => $this->status,
            'status_label'     => $this->status_label ?? $this->status,
            'admin_note'       => $this->admin_note,
            'verified_at'      => $this->verified_at?->format('d/m/Y H:i'),
            'paid_at'          => $this->paid_at?->format('d/m/Y H:i'),
            'created_at'       => $this->created_at->format('d/m/Y H:i'),
            'booking' => $this->whenLoaded('booking', fn() => [
                'id'             => $this->booking->id,
                'reference'      => $this->booking->reference ?? "BK-{$this->booking->id}",
                'status'         => $this->booking->status,
                'payment_status' => $this->booking->payment_status,
                'property_name'  => $this->booking->property?->title ?? 'N/A',
            ]),
        ];
    }
}