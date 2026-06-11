<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Changer la valeur par défaut de max_guests : 2 → 1
        Schema::table('properties', function (Blueprint $table) {
            $table->unsignedSmallInteger('max_guests')->default(1)->change();
        });

        // 2. Corriger les enregistrements existants qui ont encore la valeur
        //    par défaut de 2 ET dont capacity est null (non renseignée).
        //    On les passe à 1 pour ne pas bloquer les propriétaires qui
        //    devront renseigner la vraie capacité dans le back-office.
        DB::table('properties')
            ->where('max_guests', 2)
            ->whereNull('capacity')
            ->update(['max_guests' => 1]);
    }

    public function down(): void
    {
        // Rétablir l'ancien défaut
        Schema::table('properties', function (Blueprint $table) {
            $table->unsignedSmallInteger('max_guests')->default(2)->change();
        });
    }
};
