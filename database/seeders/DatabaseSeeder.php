<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('admins')->insertOrIgnore([
            [
                'name'       => 'Admin ImmoStay',
                'email'      => 'admin@immostay.com',
                'password'   => Hash::make('Admin@1234'),
                'role'       => 'admin',
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('users')->insertOrIgnore([
            [
                'id' => 1,
                'name' => 'Jean-Baptiste Moukala',
                'email' => 'jb.moukala@gmail.com',
                'phone' => '+242060123456',
                'country_code' => '+242',
                'country' => 'Congo Brazzaville',
                'role' => 'owner',
                'is_verified' => 1,
                'is_active' => 1,
                'password' => Hash::make('Admin@1234'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Marie-Claire Ngoyi',
                'email' => 'marie.ngoyi@gmail.com',
                'phone' => '+242055987654',
                'country_code' => '+242',
                'country' => 'Congo Brazzaville',
                'role' => 'client',
                'is_verified' => 1,
                'is_active' => 1,
                'password' => Hash::make('Admin@1234'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'Paul Ondongo',
                'email' => 'paul.ondongo@hotmail.com',
                'phone' => '+242066543210',
                'country_code' => '+242',
                'country' => 'Congo Brazzaville',
                'role' => 'client',
                'is_verified' => 1,
                'is_active' => 1,
                'password' => Hash::make('Admin@1234'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'name' => 'Fatou Diallo',
                'email' => 'fatou.diallo@yahoo.fr',
                'phone' => '+221771234567',
                'country_code' => '+221',
                'country' => 'Sénégal',
                'role' => 'owner',
                'is_verified' => 1,
                'is_active' => 1,
                'password' => Hash::make('Admin@1234'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('transactions')->insertOrIgnore([
            [
                'type' => 'revenue',
                'amount' => 472500,
                'currency' => 'XAF',
                'category' => 'location',
                'description' => 'Réservation BK-2025-AA001',
                'reference' => 'PAY-2025-AA001',
                'booking_id' => 1,
                'date' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'type' => 'commission',
                'amount' => 22500,
                'currency' => 'XAF',
                'category' => 'commission',
                'description' => 'Commission 5% — BK-2025-AA001',
                'reference' => 'PAY-2025-AA001',
                'booking_id' => 1,
                'date' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'type' => 'revenue',
                'amount' => 1575000,
                'currency' => 'XAF',
                'category' => 'location',
                'description' => 'Réservation BK-2025-AA002',
                'reference' => 'PAY-2025-AA002',
                'booking_id' => 2,
                'date' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'type' => 'commission',
                'amount' => 75000,
                'currency' => 'XAF',
                'category' => 'commission',
                'description' => 'Commission 5% — BK-2025-AA002',
                'reference' => 'PAY-2025-AA002',
                'booking_id' => 2,
                'date' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'type' => 'expense',
                'amount' => 85000,
                'currency' => 'XAF',
                'category' => 'maintenance',
                'description' => 'Réparation climatisation — Villa Luxe',
                'reference' => 'EXP-2025-AA001',
                'booking_id' => null,
                'date' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'type' => 'revenue',
                'amount' => 126000,
                'currency' => 'XAF',
                'category' => 'location',
                'description' => 'Réservation BK-2025-AA004',
                'reference' => 'PAY-2025-AA003',
                'booking_id' => 4,
                'date' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);

        // Créer/mettre à jour l'utilisateur support TholadImmo
        $this->call([
            SupportUserSeeder::class,
        ]);

        $this->command->info('✅ Seed OK');
    }
}
