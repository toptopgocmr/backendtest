<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'user_id', 'property_id', 'booking_id',
        'rating', 'rating_location', 'rating_cleanliness', 'rating_value',
        'comment', 'owner_reply', 'is_visible',
    ];

    protected $casts = ['is_visible' => 'boolean'];

    public function user()     { return $this->belongsTo(User::class); }
    public function property() { return $this->belongsTo(Property::class); }
    public function booking()  { return $this->belongsTo(Booking::class); }
}
