<?php
// database/seeders/SupportUserSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class SupportUserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Chercher par email
        $user = User::where('email', 'support@tholadimmo.com')->first();

        // 2. Si pas trouvé par email, chercher par téléphone
        if (!$user) {
            $user = User::where('phone', '+242000000000')->first();
        }

        if ($user) {
            // L'utilisateur existe déjà (avec ce téléphone ou email) → on le met à jour
            $user->update([
                'name'        => 'TholadImmo Support',
                'first_name'  => 'TholadImmo',
                'last_name'   => 'Support',
                'email'       => 'support@tholadimmo.com',
                'role'        => 'admin',
                'is_verified' => true,
                'is_active'   => true,
            ]);
            $this->command->info("Utilisateur support mis à jour. ID: {$user->id}");
            $this->command->info("Rôle défini à 'admin' → /api/v1/support/agent le trouvera.");
            return;
        }

        // 3. Aucun utilisateur trouvé → en créer un nouveau avec un téléphone unique
        $user = User::create([
            'name'         => 'TholadImmo Support',
            'first_name'   => 'TholadImmo',
            'last_name'    => 'Support',
            'email'        => 'support@tholadimmo.com',
            'phone'        => '+242000000001', // Numéro différent pour éviter le doublon
            'country_code' => 'CG',
            'country'      => 'Congo (Brazzaville)',
            'password'     => Hash::make('Support@Tholad2026!'),
            'role'         => 'admin',
            'is_verified'  => true,
            'is_active'    => true,
        ]);

        $this->command->info("Utilisateur support créé. ID: {$user->id}");
        $this->command->info("Cet ID est retourné par GET /api/v1/support/agent");
    }
}
