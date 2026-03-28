<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'stock_item_id', 'agent_id', 'property_id', 'type',
        'quantity', 'quantity_before', 'quantity_after',
        'unit_price', 'reason', 'reference', 'notes', 'movement_date',
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
