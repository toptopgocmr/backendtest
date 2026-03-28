<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE properties MODIFY COLUMN price_period ENUM(
            'heure',
            'nuit',
            'jour',
            'semaine',
            'mois',
            'an',
            'total'
        ) DEFAULT 'mois'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE properties MODIFY COLUMN price_period ENUM(
            'nuit','semaine','mois','an'
        ) DEFAULT 'mois'");
    }
};