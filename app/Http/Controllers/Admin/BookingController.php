<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookingController extends Controller
{
    // ── Liste des réservations ────────────────────────────────────────────────
    public function index(Request $request)
    {
        $bookings = Booking::with([
                'user',
                'property.owner.ownerProfile',  // ✅ pour afficher la commission
                'payment',
            ])
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->latest()
            ->paginate(15);

        return view('admin.bookings.index', compact('bookings'));
    }

    // ── Détail d'une réservation ──────────────────────────────────────────────
    public function show(string $ref)
    {
        $booking = Booking::with([
                'user',
                'property.images',
                'property.owner.ownerProfile',  // ✅ pour la commission dans la fiche
                'payment',
                'review',
            ])
            ->where('reference', $ref)
            ->firstOrFail();

        return view('admin.bookings.show', compact('booking'));
    }

    // ── Confirmer ─────────────────────────────────────────────────────────────
    public function confirm(string $ref)
    {
        Booking::where('reference', $ref)->firstOrFail()
            ->update(['status' => 'confirmé']);

        return back()->with('success', 'Réservation confirmée.');
    }

    // ── Marquer comme terminée ────────────────────────────────────────────────
    public function complete(string $ref)
    {
        Booking::where('reference', $ref)->firstOrFail()
            ->update(['status' => 'terminé']);

        return back()->with('success', 'Réservation marquée comme terminée.');
    }

    // ── ✅ FIX Bug 2 — Annuler une réservation ────────────────────────────────
    /**
     * Annuler une réservation depuis le panel admin.
     * PUT admin/bookings/{ref}/cancel  →  admin.bookings.cancel
     */
    public function cancel(Request $request, string $ref): RedirectResponse
    {
        $booking = Booking::where('reference', $ref)->firstOrFail();

        // Seules les réservations en_attente ou confirmées peuvent être annulées
        if (!in_array($booking->status, ['en_attente', 'confirmé'])) {
            return back()->with(
                'error',
                "La réservation {$ref} ne peut pas être annulée (statut actuel : {$booking->status})."
            );
        }

        $booking->update([
            'status'        => 'annulé',
            'cancel_reason' => $request->input('reason', "Annulation par l'administrateur"),
            'cancelled_at'  => now(),
        ]);

        // Si un paiement validé existe → le marquer comme remboursé
        if ($booking->payment && $booking->payment->status === 'succès') {
            $booking->payment->update([
                'status'        => 'remboursé',
                'refund_reason' => $request->input('reason', 'Annulation admin'),
                'refunded_at'   => now(),
            ]);
        }

        // Notifier le client
        if (class_exists(\App\Models\Notification::class)) {
            \App\Models\Notification::create([
                'user_id' => $booking->user_id,
                'title'   => 'Réservation annulée',
                'body'    => "Votre réservation {$ref} a été annulée."
                             . ($request->input('reason')
                                 ? ' Motif : ' . $request->input('reason')
                                 : ''),
                'type'    => 'booking',
                'data'    => ['booking_id' => $booking->id],
            ]);
        }

        return redirect()
            ->route('admin.bookings.index')
            ->with('success', "Réservation {$ref} annulée avec succès.");
    }

    // ── 📄 EXPORT CSV ─────────────────────────────────────────────────────────
    /**
     * Export CSV des réservations (filtrables par statut/dates).
     * GET /admin/bookings/export-csv
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $bookings = Booking::with([
                'user',
                'property.owner.ownerProfile',  // ✅ commission
                'payment',
            ])
            ->when($request->status,    fn ($q, $v) => $q->where('status', $v))
            ->when($request->date_from, fn ($q, $v) => $q->whereDate('check_in', '>=', $v))
            ->when($request->date_to,   fn ($q, $v) => $q->whereDate('check_in', '<=', $v))
            ->latest()
            ->get();

        $filename = 'reservations_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($bookings) {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 pour Excel
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Référence',
                'Client',
                'Téléphone',
                'Propriété',
                'Ville',
                'Arrivée',
                'Départ',
                'Durée',
                'Unité durée',
                'Voyageurs',
                'Montant base (XAF)',
                'Frais (XAF)',
                'Total (XAF)',
                'Taux commission (%)',       // ✅ NEW
                'Commission Tholad (XAF)',   // ✅ NEW
                'Reversé propriétaire (XAF)', // ✅ NEW
                'Statut réservation',
                'Statut paiement',
                'Créée le',
            ], ';');

            foreach ($bookings as $b) {
                $durationUnit = $this->durationUnitLabel(
                    $b->property?->price_period ?? 'nuit',
                    $b->nights
                );

                fputcsv($handle, [
                    $b->reference,
                    $b->user->name    ?? '—',
                    $b->user->phone   ?? '—',
                    $b->property->title ?? '—',
                    $b->property->city  ?? '—',
                    $b->check_in?->format('d/m/Y'),
                    $b->check_out?->format('d/m/Y'),
                    $b->nights,
                    $durationUnit,
                    $b->guests,
                    number_format($b->base_amount,         0, ',', ' '),
                    number_format($b->fees_amount,         0, ',', ' '),
                    number_format($b->total_amount,        0, ',', ' '),
                    number_format($b->commission_rate,     2, ',', ' ') . ' %', // ✅
                    number_format($b->commission_amount,   0, ',', ' '),        // ✅
                    number_format($b->owner_amount,        0, ',', ' '),        // ✅
                    $b->status,
                    $b->payment?->status ?? 'non_payé',
                    $b->created_at?->format('d/m/Y H:i'),
                ], ';');
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Retourne le libellé de durée correct selon le price_period.
     * Ex : price_period='heure', nights=3 → '3 heures'
     *      price_period='nuit',  nights=1 → '1 nuit'
     */
    private function durationUnitLabel(string $pricePeriod, int $duration): string
    {
        $singular = match ($pricePeriod) {
            'heure'   => 'heure',
            'jour'    => 'jour',
            'semaine' => 'semaine',
            'mois'    => 'mois',
            'an'      => 'an',
            default   => 'nuit',
        };

        $plural = match ($pricePeriod) {
            'heure'   => 'heures',
            'jour'    => 'jours',
            'semaine' => 'semaines',
            'mois'    => 'mois',
            'an'      => 'ans',
            default   => 'nuits',
        };

        return $duration <= 1 ? "{$duration} {$singular}" : "{$duration} {$plural}";
    }
}
