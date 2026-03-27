<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;

class ReviewController extends Controller
{
    public function index()
    {
        $reviews = Review::with(['user','property'])->latest()->paginate(15);
        return view('admin.reviews.index', compact('reviews'));
    }

    public function destroy(string $id)
    {
        $review = Review::findOrFail($id);
        $property = $review->property;
        $review->delete();
        $property?->updateRating();
        return back()->with('success','Avis supprimé.');
    }

    public function toggle(string $id)
    {
        $review = Review::findOrFail($id);
        $review->update(['is_visible' => !$review->is_visible]);
        $review->property?->updateRating();
        return back()->with('success', $review->is_visible ? 'Avis affiché.' : 'Avis masqué.');
    }
}