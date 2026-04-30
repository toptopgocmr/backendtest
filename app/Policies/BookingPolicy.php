<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        return (int) $user->id === (int) $booking->user_id
            || in_array($user->role ?? '', ['admin', 'owner', 'proprietaire']);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Booking $booking): bool
    {
        return (int) $user->id === (int) $booking->user_id
            || $user->role === 'admin';
    }

    public function delete(User $user, Booking $booking): bool
    {
        return (int) $user->id === (int) $booking->user_id
            || $user->role === 'admin';
    }
}
