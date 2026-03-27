<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        // Déjà connecté → rediriger
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string|min:1',
        ], [
            'email.required'    => 'L\'email est obligatoire.',
            'email.email'       => 'L\'email n\'est pas valide.',
            'password.required' => 'Le mot de passe est obligatoire.',
        ]);

        // Chercher l'admin directement dans la table
        $admin = Admin::where('email', $request->email)->first();

        // Vérifications manuelles pour donner des messages clairs
        if (!$admin) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'Aucun compte admin trouvé avec cet email.']);
        }

        if (!$admin->is_active) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'Ce compte administrateur est suspendu.']);
        }

        if (!Hash::check($request->password, $admin->password)) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'Mot de passe incorrect.']);
        }

        // Connexion manuelle via Auth::login
        Auth::guard('admin')->login($admin, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login')->with('success', 'Vous êtes déconnecté.');
    }
}
