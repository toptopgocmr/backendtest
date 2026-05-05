<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('properties', function (Blueprint $t) {
            $t->id();
            $t->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $t->string('title');
            $t->text('description')->nullable();
            $t->enum('type', ['appartement','villa','studio','maison','chambre','bureau','terrain'])->default('appartement');
            $t->decimal('price', 12, 2);
            $t->enum('price_period', ['nuit','semaine','mois','an'])->default('mois');
            $t->string('currency', 10)->default('XAF');
            $t->string('address')->nullable();
            $t->string('city', 100);
            $t->string('district', 100)->nullable();
            $t->string('country', 100)->default('Congo Brazzaville');
            $t->decimal('latitude',  10, 8)->nullable();
            $t->decimal('longitude', 11, 8)->nullable();
            $t->tinyInteger('bedrooms')->default(1);
            $t->tinyInteger('bathrooms')->default(1);
            $t->decimal('area', 8, 2)->nullable();
            $t->tinyInteger('max_guests')->default(2);
            $t->enum('status', ['disponible','occupé','maintenance','suspendu'])->default('disponible');
            $t->boolean('is_featured')->default(false);
            $t->boolean('is_approved')->default(false);
            $t->decimal('rating', 3, 2)->default(0);
            $t->integer('reviews_count')->default(0);
            $t->integer('views_count')->default(0);
            $t->timestamps();
        });

        Schema::create('property_images', function (Blueprint $t) {
            $t->id();
            $t->foreignId('property_id')->constrained()->cascadeOnDelete();
            $t->string('url');
            $t->boolean('is_primary')->default(false);
            $t->tinyInteger('sort_order')->default(0);
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('property_amenities', function (Blueprint $t) {
            $t->id();
            $t->foreignId('property_id')->constrained()->cascadeOnDelete();
            $t->string('name', 100);
            $t->string('icon', 50)->default('check-circle');
        });

        // NOTE : property_availability (ancienne table) supprimée ici.
        // Utiliser la migration 2026_03_31_000001_create_property_availabilities_table.php
        // qui crée 'property_availabilities' (avec 's'), la version canonique.
    }

    public function down(): void {
        Schema::dropIfExists('property_amenities');
        Schema::dropIfExists('property_images');
        Schema::dropIfExists('properties');
    }
};
