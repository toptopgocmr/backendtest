<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAlert extends Model
{
    protected $fillable = [
        'stock_item_id', 'level', 'quantity_at_alert',
        'is_read', 'is_resolved', 'resolved_at',
    ];

    protected $casts = [
        'is_read'           => 'boolean',
        'is_resolved'       => 'boolean',
        'resolved_at'       => 'datetime',
        'quantity_at_alert' => 'float',
    ];

    public function stockItem() { return $this->belongsTo(StockItem::class); }

    public function scopeUnread($q)   { return $q->where('is_read', false); }
    public function scopeActive($q)   { return $q->where('is_resolved', false); }
    public function scopeCritical($q) { return $q->where('level', 'critical'); }
}
