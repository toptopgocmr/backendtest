<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    // FIX : la table n'a pas de colonne updated_at (migration useCurrent sur created_at seulement)
    // Laravel essayait d'écrire updated_at → SQLSTATE column not found → 500
    public $timestamps = false;

    // On gère created_at manuellement via booted()
    const CREATED_AT = 'created_at';

    protected $fillable = ['user_id', 'title', 'body', 'type', 'data', 'is_read'];

    protected $casts = [
        'data'    => 'array',
        'is_read' => 'boolean',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($notification) {
            if (empty($notification->created_at)) {
                $notification->created_at = now();
            }
        });
    }

    public function user() { return $this->belongsTo(User::class); }
}
