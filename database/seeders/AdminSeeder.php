<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Supprimer l'ancien et recréer proprement
        DB::table('admins')->where('email', 'admin@immostay.com')->delete();

        DB::table('admins')->insert([
            'name'       => 'Admin ImmoStay',
            'email'      => 'admin@immostay.com',
            'password'   => Hash::make('Admin@1234'),
            'role'       => 'admin',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('✅ Admin créé : admin@immostay.com / Admin@1234');
    }
}
