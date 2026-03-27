<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $v = Validator::make($request->all(), [
            'name'         => 'required|string|max:191',
            'phone'        => 'required|string|unique:users',
            'email'        => 'nullable|email|unique:users',
            'country_code' => 'required|string',
            'country'      => 'required|string',
            'password'     => 'required|string|min:6|confirmed',
        ]);

        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $user = User::create([
            'name'         => $request->name,
            'email'        => $request->email,
            'phone'        => $request->phone,
            'country_code' => $request->country_code,
            'country'      => $request->country,
            'password'     => Hash::make($request->password),
            'role'         => 'client',
            'is_active'    => false, // FIX: is_active pas status
            'is_verified'  => false,
        ]);

        $this->sendOtpToUser($user);

        return response()->json([
            'success' => true,
            'message' => 'Compte créé. Vérifiez votre numéro via OTP.',
            'phone'   => $user->phone,
        ], 201);
    }

    public function login(Request $request)
    {
        $v = Validator::make($request->all(), [
            'phone'    => 'required|string',
            'password' => 'required|string',
        ]);

        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $user = User::where('phone', $request->phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Identifiants incorrects.'], 401);
        }

        // FIX: is_active pas status
        if (!$user->is_active) {
            if (!$user->is_verified) {
                return response()->json(['success' => false, 'message' => 'Compte non vérifié. Vérifiez votre numéro via OTP.'], 403);
            }
            return response()->json(['success' => false, 'message' => 'Compte suspendu. Contactez le support.'], 403);
        }

        $user->update(['last_login_at' => now()]);

        if ($request->device_token) {
            $user->update(['device_token' => $request->device_token]);
        }

        $token = $user->createToken('mobile_app')->plainTextToken;

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => $this->userResource($user),
        ]);
    }

    public function sendOtp(Request $request)
    {
        $v = Validator::make($request->all(), ['phone' => 'required|string']);
        if ($v->fails()) return response()->json(['success' => false, 'errors' => $v->errors()], 422);

        $user = User::where('phone', $request->phone)->first();
        if ($user) $this->sendOtpToUser($user);

        return response()->json(['success' => true, 'message' => 'Si ce numéro existe, un OTP a été envoyé.']);
    }

    public function verifyOtp(Request $request)
    {
        $v = Validator::make($request->all(), [
            'phone' => 'required|string',
            'otp'   => 'required|string|size:6',
        ]);
        if ($v->fails()) return response()->json(['success' => false, 'errors' => $v->errors()], 422);

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Utilisateur introuvable.'], 404);
        }

        if ($user->otp_code !== $request->otp || now()->isAfter($user->otp_expires_at)) {
            return response()->json(['success' => false, 'message' => 'OTP invalide ou expiré.'], 422);
        }

        // FIX: is_active pas status='active'
        $user->update([
            'is_verified'    => true,
            'is_active'      => true,
            'otp_code'       => null,
            'otp_expires_at' => null,
        ]);

        $token = $user->createToken('mobile_app')->plainTextToken;

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => $this->userResource($user),
        ]);
    }

    public function me(Request $request)
    {
        return response()->json(['success' => true, 'user' => $this->userResource($request->user())]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true, 'message' => 'Déconnecté.']);
    }

    public function forgotPassword(Request $request)
    {
        $user = User::where('phone', $request->phone)->first();
        if ($user) $this->sendOtpToUser($user);
        return response()->json(['success' => true, 'message' => 'Si ce numéro existe, un OTP a été envoyé.']);
    }

    public function resetPassword(Request $request)
    {
        $v = Validator::make($request->all(), [
            'phone'    => 'required',
            'otp'      => 'required',
            'password' => 'required|min:6|confirmed',
        ]);
        if ($v->fails()) return response()->json(['success' => false, 'errors' => $v->errors()], 422);

        $user = User::where('phone', $request->phone)->first();

        if (!$user || $user->otp_code !== $request->otp || now()->isAfter($user->otp_expires_at)) {
            return response()->json(['success' => false, 'message' => 'OTP invalide ou expiré.'], 422);
        }

        $user->update([
            'password'       => Hash::make($request->password),
            'otp_code'       => null,
            'otp_expires_at' => null,
        ]);

        return response()->json(['success' => true, 'message' => 'Mot de passe réinitialisé.']);
    }

    // ─────────────────────────────────────────────────────────────
    private function sendOtpToUser(User $user): void
    {
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->update([
            'otp_code'       => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(10),
        ]);
        // TODO: Brancher AfricasTalking / Infobip pour l'envoi SMS réel
        \Log::info("OTP [{$user->phone}]: $otp");
    }

    private function userResource(User $user): array
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
            'is_active'    => $user->is_active,  // FIX
            'created_at'   => $user->created_at,
        ];
    }
}
