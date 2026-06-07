<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ajouter 'pending_payment' à l'ENUM avant l'UPDATE
        DB::statement("
            ALTER TABLE bookings
            MODIFY COLUMN status ENUM(
                'pending_payment',
                'en_attente',
                'confirmé',
                'terminé',
                'annulé'
            ) NOT NULL DEFAULT 'pending_payment'
        ");

        // 2. Passer les réservations sans paiement → pending_payment
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
        // Remettre pending_payment → en_attente
        DB::statement("
            UPDATE bookings SET status = 'en_attente' WHERE status = 'pending_payment'
        ");

        // Retirer pending_payment de l'ENUM
        DB::statement("
            ALTER TABLE bookings
            MODIFY COLUMN status ENUM(
                'en_attente',
                'confirmé',
                'terminé',
                'annulé'
            ) NOT NULL DEFAULT 'en_attente'
        ");
    }
};
