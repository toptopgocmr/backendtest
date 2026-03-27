<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'user1_id', 'user2_id', 'property_id', 'booking_id',
        'last_message', 'last_message_at', 'user1_unread', 'user2_unread',
    ];

    protected $casts = ['last_message_at' => 'datetime'];

    public function user1()    { return $this->belongsTo(User::class, 'user1_id'); }
    public function user2()    { return $this->belongsTo(User::class, 'user2_id'); }
    public function property() { return $this->belongsTo(Property::class); }
    public function booking()  { return $this->belongsTo(Booking::class); }
    public function messages() { return $this->hasMany(Message::class); }
}
