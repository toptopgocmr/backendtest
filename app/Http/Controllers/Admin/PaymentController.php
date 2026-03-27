<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $payments = Payment::with(['user', 'booking.property'])
            ->when($request->method, fn($q, $v) => $q->where('method', $v))
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->latest()->paginate(15);

        $stats = [
            'success_amount' => Payment::where('status', 'succès')->sum('amount'),     // FIX: 'succès' pas 'success'
            'pending_count'  => Payment::where('status', 'en_attente')->count(),       // FIX
            'failed_count'   => Payment::where('status', 'échoué')->count(),           // FIX
            'refunded_count' => Payment::where('status', 'remboursé')->count(),        // FIX
        ];

        return view('admin.payments.index', compact('payments', 'stats'));
    }

    public function refund(Request $request, string $ref)
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        $payment = Payment::where('reference', $ref)->firstOrFail();

        if (!$payment->isSuccess()) {
            return back()->with('error', 'Seuls les paiements réussis peuvent être remboursés.');
        }

        $payment->update([
            'status'        => 'remboursé',   // FIX: valeur enum FR
            'refund_reason' => $request->reason,
            'refunded_at'   => now(),
        ]);

        $payment->booking->update(['status' => 'annulé']); // FIX: valeur enum FR

        return back()->with('success', 'Remboursement effectué.');
    }
}
