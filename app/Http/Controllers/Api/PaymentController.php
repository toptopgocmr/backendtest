<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\CloudinaryService;
use App\Services\CloudinaryService;

class PaymentController extends Controller
{
    public static function paymentNumbers(): array
    {
        return [
            'mtn_momo' => [
                'number' => env('MTN_MOMO_NUMBER', '+242 06 XXX XX XX'),
                'label'  => 'MTN MoMo',
            ],
            'airtel_money' => [
                'number' => env('AIRTEL_MONEY_NUMBER', '+242 05 XXX XX XX'),
                'label'  => 'Airtel Money',
            ],
        ];
    }

    public function initiate(Request $request): JsonResponse
    {
        $request->validate([
            'booking_id'  => 'required_without:booking_ref|exists:bookings,id',
            'booking_ref' => 'required_without:booking_id|string',
            'method'      => 'required|string|in:mtn_momo,airtel_money',
            'phone'       => 'nullable|string|max:25',
        ]);

        $booking = $request->booking_id
            ? Booking::findOrFail($request->booking_id)
            : Booking::where('reference', $request->booking_ref)->firstOrFail();

        if ((int) $booking->user_id !== (int) auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette réservation ne vous appartient pas.',
            ], 403);
        }

        $existing = Payment::where('booking_id', $booking->id)
            ->whereIn('status', [
                Payment::STATUS_EN_ATTENTE,
                Payment::STATUS_EN_ATTENTE_CONFIRMATION,
                Payment::STATUS_SUCCES,
            ])->first();

        if ($existing) {
            return response()->json([
                'success'     => false,
                'message'     => 'Un paiement est déjà en cours ou validé pour cette réservation.',
                'payment_ref' => $existing->reference ?? "PAY-{$existing->id}",
                'booking_ref' => $booking->reference ?? "BK-{$booking->id}",
                'payment'     => new PaymentResource($existing),
            ], 422);
        }

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'user_id'    => auth()->id(),
            'amount'     => $booking->total_amount,
            'currency'   => $booking->currency ?? 'XAF',
            'method'     => $request->method,
            'phone'      => $request->phone,
            'status'     => Payment::STATUS_EN_ATTENTE,
        ]);

        return response()->json([
            'success'         => true,
            'payment_ref'     => $payment->reference ?? "PAY-{$payment->id}",
            'booking_ref'     => $booking->reference ?? "BK-{$booking->id}",
            'booking_id'      => $booking->id,
            'amount'          => $booking->total_amount,
            'currency'        => $booking->currency ?? 'XAF',
            'payment_numbers' => self::paymentNumbers(),
            'instructions'    => [
                "1. Envoyez {$booking->total_amount} XAF à l'un des numéros ci-dessous.",
                "2. Notez l'ID de transaction affiché sur votre téléphone.",
                "3. Prenez une capture d'écran de la confirmation.",
                "4. Soumettez l'ID et la capture dans l'application.",
                "⏳ Validation sous 5 à 30 minutes.",
            ],
        ]);
    }

    public function instructions(Booking $booking): JsonResponse
    {
        if ((int) $booking->user_id !== (int) auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        return response()->json([
            'success'         => true,
            'amount'          => $booking->total_amount,
            'currency'        => 'XAF',
            'payment_numbers' => self::paymentNumbers(),
        ]);
    }

    // ── Soumission preuve — Flutter envoie : method, provider_ref, phone, proof (multipart) ou proof_base64
    public function store(SubmitPaymentRequest $request, Booking $booking): JsonResponse
    {
        if ((int) $booking->user_id !== (int) auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

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
            $proofPath  = null;
            $cloudinary = new CloudinaryService();

            // Mobile : multipart file
            if ($request->hasFile('proof')) {
                // FIX Railway : Cloudinary au lieu du storage local ephemere
                $proofPath = $cloudinary->upload(
                    $request->file('proof'),
                    'immostay/payments/' . $booking->id
                );
            }
            // Web : base64 -> temp file -> Cloudinary
            elseif ($request->filled('proof_base64')) {
                $imageData = base64_decode($request->proof_base64);
                $tmpPath   = tempnam(sys_get_temp_dir(), 'proof_') . '.jpg';
                file_put_contents($tmpPath, $imageData);
                $tmpFile   = new \Illuminate\Http\UploadedFile(
                    $tmpPath, 'preuve.jpg', 'image/jpeg', null, true
                );
                $proofPath = $cloudinary->upload($tmpFile, 'immostay/payments/' . $booking->id);
                @unlink($tmpPath);
            }

            // Colonnes réelles du modèle Payment : method, phone, provider_ref
            $payment = Payment::create([
                'booking_id'  => $booking->id,
                'user_id'     => auth()->id(),
                'amount'      => $booking->total_amount,
                'currency'    => 'XAF',
                'method'      => $request->method,        // Flutter envoie 'method'
                'phone'       => $request->phone,         // Flutter envoie 'phone'
                'provider_ref'=> $request->provider_ref,  // Flutter envoie 'provider_ref'
                'proof_image' => $proofPath,
                'status'      => Payment::STATUS_EN_ATTENTE_CONFIRMATION,
            ]);

            $booking->update(['payment_status' => 'en_attente_confirmation']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Preuve de paiement soumise. Validation sous 5 à 30 minutes.',
                'payment' => new PaymentResource($payment->load('booking')),
                'receipt' => [
                    'type'        => 'provisoire',
                    'payment_ref' => $payment->reference ?? "PAY-{$payment->id}",
                    'booking_ref' => $booking->reference  ?? "BK-{$booking->id}",
                    'amount'      => $payment->amount,
                    'currency'    => $payment->currency,
                    'provider_ref'=> $payment->provider_ref,
                    'submitted_at'=> $payment->created_at->toIso8601String(),
                    'status'      => $payment->status_label ?? 'en_attente_confirmation',
                    'message'     => "Votre paiement est en cours de vérification. Confirmation d'ici 30 minutes.",
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

    public function confirmManual(Request $request, string $paymentRef): JsonResponse
    {
        $payment = Payment::where('reference', $paymentRef)->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => "Paiement introuvable pour la référence « {$paymentRef} ».",
            ], 404);
        }

        if ((int) $payment->booking->user_id !== (int) auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        if (!in_array($payment->status, [Payment::STATUS_EN_ATTENTE, Payment::STATUS_EN_ATTENTE_CONFIRMATION])) {
            return response()->json([
                'success' => false,
                'message' => 'Ce paiement ne peut pas être confirmé manuellement.',
            ], 422);
        }

        $request->validate([
            'provider_ref' => 'nullable|string|max:100',
            'phone'        => 'nullable|string|max:25',
            'proof'        => 'nullable|image|max:5120',
            'proof_base64' => 'nullable|string',
        ]);

        $proofPath  = $payment->proof_image;
        $proofFile  = $request->file('proof');
        $cloudinary = new CloudinaryService();

        if ($proofFile) {
            // FIX Railway : Cloudinary
            $proofPath = $cloudinary->upload(
                $proofFile,
                'immostay/payments/' . $payment->booking_id
            );
        } elseif ($request->filled('proof_base64')) {
            $imageData   = base64_decode($request->proof_base64);
            $filename    = 'preuve_' . time() . '.jpg';
            $storagePath = "payments/{$payment->booking_id}/{$filename}";
            Storage::disk('public')->put($storagePath, $imageData);
            $proofPath   = $storagePath;
        }

        $payment->update([
            'provider_ref' => $request->provider_ref ?? $payment->provider_ref,
            'phone'        => $request->phone         ?? $payment->phone,
            'proof_image'  => $proofPath,
            'status'       => Payment::STATUS_EN_ATTENTE_CONFIRMATION,
        ]);

        $payment->booking?->update(['payment_status' => 'en_attente_confirmation']);

        return response()->json([
            'success' => true,
            'message' => 'Preuve soumise. Validation sous 5 à 30 minutes.',
            'payment' => new PaymentResource($payment->fresh()->load('booking')),
            'receipt' => [
                'type'         => 'provisoire',
                'payment_ref'  => $payment->reference ?? "PAY-{$payment->id}",
                'booking_ref'  => $payment->booking?->reference ?? "BK-{$payment->booking_id}",
                'amount'       => $payment->amount,
                'currency'     => $payment->currency ?? 'XAF',
                'provider_ref' => $payment->provider_ref,
                'submitted_at' => now()->toIso8601String(),
                'status'       => 'en_attente_confirmation',
                'message'      => "Votre paiement est en cours de vérification.",
            ],
        ]);
    }

    public function status(string $paymentRef): JsonResponse
    {
        $payment = Payment::where('reference', $paymentRef)->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => "Paiement introuvable pour la référence « {$paymentRef} ».",
            ], 404);
        }

        if ((int) $payment->booking->user_id !== (int) auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        return response()->json([
            'success'     => true,
            'status'      => $payment->status,
            'label'       => $payment->status_label,
            'validated'   => $payment->status === Payment::STATUS_SUCCES,
            'verified_at' => $payment->verified_at?->toIso8601String(),
        ]);
    }

    public function show(string $paymentRef): JsonResponse
    {
        $payment = Payment::where('reference', $paymentRef)->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => "Paiement introuvable pour la référence « {$paymentRef} ».",
            ], 404);
        }

        if ((int) $payment->booking->user_id !== (int) auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        $payment->load('booking');
        $response = ['success' => true, 'payment' => new PaymentResource($payment)];

        if ($payment->status === Payment::STATUS_SUCCES) {
            $response['receipt'] = [
                'type'         => 'final',
                'payment_id'   => $payment->id,
                'booking_ref'  => $payment->booking->reference ?? "BK-{$payment->booking_id}",
                'amount'       => $payment->amount,
                'currency'     => $payment->currency,
                'provider_ref' => $payment->provider_ref,
                'verified_at'  => $payment->verified_at?->format('d/m/Y H:i'),
                'status'       => 'Paiement confirmé ✅',
                'message'      => 'Votre paiement a été validé. Votre réservation est confirmée !',
            ];
        }

        return response()->json($response);
    }

    public function myPayments(Request $request): JsonResponse
    {
        $payments = Payment::where('user_id', auth()->id())
            ->with('booking')
            ->latest()
            ->paginate($request->per_page ?? 10);

        return response()->json([
            'success'  => true,
            'payments' => PaymentResource::collection($payments),
            'meta'     => [
                'current_page' => $payments->currentPage(),
                'last_page'    => $payments->lastPage(),
                'total'        => $payments->total(),
            ],
        ]);
    }
}
