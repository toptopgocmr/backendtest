<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Reviews ─────────────────────────────────────────────
        Schema::create('reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('property_id')->constrained()->cascadeOnDelete();
            $t->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $t->tinyInteger('rating')->default(5);
            $t->tinyInteger('rating_location')->nullable();
            $t->tinyInteger('rating_cleanliness')->nullable();
            $t->tinyInteger('rating_value')->nullable();
            $t->text('comment')->nullable();
            $t->text('owner_reply')->nullable();
            $t->boolean('is_visible')->default(true);
            $t->timestamps();
        });

        // ── Favorites ────────────────────────────────────────────
        Schema::create('favorites', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('property_id')->constrained()->cascadeOnDelete();
            $t->timestamp('created_at')->useCurrent();
            $t->unique(['user_id','property_id']);
        });

        // ── Conversations ────────────────────────────────────────
        Schema::create('conversations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user1_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('user2_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $t->text('last_message')->nullable();
            $t->timestamp('last_message_at')->nullable();
            $t->integer('user1_unread')->default(0);
            $t->integer('user2_unread')->default(0);
            $t->timestamps();
            $t->unique(['user1_id','user2_id']);
        });

        // ── Messages ─────────────────────────────────────────────
        Schema::create('messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $t->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $t->text('content');
            $t->enum('type', ['text','image','document'])->default('text');
            $t->string('attachment_url', 191)->nullable();
            $t->boolean('is_read')->default(false);
            $t->timestamp('read_at')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        // ── Notifications ────────────────────────────────────────
        Schema::create('notifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('title', 191);
            $t->text('body');
            $t->enum('type', ['booking','payment','message','review','system','promotion'])->default('system');
            $t->json('data')->nullable();
            $t->boolean('is_read')->default(false);
            $t->timestamp('read_at')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        // ── Support Tickets ──────────────────────────────────────
        Schema::create('support_tickets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('subject', 191);
            $t->text('message');
            $t->enum('category', ['booking','payment','technical','other'])->default('other');
            $t->enum('status', ['ouvert','en_cours','résolu','fermé'])->default('ouvert');
            $t->enum('priority', ['basse','normale','haute','urgente'])->default('normale');
            $t->text('admin_reply')->nullable();
            $t->timestamp('replied_at')->nullable();
            $t->timestamp('closed_at')->nullable();
            $t->timestamps();
        });

        // ── Transactions ─────────────────────────────────────────
        Schema::create('transactions', function (Blueprint $t) {
            $t->id();
            $t->enum('type', ['revenue','expense','commission','refund']);
            $t->decimal('amount', 12, 2);
            $t->string('currency', 10)->default('XAF');
            $t->string('category', 100);
            $t->text('description')->nullable();
            $t->string('reference', 191)->nullable();
            $t->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $t->date('date');
            $t->timestamps();
        });

        // ── Employees ────────────────────────────────────────────
        Schema::create('employees', function (Blueprint $t) {
            $t->id();
            $t->string('name', 191);
            $t->string('email', 191)->nullable();
            $t->string('phone', 30)->nullable();
            $t->string('position', 100);
            $t->string('department', 100)->nullable();
            $t->decimal('salary', 12, 2)->default(0);
            $t->date('hire_date');
            $t->enum('status', ['actif','congé','terminé'])->default('actif');
            $t->timestamps();
        });

        // ── Personal Access Tokens (Sanctum) ─────────────────────
        Schema::create('personal_access_tokens', function (Blueprint $t) {
            $t->id();
            $t->string('tokenable_type', 191);
            $t->unsignedBigInteger('tokenable_id');
            $t->index(['tokenable_type','tokenable_id']);
            $t->string('name', 191);
            $t->string('token', 64)->unique();
            $t->text('abilities')->nullable();
            $t->timestamp('last_used_at')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('reviews');
    }
};