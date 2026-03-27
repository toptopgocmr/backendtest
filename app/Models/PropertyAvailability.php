<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyAvailability extends Model
{
    public $timestamps = false;
    protected $fillable = ['property_id', 'unavailable_date', 'reason'];

    protected $casts = ['unavailable_date' => 'date'];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}
