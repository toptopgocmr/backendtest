<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Booking;
use App\Models\Payment;
use App\Notifications\PaymentReceived;
use App\Notifications\PaymentProvisionalReceipt;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    // ── Numéros de paiement de la plateforme ─────────────────────────────────
    const PAYMENT_NUMBERS = [
        'mtn_momo'     => [
            'number' => '+242 06 XXX XX XX',  // ← Remplacez par vos vrais numéros
            'label'  => 'MTN MoMo',
        ],
        'airtel_money' => [
            'number' => '+242 05 XXX XX XX',  // ← Remplacez par vos vrais numéros
            'label'  => 'Airtel Money',
        ],
    ];

    /**
     * Retourner les instructions de paiement pour une réservation.
     */
    public function instructions(Booking $booking): JsonResponse
    {
        $this->authorize('view', $booking);

        return response()->json([
            'success'         => true,
            'amount'          => $booking->total_price,
            'currency'        => 'XAF',
            'payment_numbers' => self::PAYMENT_NUMBERS,
            'instructions'    => [
                "1. Envoyez {$booking->total_price} XAF à l'un des numéros ci-dessus.",
                "2. Notez l'ID de transaction affiché sur votre téléphone.",
                "3. Prenez une capture d'écran de la confirmation.",
                "4. Soumettez l'ID et la capture dans l'application.",
                "⏳ Validation sous 5 à 30 minutes.",
            ],
        ]);
    }

    /**
     * Client soumet la preuve de paiement.
     */
    public function store(SubmitPaymentRequest $request, Booking $booking): JsonResponse
    {
        $this->authorize('view', $booking);

        // Vérifier qu'il n'y a pas déjà un paiement en cours ou validé
        $existingPayment = Payment::where('booking_id', $booking->id)
            ->whereIn('status', [
                Payment::STATUS_EN_ATTENTE_CONFIRMATION,
                Payment::STATUS_SUCCES,
            ])->first();

        if ($existingPayment) {
            return response()->json([
                'success' => false,
                'message' => 'Un paiement est déjà en cours pour cette réservation.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Sauvegarder la preuve image
            $proofPath = null;
            if ($request->hasFile('proof_image')) {
                $proofPath = $request->file('proof_image')
                    ->store("payments/{$booking->id}", 'public');
            }

            // Créer le paiement
            $payment = Payment::create([
                'booking_id'     => $booking->id,
                'user_id'        => auth()->id(),
                'amount'         => $booking->total_price,
                'currency'       => 'XAF',
                'payment_method' => $request->payment_method,
                'phone_number'   => $request->phone_number,
                'transaction_id' => $request->transaction_id,
                'proof_image'    => $proofPath,
                'status'         => Payment::STATUS_EN_ATTENTE_CONFIRMATION,
            ]);

            // Mettre à jour le statut de la réservation
            $booking->update(['payment_status' => 'en_attente_confirmation']);

            DB::commit();

            // Notifier l'admin (vous pouvez adapter selon votre logique)
            // Notification::route('mail', config('app.admin_email'))
            //     ->notify(new PaymentReceived($payment));

            // Envoyer le reçu provisoire au client
            // auth()->user()->notify(new PaymentProvisionalReceipt($payment));

            return response()->json([
                'success' => true,
                'message' => 'Preuve de paiement soumise. Validation sous 5 à 30 minutes.',
                'payment' => new PaymentResource($payment->load('booking')),
                'receipt' => [
                    'type'           => 'provisoire',
                    'payment_id'     => $payment->id,
                    'booking_ref'    => $booking->reference ?? "BK-{$booking->id}",
                    'amount'         => $payment->amount,
                    'currency'       => $payment->currency,
                    'transaction_id' => $payment->transaction_id,
                    'submitted_at'   => $payment->created_at->format('d/m/Y H:i'),
                    'status'         => $payment->status_label,
                    'message'        => "Votre paiement est en cours de vérification. Vous recevrez une confirmation d'ici 30 minutes.",
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la soumission. Veuillez réessayer.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Voir le statut d'un paiement (polling ou après notification).
     */
    public function show(Payment $payment): JsonResponse
    {
        $this->authorize('view', $payment->booking);

        $payment->load('booking');

        $response = [
            'success' => true,
            'payment' => new PaymentResource($payment),
        ];

        // Si paiement validé → joindre le reçu final
        if ($payment->status === Payment::STATUS_SUCCES) {
            $response['receipt'] = [
                'type'           => 'final',
                'payment_id'     => $payment->id,
                'booking_ref'    => $payment->booking->reference ?? "BK-{$payment->booking_id}",
                'amount'         => $payment->amount,
                'currency'       => $payment->currency,
                'transaction_id' => $payment->transaction_id,
                'verified_at'    => $payment->verified_at?->format('d/m/Y H:i'),
                'status'         => 'Paiement confirmé ✅',
                'message'        => 'Votre paiement a été validé. Votre réservation est confirmée !',
            ];
        }

        return response()->json($response);
    }

    /**
     * Historique des paiements de l'utilisateur connecté.
     */
    public function index(): JsonResponse
    {
        $payments = Payment::where('user_id', auth()->id())
            ->with('booking')
            ->latest()
            ->paginate(10);

        return response()->json([
            'success'  => true,
            'payments' => PaymentResource::collection($payments),
        ]);
    }
}
