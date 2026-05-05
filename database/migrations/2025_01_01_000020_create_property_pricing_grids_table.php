<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guard : ne pas recréer si déjà existante
        if (Schema::hasTable('property_pricing_grids')) {
            return;
        }

        Schema::create('property_pricing_grids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')
                  ->constrained('properties')
                  ->cascadeOnDelete();

            $table->enum('period', ['heure', 'jour', 'nuit', 'semaine', 'mois', 'an']);
            $table->unsignedBigInteger('price');           // en XAF, pas de décimale
            $table->unsignedSmallInteger('min_duration')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Une propriété ne peut avoir qu'un seul tarif par période
            $table->unique(['property_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_pricing_grids');
    }
};
