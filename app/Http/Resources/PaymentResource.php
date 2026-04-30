<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'reference'      => $this->reference ?? "PAY-{$this->id}",
            'booking_id'     => $this->booking_id,
            'amount'         => $this->amount,
            'currency'       => $this->currency ?? 'XAF',
            'method'         => $this->method,
            'phone'          => $this->phone,
            'provider_ref'   => $this->provider_ref,
            'status'         => $this->status,
            'status_label'   => $this->status_label ?? $this->status,
            'created_at'     => $this->created_at->format('d/m/Y H:i'),
        ];
    }
}
