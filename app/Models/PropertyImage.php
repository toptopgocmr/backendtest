<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * FIX — is_primary (migration) au lieu de is_cover
 * FIX — accesseur url : force une URL absolue pour les images stockées localement
 */
class PropertyImage extends Model
{
    public $timestamps = false;
    protected $table = 'property_images';

    protected $fillable = ['property_id', 'url', 'is_primary', 'sort_order'];
    protected $casts = ['is_primary' => 'boolean'];

    /**
     * Toujours retourner une URL absolue.
     * - URLs Cloudinary (https://...) → inchangées
     * - Chemins locaux (/storage/...) → préfixés avec APP_URL via asset()
     */
    public function getUrlAttribute(string $value): string
    {
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }
        return asset($value);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}
