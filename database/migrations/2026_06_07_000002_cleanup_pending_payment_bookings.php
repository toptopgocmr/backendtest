<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Passer les réservations 'en_attente' sans paiement → 'pending_payment'
        // pour nettoyer les réservations orphelines déjà en base
        DB::statement("
            UPDATE bookings
            SET status = 'pending_payment'
            WHERE status = 'en_attente'
            AND id NOT IN (
                SELECT booking_id FROM payments
                WHERE status IN ('en_attente', 'en_attente_confirmation', 'succès', 'remboursé')
            )
        ");
    }

    public function down(): void
    {
        // Rollback : remettre pending_payment → en_attente
        DB::statement("UPDATE bookings SET status = 'en_attente' WHERE status = 'pending_payment'");
    }
};
