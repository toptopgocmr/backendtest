<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        $query = Property::with('primaryImage')
            ->where('is_approved', true)
            ->where('status', 'disponible')
            ->when($request->type,     fn($q, $v) => $q->where('type', $v))
            ->when($request->city,     fn($q, $v) => $q->where('city', $v))
            ->when($request->guests,   fn($q, $v) => $q->where('max_guests', '>=', $v))
            ->when($request->min_price,fn($q, $v) => $q->where('price', '>=', $v))
            ->when($request->max_price,fn($q, $v) => $q->where('price', '<=', $v))
            ->when($request->featured, fn($q)     => $q->where('is_featured', true));

        $properties = $query->orderByDesc('is_featured')
                            ->orderByDesc('created_at')
                            ->paginate($request->per_page ?? 12);

        return response()->json([
            'success' => true,
            'data'    => $properties->map(fn($p) => $this->listResource($p)),
            'meta'    => [
                'total'     => $properties->total(),
                'last_page' => $properties->lastPage(),
            ],
        ]);
    }

    public function featured(Request $request)
    {
        $properties = Property::with('primaryImage')
            ->where('is_approved', true)
            ->where('status', 'disponible')
            ->where('is_featured', true)
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $properties->map(fn($p) => $this->listResource($p)),
        ]);
    }

    public function show(Request $request, string $id)
    {
        $p = Property::with([
            'images', 'amenities', 'pricingGrids',
            'owner', 'primaryImage', 'reviews.user',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $this->detailResource($p),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'type'         => 'required|string',
            'description'  => 'required|string',
            'price'        => 'required|numeric|min:0',
            'price_period' => 'required|string',
            'city'         => 'required|string',
            'district'     => 'nullable|string',
            'address'      => 'required|string',
            'max_guests'   => 'required|integer|min:1',
            'capacity'     => 'nullable|integer|min:1',
            'currency'     => 'nullable|string',
            'bedrooms'     => 'nullable|integer',
            'bathrooms'    => 'nullable|integer',
            'area'         => 'nullable|string',
        ]);

        $p = Property::create(array_merge(
            $request->only([
                'title', 'type', 'description', 'price', 'price_period',
                'city', 'district', 'address', 'max_guests', 'capacity',
                'currency', 'bedrooms', 'bathrooms', 'area',
            ]),
            [
                'owner_id'    => $request->user()->id,
                'status'      => 'disponible',
                'is_approved' => false,
            ]
        ));

        return response()->json([
            'success' => true,
            'data'    => $this->detailResource($p->fresh()),
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $p = Property::findOrFail($id);

        $p->update($request->only([
            'title', 'type', 'description', 'price', 'price_period',
            'city', 'district', 'address', 'max_guests', 'capacity',
            'currency', 'bedrooms', 'bathrooms', 'area',
            'contact_phone', 'contact_email', 'deposit',
        ]));

        return response()->json([
            'success' => true,
            'data'    => $this->detailResource($p->fresh([
                'images','amenities','pricingGrids','owner','primaryImage','reviews.user',
            ])),
        ]);
    }

    public function destroy(string $id)
    {
        Property::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function uploadImages(Request $request, string $id)
    {
        $request->validate(['images' => 'required|array', 'images.*' => 'image|max:5120']);
        $p = Property::findOrFail($id);

        foreach ($request->file('images') as $file) {
            $url = $file->store("properties/{$id}", 'public');
            $p->images()->create(['url' => asset("storage/{$url}"), 'is_primary' => false]);
        }

        if (!$p->primaryImage) {
            $p->images()->first()?->update(['is_primary' => true]);
        }

        return response()->json(['success' => true]);
    }

    // ── Resources ─────────────────────────────────────────────────────────────

    private function listResource(Property $p): array
    {
        if (!$p->relationLoaded('primaryImage')) {
            $p->load('primaryImage');
        }

        $imageUrl = $p->primaryImage?->url ?? null;

        return [
            'id'              => $p->id,
            'title'           => $p->title,
            'type'            => $p->type,
            'city'            => $p->city,
            'district'        => $p->district,
            'address'         => $p->address,
            'price'           => (float) $p->price,
            'price_period'    => $p->price_period,
            'currency'        => $p->currency,
            'formatted_price' => $p->formatted_price,
            'bedrooms'        => $p->bedrooms,
            'bathrooms'       => $p->bathrooms,
            'area'            => $p->area,
            'max_guests'      => $p->max_guests,
            // ── AJOUT : capacity (nombre de personnes réel du bien) ──────────
            'capacity'        => $p->capacity ?? $p->max_guests,
            'rating'          => (float) $p->rating,
            'reviews_count'   => $p->reviews_count,
            'is_featured'     => (bool) $p->is_featured,
            'status'          => $p->status,
            'image_url'       => $imageUrl,
            'cover_image'     => $imageUrl,
        ];
    }

    private function detailResource(Property $p): array
    {
        if (!$p->relationLoaded('pricingGrids')) {
            $p->load('pricingGrids');
        }

        return array_merge($this->listResource($p), [
            'description'    => $p->description,
            'latitude'       => $p->latitude,
            'longitude'      => $p->longitude,
            'deposit'        => $p->deposit,
            'contact_phone'  => $p->contact_phone,
            'duration_hours' => $p->duration_hours,
            // ── AJOUT : capacity explicitement dans le détail ────────────────
            'capacity'       => $p->capacity ?? $p->max_guests,
            'workstations'   => $p->workstations,
            'images'         => $p->images->pluck('url'),

            'pricing_grids' => $p->pricingGrids->map(fn($g) => [
                'id'           => $g->id,
                'period'       => $g->period,
                'period_label' => $g->period_label,
                'price'        => $g->price,
                'min_duration' => $g->min_duration,
                'formatted'    => $g->formatted_price,
            ]),

            'amenities' => $p->amenities->map(fn($a) => [
                'name' => $a->name,
                'icon' => $a->icon,
            ]),
            'owner' => $p->owner ? [
                'id'         => $p->owner->id,
                'name'       => $p->owner->name,
                'avatar_url' => $p->owner->avatar_url,
            ] : null,
            'reviews' => $p->reviews->map(fn($r) => [
                'id'      => $r->id,
                'rating'  => $r->rating,
                'comment' => $r->comment,
                'user'    => [
                    'name'       => $r->user?->name,
                    'avatar_url' => $r->user?->avatar_url,
                ],
                'date' => $r->created_at->diffForHumans(),
            ]),
        ]);
    }
}
