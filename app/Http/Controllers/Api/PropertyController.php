<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Http\Request;
use App\Services\CloudinaryService;
use Illuminate\Support\Facades\Log;

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        $query = Property::with(['primaryImage'])
            ->where('is_approved', true)
            ->where('status', 'disponible')
            ->when($request->type,      fn($q, $v) => $q->where('type', $v))
            ->when($request->city,      fn($q, $v) => $q->where('city', 'like', "%$v%"))
            ->when($request->min_price, fn($q, $v) => $q->where('price', '>=', $v))
            ->when($request->max_price, fn($q, $v) => $q->where('price', '<=', $v))
            ->when($request->bedrooms,  fn($q, $v) => $q->where('bedrooms', '>=', $v))
            ->when($request->guests,    fn($q, $v) => $q->where('max_guests', '>=', $v))
            ->when($request->search,    fn($q, $v) => $q->where(function ($q2) use ($v) {
                $q2->where('title', 'like', "%$v%")
                   ->orWhere('address', 'like', "%$v%")
                   ->orWhere('city', 'like', "%$v%");
            }))
            ->when($request->sort === 'price_asc',  fn($q) => $q->orderBy('price'))
            ->when($request->sort === 'price_desc', fn($q) => $q->orderByDesc('price'))
            ->when($request->sort === 'rating',     fn($q) => $q->orderByDesc('rating'))
            ->when(!$request->sort, fn($q) => $q->orderByDesc('is_featured')->orderByDesc('created_at'));

        $properties = $query->paginate($request->per_page ?? 12);

        return response()->json([
            'success' => true,
            'data'    => $properties->map(fn($p) => $this->listResource($p)),
            'meta'    => [
                'current_page' => $properties->currentPage(),
                'last_page'    => $properties->lastPage(),
                'total'        => $properties->total(),
            ],
        ]);
    }

    public function featured()
    {
        $properties = Property::with(['primaryImage'])
            ->where('is_approved', true)
            ->where('status', 'disponible')
            ->where('is_featured', true)
            ->orderByDesc('rating')
            ->take(8)->get();

        return response()->json([
            'success' => true,
            'data'    => $properties->map(fn($p) => $this->listResource($p)),
        ]);
    }

    public function show(string $id)
    {
        $property = Property::with([
            'images',
            'owner',
            'amenities',
            'pricingGrids',
            'reviews' => fn($q) => $q->where('is_visible', true)->with('user')->latest()->take(10),
        ])->findOrFail($id);

        $property->increment('views_count');

        return response()->json([
            'success' => true,
            'data'    => $this->detailResource($property),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'required|string',
            'type'         => 'required|in:appartement,villa,studio,maison,chambre,bureau,terrain',
            'address'      => 'required|string',
            'city'         => 'required|string',
            'price'        => 'required|numeric|min:0',
            'price_period' => 'required|in:heure,nuit,jour,semaine,mois,an',
            'bedrooms'     => 'required|integer|min:0',
            'bathrooms'    => 'required|integer|min:0',
            'max_guests'   => 'required|integer|min:1',
        ]);

        $property = Property::create(array_merge(
            $request->only([
                'title', 'description', 'type', 'address', 'city', 'country',
                'district', 'latitude', 'longitude', 'price', 'price_period',
                'currency', 'bedrooms', 'bathrooms', 'max_guests', 'area',
            ]),
            [
                'owner_id'    => $request->user()->id,
                'status'      => 'disponible',
                'is_approved' => false,
            ]
        ));

        return response()->json([
            'success' => true,
            'data'    => $property,
            'message' => 'Propriété soumise pour validation.',
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $property = Property::findOrFail($id);

        if ($property->owner_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        $property->update($request->only([
            'title', 'description', 'address', 'city', 'price',
            'bedrooms', 'bathrooms', 'max_guests',
        ]));

        return response()->json(['success' => true, 'data' => $property]);
    }

    public function destroy(Request $request, string $id)
    {
        $property = Property::findOrFail($id);

        if ($property->owner_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        $property->update(['status' => 'suspendu']);

        return response()->json(['success' => true, 'message' => 'Propriété suspendue.']);
    }

    public function uploadImages(Request $request, string $id)
    {
        $request->validate(['images' => 'required|array', 'images.*' => 'image|max:5120']);

        $property   = Property::findOrFail($id);
        $hasPrimary = $property->images()->where('is_primary', true)->exists();

        $cloudName     = config('services.cloudinary.cloud_name');
        $apiKey        = config('services.cloudinary.api_key');
        $apiSecret     = config('services.cloudinary.api_secret');
        $useCloudinary = $cloudName && $apiKey && $apiSecret;

        foreach ($request->file('images', []) as $i => $file) {
            $url = null;

            if ($useCloudinary) {
                try {
                    $cloudinary = new CloudinaryService();
                    $url = $cloudinary->upload($file, "immostay/properties/{$id}");
                    Log::info("Cloudinary upload OK [{$id}]: {$url}");
                } catch (\Throwable $e) {
                    Log::error("Cloudinary upload FAILED [{$id}]: " . $e->getMessage());
                    $url = null;
                }
            }

            if (!$url) {
                $path = $file->store("properties/{$id}", 'public');
                $url  = url(\Storage::url($path));
            }

            PropertyImage::create([
                'property_id' => $property->id,
                'url'         => $url,
                'is_primary'  => !$hasPrimary && $i === 0,
                'sort_order'  => $property->images()->count() + $i,
            ]);

            if (!$hasPrimary && $i === 0) {
                $hasPrimary = true;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Images téléchargées.',
            'count'   => count($request->file('images', [])),
        ]);
    }

    // ── Ressources ────────────────────────────────────────────────────────────

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
        // Charge les relations manquantes si nécessaire
        if (!$p->relationLoaded('pricingGrids')) {
            $p->load('pricingGrids');
        }

        return array_merge($this->listResource($p), [
            'description'   => $p->description,
            'latitude'      => $p->latitude,
            'longitude'     => $p->longitude,
            'deposit'       => $p->deposit,
            'contact_phone' => $p->contact_phone,
            'duration_hours'=> $p->duration_hours,
            'images'        => $p->images->pluck('url'),

            // ── Grille tarifaire multi-périodes ──────────────────
            // Si vide → le client utilise price/price_period du tarif de base
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
