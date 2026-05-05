<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique();
            $t->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->decimal('amount', 12, 2);
            $t->string('currency', 10)->default('XAF');
            $t->enum('method', ['mtn_momo','airtel_money','orange_money','wave','carte','virement'])->default('mtn_momo');
            $t->string('provider_ref', 100)->nullable();
            $t->string('phone', 30)->nullable();
            $t->string('proof_image')->nullable();
            $t->enum('status', [
                'en_attente',
                'en_attente_confirmation',
                'succès',
                'échoué',
                'remboursé',
            ])->default('en_attente');
            $t->text('admin_note')->nullable();
            $t->unsignedBigInteger('verified_by')->nullable();
            $t->timestamp('verified_at')->nullable();
            $t->text('refund_reason')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->timestamp('refunded_at')->nullable();
            $t->json('gateway_response')->nullable();
            $t->timestamps();

            $t->index(['booking_id', 'status']);
            $t->index('provider_ref');
        });
    }

    public function down(): void {
        Schema::dropIfExists('payments');
    }
};
