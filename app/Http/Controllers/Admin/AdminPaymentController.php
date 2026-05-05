<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class AdminPaymentController extends Controller
{
    public function index(Request $request)
    {
        $payments = Payment::with(['booking.property', 'booking.user'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $payments->map(fn($p) => $this->formatPayment($p)),
            'meta' => [
                'total'        => $payments->total(),
                'current_page' => $payments->currentPage(),
                'last_page'    => $payments->lastPage(),
            ],
        ]);
    }

    // Renommé validate() → validatePayment() pour éviter le conflit avec Controller::validate()
    public function validatePayment(Request $request, Payment $payment)
    {
        $payment->update([
            'status'      => 'succès',
            'paid_at'     => now(),
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        $payment->booking?->update(['status' => 'confirmé']);

        return response()->json([
            'message' => 'Paiement validé avec succès.',
            'payment' => $this->formatPayment($payment->fresh()),
        ]);
    }

    public function reject(Request $request, Payment $payment)
    {
        $request->validate(['reason' => 'nullable|string|max:255']);

        $payment->update([
            'status'     => 'échoué',
            'admin_note' => $request->reason,
        ]);

        $payment->booking?->update(['status' => 'en_attente']);

        return response()->json([
            'message' => 'Paiement refusé.',
            'payment' => $this->formatPayment($payment->fresh()),
        ]);
    }

    private function formatPayment(Payment $payment): array
    {
        return [
            'id'           => $payment->id,
            'reference'    => $payment->reference,
            'amount'       => $payment->amount,
            'status'       => $payment->status,
            'is_confirmed' => $payment->status === 'succès',
            'method'       => $payment->method,
            'phone'        => $payment->phone,
            'provider_ref' => $payment->provider_ref,
            'proof_image'  => $payment->proof_image
                ? asset('storage/' . $payment->proof_image)
                : null,
            'admin_note'   => $payment->admin_note,
            'verified_at'  => $payment->verified_at?->toISOString(),
            'created_at'   => $payment->created_at?->toISOString(),

            'booking' => $payment->booking ? [
                'id'        => $payment->booking->id,
                'reference' => $payment->booking->reference ?? "BK-{$payment->booking->id}",
                'property'  => $payment->booking->property?->title,
                'client'    => $payment->booking->user?->name,
                'phone'     => $payment->booking->user?->phone,
            ] : null,
        ];
    }
}
