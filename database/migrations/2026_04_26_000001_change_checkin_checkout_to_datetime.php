<?php
// database/migrations/2026_04_26_000001_change_checkin_checkout_to_datetime.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $t) {
            $t->dateTime('check_in')->change();
            $t->dateTime('check_out')->change();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $t) {
            $t->date('check_in')->change();
            $t->date('check_out')->change();
        });
    }
};
