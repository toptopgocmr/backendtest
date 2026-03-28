<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockCategory extends Model
{
    protected $fillable = ['name','icon','color'];

    public function items()
    {
        return $this->hasMany(StockItem::class, 'category_id');
    }
}

// ──────────────────────────────────────────────────────────
// Fichier séparé recommandé : app/Models/StockItem.php
// ──────────────────────────────────────────────────────────

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockItem extends Model
{
    protected $fillable = [
        'category_id','property_id','name','reference','unit','description',
        'quantity_current','quantity_minimum','quantity_optimal',
        'unit_price','currency','supplier','is_active',
    ];

    protected $casts = [
        'quantity_current' => 'float',
        'quantity_minimum' => 'float',
        'quantity_optimal' => 'float',
        'unit_price'       => 'float',
        'is_active'        => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(StockCategory::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function alerts()
    {
        return $this->hasMany(StockAlert::class);
    }

    // ── Helpers ──────────────────────────────────────────
    public function isLow(): bool
    {
        return $this->quantity_current <= $this->quantity_minimum;
    }

    public function isCritical(): bool
    {
        return $this->quantity_current <= ($this->quantity_minimum * 0.5);
    }

    public function getStockLevelAttribute(): string
    {
        if ($this->isCritical()) return 'critical';
        if ($this->isLow())      return 'warning';
        return 'ok';
    }

    public function getStockPercentAttribute(): int
    {
        if ($this->quantity_optimal <= 0) return 0;
        return min(100, (int)(($this->quantity_current / $this->quantity_optimal) * 100));
    }

    /**
     * Ajouter du stock (entrée)
     */
    public function addStock(float $qty, ?int $agentId = null, string $reason = '', string $ref = ''): StockMovement
    {
        $before = $this->quantity_current;
        $after  = $before + $qty;
        $this->update(['quantity_current' => $after]);

        return StockMovement::create([
            'stock_item_id'   => $this->id,
            'agent_id'        => $agentId,
            'type'            => 'entrée',
            'quantity'        => $qty,
            'quantity_before' => $before,
            'quantity_after'  => $after,
            'reason'          => $reason,
            'reference'       => $ref,
        ]);
    }

    /**
     * Retirer du stock (sortie)
     */
    public function removeStock(float $qty, ?int $agentId = null, ?int $propertyId = null, string $reason = ''): StockMovement
    {
        $before = $this->quantity_current;
        $after  = max(0, $before - $qty);
        $this->update(['quantity_current' => $after]);

        $movement = StockMovement::create([
            'stock_item_id'   => $this->id,
            'agent_id'        => $agentId,
            'property_id'     => $propertyId,
            'type'            => 'sortie',
            'quantity'        => $qty,
            'quantity_before' => $before,
            'quantity_after'  => $after,
            'reason'          => $reason,
        ]);

        // Vérifier si une alerte doit être créée
        $this->checkAndCreateAlert();

        return $movement;
    }

    public function checkAndCreateAlert(): void
    {
        if ($this->isCritical()) {
            StockAlert::firstOrCreate(
                ['stock_item_id' => $this->id, 'is_resolved' => false, 'level' => 'critical'],
                ['quantity_at_alert' => $this->quantity_current]
            );
        } elseif ($this->isLow()) {
            StockAlert::firstOrCreate(
                ['stock_item_id' => $this->id, 'is_resolved' => false, 'level' => 'warning'],
                ['quantity_at_alert' => $this->quantity_current]
            );
        }
    }

    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeLow($q)    { return $q->whereRaw('quantity_current <= quantity_minimum'); }
    public function scopeCritical($q){ return $q->whereRaw('quantity_current <= (quantity_minimum * 0.5)'); }
}

// ──────────────────────────────────────────────────────────
// Fichier séparé recommandé : app/Models/StockMovement.php
// ──────────────────────────────────────────────────────────

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'stock_item_id','agent_id','property_id','type',
        'quantity','quantity_before','quantity_after',
        'unit_price','reason','reference','notes','movement_date',
    ];

    protected $casts = [
        'quantity'        => 'float',
        'quantity_before' => 'float',
        'quantity_after'  => 'float',
        'unit_price'      => 'float',
        'movement_date'   => 'datetime',
    ];

    public function stockItem() { return $this->belongsTo(StockItem::class); }
    public function agent()     { return $this->belongsTo(Agent::class); }
    public function property()  { return $this->belongsTo(Property::class); }
}

// ──────────────────────────────────────────────────────────
// Fichier séparé recommandé : app/Models/StockAlert.php
// ──────────────────────────────────────────────────────────

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAlert extends Model
{
    protected $fillable = [
        'stock_item_id','level','quantity_at_alert',
        'is_read','is_resolved','resolved_at',
    ];

    protected $casts = [
        'is_read'      => 'boolean',
        'is_resolved'  => 'boolean',
        'resolved_at'  => 'datetime',
        'quantity_at_alert' => 'float',
    ];

    public function stockItem() { return $this->belongsTo(StockItem::class); }

    public function scopeUnread($q)    { return $q->where('is_read', false); }
    public function scopeActive($q)    { return $q->where('is_resolved', false); }
    public function scopeCritical($q)  { return $q->where('level', 'critical'); }
}
