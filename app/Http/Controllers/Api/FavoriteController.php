<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Property;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $favorites = Favorite::with(['property.primaryImage'])  // FIX: primaryImage
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($f) => $this->propertyResource($f->property));

        return response()->json(['success' => true, 'data' => $favorites]);
    }

    public function toggle(Request $request, string $id)
    {
        Property::findOrFail($id);

        $existing = Favorite::where('user_id', $request->user()->id)
            ->where('property_id', $id)->first();

        if ($existing) {
            $existing->delete();
            $isFav = false;
        } else {
            Favorite::create(['user_id' => $request->user()->id, 'property_id' => $id]);
            $isFav = true;
        }

        return response()->json([
            'success'     => true,
            'is_favorite' => $isFav,
            'message'     => $isFav ? 'Ajouté aux favoris.' : 'Retiré des favoris.',
        ]);
    }

    private function propertyResource(Property $p): array
    {
        return [
            'id'            => $p->id,
            'title'         => $p->title,
            'type'          => $p->type,
            'city'          => $p->city,
            'district'      => $p->district,
            'price'         => (float) $p->price,       // FIX: price
            'price_period'  => $p->price_period,
            'currency'      => $p->currency,
            'formatted_price' => $p->formatted_price,
            'bedrooms'      => $p->bedrooms,
            'bathrooms'     => $p->bathrooms,
            'area'          => $p->area,                // FIX: area
            'rating'        => (float) $p->rating,      // FIX: rating
            'reviews_count' => $p->reviews_count,
            'is_featured'   => (bool) $p->is_featured,
            'status'        => $p->status,
            'image_url'     => $p->primaryImage?->url,  // FIX: primaryImage
            'is_favorite'   => true,
        ];
    }
}
