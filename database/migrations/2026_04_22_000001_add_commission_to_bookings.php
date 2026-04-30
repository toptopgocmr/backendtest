<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration : ajout des colonnes de commission Tholad dans la table bookings.
 *
 * commission_rate          → taux appliqué (ex: 10.00)
 * owner_commission_amount  → montant prélevé sur le propriétaire
 * owner_amount             → montant reversé au propriétaire après commission
 *
 * Ces colonnes sont purement comptables : elles ne modifient pas le total_amount
 * payé par le client.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->default(10.00)->after('total_amount')
                  ->comment('Taux de commission Tholad (%)');
            $table->decimal('owner_commission_amount', 12, 2)->default(0)->after('commission_rate')
                  ->comment('Montant commission déduit du propriétaire');
            $table->decimal('owner_amount', 12, 2)->default(0)->after('owner_commission_amount')
                  ->comment('Montant reversé au propriétaire après commission');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['commission_rate', 'owner_commission_amount', 'owner_amount']);
        });
    }
};
