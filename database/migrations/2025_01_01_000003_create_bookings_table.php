<?php
// 2025_01_01_000003_create_bookings_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bookings', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 25)->unique();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('property_id')->constrained()->cascadeOnDelete();
            $t->date('check_in');
            $t->date('check_out');
            $t->integer('nights')->default(1);
            $t->tinyInteger('guests')->default(1);
            $t->decimal('base_amount', 12, 2);
            $t->decimal('fees_amount', 12, 2)->default(0);
            $t->decimal('total_amount', 12, 2);
            $t->string('currency', 10)->default('XAF');
            $t->enum('status', ['en_attente','confirmé','annulé','terminé'])->default('en_attente');
            $t->text('notes')->nullable();
            $t->timestamp('cancelled_at')->nullable();
            $t->text('cancel_reason')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('bookings'); }
};
