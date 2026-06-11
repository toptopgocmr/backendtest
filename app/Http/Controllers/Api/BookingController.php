<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Property;
use App\Models\PropertyAvailability;
use App\Models\PropertyPricingGrid;
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
            'check_in'    => 'required|date',
            'check_out'   => 'required|date|after:check_in',
            'guests'      => 'required|integer|min:1',
            'period'      => 'nullable|string|in:heure,jour,nuit,semaine,mois,an',
            'notes'       => 'nullable|string|max:500',
            'options'     => 'nullable|array',
            'options.*'   => 'nullable|string',
        ]);

        $property = Property::findOrFail($request->property_id);

        if ($property->status !== 'disponible' || !$property->is_approved) {
            return response()->json([
                'success' => false,
                'message' => "Ce bien n'est pas disponible.",
            ], 422);
        }

        $effectiveCapacity = $property->capacity ?? $property->max_guests;
        if ($effectiveCapacity && $request->guests > $effectiveCapacity) {
            return response()->json([
                'success' => false,
                'message' => "Ce bien accueille maximum {$effectiveCapacity} personne(s).",
            ], 422);
        }

        $checkIn  = Carbon::parse($request->check_in);
        $checkOut = Carbon::parse($request->check_out);

        // FIX : today() au lieu de yesterday() — évite de rejeter les réservations du jour même
        if ($checkIn->lt(Carbon::today()->startOfDay())) {
            return response()->json([
                'success' => false,
                'message' => "La date d'arrivée ne peut pas être dans le passé.",
            ], 422);
        }

        // Utilise le tarif sélectionné par l'utilisateur (period) en priorité
        $requestedPeriod = $request->input('period');
        $pricingGrid     = null;

        if ($requestedPeriod) {
            // Cherche le tarif sélectionné par le client
            $pricingGrid = PropertyPricingGrid::where('property_id', $property->id)
                ->where('period', $requestedPeriod)
                ->where('is_active', true)
                ->first();
        }

        // Fallback : si period absent (ancienne app), on prend le premier tarif actif
        // correspondant au price_period du bien, ou le moins cher disponible.
        if (!$pricingGrid) {
            $pricingGrid = PropertyPricingGrid::where('property_id', $property->id)
                ->where('is_active', true)
                ->where('period', $property->price_period ?? 'jour')
                ->first();

            // Si toujours rien, on prend n'importe quel tarif actif
            if (!$pricingGrid) {
                $pricingGrid = PropertyPricingGrid::where('property_id', $property->id)
                    ->where('is_active', true)
                    ->orderBy('price')
                    ->first();
            }
        }

        // Log stderr (visible dans Railway)
        error_log(sprintf(
            '[BOOKING_CREATE] property_id=%s period=%s grid=%s price_grid=%s price_prop=%s',
            $request->property_id,
            $requestedPeriod ?? 'NULL',
            $pricingGrid ? 'FOUND(id='.$pricingGrid->id.',period='.$pricingGrid->period.')' : 'NOT_FOUND',
            $pricingGrid ? $pricingGrid->price : 'N/A',
            $property->price
        ));

        // Fallback sur price_period du bien si aucun tarif grille trouvé
        $pricePeriod = $pricingGrid?->period ?? $requestedPeriod ?? $property->price_period ?? 'nuit';
        $duration    = $this->calcDuration($checkIn, $checkOut, $pricePeriod);

        if ($duration < 1) {
            return response()->json([
                'success' => false,
                'message' => "La durée du séjour doit être d'au moins 1 {$pricePeriod}.",
            ], 422);
        }

        // ── Vérification 1 : jours bloqués manuellement (PropertyAvailability) ─
        $unavailableConflict = PropertyAvailability::where('property_id', $request->property_id)
            ->whereBetween('unavailable_date', [
                $checkIn->toDateString(),
                $checkOut->clone()->subDay()->toDateString(),
            ])->exists();

        if ($unavailableConflict) {
            return response()->json([
                'success' => false,
                'message' => 'Ces dates ne sont pas disponibles (bien bloqué par le propriétaire).',
            ], 409);
        }

        // ── Vérification 2 : chevauchement avec réservations existantes ────────
        // Statuts exclus : 'annulé' uniquement — en_attente et confirmé bloquent le créneau.
        //
        // Logique d'overlap :
        //   Une réservation B chevauche [newCheckIn, newCheckOut[ si :
        //     B.check_in  < newCheckOut   ET   B.check_out > newCheckIn
        //
        // Pour le mode 'heure' : comparaison datetime précise (à la minute).
        // Pour les autres modes : comparaison date seule (jour).

        $overlappingBooking = null;

        if ($pricePeriod === 'heure') {
            // Comparaison datetime complète — même jour, même créneau horaire
            $overlappingBooking = Booking::where('property_id', $request->property_id)
                ->whereNotIn('status', ['annulé', 'pending_payment']) // pending_payment ne bloque pas le créneau
                ->where('check_in',  '<', $checkOut)
                ->where('check_out', '>', $checkIn)
                ->first();
        } else {
            // Comparaison par date (jour) pour nuit / jour / semaine / mois / an
            $overlappingBooking = Booking::where('property_id', $request->property_id)
                ->whereNotIn('status', ['annulé', 'pending_payment']) // pending_payment ne bloque pas le créneau
                ->whereDate('check_in',  '<', $checkOut->toDateString())
                ->whereDate('check_out', '>', $checkIn->toDateString())
                ->first();
        }

        if ($overlappingBooking) {
            // Message détaillé selon le mode de tarification
            if ($pricePeriod === 'heure') {
                $ciLabel = $overlappingBooking->check_in->format('d/m/Y \à H\hi');
                $coLabel = $overlappingBooking->check_out->format('d/m/Y \à H\hi');
                $message = "Ce bien est déjà réservé du {$ciLabel} au {$coLabel}. "
                         . "Veuillez choisir un autre créneau horaire.";
            } else {
                $ciLabel = $overlappingBooking->check_in->format('d/m/Y');
                $coLabel = $overlappingBooking->check_out->format('d/m/Y');
                $message = "Ce bien est déjà réservé du {$ciLabel} au {$coLabel}. "
                         . "Veuillez choisir d'autres dates.";
            }

            return response()->json([
                'success'  => false,
                'message'  => $message,
                'conflict' => [
                    'check_in'  => $overlappingBooking->check_in->toIso8601String(),
                    'check_out' => $overlappingBooking->check_out->toIso8601String(),
                ],
            ], 409);
        }

        // ── Calcul du montant ──────────────────────────────────────────────────
        // Priorité : prix de la grille tarifaire sélectionnée → prix de base du bien
        $price = $pricingGrid ? (float) $pricingGrid->price : (float) ($property->price ?? 0);
        if ($price <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Le prix de ce bien est invalide.',
            ], 422);
        }

        $baseAmount = round($price * $duration, 2);
        $feesAmount = 0;
        $total      = $baseAmount + $feesAmount;

        $commissionRate   = $this->getOwnerCommissionRate($property);
        $commissionAmount = round($baseAmount * ($commissionRate / 100), 2);
        $ownerAmount      = round($baseAmount - $commissionAmount, 2);

        $booking = Booking::create([
            'user_id'                 => $request->user()->id,
            'property_id'             => $request->property_id,
            'check_in'                => $checkIn,
            'check_out'               => $checkOut,
            'nights'                  => $duration,
            'guests'                  => $request->guests,
            'base_amount'             => $baseAmount,
            'fees_amount'             => $feesAmount,
            'total_amount'            => $total,
            'commission_rate'         => $commissionRate,
            'owner_commission_amount' => $commissionAmount,
            'owner_amount'            => $ownerAmount,
            'currency'                => $property->currency ?? 'XAF',
            'status'                  => 'pending_payment', // ← caché de l'admin jusqu'au paiement
            'notes'                   => $request->notes,
        ]);

        $booking->load(['property.images', 'user']);

        Notification::create([
            'user_id' => $request->user()->id,
            'title'   => 'Réservation créée 📅',
            'body'    => "Votre réservation {$booking->reference} a été initialisée. Soumettez votre preuve de paiement pour la confirmer.",
            'type'    => 'booking',
            'data'    => ['booking_id' => $booking->id, 'reference' => $booking->reference],
        ]);

        return response()->json([
            'success' => true,
            'data'    => $this->bookingResource($booking),
            'message' => 'Réservation initialisée. Soumettez votre preuve de paiement pour la valider.',
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

        if (!in_array($booking->status, ['en_attente', 'confirmé'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cette réservation ne peut pas être annulée.',
            ], 422);
        }

        $booking->update([
            'status'        => 'annulé',
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

        Booking::where('reference', $ref)->firstOrFail()->update(['status' => 'confirmé']);

        return response()->json(['success' => true, 'message' => 'Réservation confirmée.']);
    }

    // ── Dates réservées pour le calendrier Flutter ────────────────────────────

    public function bookedDates(string $id)
    {
        $bookings = Booking::where('property_id', $id)
            ->whereNotIn('status', ['annulé', 'pending_payment'])
            ->get(['check_in', 'check_out']);

        $dates = [];
        foreach ($bookings as $booking) {
            $current = $booking->check_in->copy()->startOfDay();
            $end     = $booking->check_out->copy()->startOfDay();
            while ($current->lt($end)) {
                $dates[] = $current->toDateString();
                $current->addDay();
            }
        }

        return response()->json([
            'success'      => true,
            'booked_dates' => array_values(array_unique($dates)),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function calcDuration(Carbon $checkIn, Carbon $checkOut, string $period): int
    {
        return match($period) {
            'heure'   => max(1, $checkIn->diffInHours($checkOut)),
            'nuit',
            'jour'    => max(1, $checkIn->diffInDays($checkOut)),
            'semaine' => max(1, (int) floor($checkIn->diffInDays($checkOut) / 7)),
            'mois'    => max(1, $checkIn->diffInMonths($checkOut)),
            'an'      => max(1, $checkIn->diffInYears($checkOut)),
            default   => max(1, $checkIn->diffInDays($checkOut)),
        };
    }

    private function getOwnerCommissionRate(Property $property): float
    {
        $ownerProfile = $property->owner?->ownerProfile ?? null;
        if ($ownerProfile && $ownerProfile->commission_rate > 0) {
            return (float) $ownerProfile->commission_rate;
        }
        return 10.0;
    }

    // ── Ressource API ─────────────────────────────────────────────────────────

    private function bookingResource(Booking $b): array
    {
        $image = $b->property?->primaryImage?->url
               ?? $b->property?->images?->first()?->url
               ?? null;

        $pricePeriod = $b->property?->price_period ?? 'nuit';

        return [
            'id'                      => $b->id,
            'reference'               => $b->reference,
            'ref'                     => $b->reference,
            'check_in'                => $b->check_in?->format('Y-m-d\TH:i:s'),
            'check_out'               => $b->check_out?->format('Y-m-d\TH:i:s'),
            'nights'                  => $b->nights,
            'duration_unit'           => $pricePeriod,
            'guests'                  => $b->guests,
            'base_amount'             => (float) $b->base_amount,
            'fees_amount'             => (float) $b->fees_amount,
            'total_amount'            => (float) $b->total_amount,
            'commission_rate'         => (float) ($b->commission_rate ?? 10),
            'owner_commission_amount' => (float) ($b->owner_commission_amount ?? 0),
            'owner_amount'            => (float) ($b->owner_amount ?? 0),
            'currency'                => $b->currency ?? 'XAF',
            'status'                  => $b->status,
            'notes'                   => $b->notes,
            'created_at'              => $b->created_at?->toISOString(),
            'payment_status'          => $b->payment?->status ?? 'non_payé',
            'property'                => $b->property ? [
                'id'           => $b->property->id,
                'title'        => $b->property->title,
                'name'         => $b->property->title,
                'city'         => $b->property->city,
                'price'        => (float) $b->property->price,
                'price_period' => $pricePeriod,
                'image_url'    => $image,
                'cover_image'  => $image,
            ] : null,
        ];
    }
}
