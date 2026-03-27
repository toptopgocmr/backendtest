<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\Transaction;

/**
 * FIX #12 — Alignement avec les vrais noms de colonnes
 * - Property: is_approved, status='disponible'
 * - Booking: status='en_attente'
 * - Payment: status='succès'
 */
class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_properties'  => Property::where('is_approved', true)->count(),
            'new_properties'    => Property::whereMonth('created_at', now()->month)->count(),
            'total_bookings'    => Booking::count(),
            'pending_bookings'  => Booking::where('status', 'en_attente')->count(),
            'total_revenue'     => Payment::where('status', 'succès')->sum('amount'),
            'monthly_revenue'   => Payment::where('status', 'succès')
                                        ->whereMonth('paid_at', now()->month)
                                        ->sum('amount'),
            'total_users'       => User::where('role', '!=', 'admin')->count(),
            'new_users'         => User::where('role', '!=', 'admin')
                                        ->whereMonth('created_at', now()->month)->count(),
        ];

        $recentBookings = Booking::with(['user', 'property', 'payment'])
            ->latest()
            ->take(6)
            ->get();

        $recentPayments = Payment::with(['user', 'booking.property'])
            ->latest()
            ->take(6)
            ->get();

        $topProperties = Property::withCount('bookings')
            ->where('is_approved', true)
            ->orderByDesc('bookings_count')
            ->take(5)
            ->get();

        $newUsers = User::where('role', '!=', 'admin')
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard.index', compact(
            'stats',
            'recentBookings',
            'recentPayments',
            'topProperties',
            'newUsers'
        ))
        // ✅ Compatibilité Blade sans casser l'existant
        ->with('recent_bookings', $recentBookings)
        ->with('recent_payments', $recentPayments)
        ->with('top_properties', $topProperties)
        ->with('new_users', $newUsers);
    }
}