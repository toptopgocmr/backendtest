<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ⚠️ sécurité : éviter erreur si colonne existe déjà
        if (!Schema::hasColumn('payments', 'proof_image')) {

            Schema::table('payments', function (Blueprint $table) {
                $table->string('proof_image')
                      ->nullable()
                      ->after('phone');
            });

        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ⚠️ sécurité : éviter crash si rollback
        if (Schema::hasColumn('payments', 'proof_image')) {

            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn('proof_image');
            });

        }
    }
};