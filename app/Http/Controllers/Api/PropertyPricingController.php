<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyPricingGrid;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyPricingController extends Controller
{
    /**
     * GET /api/properties/{id}/pricing
     * Retourne tous les tarifs actifs d'une propriété.
     * Utilisé par le mobile pour afficher les options de durée.
     */
    public function index(string $id): JsonResponse
    {
        $property = Property::findOrFail($id);

        $grids = $property->pricingGrids()->get()->map(fn($g) => [
            'id'            => $g->id,
            'period'        => $g->period,
            'period_label'  => $g->period_label,
            'price'         => $g->price,
            'min_duration'  => $g->min_duration,
            'formatted'     => $g->formatted_price,
        ]);

        return response()->json([
            'success'  => true,
            'property' => ['id' => $property->id, 'title' => $property->title],
            'pricing'  => $grids,
        ]);
    }

    /**
     * POST /api/properties/{id}/pricing
     * Crée ou met à jour la grille complète (admin/owner).
     * Body: { "grids": [ { "period": "heure", "price": 5000, "min_duration": 1 }, ... ] }
     */
    public function store(Request $request, string $id): JsonResponse
    {
        $property = Property::findOrFail($id);

        // Vérifie droits
        if ($property->owner_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        $request->validate([
            'grids'                  => 'required|array|min:1',
            'grids.*.period'         => 'required|in:heure,jour,nuit,semaine,mois,an',
            'grids.*.price'          => 'required|integer|min:0',
            'grids.*.min_duration'   => 'nullable|integer|min:1|max:365',
        ]);

        $saved = [];
        foreach ($request->grids as $gridData) {
            $grid = PropertyPricingGrid::updateOrCreate(
                [
                    'property_id' => $property->id,
                    'period'      => $gridData['period'],
                ],
                [
                    'price'        => $gridData['price'],
                    'min_duration' => $gridData['min_duration'] ?? 1,
                    'is_active'    => true,
                ]
            );
            $saved[] = $grid;
        }

        return response()->json([
            'success' => true,
            'message' => 'Grille tarifaire mise à jour.',
            'pricing' => collect($saved)->map(fn($g) => [
                'id'           => $g->id,
                'period'       => $g->period,
                'period_label' => $g->period_label,
                'price'        => $g->price,
                'min_duration' => $g->min_duration,
                'formatted'    => $g->formatted_price,
            ]),
        ]);
    }

    /**
     * DELETE /api/properties/{id}/pricing/{period}
     * Désactive un tarif (ex: supprimer "par semaine").
     */
    public function destroy(Request $request, string $id, string $period): JsonResponse
    {
        $property = Property::findOrFail($id);

        if ($property->owner_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        PropertyPricingGrid::where('property_id', $property->id)
            ->where('period', $period)
            ->update(['is_active' => false]);

        return response()->json(['success' => true, 'message' => "Tarif '{$period}' désactivé."]);
    }

    /**
     * POST /api/properties/{id}/pricing/calculate
     * Calcule le prix total pour une période + durée données.
     * Body: { "period": "heure", "duration": 3 }
     * Utilisé par Flutter avant de créer la réservation.
     */
    public function calculate(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'period'   => 'required|in:heure,jour,nuit,semaine,mois,an',
            'duration' => 'required|integer|min:1',
        ]);

        $grid = PropertyPricingGrid::where('property_id', $id)
            ->where('period', $request->period)
            ->where('is_active', true)
            ->first();

        if (!$grid) {
            return response()->json([
                'success' => false,
                'message' => "Aucun tarif disponible pour la période « {$request->period} ».",
            ], 404);
        }

        $duration    = (int) $request->duration;
        $effectiveDur = max($duration, $grid->min_duration);
        $total        = $grid->price * $effectiveDur;

        return response()->json([
            'success'        => true,
            'period'         => $grid->period,
            'period_label'   => $grid->period_label,
            'unit_price'     => $grid->price,
            'duration'       => $duration,
            'effective_duration' => $effectiveDur,  // si durée < min, on applique le min
            'total'          => $total,
            'formatted_total'=> number_format($total, 0, ',', ' ') . ' XAF',
            'min_duration'   => $grid->min_duration,
            'warning'        => $duration < $grid->min_duration
                ? "Durée minimum : {$grid->min_duration} {$grid->period}(s). Ajustée automatiquement."
                : null,
        ]);
    }
}
