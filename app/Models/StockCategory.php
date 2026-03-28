<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockCategory extends Model
{
    protected $fillable = ['name', 'icon', 'color'];

    public function items()
    {
        return $this->hasMany(StockItem::class, 'category_id');
    }
}
