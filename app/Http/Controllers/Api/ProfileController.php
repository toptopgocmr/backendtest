<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
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
            'first_name'   => 'sometimes|nullable|string|max:100',
            'last_name'    => 'sometimes|nullable|string|max:100',
            'email'        => 'sometimes|nullable|email|unique:users,email,' . $user->id,
            'phone'        => 'sometimes|string|unique:users,phone,' . $user->id,
            'country_code' => 'sometimes|string',
            'country'      => 'sometimes|string',
        ]);

        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $updateData = $request->only(['email', 'phone', 'country_code', 'country']);

        if ($request->has('first_name') || $request->has('last_name')) {
            $firstName = $request->input('first_name', $user->name ? explode(' ', $user->name)[0] : '');
            $lastName  = $request->input('last_name',  $user->name ? implode(' ', array_slice(explode(' ', $user->name), 1)) : '');
            $updateData['name'] = trim("$firstName $lastName");
        } elseif ($request->has('name')) {
            $updateData['name'] = $request->name;
        }

        $user->update($updateData);

        return response()->json([
            'success' => true,
            'data'    => $this->userResource($user),
            'message' => 'Profil mis à jour.',
        ]);
    }

    public function updateAvatar(Request $request)
    {
        $request->validate(['avatar' => 'required|image|max:4096']);
        $user = $request->user();

        // ── Tentative upload Cloudinary (si configuré) ──────────────────
        $cloudName = config('services.cloudinary.cloud_name');
        $apiKey    = config('services.cloudinary.api_key');
        $apiSecret = config('services.cloudinary.api_secret');

        if ($cloudName && $apiKey && $apiSecret) {
            try {
                $file      = $request->file('avatar');
                $timestamp = time();
                $params    = ['timestamp' => $timestamp, 'folder' => 'tholadimmo/avatars'];
                ksort($params);
                $sigStr    = http_build_query($params) . $apiSecret;
                $signature = sha1($sigStr);

                $response = Http::attach(
                    'file',
                    file_get_contents($file->getRealPath()),
                    $file->getClientOriginalName()
                )->post("https://api.cloudinary.com/v1_1/{$cloudName}/image/upload", [
                    'api_key'   => $apiKey,
                    'timestamp' => $timestamp,
                    'signature' => $signature,
                    'folder'    => 'tholadimmo/avatars',
                ]);

                if ($response->successful()) {
                    $avatarUrl = $response->json('secure_url');
                    $user->update(['avatar' => $avatarUrl]);

                    return response()->json([
                        'success'    => true,
                        'avatar_url' => $avatarUrl,
                        'data'       => $this->userResource($user->fresh()),
                    ]);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[Avatar] Cloudinary upload failed: ' . $e->getMessage());
                // Fallback vers storage local
            }
        }

        // ── Fallback : storage local (Railway filesystem éphémère) ──────
        // ⚠️ Les fichiers seront perdus au redémarrage Railway.
        // Configurez CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY, CLOUDINARY_API_SECRET
        // dans les variables Railway pour une solution persistante.
        if ($user->avatar && !str_starts_with($user->avatar, 'http')) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        return response()->json([
            'success'    => true,
            'avatar_url' => $user->avatar_url,
            'data'       => $this->userResource($user),
        ]);
    }

    public function changePassword(Request $request)
    {
        $v = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password'         => 'required|string|min:6|confirmed',
        ]);

        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mot de passe actuel incorrect.',
            ], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json(['success' => true, 'message' => 'Mot de passe modifié.']);
    }

    private function userResource($user): array
    {
        $nameParts  = explode(' ', trim($user->name ?? ''));
        $firstName  = $nameParts[0] ?? '';
        $lastName   = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';

        return [
            'id'           => $user->id,
            'name'         => $user->name,
            'first_name'   => $firstName,
            'last_name'    => $lastName,
            'email'        => $user->email,
            'phone'        => $user->phone,
            'country_code' => $user->country_code,
            'country'      => $user->country,
            'avatar_url'   => $user->avatar_url,
            'avatar'       => $user->avatar,
            'role'         => $user->role,
            'is_verified'  => $user->is_verified,
            'is_active'    => $user->is_active,
            'created_at'   => $user->created_at,
        ];
    }
}
