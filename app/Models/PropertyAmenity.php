<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyAmenity extends Model
{
    public $timestamps = false;
    protected $table = 'property_amenities';
    protected $fillable = ['property_id', 'name', 'icon'];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}
