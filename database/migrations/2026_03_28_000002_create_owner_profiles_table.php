<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration 2 — Table des propriétaires (owner_profiles)
 * Complète le rôle 'owner' du modèle User avec un profil détaillé
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('owner_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Informations personnelles / société
            $table->string('company_name')->nullable();
            $table->string('siret', 50)->nullable();
            $table->string('legal_form', 100)->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->string('contact_email')->nullable();

            // Adresse
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->default('Congo Brazzaville');

            // Documents
            $table->string('id_document_type', 50)->nullable();
            $table->string('id_document_number', 100)->nullable();
            $table->string('id_document_file')->nullable();
            $table->string('kbis_file')->nullable();
            $table->date('id_document_expiry')->nullable();

            // Financier
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account', 100)->nullable();
            $table->string('mobile_money_number', 30)->nullable();
            // FIX: la valeur par défaut était 'mobile_money_number' (nom de colonne) au lieu d'une valeur valide de l'enum
            $table->enum('preferred_payment', ['bank', 'mtn_momo', 'airtel_money', 'cash'])->default('mtn_momo');
            $table->decimal('commission_rate', 5, 2)->default(10.00);

            // Statut
            $table->enum('status', ['en_attente', 'vérifié', 'suspendu'])->default('en_attente');
            $table->boolean('is_verified')->default(false);
            $table->text('notes')->nullable();
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_profiles');
    }
};
