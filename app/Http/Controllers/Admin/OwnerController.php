<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\OwnerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class OwnerController extends Controller
{
    public function index(Request $request)
    {
        $owners = User::where('role', 'owner')
            ->with('ownerProfile')
            ->when($request->search, fn($q, $v) =>
                $q->where('name', 'like', "%$v%")
                  ->orWhere('phone', 'like', "%$v%")
                  ->orWhere('email', 'like', "%$v%"))
            ->when($request->status, fn($q, $v) =>
                $q->whereHas('ownerProfile', fn($q2) => $q2->where('status', $v)))
            ->latest()->paginate(15);

        $stats = [
            'total'     => User::where('role', 'owner')->count(),
            'verified'  => OwnerProfile::where('status', 'vérifié')->count(),
            'pending'   => OwnerProfile::where('status', 'en_attente')->count(),
        ];

        return view('admin.owners.index', compact('owners', 'stats'));
    }

    public function create()
    {
        return view('admin.owners.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:100',
            'email'        => 'required|email|unique:users,email',
            'phone'        => 'required|string|max:30',
            'password'     => 'required|min:8|confirmed',
            'company_name' => 'nullable|string|max:150',
        ]);

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'phone'     => $request->phone,
            'password'  => Hash::make($request->password),
            'role'      => 'owner',
            'is_active' => true,
        ]);

        OwnerProfile::create([
            'user_id'            => $user->id,
            'company_name'       => $request->company_name,
            'siret'              => $request->siret,
            'legal_form'         => $request->legal_form,
            'contact_phone'      => $request->contact_phone ?? $request->phone,
            'contact_email'      => $request->contact_email ?? $request->email,
            'address'            => $request->address,
            'city'               => $request->city,
            'country'            => $request->country ?? 'Congo Brazzaville',
            'mobile_money_number'=> $request->mobile_money_number,
            'commission_rate'    => $request->commission_rate ?? 10.00,
            'status'             => 'en_attente',
        ]);

        return redirect()->route('admin.owners.index')
            ->with('success', "Propriétaire {$user->name} enregistré avec succès.");
    }

    public function show(string $id)
    {
        $user = User::with(['ownerProfile', 'properties.images'])->findOrFail($id);
        return view('admin.owners.show', compact('user'));
    }

    public function edit(string $id)
    {
        $user = User::with('ownerProfile')->findOrFail($id);
        return view('admin.owners.edit', compact('user'));
    }

    public function update(Request $request, string $id)
    {
        $user = User::with('ownerProfile')->findOrFail($id);

        $user->update($request->only(['name','phone','email']));

        $user->ownerProfile()->updateOrCreate(
            ['user_id' => $user->id],
            $request->only([
                'company_name','siret','legal_form','contact_person',
                'contact_phone','contact_email','address','city','country',
                'mobile_money_number','bank_name','bank_account',
                'commission_rate','notes',
            ])
        );

        return redirect()->route('admin.owners.show', $user->id)
            ->with('success', 'Propriétaire mis à jour.');
    }

    public function verify(string $id)
    {
        $profile = OwnerProfile::where('user_id', $id)->firstOrFail();
        $profile->update([
            'status'      => 'vérifié',
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        return back()->with('success', 'Propriétaire vérifié.');
    }

    public function toggle(string $id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => !$user->is_active]);

        $profile = $user->ownerProfile;
        if ($profile) {
            $profile->update(['status' => $user->is_active ? 'vérifié' : 'suspendu']);
        }

        $msg = $user->is_active ? 'Propriétaire activé.' : 'Propriétaire suspendu.';
        return back()->with('success', $msg);
    }
}
