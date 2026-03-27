<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin (table séparée) ─────────────────────────────────
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

        // ── Users (FIX #9: is_active pas status) ─────────────────
        DB::table('users')->insertOrIgnore([
            [
                'id'           => 1,
                'name'         => 'Jean-Baptiste Moukala',
                'email'        => 'jb.moukala@gmail.com',
                'phone'        => '+242060123456',
                'country_code' => '+242',
                'country'      => 'Congo Brazzaville',
                'role'         => 'owner',
                'is_verified'  => 1,
                'is_active'    => 1,   // FIX: is_active, pas status
                'password'     => Hash::make('Admin@1234'),
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => 2,
                'name'         => 'Marie-Claire Ngoyi',
                'email'        => 'marie.ngoyi@gmail.com',
                'phone'        => '+242055987654',
                'country_code' => '+242',
                'country'      => 'Congo Brazzaville',
                'role'         => 'client',
                'is_verified'  => 1,
                'is_active'    => 1,
                'password'     => Hash::make('Admin@1234'),
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => 3,
                'name'         => 'Paul Ondongo',
                'email'        => 'paul.ondongo@hotmail.com',
                'phone'        => '+242066543210',
                'country_code' => '+242',
                'country'      => 'Congo Brazzaville',
                'role'         => 'client',
                'is_verified'  => 1,
                'is_active'    => 1,
                'password'     => Hash::make('Admin@1234'),
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => 4,
                'name'         => 'Fatou Diallo',
                'email'        => 'fatou.diallo@yahoo.fr',
                'phone'        => '+221771234567',
                'country_code' => '+221',
                'country'      => 'Sénégal',
                'role'         => 'owner',
                'is_verified'  => 1,
                'is_active'    => 1,
                'password'     => Hash::make('Admin@1234'),
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ]);

        // ── Properties (FIX #4: owner_id, price, is_approved=1) ──
        DB::table('properties')->insertOrIgnore([
            [
                'id'           => 1,
                'owner_id'     => 1,                    // FIX: owner_id pas user_id
                'title'        => 'Villa Luxe — Piscine & Vue Panoramique',
                'description'  => 'Magnifique villa 4 chambres avec piscine privée, salle de sport et vue panoramique sur Brazzaville.',
                'type'         => 'villa',
                'price'        => 1500000,              // FIX: price pas price_per_night
                'price_period' => 'mois',
                'currency'     => 'XAF',
                'address'      => 'Avenue de l\'Amitié',
                'city'         => 'Brazzaville',
                'district'     => 'Plateau',
                'country'      => 'Congo Brazzaville',
                'latitude'     => -4.2634,
                'longitude'    => 15.2429,
                'bedrooms'     => 4,
                'bathrooms'    => 3,
                'area'         => 280,                  // FIX: area pas area_m2
                'max_guests'   => 8,
                'status'       => 'disponible',         // FIX: enum FR
                'is_featured'  => 1,
                'is_approved'  => 1,
                'rating'       => 4.90,                 // FIX: rating pas rating_avg
                'reviews_count'=> 24,                   // FIX: reviews_count pas rating_count
                'views_count'  => 312,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => 2,
                'owner_id'     => 1,
                'title'        => 'Appartement Moderne — Centre Ville',
                'description'  => 'Bel appartement 3 pièces entièrement rénové, cuisine équipée, parking sécurisé.',
                'type'         => 'appartement',
                'price'        => 450000,
                'price_period' => 'mois',
                'currency'     => 'XAF',
                'address'      => 'Rue Behagle',
                'city'         => 'Brazzaville',
                'district'     => 'Centre-ville',
                'country'      => 'Congo Brazzaville',
                'latitude'     => -4.2610,
                'longitude'    => 15.2400,
                'bedrooms'     => 2,
                'bathrooms'    => 1,
                'area'         => 85,
                'max_guests'   => 4,
                'status'       => 'disponible',
                'is_featured'  => 1,
                'is_approved'  => 1,
                'rating'       => 4.70,
                'reviews_count'=> 18,
                'views_count'  => 245,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => 3,
                'owner_id'     => 4,
                'title'        => 'Studio Meublé — Poto-Poto',
                'description'  => 'Studio entièrement meublé et équipé, idéal pour professionnel ou étudiant.',
                'type'         => 'studio',
                'price'        => 120000,
                'price_period' => 'mois',
                'currency'     => 'XAF',
                'address'      => 'Rue des Flamboyants',
                'city'         => 'Brazzaville',
                'district'     => 'Poto-Poto',
                'country'      => 'Congo Brazzaville',
                'latitude'     => -4.2580,
                'longitude'    => 15.2475,
                'bedrooms'     => 1,
                'bathrooms'    => 1,
                'area'         => 35,
                'max_guests'   => 2,
                'status'       => 'disponible',
                'is_featured'  => 0,
                'is_approved'  => 1,
                'rating'       => 4.20,
                'reviews_count'=> 7,
                'views_count'  => 98,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => 4,
                'owner_id'     => 1,
                'title'        => 'Maison 5 Chambres — Résidence Fermée',
                'description'  => 'Grande maison familiale dans résidence sécurisée avec jardin et gardien 24h/24.',
                'type'         => 'maison',
                'price'        => 800000,
                'price_period' => 'mois',
                'currency'     => 'XAF',
                'address'      => 'Résidence les Jacarandas',
                'city'         => 'Brazzaville',
                'district'     => 'Bacongo',
                'country'      => 'Congo Brazzaville',
                'latitude'     => -4.2720,
                'longitude'    => 15.2355,
                'bedrooms'     => 5,
                'bathrooms'    => 3,
                'area'         => 320,
                'max_guests'   => 10,
                'status'       => 'disponible',
                'is_featured'  => 1,
                'is_approved'  => 1,
                'rating'       => 4.80,
                'reviews_count'=> 12,
                'views_count'  => 187,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => 5,
                'owner_id'     => 4,
                'title'        => 'Appartement Vue Fleuve Congo',
                'description'  => 'Appartement 2 chambres avec terrasse privée offrant une vue imprenable sur le fleuve Congo.',
                'type'         => 'appartement',
                'price'        => 650000,
                'price_period' => 'mois',
                'currency'     => 'XAF',
                'address'      => 'Boulevard du Maréchal Lyautey',
                'city'         => 'Brazzaville',
                'district'     => 'Centre-ville',
                'country'      => 'Congo Brazzaville',
                'latitude'     => -4.2595,
                'longitude'    => 15.2415,
                'bedrooms'     => 2,
                'bathrooms'    => 2,
                'area'         => 110,
                'max_guests'   => 4,
                'status'       => 'disponible',
                'is_featured'  => 1,
                'is_approved'  => 1,
                'rating'       => 4.95,
                'reviews_count'=> 31,
                'views_count'  => 423,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ]);

        // ── Property Images ──────────────────────────────────────
        DB::table('property_images')->insertOrIgnore([
            ['property_id' => 1, 'url' => 'https://images.unsplash.com/photo-1613977257363-707ba9348227?w=800', 'is_primary' => 1, 'sort_order' => 0],
            ['property_id' => 2, 'url' => 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800', 'is_primary' => 1, 'sort_order' => 0],
            ['property_id' => 3, 'url' => 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?w=800', 'is_primary' => 1, 'sort_order' => 0],
            ['property_id' => 4, 'url' => 'https://images.unsplash.com/photo-1580587771525-78b9dba3b914?w=800', 'is_primary' => 1, 'sort_order' => 0],
            ['property_id' => 5, 'url' => 'https://images.unsplash.com/photo-1502005229762-cf1b2da7c5d6?w=800', 'is_primary' => 1, 'sort_order' => 0],
        ]);

        // ── Property Amenities ───────────────────────────────────
        DB::table('property_amenities')->insertOrIgnore([
            ['property_id' => 1, 'name' => 'Piscine',        'icon' => 'fa-swimming-pool'],
            ['property_id' => 1, 'name' => 'Wi-Fi',          'icon' => 'fa-wifi'],
            ['property_id' => 1, 'name' => 'Climatisation',  'icon' => 'fa-snowflake'],
            ['property_id' => 1, 'name' => 'Parking',        'icon' => 'fa-car'],
            ['property_id' => 1, 'name' => 'Sécurité 24h',   'icon' => 'fa-shield-alt'],
            ['property_id' => 2, 'name' => 'Wi-Fi',          'icon' => 'fa-wifi'],
            ['property_id' => 2, 'name' => 'Climatisation',  'icon' => 'fa-snowflake'],
            ['property_id' => 2, 'name' => 'Parking',        'icon' => 'fa-car'],
            ['property_id' => 3, 'name' => 'Wi-Fi',          'icon' => 'fa-wifi'],
            ['property_id' => 3, 'name' => 'Cuisine équipée','icon' => 'fa-utensils'],
            ['property_id' => 4, 'name' => 'Jardin',         'icon' => 'fa-tree'],
            ['property_id' => 4, 'name' => 'Garage',         'icon' => 'fa-warehouse'],
            ['property_id' => 4, 'name' => 'Sécurité 24h',   'icon' => 'fa-shield-alt'],
            ['property_id' => 5, 'name' => 'Vue fleuve',     'icon' => 'fa-water'],
            ['property_id' => 5, 'name' => 'Terrasse',       'icon' => 'fa-sun'],
            ['property_id' => 5, 'name' => 'Wi-Fi',          'icon' => 'fa-wifi'],
        ]);

        // ── Bookings (FIX #7: base_amount, fees_amount, status FR) 
        DB::table('bookings')->insertOrIgnore([
            [
                'id'           => 1,
                'reference'    => 'BK-2025-AA001',
                'user_id'      => 2,
                'property_id'  => 2,
                'check_in'     => '2025-03-01',
                'check_out'    => '2025-04-01',
                'nights'       => 31,
                'guests'       => 2,
                'base_amount'  => 450000,    // FIX
                'fees_amount'  => 22500,     // FIX
                'total_amount' => 472500,
                'currency'     => 'XAF',
                'status'       => 'confirmé',  // FIX: enum FR
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => 2,
                'reference'    => 'BK-2025-AA002',
                'user_id'      => 3,
                'property_id'  => 1,
                'check_in'     => '2025-03-15',
                'check_out'    => '2025-04-15',
                'nights'       => 31,
                'guests'       => 4,
                'base_amount'  => 1500000,
                'fees_amount'  => 75000,
                'total_amount' => 1575000,
                'currency'     => 'XAF',
                'status'       => 'confirmé',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => 3,
                'reference'    => 'BK-2025-AA003',
                'user_id'      => 2,
                'property_id'  => 5,
                'check_in'     => '2025-04-01',
                'check_out'    => '2025-05-01',
                'nights'       => 30,
                'guests'       => 2,
                'base_amount'  => 650000,
                'fees_amount'  => 32500,
                'total_amount' => 682500,
                'currency'     => 'XAF',
                'status'       => 'en_attente',  // FIX
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => 4,
                'reference'    => 'BK-2025-AA004',
                'user_id'      => 3,
                'property_id'  => 3,
                'check_in'     => '2025-02-01',
                'check_out'    => '2025-03-01',
                'nights'       => 28,
                'guests'       => 1,
                'base_amount'  => 120000,
                'fees_amount'  => 6000,
                'total_amount' => 126000,
                'currency'     => 'XAF',
                'status'       => 'terminé',     // FIX
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ]);

        // ── Payments (FIX #8: method, provider_ref, phone, status FR)
        DB::table('payments')->insertOrIgnore([
            [
                'reference'    => 'PAY-2025-AA001',
                'booking_id'   => 1,
                'user_id'      => 2,
                'amount'       => 472500,
                'currency'     => 'XAF',
                'method'       => 'mtn_momo',       // FIX: method pas gateway
                'provider_ref' => 'MTN-TXN-001',    // FIX: provider_ref pas gateway_ref
                'phone'        => '+242055987654',  // FIX: phone pas phone_number
                'status'       => 'succès',         // FIX: enum FR
                'paid_at'      => now(),
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'reference'    => 'PAY-2025-AA002',
                'booking_id'   => 2,
                'user_id'      => 3,
                'amount'       => 1575000,
                'currency'     => 'XAF',
                'method'       => 'airtel_money',
                'provider_ref' => 'AIR-TXN-001',
                'phone'        => '+242066543210',
                'status'       => 'succès',
                'paid_at'      => now(),
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'reference'    => 'PAY-2025-AA003',
                'booking_id'   => 4,
                'user_id'      => 3,
                'amount'       => 126000,
                'currency'     => 'XAF',
                'method'       => 'mtn_momo',
                'provider_ref' => 'MTN-TXN-002',
                'phone'        => '+242066543210',
                'status'       => 'succès',
                'paid_at'      => now(),
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ]);

        // ── Reviews ──────────────────────────────────────────────
        DB::table('reviews')->insertOrIgnore([
            ['user_id' => 3, 'property_id' => 3, 'booking_id' => 4, 'rating' => 5, 'comment' => 'Studio propre et bien situé. Je recommande vraiment !', 'is_visible' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => 2, 'property_id' => 2, 'booking_id' => 1, 'rating' => 5, 'comment' => 'Appartement superbe, exactement comme les photos. Hôte très réactif.', 'is_visible' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => 3, 'property_id' => 1, 'booking_id' => 2, 'rating' => 5, 'comment' => 'Villa exceptionnelle, la piscine est magnifique ! On reviendra.', 'is_visible' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── Transactions (comptabilité) ───────────────────────────
        DB::table('transactions')->insertOrIgnore([
            ['type' => 'revenue',    'amount' => 472500,  'currency' => 'XAF', 'category' => 'location',    'description' => 'Réservation BK-2025-AA001', 'reference' => 'PAY-2025-AA001', 'booking_id' => 1, 'date' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'commission', 'amount' => 22500,   'currency' => 'XAF', 'category' => 'commission',  'description' => 'Commission 5% — BK-2025-AA001', 'reference' => 'PAY-2025-AA001', 'booking_id' => 1, 'date' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'revenue',    'amount' => 1575000, 'currency' => 'XAF', 'category' => 'location',    'description' => 'Réservation BK-2025-AA002', 'reference' => 'PAY-2025-AA002', 'booking_id' => 2, 'date' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'commission', 'amount' => 75000,   'currency' => 'XAF', 'category' => 'commission',  'description' => 'Commission 5% — BK-2025-AA002', 'reference' => 'PAY-2025-AA002', 'booking_id' => 2, 'date' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'expense',    'amount' => 85000,   'currency' => 'XAF', 'category' => 'maintenance', 'description' => 'Réparation climatisation — Villa Luxe', 'date' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'revenue',    'amount' => 126000,  'currency' => 'XAF', 'category' => 'location',    'description' => 'Réservation BK-2025-AA004', 'reference' => 'PAY-2025-AA003', 'booking_id' => 4, 'date' => now(), 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── Support tickets de démonstration ─────────────────────
        DB::table('support_tickets')->insertOrIgnore([
            ['user_id' => 2, 'subject' => 'Problème de paiement MTN', 'message' => 'Mon paiement MTN MoMo a été débité mais la réservation est toujours en attente.', 'category' => 'payment', 'status' => 'ouvert', 'priority' => 'haute', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => 3, 'subject' => 'Question sur la caution', 'message' => 'Est-ce qu\'une caution est requise pour la villa ?', 'category' => 'booking', 'status' => 'résolu', 'priority' => 'normale', 'admin_reply' => 'Bonjour, aucune caution n\'est requise. Bonne location !', 'replied_at' => now(), 'closed_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->command->info('✅ Base de données seeded avec succès !');
        $this->command->info('   → Admin: admin@immostay.com / Admin@1234');
        $this->command->info('   → 4 utilisateurs, 5 propriétés, 4 réservations, 3 paiements');
    }
}
