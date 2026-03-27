<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Booking;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function propertyReviews(string $id)
    {
        $reviews = Review::with('user')
            ->where('property_id', $id)
            ->where('is_visible', true)
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data'    => $reviews->map(fn($r) => [
                'id'      => $r->id,
                'rating'  => $r->rating,
                'comment' => $r->comment,
                'user'    => ['name' => $r->user?->name, 'avatar_url' => $r->user?->avatar_url],
                'date'    => $r->created_at->diffForHumans(),
            ]),
            'meta' => ['total' => $reviews->total()],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'rating'     => 'required|integer|min:1|max:5',
            'comment'    => 'nullable|string|max:1000',
        ]);

        $booking = Booking::where('id', $request->booking_id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'terminé')  // FIX: enum FR
            ->firstOrFail();

        if ($booking->review) {
            return response()->json(['success' => false, 'message' => 'Avis déjà soumis.'], 409);
        }

        $review = Review::create([
            'booking_id'  => $booking->id,
            'user_id'     => $request->user()->id,
            'property_id' => $booking->property_id,
            'rating'      => $request->rating,
            'comment'     => $request->comment,
            'is_visible'  => true,
        ]);

        $booking->property->updateRating();

        return response()->json(['success' => true, 'data' => $review, 'message' => 'Avis publié.'], 201);
    }

    public function update(Request $request, string $id)
    {
        $review = Review::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $request->validate(['rating' => 'integer|min:1|max:5', 'comment' => 'nullable|string|max:1000']);
        $review->update($request->only(['rating', 'comment']));
        $review->property->updateRating();

        return response()->json(['success' => true, 'data' => $review]);
    }
}
