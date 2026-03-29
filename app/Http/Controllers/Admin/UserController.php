<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::when($request->search, fn($q, $v) =>
                $q->where('name', 'like', "%$v%")
                  ->orWhere('phone', 'like', "%$v%")
                  ->orWhere('email', 'like', "%$v%"))
            ->when($request->role, fn($q, $v) => $q->where('role', $v))
            ->when($request->status !== null && $request->status !== '',
                fn($q) => $q->where('is_active', (int) $request->status))
            ->latest()->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    public function show(string $id)
    {
        $user = User::with([
            'bookings.property',
            'reviews',
            'favorites.property',
        ])->findOrFail($id);

        return view('admin.users.show', compact('user'));
    }

    public function toggle(string $id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => !$user->is_active]);

        $msg = $user->is_active ? 'Utilisateur activé.' : 'Utilisateur suspendu.';

        return back()->with('success', $msg);
    }

    /**
     * Vérifier manuellement un compte depuis l'admin.
     * is_verified = true + is_active = true → le client peut se connecter immédiatement.
     */
    public function verify(string $id)
    {
        $user = User::findOrFail($id);
        $user->update([
            'is_verified'    => true,
            'is_active'      => true,
            'otp_code'       => null,
            'otp_expires_at' => null,
        ]);

        return back()->with('success', "Compte de {$user->name} vérifié et activé.");
    }
}
