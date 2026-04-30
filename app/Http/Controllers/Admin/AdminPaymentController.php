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

    public function validate(Request $request, Payment $payment)
    {
        $payment->update(['status' => 'validated']);
        $payment->booking->update(['status' => 'confirmed']);

        return response()->json([
            'message' => 'Paiement validé avec succès.',
            'payment' => $this->formatPayment($payment->fresh()),
        ]);
    }

    public function reject(Request $request, Payment $payment)
    {
        $payment->update(['status' => 'rejected']);

        return response()->json([
            'message' => 'Paiement refusé.',
            'payment' => $this->formatPayment($payment->fresh()),
        ]);
    }

    // ----------------------------------------------------------------
    // BUG 4 CORRIGÉ
    // AVANT (incorrect) :
    //   'method'       => $payment->payment_method,
    //   'phone'        => $payment->phone_number,
    //   'provider_ref' => $payment->transaction_id,
    //
    // APRÈS (correct — noms réels des colonnes en base) :
    //   'method'       => $payment->method,
    //   'phone'        => $payment->phone,
    //   'provider_ref' => $payment->provider_ref,
    // ----------------------------------------------------------------
    private function formatPayment(Payment $payment): array
    {
        return [
            'id'            => $payment->id,
            'reference'     => $payment->reference,
            'amount'        => $payment->amount,
            'status'        => $payment->status,
            'is_confirmed'  => $payment->status === 'validated',

            // ↓ CHAMPS CORRIGÉS
            'method'        => $payment->method,        // était payment_method
            'phone'         => $payment->phone,         // était phone_number
            'provider_ref'  => $payment->provider_ref,  // était transaction_id

            'proof_url'     => $payment->proof_url,
            'created_at'    => $payment->created_at?->toISOString(),
            'validated_at'  => $payment->validated_at?->toISOString(),

            'booking' => $payment->booking ? [
                'id'        => $payment->booking->id,
                'reference' => $payment->booking->reference,
                'property'  => $payment->booking->property?->name,
                'client'    => $payment->booking->user?->name,
                'phone'     => $payment->booking->user?->phone,
            ] : null,
        ];
    }
}
