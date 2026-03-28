<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration 1 — Mise à jour du type enum properties
 * Ajoute : bureau, salle_reunion, appartement, villa, studio, maison,
 *           chambre, terrain, entrepot, commerce, autres
 */
return new class extends Migration {
    public function up(): void
    {
        // SQLite ne supporte pas ALTER COLUMN sur enum,
        // on utilise une colonne string à la place (compatible SQLite + MySQL)
        // Si vous êtes sur MySQL, vous pouvez utiliser un ALTER TABLE MODIFY.

        // Pour MySQL / PostgreSQL :
        // DB::statement("ALTER TABLE properties MODIFY type ENUM(
        //   'appartement','villa','studio','maison','chambre','bureau',
        //   'salle_reunion','terrain','entrepot','commerce','autres'
        // ) DEFAULT 'appartement'");

        // Compatible SQLite (et MySQL via string) :
        Schema::table('properties', function (Blueprint $table) {
            // On ajoute la colonne property_category pour les nouveaux types
            // sans casser la colonne type existante
            $table->string('property_category', 50)->default('appartement')->after('type');
            // Superficie en m² supplémentaire pour bureaux/salles
            $table->integer('capacity')->nullable()->after('max_guests'); // capacité salle réunion
            $table->string('floor')->nullable()->after('capacity'); // étage
            $table->boolean('has_parking')->default(false)->after('floor');
            $table->boolean('has_reception')->default(false)->after('has_parking');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['property_category','capacity','floor','has_parking','has_reception']);
        });
    }
};
