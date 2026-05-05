<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\PropertyAmenity;
use App\Models\PropertyPricingGrid;
use App\Models\User;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PropertyController extends Controller
{
    /**
     * Upload une image vers Cloudinary ou en local (fallback).
     * Retourne toujours une URL absolue.
     */
    private function uploadImage($file, int $propertyId): string
    {
        $cloudName = config('services.cloudinary.cloud_name');

        if ($cloudName) {
            try {
                $cloudinary = new CloudinaryService();
                return $cloudinary->upload($file, "immostay/properties/{$propertyId}");
            } catch (\Throwable $e) {
                Log::error('Cloudinary upload failed: ' . $e->getMessage());
            }
        }

        $path = $file->store('properties/' . $propertyId, 'public');
        return url(Storage::url($path));
    }

    /**
     * Sauvegarde la grille tarifaire multi-périodes.
     * Appelé depuis store() et update().
     * Format attendu dans le formulaire :
     *   pricing[heure][price] = 5000
     *   pricing[heure][min_duration] = 1
     *   pricing[jour][price] = 25000
     *   ... etc.
     */
    private function savePricingGrids(Request $request, Property $property): void
    {
        $periods = ['heure', 'jour', 'nuit', 'semaine', 'mois', 'an'];

        foreach ($periods as $period) {
            $data = $request->input("pricing.{$period}");

            // Si pas de données ou prix vide/nul → désactiver ce tarif
            if (empty($data['price']) || (int) $data['price'] <= 0) {
                PropertyPricingGrid::where('property_id', $property->id)
                    ->where('period', $period)
                    ->update(['is_active' => false]);
                continue;
            }

            PropertyPricingGrid::updateOrCreate(
                [
                    'property_id' => $property->id,
                    'period'      => $period,
                ],
                [
                    'price'        => (int) $data['price'],
                    'min_duration' => max(1, (int) ($data['min_duration'] ?? 1)),
                    'is_active'    => true,
                ]
            );
        }
    }

    // ── CRUD ──────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $properties = Property::with(['owner', 'primaryImage'])
            ->when($request->search, fn($q, $v) => $q->where('title', 'like', "%$v%")
                ->orWhere('city', 'like', "%$v%"))
            ->when($request->type,   fn($q, $v) => $q->where('type', $v))
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->approved !== null && $request->approved !== '',
                fn($q) => $q->where('is_approved', (int) $request->approved))
            ->latest()->paginate(15);

        return view('admin.properties.index', compact('properties'));
    }

    public function create()
    {
        $owners = User::where('role', 'owner')
            ->with('ownerProfile')
            ->orderBy('name')
            ->get();
        return view('admin.properties.create', compact('owners'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'          => 'required|string|max:200',
            'description'    => 'required|string',
            'owner_id'       => 'required|exists:users,id',
            'type'           => 'required|string',
            'price'          => 'required|numeric|min:0',
            'price_period'   => 'required|string',
            'city'           => 'required|string',
            'country'        => 'required|string',
            'images'         => 'nullable|array|max:20',
            'images.*'       => 'nullable|image|max:5120',
            'duration_hours' => 'nullable|required_if:price_period,heure|integer|min:1|max:24',
            // Grille tarifaire (optionnelle)
            'pricing.*.price'        => 'nullable|integer|min:0',
            'pricing.*.min_duration' => 'nullable|integer|min:1|max:365',
        ]);

        $property = Property::create([
            'owner_id'       => $request->owner_id,
            'title'          => $request->title,
            'description'    => $request->description,
            'type'           => $request->type,
            'price'          => $request->price,
            'price_period'   => $request->price_period,
            'currency'       => $request->currency ?? 'XAF',
            'address'        => $request->address,
            'city'           => $request->city,
            'district'       => $request->district,
            'country'        => $request->country,
            'latitude'       => $request->latitude,
            'longitude'      => $request->longitude,
            'bedrooms'       => $request->bedrooms ?? 1,
            'bathrooms'      => $request->bathrooms ?? 1,
            'area'           => $request->area ?? $request->area_terrain,
            'max_guests'     => $request->max_guests ?? 2,
            'status'         => $request->status ?? 'disponible',
            'is_featured'    => $request->has('is_featured'),
            'is_approved'    => (bool) ($request->is_approved ?? false),
            'deposit'        => $request->deposit ?? 0,
            'contact_phone'  => $this->buildPhone(
                $request->get('phone-indicatif-prop', '+242'),
                $request->contact_phone
            ),
            'contact_email'  => $request->contact_email,
            'view_type'      => $request->view_type,
            'rules'          => $request->rules,
            'capacity'       => $request->capacity,
            'floor'          => $request->floor ?? $request->floor_bureau,
            'workstations'   => $request->workstations,
            'terrain_type'   => $request->terrain_type,
            'land_title'     => $request->land_title,
            'duration_hours' => $request->price_period === 'heure' ? $request->duration_hours : null,
        ]);

        // ── Grille tarifaire ─────────────────────────────────────────────────
        $this->savePricingGrids($request, $property);

        // ── Upload images ────────────────────────────────────────────────────
        if ($request->hasFile('images')) {
            $sort = 0;
            foreach ($request->file('images') as $file) {
                if (!$file->isValid()) continue;
                try {
                    $url = $this->uploadImage($file, $property->id);
                    PropertyImage::create([
                        'property_id' => $property->id,
                        'url'         => $url,
                        'is_primary'  => $sort === 0,
                        'sort_order'  => $sort++,
                    ]);
                } catch (\Throwable $e) {
                    Log::error("Image upload failed for property {$property->id}: " . $e->getMessage());
                }
            }
        }

        // ── Équipements ──────────────────────────────────────────────────────
        $this->saveAmenities($request, $property);

        return redirect()->route('admin.properties.index')
            ->with('success', 'Propriété enregistrée avec succès.');
    }

    public function show(string $id)
    {
        $property = Property::with([
            'owner', 'images', 'amenities', 'pricingGrids',
            'bookings.user', 'reviews.user',
        ])->findOrFail($id);

        return view('admin.properties.show', compact('property'));
    }

    public function edit(string $id)
    {
        $property = Property::with(['images', 'amenities', 'pricingGrids'])->findOrFail($id);
        $owners   = User::where('role', 'owner')->with('ownerProfile')->orderBy('name')->get();

        // Transforme la collection en tableau indexé par period pour le Blade
        // ex: $pricingByPeriod['heure'] = ['price' => 5000, 'min_duration' => 1]
        $pricingByPeriod = $property->pricingGrids->keyBy('period')->map(fn($g) => [
            'price'        => $g->price,
            'min_duration' => $g->min_duration,
        ])->toArray();

        return view('admin.properties.edit', compact('property', 'owners', 'pricingByPeriod'));
    }

    public function update(Request $request, string $id)
    {
        $property = Property::findOrFail($id);

        $request->validate([
            'title'          => 'required|string|max:200',
            'description'    => 'required|string',
            'owner_id'       => 'required|exists:users,id',
            'type'           => 'required|string',
            'price'          => 'required|numeric|min:0',
            'price_period'   => 'required|string',
            'city'           => 'required|string',
            'country'        => 'required|string',
            'images.*'       => 'nullable|image|max:5120',
            'duration_hours' => 'nullable|required_if:price_period,heure|integer|min:1|max:24',
            'pricing.*.price'        => 'nullable|integer|min:0',
            'pricing.*.min_duration' => 'nullable|integer|min:1|max:365',
        ]);

        $property->update([
            ...$request->only([
                'owner_id', 'title', 'description', 'type', 'price', 'price_period', 'currency',
                'address', 'city', 'district', 'country', 'latitude', 'longitude',
                'bedrooms', 'bathrooms', 'max_guests', 'status', 'is_approved',
                'deposit', 'contact_phone', 'contact_email', 'view_type', 'rules',
                'capacity', 'floor', 'workstations', 'terrain_type', 'land_title',
            ]),
            'is_featured'    => $request->has('is_featured'),
            'duration_hours' => $request->price_period === 'heure' ? $request->duration_hours : null,
            'area'           => $request->area ?? $request->area_terrain,
        ]);

        // ── Grille tarifaire ─────────────────────────────────────────────────
        $this->savePricingGrids($request, $property);

        // ── Nouvelles images ─────────────────────────────────────────────────
        if ($request->hasFile('images')) {
            $sort = $property->images()->max('sort_order') + 1;
            foreach ($request->file('images') as $file) {
                if (!$file->isValid()) continue;
                try {
                    $url = $this->uploadImage($file, $property->id);
                    PropertyImage::create([
                        'property_id' => $property->id,
                        'url'         => $url,
                        'is_primary'  => $sort === 1 && $property->images()->count() === 0,
                        'sort_order'  => $sort++,
                    ]);
                } catch (\Throwable $e) {
                    Log::error("Image update failed for property {$property->id}: " . $e->getMessage());
                }
            }
        }

        return redirect()->route('admin.properties.show', $property->id)
            ->with('success', 'Propriété mise à jour.');
    }

    public function approve(string $id)
    {
        Property::findOrFail($id)->update([
            'is_approved' => true,
            'status'      => 'disponible',
        ]);
        return back()->with('success', 'Propriété approuvée avec succès.');
    }

    public function destroy(string $id)
    {
        $property = Property::with('images')->findOrFail($id);

        foreach ($property->images as $image) {
            try {
                $url = $image->url;
                if (str_contains($url, 'res.cloudinary.com')) {
                    $cloudinary = new CloudinaryService();
                    $cloudinary->delete($url);
                } elseif (str_contains($url, '/storage/')) {
                    $path = preg_replace('#^.*/storage/#', '', $url);
                    Storage::disk('public')->delete($path);
                }
            } catch (\Throwable $e) {
                Log::warning("Impossible de supprimer l'image {$image->id}: " . $e->getMessage());
            }
            $image->delete();
        }

        $property->delete();

        return back()->with('success', 'Propriété supprimée définitivement.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function buildPhone(?string $indicatif, ?string $number): ?string
    {
        if (empty($number)) return null;
        $number = preg_replace('/\s+/', '', $number);
        if (str_starts_with($number, '+')) return $number;
        return $indicatif . $number;
    }

    private function saveAmenities(Request $request, Property $property): void
    {
        $amenityMap = [
            'has_wifi'         => 'WiFi',
            'has_electricity'  => 'Électricité',
            'has_water'        => 'Eau courante',
            'has_generator'    => 'Groupe électrogène',
            'has_security'     => 'Gardiennage',
            'has_parking'      => 'Parking',
            'has_clim'         => 'Climatisation',
            'has_heating'      => 'Chauffage',
            'has_pool'         => 'Piscine',
            'has_garden'       => 'Jardin',
            'has_elevator'     => 'Ascenseur',
            'has_balcony'      => 'Balcon',
            'has_kitchen'      => 'Cuisine équipée',
            'has_laundry'      => 'Lave-linge',
            'has_tv'           => 'Télévision',
            'has_gym'          => 'Salle de sport',
            'has_projector'    => 'Vidéoprojecteur',
            'has_visio'        => 'Visioconférence',
            'has_whiteboard'   => 'Tableau blanc',
            'has_reception'    => "Salle d'accueil",
            'has_kitchen_pro'  => 'Cuisine pro',
            'has_printing'     => 'Imprimante',
            'has_sound_system' => 'Sono',
            'has_lighting'     => 'Éclairage déco',
            'has_stage'        => 'Scène',
            'has_dancefloor'   => 'Piste de danse',
            'has_catering'     => 'Traiteur',
            'has_photo_service'=> 'Photo/Vidéo',
        ];

        foreach ($amenityMap as $field => $label) {
            if ($request->has($field)) {
                PropertyAmenity::firstOrCreate(
                    ['property_id' => $property->id, 'name' => $label],
                    ['icon' => 'check-circle']
                );
            }
        }

        if ($request->custom_amenities) {
            foreach (array_filter((array) $request->custom_amenities) as $name) {
                PropertyAmenity::firstOrCreate(
                    ['property_id' => $property->id, 'name' => $name],
                    ['icon' => 'star']
                );
            }
        }
    }
}
