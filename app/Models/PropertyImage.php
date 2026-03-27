<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * FIX — is_primary (migration) au lieu de is_cover
 */
class PropertyImage extends Model
{
    public $timestamps = false;
    protected $table = 'property_images';

    protected $fillable = ['property_id', 'url', 'is_primary', 'sort_order'];
    protected $casts = ['is_primary' => 'boolean'];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}
