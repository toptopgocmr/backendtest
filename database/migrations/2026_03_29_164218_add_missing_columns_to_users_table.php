<?php
// database/migrations/TIMESTAMP_add_missing_columns_to_users_table.php
// Ajoute les colonnes manquantes à la table users
// Commande: php artisan migrate

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('client')->after('country');
            }
            if (!Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable()->after('role');
            }
            if (!Schema::hasColumn('users', 'is_verified')) {
                $table->boolean('is_verified')->default(false)->after('avatar');
            }
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(false)->after('is_verified');
            }
            if (!Schema::hasColumn('users', 'otp_code')) {
                $table->string('otp_code', 10)->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('users', 'otp_expires_at')) {
                $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
            }
            if (!Schema::hasColumn('users', 'device_token')) {
                $table->string('device_token')->nullable()->after('otp_expires_at');
            }
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('device_token');
            }
        });

        // Corriger les valeurs NULL pour les colonnes booléennes et role
        DB::table('users')->whereNull('role')->update(['role' => 'client']);
        DB::table('users')->whereNull('is_verified')->update(['is_verified' => 0]);
        DB::table('users')->whereNull('is_active')->update(['is_active' => 0]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = ['first_name','last_name','role','avatar','is_verified',
                        'is_active','otp_code','otp_expires_at','device_token','last_login_at'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
