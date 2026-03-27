<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Property;
use App\Models\PropertyAvailability;
use App\Models\Notification;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $bookings = Booking::with(['property.primaryImage', 'payment'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data'    => $bookings->map(fn($b) => $this->bookingResource($b)),
            'meta'    => ['total' => $bookings->total(), 'last_page' => $bookings->lastPage()],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'property_id' => 'required|exists:properties,id',
            'check_in'    => 'required|date|after_or_equal:today',
            'check_out'   => 'required|date|after:check_in',
            'guests'      => 'required|integer|min:1',
            'notes'       => 'nullable|string|max:500',
        ]);

        $property = Property::findOrFail($request->property_id);

        if ($property->status !== 'disponible' || !$property->is_approved) {
            return response()->json(['success' => false, 'message' => 'Ce bien n\'est pas disponible.'], 422);
        }

        if ($request->guests > $property->max_guests) {
            return response()->json([
                'success' => false,
                'message' => "Ce bien accueille maximum {$property->max_guests} personne(s).",
            ], 422);
        }

        $checkIn  = Carbon::parse($request->check_in);
        $checkOut = Carbon::parse($request->check_out);
        $nights   = $checkIn->diffInDays($checkOut);

        // Vérifier disponibilité
        $conflict = PropertyAvailability::where('property_id', $request->property_id)
            ->whereBetween('unavailable_date', [
                $checkIn->toDateString(),
                $checkOut->clone()->subDay()->toDateString(),
            ])->exists();

        if ($conflict) {
            return response()->json(['success' => false, 'message' => 'Ces dates ne sont pas disponibles.'], 409);
        }

        // Calcul montants (FIX: price pas price_per_night)
        $baseAmount = round($property->price * $nights, 2);
        $feesAmount = round($baseAmount * 0.05, 2);
        $total      = $baseAmount + $feesAmount;

        $booking = Booking::create([
            'user_id'      => $request->user()->id,
            'property_id'  => $request->property_id,
            'check_in'     => $request->check_in,
            'check_out'    => $request->check_out,
            'nights'       => $nights,
            'guests'       => $request->guests,
            'base_amount'  => $baseAmount,    // FIX: base_amount
            'fees_amount'  => $feesAmount,    // FIX: fees_amount
            'total_amount' => $total,
            'currency'     => $property->currency,
            'status'       => 'en_attente',   // FIX: enum FR
            'notes'        => $request->notes,
        ]);

        // Notification
        Notification::create([
            'user_id' => $request->user()->id,
            'title'   => 'Réservation créée 📅',
            'body'    => "Votre réservation {$booking->reference} a été créée. Procédez au paiement.",
            'type'    => 'booking',
            'data'    => ['booking_id' => $booking->id, 'reference' => $booking->reference],
        ]);

        return response()->json([
            'success' => true,
            'data'    => $this->bookingResource($booking->load(['property', 'user'])),
            'message' => 'Réservation créée. Procédez au paiement.',
        ], 201);
    }

    public function show(Request $request, string $ref)
    {
        $booking = Booking::with(['property.images', 'user', 'payment', 'review'])
            ->where('reference', $ref)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json(['success' => true, 'data' => $this->bookingResource($booking)]);
    }

    public function cancel(Request $request, string $ref)
    {
        $booking = Booking::where('reference', $ref)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (!in_array($booking->status, ['en_attente', 'confirmé'])) {  // FIX: enum FR
            return response()->json(['success' => false, 'message' => 'Cette réservation ne peut pas être annulée.'], 422);
        }

        $booking->update([
            'status'        => 'annulé',       // FIX: enum FR
            'cancel_reason' => $request->reason ?? 'Annulé par le client',
            'cancelled_at'  => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Réservation annulée.']);
    }

    public function confirm(Request $request, string $ref)
    {
        if (!$request->user()->isAdmin() && !$request->user()->isOwner()) {
            return response()->json(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        Booking::where('reference', $ref)->firstOrFail()->update(['status' => 'confirmé']); // FIX

        return response()->json(['success' => true, 'message' => 'Réservation confirmée.']);
    }

    // ── Ressource ────────────────────────────────────────────────
    private function bookingResource(Booking $b): array
    {
        return [
            'id'           => $b->id,
            'reference'    => $b->reference,
            'check_in'     => $b->check_in?->format('d/m/Y'),
            'check_out'    => $b->check_out?->format('d/m/Y'),
            'nights'       => $b->nights,
            'guests'       => $b->guests,
            'base_amount'  => (float) $b->base_amount,  // FIX
            'fees_amount'  => (float) $b->fees_amount,  // FIX
            'total_amount' => (float) $b->total_amount,
            'currency'     => $b->currency,
            'status'       => $b->status,
            'notes'        => $b->notes,
            'created_at'   => $b->created_at?->toISOString(),
            'payment_status' => $b->payment?->status ?? 'non_payé',
            'property'     => $b->property ? [
                'id'          => $b->property->id,
                'title'       => $b->property->title,
                'city'        => $b->property->city,
                'image_url'   => $b->property->primaryImage?->url,  // FIX
                'cover_image' => $b->property->primaryImage?->url,
            ] : null,
        ];
    }
}
