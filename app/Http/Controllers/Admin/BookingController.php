<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $bookings = Booking::with(['user', 'property', 'payment'])
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->latest()->paginate(15);

        return view('admin.bookings.index', compact('bookings'));
    }

    public function show(string $ref)
    {
        $booking = Booking::with(['user', 'property.images', 'payment', 'review'])
            ->where('reference', $ref)->firstOrFail();

        return view('admin.bookings.show', compact('booking'));
    }

    public function confirm(string $ref)
    {
        Booking::where('reference', $ref)->firstOrFail()
            ->update(['status' => 'confirmé']);  // FIX: valeur enum FR

        return back()->with('success', 'Réservation confirmée.');
    }

    public function complete(string $ref)
    {
        Booking::where('reference', $ref)->firstOrFail()
            ->update(['status' => 'terminé']);   // FIX: valeur enum FR

        return back()->with('success', 'Réservation marquée comme terminée.');
    }
}
