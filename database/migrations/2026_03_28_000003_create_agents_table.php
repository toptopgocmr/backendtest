<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration 3 — Table des agents TholadImmo
 * Agents commerciaux / gestionnaires internes
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();

            // Lien vers admins ou users (flexible)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Identité
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 30)->nullable();
            $table->string('avatar')->nullable();
            $table->string('password');

            // Poste
            $table->enum('role', [
                'agent_commercial',
                'gestionnaire',
                'comptable',
                'technicien',
                'superviseur',
                'directeur',
            ])->default('agent_commercial');
            $table->string('department', 100)->nullable();    // Service / département
            $table->string('employee_id', 50)->nullable();    // Matricule
            $table->date('hire_date')->nullable();
            $table->decimal('salary', 12, 2)->nullable();
            $table->enum('salary_currency', ['XAF','USD','EUR'])->default('XAF');

            // Permissions
            $table->boolean('can_manage_properties')->default(true);
            $table->boolean('can_manage_bookings')->default(true);
            $table->boolean('can_manage_payments')->default(false);
            $table->boolean('can_manage_stock')->default(true);
            $table->boolean('can_view_reports')->default(false);

            // Contact d'urgence
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();

            // Statut
            $table->enum('status', ['actif','inactif','suspendu','congé'])->default('actif');
            $table->text('notes')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
