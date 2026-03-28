<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockItem;
use App\Models\StockCategory;
use App\Models\StockMovement;
use App\Models\StockAlert;
use App\Models\Property;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $alertsCount = StockAlert::active()->unread()->count();
        $criticalCount = StockAlert::active()->critical()->count();

        $items = StockItem::with(['category','property'])
            ->active()
            ->when($request->category, fn($q, $v) => $q->where('category_id', $v))
            ->when($request->property, fn($q, $v) => $q->where('property_id', $v))
            ->when($request->level === 'low', fn($q) => $q->low())
            ->when($request->level === 'critical', fn($q) => $q->critical())
            ->when($request->search, fn($q, $v) =>
                $q->where('name', 'like', "%$v%")
                  ->orWhere('reference', 'like', "%$v%"))
            ->orderByRaw('quantity_current <= quantity_minimum DESC')
            ->paginate(20);

        $categories  = StockCategory::all();
        $properties  = Property::select('id','title','city')->get();

        $stats = [
            'total'    => StockItem::active()->count(),
            'low'      => StockItem::active()->low()->count(),
            'critical' => StockItem::active()->critical()->count(),
            'alerts'   => $alertsCount,
        ];

        return view('admin.stock.index', compact(
            'items','categories','properties','stats','alertsCount','criticalCount'
        ));
    }

    public function create()
    {
        $categories = StockCategory::all();
        $properties = Property::select('id','title','city')->get();
        return view('admin.stock.create', compact('categories','properties'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'              => 'required|string|max:150',
            'category_id'       => 'required|exists:stock_categories,id',
            'unit'              => 'required|string|max:30',
            'quantity_current'  => 'required|numeric|min:0',
            'quantity_minimum'  => 'required|numeric|min:0',
            'quantity_optimal'  => 'required|numeric|min:0',
        ]);

        $item = StockItem::create($request->all());

        // Vérifier si une alerte doit être créée immédiatement
        $item->checkAndCreateAlert();

        return redirect()->route('admin.stock.index')
            ->with('success', "Article « {$item->name} » ajouté au stock.");
    }

    public function show(string $id)
    {
        $item       = StockItem::with(['category','property','movements.agent','alerts'])->findOrFail($id);
        $movements  = $item->movements()->with('agent','property')->latest()->paginate(20);
        $alerts     = $item->alerts()->latest()->get();
        return view('admin.stock.show', compact('item','movements','alerts'));
    }

    public function edit(string $id)
    {
        $item       = StockItem::findOrFail($id);
        $categories = StockCategory::all();
        $properties = Property::select('id','title','city')->get();
        return view('admin.stock.edit', compact('item','categories','properties'));
    }

    public function update(Request $request, string $id)
    {
        $item = StockItem::findOrFail($id);
        $item->update($request->except(['_token','_method']));
        $item->checkAndCreateAlert();
        return redirect()->route('admin.stock.show', $item->id)
            ->with('success', 'Article mis à jour.');
    }

    /**
     * Entrée de stock (réapprovisionnement)
     */
    public function addStock(Request $request, string $id)
    {
        $request->validate([
            'quantity'  => 'required|numeric|min:0.01',
            'reason'    => 'nullable|string|max:200',
            'reference' => 'nullable|string|max:100',
        ]);

        $item = StockItem::findOrFail($id);
        $item->addStock(
            $request->quantity,
            auth('admin')->id(),
            $request->reason ?? 'Réapprovisionnement',
            $request->reference ?? ''
        );

        // Résoudre les alertes actives si stock ok
        if (!$item->fresh()->isLow()) {
            StockAlert::where('stock_item_id', $item->id)
                ->where('is_resolved', false)
                ->update(['is_resolved' => true, 'resolved_at' => now()]);
        }

        return back()->with('success', "+ {$request->quantity} {$item->unit} ajouté(s) pour « {$item->name} ».");
    }

    /**
     * Sortie de stock
     */
    public function removeStock(Request $request, string $id)
    {
        $request->validate([
            'quantity'    => 'required|numeric|min:0.01',
            'property_id' => 'nullable|exists:properties,id',
            'reason'      => 'nullable|string|max:200',
        ]);

        $item = StockItem::findOrFail($id);

        if ($request->quantity > $item->quantity_current) {
            return back()->with('error', "Quantité insuffisante. Stock actuel : {$item->quantity_current} {$item->unit}");
        }

        $item->removeStock(
            $request->quantity,
            auth('admin')->id(),
            $request->property_id,
            $request->reason ?? 'Sortie manuelle'
        );

        return back()->with('success', "- {$request->quantity} {$item->unit} retiré(s) de « {$item->name} ».");
    }

    /**
     * Alertes non lues
     */
    public function alerts()
    {
        $alerts = StockAlert::with('stockItem.category','stockItem.property')
            ->active()
            ->latest()
            ->paginate(20);

        StockAlert::active()->unread()->update(['is_read' => true]);

        return view('admin.stock.alerts', compact('alerts'));
    }

    /**
     * Marquer alerte résolue
     */
    public function resolveAlert(string $id)
    {
        StockAlert::findOrFail($id)->update([
            'is_resolved' => true,
            'resolved_at' => now(),
        ]);
        return back()->with('success', 'Alerte marquée comme résolue.');
    }

    /**
     * Mouvements globaux
     */
    public function movements(Request $request)
    {
        $movements = StockMovement::with(['stockItem.category','agent','property'])
            ->when($request->type, fn($q, $v) => $q->where('type', $v))
            ->latest()->paginate(30);

        return view('admin.stock.movements', compact('movements'));
    }
}
