<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email', 191)->nullable()->unique(); // <- limité à 191 caractères
            $t->string('phone', 30)->unique();
            $t->string('country_code', 10)->default('+242');
            $t->string('country', 100)->default('Congo Brazzaville');
            $t->string('avatar')->nullable();
            $t->enum('role', ['client','owner','admin'])->default('client');
            $t->boolean('is_verified')->default(false);
            $t->boolean('is_active')->default(false);
            $t->string('password');
            $t->string('remember_token', 100)->nullable();
            $t->timestamp('email_verified_at')->nullable();
            $t->timestamp('phone_verified_at')->nullable();
            $t->string('otp_code', 10)->nullable();
            $t->timestamp('otp_expires_at')->nullable();
            $t->string('fcm_token')->nullable();
            $t->timestamp('last_login_at')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('users'); }
};