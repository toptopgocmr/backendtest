<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\MtnMomoService;
use App\Services\AirtelMoneyService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private MtnMomoService $mtn,
        private AirtelMoneyService $airtel
    ) {}

    public function initiate(Request $request)
    {
        $request->validate([
            'booking_reference' => 'required|string',
            'gateway'           => 'required|in:mtn_momo,airtel_money,orange_money,wave,carte,virement',
            'phone_number'      => 'required_if:gateway,mtn_momo,airtel_money,orange_money,wave|string',
        ]);

        $booking = Booking::where('reference', $request->booking_reference)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($booking->payment?->isSuccess()) {
            return response()->json(['success' => false, 'message' => 'Cette réservation est déjà payée.'], 409);
        }

        // FIX: colonnes correctes (method, phone, provider_ref)
        $payment = Payment::create([
            'booking_id' => $booking->id,
            'user_id'    => $request->user()->id,
            'method'     => $request->gateway,       // FIX: method pas gateway
            'phone'      => $request->phone_number,  // FIX: phone pas phone_number
            'amount'     => $booking->total_amount,
            'currency'   => $booking->currency,
            'status'     => 'en_attente',             // FIX: enum FR
        ]);

        try {
            $result = match ($request->gateway) {
                'mtn_momo'     => $this->mtn->requestPayment($payment, $request->phone_number),
                'airtel_money' => $this->airtel->requestPayment($payment, $request->phone_number),
                default        => ['status' => 'en_attente', 'message' => 'En attente de confirmation.'],
            };

            // FIX: provider_ref pas gateway_ref, et statut FR
            $payment->update([
                'status'       => $result['status'] === 'processing' ? 'en_attente' : ($result['status'] ?? 'en_attente'),
                'provider_ref' => $result['gateway_ref'] ?? null,  // FIX: provider_ref
            ]);
        } catch (\Exception $e) {
            $payment->update(['status' => 'échoué']); // FIX: enum FR
            return response()->json(['success' => false, 'message' => 'Erreur paiement: ' . $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'data'    => $payment,
            'message' => 'Paiement initié. Validez sur votre téléphone.',
        ]);
    }

    public function status(string $ref)
    {
        $payment = Payment::where('reference', $ref)->firstOrFail();

        if ($payment->status === 'en_attente') { // FIX: enum FR
            try {
                $result = match ($payment->method) { // FIX: method
                    'mtn_momo'     => $this->mtn->checkStatus($payment),
                    'airtel_money' => $this->airtel->checkStatus($payment),
                    default        => ['status' => $payment->status],
                };

                if ($result['status'] === 'success') {
                    $payment->update(['status' => 'succès', 'paid_at' => now()]); // FIX: enum FR
                    $payment->booking->update(['status' => 'confirmé']);          // FIX: enum FR
                } elseif ($result['status'] === 'failed') {
                    $payment->update(['status' => 'échoué']); // FIX: enum FR
                }
            } catch (\Exception $e) {}
        }

        return response()->json(['success' => true, 'data' => $payment]);
    }

    public function mtnCallback(Request $request)
    {
        $ref    = $request->input('financialTransactionId') ?? $request->input('referenceId');
        $status = $request->input('status');

        // FIX: provider_ref pas gateway_ref
        $payment = Payment::where('provider_ref', $ref)->first();

        if ($payment && $status === 'SUCCESSFUL') {
            $payment->update([
                'status'  => 'succès',  // FIX: enum FR
                'paid_at' => now(),
            ]);
            $payment->booking->update(['status' => 'confirmé']); // FIX: enum FR
        }

        return response()->json(['status' => 'OK']);
    }

    public function airtelCallback(Request $request)
    {
        $ref    = $request->input('transaction.id');
        $status = $request->input('status');

        $payment = Payment::where('provider_ref', $ref)->first(); // FIX: provider_ref

        if ($payment && $status === 'TS') {
            $payment->update([
                'status'  => 'succès', // FIX: enum FR
                'paid_at' => now(),
            ]);
            $payment->booking->update(['status' => 'confirmé']); // FIX: enum FR
        }

        return response()->json(['status' => 'OK']);
    }
}
