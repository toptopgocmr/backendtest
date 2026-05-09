<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyAmenity;
use App\Models\PropertyImage;
use App\Models\PropertyPricingGrid;
use App\Models\User;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PropertyController extends Controller
{
    // ── Liste des équipements connus (checkboxes) ─────────────────────────────
    private const AMENITY_MAP = [
        // Essentiels
        'has_wifi'          => ['WiFi / Internet',       'wifi'],
        'has_electricity'   => ['Électricité',            'bolt'],
        'has_water'         => ['Eau courante',           'tint'],
        'has_generator'     => ['Groupe électrogène',     'charging-station'],
        'has_security'      => ['Gardiennage / Sécurité', 'shield-alt'],
        'has_parking'       => ['Parking',                'parking'],
        // Confort
        'has_clim'          => ['Climatisation',          'wind'],
        'has_heating'       => ['Chauffage',               'fire'],
        'has_pool'          => ['Piscine',                 'swimming-pool'],
        'has_garden'        => ['Jardin / Terrasse',       'leaf'],
        'has_elevator'      => ['Ascenseur',               'chevron-circle-up'],
        'has_balcony'       => ['Balcon / Véranda',        'archway'],
        'has_kitchen'       => ['Cuisine équipée',         'utensils'],
        'has_laundry'       => ['Lave-linge',              'tshirt'],
        'has_tv'            => ['Télévision',              'tv'],
        'has_gym'           => ['Salle de sport',          'dumbbell'],
        // Professionnels
        'has_projector'     => ['Vidéoprojecteur',         'chalkboard-teacher'],
        'has_visio'         => ['Visioconférence',         'video'],
        'has_whiteboard'    => ['Tableau blanc',           'edit'],
        'has_reception'     => ["Salle d'accueil",         'concierge-bell'],
        'has_kitchen_pro'   => ['Cuisine / Cafétéria',     'coffee'],
        'has_printing'      => ['Imprimante / Copie',      'print'],
        // Événementiel
        'has_sound_system'  => ['Sono / Musique',          'music'],
        'has_lighting'      => ['Éclairage déco',          'lightbulb'],
        'has_stage'         => ['Scène / Podium',          'theater-masks'],
        'has_dancefloor'    => ['Piste de danse',          'compact-disc'],
        'has_catering'      => ['Service traiteur',        'utensils'],
        'has_photo_service' => ['Photo / Vidéo',           'camera'],
    ];

    // ─────────────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = Property::with(['primaryImage', 'owner'])
            ->when($request->search, fn($q, $v) => $q->where(function ($q2) use ($v) {
                $q2->where('title', 'like', "%$v%")
                   ->orWhere('city', 'like', "%$v%")
                   ->orWhere('address', 'like', "%$v%");
            }))
            ->when($request->type,     fn($q, $v) => $q->where('type', $v))
            ->when($request->status,   fn($q, $v) => $q->where('status', $v))
            ->when($request->approved !== null && $request->approved !== '',
                   fn($q) => $q->where('is_approved', (bool) $request->approved))
            ->orderByDesc('created_at');

        $properties = $query->paginate(20);

        return view('admin.properties.index', compact('properties'));
    }

    public function create()
    {
        // FIX : les propriétaires sont créés avec role='owner' dans OwnerController
        // 'proprietaire' était incorrect et rendait le dropdown vide
        $owners = User::where('role', 'owner')
                      ->orderBy('name')
                      ->get();

        return view('admin.properties.create', compact('owners'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type'         => 'required|in:appartement,villa,studio,maison,chambre,bureau,salle_reunion,salle_fete,terrain,entrepot,commerce',
            'title'        => 'required|string|max:255',
            'description'  => 'required|string',
            'owner_id'     => 'required|exists:users,id',
            'price'        => 'required|numeric|min:0',
            'price_period' => 'required|in:heure,nuit,jour,semaine,mois,an,total',
            'currency'     => 'nullable|in:XAF,USD,EUR,XOF',
            'address'      => 'nullable|string|max:255',
            'country'      => 'required|string|max:100',
            'city'         => 'required|string|max:100',
            'images'       => 'nullable|array',
            'images.*'     => 'image|max:5120',
            // Grille tarifaire
            'pricing'                      => 'nullable|array',
            'pricing.*.price'              => 'nullable|integer|min:0',
            'pricing.*.min_duration'       => 'nullable|integer|min:1|max:365',
        ]);

        // ── Champs selon le type ────────────────────────────────────────────
        $type          = $request->type;
        $habitTypes    = ['appartement', 'villa', 'studio', 'maison', 'chambre'];
        $bureauTypes   = ['bureau', 'salle_reunion', 'salle_fete'];
        $terrainTypes  = ['terrain', 'entrepot', 'commerce'];

        $propertyData = [
            'owner_id'      => $request->owner_id,
            'type'          => $type,
            'title'         => $request->title,
            'description'   => $request->description,
            'price'         => $request->price,
            'price_period'  => $request->price_period,
            'currency'      => $request->currency ?? 'XAF',
            'deposit'       => $request->deposit ?? 0,
            'address'       => $request->address,
            'country'       => $request->country,
            'city'          => $request->city,
            'district'      => $request->district,
            'latitude'      => $request->latitude,
            'longitude'     => $request->longitude,
            'contact_phone' => $request->contact_phone,
            'contact_email' => $request->contact_email,
            'status'        => $request->status ?? 'disponible',
            'is_approved'   => (bool) $request->is_approved,
            'is_featured'   => $request->boolean('is_featured'),
            'rules'         => $request->rules,
        ];

        // Durée minimale (tarif horaire)
        if ($request->price_period === 'heure' && $request->duration_hours) {
            $propertyData['duration_hours'] = (int) $request->duration_hours;
        }

        // Habitat
        if (in_array($type, $habitTypes)) {
            $propertyData['bedrooms']   = $request->bedrooms   ?? 0;
            $propertyData['bathrooms']  = $request->bathrooms  ?? 0;
            $propertyData['max_guests'] = $request->max_guests ?? 1;
            $propertyData['area']       = $request->area;
            $propertyData['floor']      = $request->floor !== '_' ? $request->floor : null;
            $propertyData['view_type']  = $request->view_type;
        }

        // Bureau / salle
        if (in_array($type, $bureauTypes)) {
            $propertyData['capacity']    = $request->capacity;
            $propertyData['area']        = $request->area;
            $propertyData['floor']       = $request->floor_bureau !== '_' ? $request->floor_bureau : null;
            $propertyData['workstations']= $request->workstations;
        }

        // Terrain / commerce
        if (in_array($type, $terrainTypes)) {
            $propertyData['area']         = $request->area_terrain;
            $propertyData['terrain_type'] = $request->terrain_type;
            $propertyData['land_title']   = $request->land_title;
        }

        // ── Création ───────────────────────────────────────────────────────
        $property = Property::create($propertyData);

        // ── Images ─────────────────────────────────────────────────────────
        if ($request->hasFile('images')) {
            $this->handleImageUploads($request, $property);
        }

        // ── Équipements ────────────────────────────────────────────────────
        $this->saveAmenities($request, $property);

        // ── Grille tarifaire ───────────────────────────────────────────────
        $this->savePricingGrids($request, $property);

        return redirect()
            ->route('admin.properties.show', $property->id)
            ->with('success', 'Propriété créée avec succès.');
    }

    public function show(string $id)
    {
        $property = Property::with([
            'images',
            'owner',
            'amenities',
            'pricingGrids',
            'bookings.user',
            'reviews' => fn($q) => $q->where('is_visible', true)->latest()->take(10),
        ])->findOrFail($id);

        return view('admin.properties.show', compact('property'));
    }

    public function approve(string $id)
    {
        Property::findOrFail($id)->update(['is_approved' => true]);

        return back()->with('success', 'Propriété approuvée.');
    }

    public function destroy(string $id)
    {
        Property::findOrFail($id)->update(['status' => 'suspendu']);

        return redirect()
            ->route('admin.properties.index')
            ->with('success', 'Propriété suspendue.');
    }

    // ── Helpers privés ────────────────────────────────────────────────────────

    /**
     * Enregistre les images en Cloudinary (si configuré) ou en local.
     */
    private function handleImageUploads(Request $request, Property $property): void
    {
        $cloudName     = config('services.cloudinary.cloud_name');
        $apiKey        = config('services.cloudinary.api_key');
        $apiSecret     = config('services.cloudinary.api_secret');
        $useCloudinary = $cloudName && $apiKey && $apiSecret;

        $hasPrimary = false;

        foreach ($request->file('images', []) as $i => $file) {
            $url = null;

            if ($useCloudinary) {
                try {
                    $cloudinary = new CloudinaryService();
                    $url = $cloudinary->upload($file, "tholadimmo/properties/{$property->id}");
                } catch (\Throwable $e) {
                    Log::error("Cloudinary upload failed [{$property->id}]: " . $e->getMessage());
                }
            }

            if (!$url) {
                $path = $file->store("properties/{$property->id}", 'public');
                $url  = url(Storage::url($path));
            }

            PropertyImage::create([
                'property_id' => $property->id,
                'url'         => $url,
                'is_primary'  => !$hasPrimary && $i === 0,
                'sort_order'  => $i,
            ]);

            if ($i === 0) $hasPrimary = true;
        }
    }

    /**
     * Synchronise les équipements (checkboxes + champs libres).
     */
    private function saveAmenities(Request $request, Property $property): void
    {
        // Supprime les anciens lors d'une mise à jour
        $property->amenities()->delete();

        $amenities = [];

        // Checkboxes connues
        foreach (self::AMENITY_MAP as $key => [$name, $icon]) {
            if ($request->boolean($key)) {
                $amenities[] = ['property_id' => $property->id, 'name' => $name, 'icon' => $icon];
            }
        }

        // Équipements personnalisés saisis librement
        foreach ($request->input('custom_amenities', []) as $custom) {
            $label = trim($custom);
            if ($label !== '') {
                $amenities[] = ['property_id' => $property->id, 'name' => $label, 'icon' => 'star'];
            }
        }

        if (!empty($amenities)) {
            PropertyAmenity::insert($amenities);
        }
    }

    /**
     * Enregistre ou met à jour la grille tarifaire multi-périodes.
     *
     * Les données proviennent du formulaire sous la forme :
     *   pricing[heure][price]        = 5000
     *   pricing[heure][min_duration] = 1
     *   pricing[jour][price]         = 25000
     *   ...
     *
     * Une période est ignorée si son prix est vide ou 0.
     */
    private function savePricingGrids(Request $request, Property $property): void
    {
        $grids = $request->input('pricing', []);

        if (empty($grids)) {
            return;
        }

        $validPeriods = ['heure', 'jour', 'nuit', 'semaine', 'mois', 'an'];

        foreach ($grids as $period => $data) {
            if (!in_array($period, $validPeriods, true)) {
                continue;
            }

            $price       = (int) ($data['price'] ?? 0);
            $minDuration = max(1, (int) ($data['min_duration'] ?? 1));

            if ($price <= 0) {
                // Désactiver si déjà existant et que le prix est supprimé
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
                    'price'        => $price,
                    'min_duration' => $minDuration,
                    'is_active'    => true,
                ]
            );
        }
    }
}
