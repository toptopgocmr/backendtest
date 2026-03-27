<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyType extends Model
{
    public $timestamps = false;
    protected $fillable = ['name', 'icon'];

    public function properties()
    {
        return $this->hasMany(Property::class);
    }
}
