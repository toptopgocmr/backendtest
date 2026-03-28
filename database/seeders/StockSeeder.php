<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StockCategory;
use App\Models\StockItem;

class StockSeeder extends Seeder
{
    public function run(): void
    {
        // ── Catégories ────────────────────────────────────────────
        $categories = [
            ['name' => 'Hygiène & Sanitaire', 'icon' => 'soap',         'color' => '#3B82F6'],
            ['name' => 'Linge & Literie',     'icon' => 'bed',          'color' => '#7C3AED'],
            ['name' => 'Cuisine',              'icon' => 'utensils',     'color' => '#F59E0B'],
            ['name' => 'Entretien',            'icon' => 'broom',        'color' => '#10B981'],
            ['name' => 'Électricité',          'icon' => 'bolt',         'color' => '#EF4444'],
            ['name' => 'Papeterie / Bureau',   'icon' => 'file-alt',     'color' => '#64748B'],
            ['name' => 'Divers',               'icon' => 'box',          'color' => '#9CA3AF'],
        ];

        foreach ($categories as $cat) {
            StockCategory::firstOrCreate(['name' => $cat['name']], $cat);
        }

        // ── Articles de base ──────────────────────────────────────
        $hygiene = StockCategory::where('name', 'Hygiène & Sanitaire')->first();
        $linge   = StockCategory::where('name', 'Linge & Literie')->first();
        $cuisine = StockCategory::where('name', 'Cuisine')->first();
        $entret  = StockCategory::where('name', 'Entretien')->first();
        $elec    = StockCategory::where('name', 'Électricité')->first();

        $items = [
            // Hygiène
            ['category_id'=>$hygiene->id,'name'=>'Papier toilette','unit'=>'rouleau','quantity_current'=>50,'quantity_minimum'=>10,'quantity_optimal'=>100,'unit_price'=>250,'reference'=>'HYG-001'],
            ['category_id'=>$hygiene->id,'name'=>'Savon liquide mains','unit'=>'litre','quantity_current'=>15,'quantity_minimum'=>3,'quantity_optimal'=>30,'unit_price'=>2000,'reference'=>'HYG-002'],
            ['category_id'=>$hygiene->id,'name'=>'Gel douche','unit'=>'unité','quantity_current'=>20,'quantity_minimum'=>5,'quantity_optimal'=>40,'unit_price'=>1500,'reference'=>'HYG-003'],
            ['category_id'=>$hygiene->id,'name'=>'Shampoing','unit'=>'unité','quantity_current'=>15,'quantity_minimum'=>4,'quantity_optimal'=>30,'unit_price'=>2000,'reference'=>'HYG-004'],
            ['category_id'=>$hygiene->id,'name'=>'Papier essuie-mains','unit'=>'rouleau','quantity_current'=>30,'quantity_minimum'=>8,'quantity_optimal'=>60,'unit_price'=>400,'reference'=>'HYG-005'],

            // Linge
            ['category_id'=>$linge->id,'name'=>'Serviettes bain','unit'=>'unité','quantity_current'=>40,'quantity_minimum'=>10,'quantity_optimal'=>80,'unit_price'=>5000,'reference'=>'LIN-001'],
            ['category_id'=>$linge->id,'name'=>'Draps de lit (set)','unit'=>'set','quantity_current'=>25,'quantity_minimum'=>6,'quantity_optimal'=>50,'unit_price'=>15000,'reference'=>'LIN-002'],
            ['category_id'=>$linge->id,'name'=>'Oreillers','unit'=>'unité','quantity_current'=>30,'quantity_minimum'=>8,'quantity_optimal'=>60,'unit_price'=>8000,'reference'=>'LIN-003'],

            // Cuisine
            ['category_id'=>$cuisine->id,'name'=>'Liquide vaisselle','unit'=>'litre','quantity_current'=>10,'quantity_minimum'=>3,'quantity_optimal'=>25,'unit_price'=>1500,'reference'=>'CUI-001'],
            ['category_id'=>$cuisine->id,'name'=>'Éponges','unit'=>'paquet','quantity_current'=>20,'quantity_minimum'=>5,'quantity_optimal'=>40,'unit_price'=>500,'reference'=>'CUI-002'],
            ['category_id'=>$cuisine->id,'name'=>'Sacs poubelle (rouleau)','unit'=>'rouleau','quantity_current'=>15,'quantity_minimum'=>4,'quantity_optimal'=>30,'unit_price'=>800,'reference'=>'CUI-003'],

            // Entretien
            ['category_id'=>$entret->id,'name'=>'Désinfectant multi-surface','unit'=>'litre','quantity_current'=>8,'quantity_minimum'=>2,'quantity_optimal'=>20,'unit_price'=>3000,'reference'=>'ENT-001'],
            ['category_id'=>$entret->id,'name'=>'Produit WC','unit'=>'unité','quantity_current'=>12,'quantity_minimum'=>3,'quantity_optimal'=>25,'unit_price'=>1200,'reference'=>'ENT-002'],
            ['category_id'=>$entret->id,'name'=>'Lessive (kg)','unit'=>'kg','quantity_current'=>20,'quantity_minimum'=>5,'quantity_optimal'=>50,'unit_price'=>2500,'reference'=>'ENT-003'],

            // Électricité
            ['category_id'=>$elec->id,'name'=>'Ampoules LED','unit'=>'unité','quantity_current'=>30,'quantity_minimum'=>5,'quantity_optimal'=>60,'unit_price'=>1500,'reference'=>'ELC-001'],
            ['category_id'=>$elec->id,'name'=>'Piles AA','unit'=>'paquet','quantity_current'=>10,'quantity_minimum'=>3,'quantity_optimal'=>20,'unit_price'=>1000,'reference'=>'ELC-002'],
        ];

        foreach ($items as $item) {
            StockItem::firstOrCreate(
                ['reference' => $item['reference']],
                array_merge($item, ['is_active' => true])
            );
        }

        // Vérifier et créer alertes si nécessaire
        StockItem::all()->each(fn($i) => $i->checkAndCreateAlert());

        $this->command->info('✅ Stock seedé : ' . count($categories) . ' catégories, ' . count($items) . ' articles.');
    }
}
