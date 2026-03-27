<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        $properties = Property::with(['owner', 'primaryImage'])  // FIX #10: primaryImage() pas primaryimage()
            ->when($request->search, fn($q, $v) => $q->where('title', 'like', "%$v%")
                ->orWhere('city', 'like', "%$v%"))
            ->when($request->type,   fn($q, $v) => $q->where('type', $v))
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->approved !== null && $request->approved !== '',
                fn($q) => $q->where('is_approved', (int) $request->approved))
            ->latest()->paginate(15);

        return view('admin.properties.index', compact('properties'));
    }

    public function show(string $id)
    {
        $property = Property::with(['owner', 'images', 'amenities', 'bookings.user', 'reviews.user'])
            ->findOrFail($id);

        return view('admin.properties.show', compact('property'));
    }

    public function approve(string $id)
    {
        Property::findOrFail($id)->update([
            'is_approved' => true,
            'status'      => 'disponible',  // FIX: enum correct
        ]);

        return back()->with('success', 'Propriété approuvée avec succès.');
    }

    public function destroy(string $id)
    {
        Property::findOrFail($id)->update([
            'status'      => 'suspendu',
            'is_approved' => false,
        ]);

        return back()->with('success', 'Propriété suspendue.');
    }
}
