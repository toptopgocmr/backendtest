<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return response()->json(['success' => true, 'data' => $this->userResource($request->user())]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $v = Validator::make($request->all(), [
            'name'         => 'sometimes|string|max:191',
            'email'        => 'sometimes|nullable|email|unique:users,email,' . $user->id,
            'phone'        => 'sometimes|string|unique:users,phone,' . $user->id,
            'country_code' => 'sometimes|string',
            'country'      => 'sometimes|string',
        ]);

        if ($v->fails()) return response()->json(['success' => false, 'errors' => $v->errors()], 422);

        $user->update($request->only(['name', 'email', 'phone', 'country_code', 'country']));

        return response()->json(['success' => true, 'data' => $this->userResource($user), 'message' => 'Profil mis à jour.']);
    }

    public function updateAvatar(Request $request)
    {
        $request->validate(['avatar' => 'required|image|max:2048']);
        $user = $request->user();

        if ($user->avatar) Storage::disk('public')->delete($user->avatar);

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        return response()->json(['success' => true, 'avatar_url' => $user->avatar_url]);
    }

    public function changePassword(Request $request)
    {
        $v = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password'         => 'required|string|min:6|confirmed',
        ]);

        if ($v->fails()) return response()->json(['success' => false, 'errors' => $v->errors()], 422);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Mot de passe actuel incorrect.'], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json(['success' => true, 'message' => 'Mot de passe modifié.']);
    }

    private function userResource($user): array
    {
        return [
            'id'           => $user->id,
            'name'         => $user->name,
            'email'        => $user->email,
            'phone'        => $user->phone,
            'country_code' => $user->country_code,
            'country'      => $user->country,
            'avatar_url'   => $user->avatar_url,
            'role'         => $user->role,
            'is_verified'  => $user->is_verified,
            'is_active'    => $user->is_active, // FIX: is_active
            'created_at'   => $user->created_at,
        ];
    }
}
