<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('XAF');
            $table->string('payment_method')->default('mobile_money'); // mtn_momo, airtel_money
            $table->string('phone_number', 20)->nullable();    // numéro utilisé pour payer
            $table->string('transaction_id', 100)->nullable(); // ID transaction du client
            $table->string('proof_image')->nullable();         // chemin image preuve
            $table->enum('status', [
                'en_attente',               // client n'a pas encore payé
                'en_attente_confirmation',  // client a soumis la preuve, en attente admin
                'succes',                   // admin a validé
                'echoue',                   // admin a refusé
                'rembourse',                // remboursement effectué
            ])->default('en_attente');
            $table->text('admin_note')->nullable();            // note de l'admin (motif refus, etc.)
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'status']);
            $table->index('transaction_id');
        });

        // Ajouter le statut à la table bookings si elle existe
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'payment_status')) {
                $table->enum('payment_status', [
                    'non_paye',
                    'en_attente_confirmation',
                    'paye',
                    'rembourse',
                ])->default('non_paye')->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('payment_status');
        });
    }
};
