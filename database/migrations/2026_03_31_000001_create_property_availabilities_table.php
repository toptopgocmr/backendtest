<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FIX : Table manquante 'property_availabilities'
 * Référencée dans BookingController ligne 80 mais jamais créée.
 * Stocke les dates d'indisponibilité par propriété.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('property_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')
                  ->constrained('properties')
                  ->cascadeOnDelete();
            $table->date('unavailable_date');
            $table->string('reason', 100)->nullable(); // ex: maintenance, déjà réservé
            $table->timestamps();

            // Index pour accélérer les requêtes de disponibilité
            $table->index(['property_id', 'unavailable_date']);
            // Éviter les doublons
            $table->unique(['property_id', 'unavailable_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_availabilities');
    }
};
