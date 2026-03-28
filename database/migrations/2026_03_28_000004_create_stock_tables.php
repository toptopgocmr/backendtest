<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration 4 — Gestion des stocks
 * Articles (papier toilette, savon, serviettes, etc.) par propriété
 */
return new class extends Migration {
    public function up(): void
    {
        // Catégories de stock
        Schema::create('stock_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);            // Ex: Hygiène, Cuisine, Entretien
            $table->string('icon', 50)->default('box');
            $table->string('color', 20)->default('#3B82F6');
            $table->timestamps();
        });

        // Articles en stock
        Schema::create('stock_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('stock_categories')->cascadeOnDelete();
            $table->foreignId('property_id')->nullable()->constrained('properties')->nullOnDelete();
            // null = stock global (entrepôt central)

            $table->string('name');                 // Ex: Papier toilette
            $table->string('reference', 100)->nullable(); // Code article
            $table->string('unit', 30)->default('unité'); // unité, rouleau, litre, kg...
            $table->text('description')->nullable();

            // Quantités
            $table->decimal('quantity_current', 10, 2)->default(0);   // Stock actuel
            $table->decimal('quantity_minimum', 10, 2)->default(5);   // Seuil d'alerte
            $table->decimal('quantity_optimal', 10, 2)->default(20);  // Quantité idéale

            // Prix
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->enum('currency', ['XAF','USD','EUR'])->default('XAF');
            $table->string('supplier', 150)->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Mouvements de stock (entrées/sorties)
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->foreignId('property_id')->nullable()->constrained('properties')->nullOnDelete();

            $table->enum('type', ['entrée','sortie','transfert','inventaire','perte']);
            $table->decimal('quantity', 10, 2);
            $table->decimal('quantity_before', 10, 2);
            $table->decimal('quantity_after', 10, 2);
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->string('reason')->nullable();    // Motif
            $table->string('reference', 100)->nullable(); // N° bon de livraison
            $table->text('notes')->nullable();
            $table->timestamp('movement_date')->useCurrent();
            $table->timestamps();
        });

        // Alertes stock (log des alertes envoyées)
        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_item_id')->constrained()->cascadeOnDelete();
            $table->enum('level', ['warning','critical']); // warning = proche du min, critical = en dessous
            $table->decimal('quantity_at_alert', 10, 2);
            $table->boolean('is_read')->default(false);
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_alerts');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_items');
        Schema::dropIfExists('stock_categories');
    }
};
