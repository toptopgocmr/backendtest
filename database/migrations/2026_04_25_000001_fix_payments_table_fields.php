<?php
// database/migrations/2026_04_25_000001_fix_payments_table_fields.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Corrections sur la table payments :
     * 1. Ajouter 'en_attente_confirmation' à l'enum status (manquant en migration originale)
     * 2. Ajouter colonne admin_note (utilisée dans le modèle/contrôleur)
     * 3. Ajouter colonne verified_by + verified_at (utilisées dans le modèle)
     * 4. S'assurer que proof_image existe (sécurité si migration précédente a échoué)
     */
    public function up(): void
    {
        // ── 1. Corriger l'enum status ─────────────────────────────────────────
        // MySQL ne supporte pas ALTER COLUMN sur enum facilement → on utilise raw SQL
        DB::statement("
            ALTER TABLE payments
            MODIFY COLUMN status ENUM(
                'en_attente',
                'en_attente_confirmation',
                'succès',
                'échoué',
                'remboursé'
            ) NOT NULL DEFAULT 'en_attente'
        ");

        Schema::table('payments', function (Blueprint $table) {

            // ── 2. Colonne admin_note ─────────────────────────────────────────
            if (!Schema::hasColumn('payments', 'admin_note')) {
                $table->text('admin_note')->nullable()->after('proof_image');
            }

            // ── 3. Colonnes verified_by + verified_at ─────────────────────────
            if (!Schema::hasColumn('payments', 'verified_by')) {
                $table->unsignedBigInteger('verified_by')->nullable()->after('admin_note');
            }
            if (!Schema::hasColumn('payments', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verified_by');
            }

            // ── 4. proof_image (sécurité) ─────────────────────────────────────
            if (!Schema::hasColumn('payments', 'proof_image')) {
                $table->string('proof_image')->nullable()->after('phone');
            }
        });
    }

    public function down(): void
    {
        // Rollback : retour à l'enum d'origine sans en_attente_confirmation
        DB::statement("
            ALTER TABLE payments
            MODIFY COLUMN status ENUM(
                'en_attente',
                'succès',
                'échoué',
                'remboursé'
            ) NOT NULL DEFAULT 'en_attente'
        ");

        Schema::table('payments', function (Blueprint $table) {
            foreach (['admin_note', 'verified_by', 'verified_at'] as $col) {
                if (Schema::hasColumn('payments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
