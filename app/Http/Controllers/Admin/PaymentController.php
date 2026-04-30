<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    // ── Valeurs enum exactes (avec accents, identiques à la migration) ────────
    const STATUS_SUCCES    = 'succès';
    const STATUS_ECHOUE    = 'échoué';
    const STATUS_REMBOURSE = 'remboursé';

    // ── Liste des paiements ───────────────────────────────────────────────────
    public function index(Request $request)
    {
        $payments = Payment::with(['user', 'booking.property'])
            ->when($request->method,    fn ($q, $v) => $q->where('method', $v))
            ->when($request->status,    fn ($q, $v) => $q->where('status', $v))
            // ✅ FIX Bug 1 : les filtres date_from / date_to étaient absents de index()
            //    alors qu'ils existent dans le formulaire Blade et dans exportCsv().
            ->when($request->date_from, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->date_to,   fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest()
            ->paginate(15);

        $stats = [
            'success_amount' => Payment::where('status', self::STATUS_SUCCES)->sum('amount'),
            'pending_count'  => Payment::whereIn('status', ['en_attente', 'en_attente_confirmation'])->count(),
            'failed_count'   => Payment::where('status', self::STATUS_ECHOUE)->count(),
            'refunded_count' => Payment::where('status', self::STATUS_REMBOURSE)->count(),
        ];

        return view('admin.payments.index', compact('payments', 'stats'));
    }

    // ── ✅ VALIDER PAIEMENT ───────────────────────────────────────────────────
    public function validatePayment($id)
    {
        $payment = Payment::findOrFail($id);

        if ($payment->status === self::STATUS_SUCCES) {
            return back()->with('info', 'Ce paiement est déjà validé.');
        }

        $payment->update([
            'status'      => self::STATUS_SUCCES,
            'paid_at'     => now(),
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        $booking = $payment->booking;
        if ($booking) {
            $booking->update(['status' => 'confirmé']);
        }

        Notification::create([
            'user_id' => $payment->user_id,
            'title'   => 'Paiement validé ✅',
            'body'    => "Votre paiement de {$payment->formatted_amount} a été confirmé. Votre réservation est active.",
            'type'    => 'payment',
            'data'    => ['payment_id' => $payment->id, 'booking_id' => $payment->booking_id],
        ]);

        return back()->with('success', 'Paiement validé avec succès.');
    }

    // ── ❌ REFUSER PAIEMENT ───────────────────────────────────────────────────
    public function rejectPayment(Request $request, $id)
    {
        $request->validate(['reason' => 'nullable|string|max:255']);

        $payment = Payment::findOrFail($id);

        if ($payment->status === self::STATUS_ECHOUE) {
            return back()->with('info', 'Ce paiement est déjà refusé.');
        }

        $payment->update([
            'status'        => self::STATUS_ECHOUE,
            'refund_reason' => $request->reason,
            'admin_note'    => $request->reason,
        ]);

        $payment->booking?->update(['status' => 'en_attente']);

        Notification::create([
            'user_id' => $payment->user_id,
            'title'   => 'Paiement refusé ❌',
            'body'    => "Votre paiement a été refusé."
                         . ($request->reason ? " Motif : {$request->reason}" : ''),
            'type'    => 'payment',
            'data'    => ['payment_id' => $payment->id, 'booking_id' => $payment->booking_id],
        ]);

        return back()->with('error', 'Paiement refusé.');
    }

    // ── 💸 REMBOURSEMENT ──────────────────────────────────────────────────────
    public function refund(Request $request, string $ref)
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        $payment = Payment::where('reference', $ref)->firstOrFail();

        if ($payment->status !== self::STATUS_SUCCES) {
            return back()->with('error', 'Seuls les paiements validés peuvent être remboursés.');
        }

        $payment->update([
            'status'        => self::STATUS_REMBOURSE,
            'refund_reason' => $request->reason,
            'refunded_at'   => now(),
        ]);

        $payment->booking?->update(['status' => 'annulé']);

        Notification::create([
            'user_id' => $payment->user_id,
            'title'   => 'Remboursement effectué 💸',
            'body'    => "Votre paiement de {$payment->formatted_amount} a été remboursé.",
            'type'    => 'payment',
        ]);

        return back()->with('success', 'Remboursement effectué.');
    }

    // ── 📄 EXPORT CSV ─────────────────────────────────────────────────────────
    public function exportCsv(Request $request): StreamedResponse
    {
        $payments = Payment::with(['user', 'booking.property'])
            ->when($request->method,    fn ($q, $v) => $q->where('method', $v))
            ->when($request->status,    fn ($q, $v) => $q->where('status', $v))
            ->when($request->date_from, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->date_to,   fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest()
            ->get();

        $filename = 'paiements_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($payments) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // BOM UTF-8 pour Excel

            fputcsv($handle, [
                'Référence paiement',
                'Référence réservation',
                'Client',
                'Téléphone client',
                'Propriété',
                'Méthode',
                'Tél. utilisé',
                'ID transaction',
                'Montant (XAF)',
                'Statut',
                'Date soumission',
                'Date validation',
            ], ';');

            foreach ($payments as $p) {
                fputcsv($handle, [
                    $p->reference ?? "PAY-{$p->id}",
                    $p->booking->reference ?? "BK-{$p->booking_id}",
                    $p->user->name ?? '—',
                    $p->user->phone ?? '—',
                    $p->booking->property?->title ?? '—',
                    $p->method_label ?? $p->method,
                    $p->phone ?? '—',
                    $p->provider_ref ?? '—',
                    number_format($p->amount, 0, ',', ' '),
                    $p->status_label ?? $p->status,
                    $p->created_at?->format('d/m/Y H:i'),
                    $p->verified_at?->format('d/m/Y H:i') ?? '—',
                ], ';');
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── 🖨️ REÇU DÉFINITIF ────────────────────────────────────────────────────
    public function receipt($id)
    {
        $payment = Payment::with(['user', 'booking.property'])->findOrFail($id);

        if ($payment->status !== self::STATUS_SUCCES) {
            return back()->with('error', 'Seuls les paiements validés ont un reçu définitif.');
        }

        return view('admin.payments.receipt', compact('payment'));
    }
}
