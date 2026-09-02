<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('maisons', function (Blueprint $table) {
            // Modification du type de numero_porte pour supporter les codes alphanumériques
            $table->string('numero_porte', 50)->nullable()->change();

            // Ajout des colonnes de caractéristiques du bâtiment issues de l'application mobile
            $table->integer('annee_construction')->nullable()->after('type_construction_id');
            $table->integer('nombre_pieces')->nullable()->after('annee_construction');
            $table->integer('nombre_etages')->nullable()->after('nombre_pieces');
            $table->string('occupation', 100)->nullable()->after('nombre_etages');
            $table->string('materiau_murs', 100)->nullable()->after('occupation');
            $table->string('materiau_toiture', 100)->nullable()->after('materiau_murs');
            $table->string('materiau_sol', 100)->nullable()->after('materiau_toiture');
            $table->string('etat_general', 100)->nullable()->after('materiau_sol');
            $table->string('acces_voirie', 100)->nullable()->after('gestion_dechet_id');
            $table->string('acces_internet', 100)->nullable()->after('acces_voirie');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maisons', function (Blueprint $table) {
            $table->dropColumn([
                'annee_construction',
                'nombre_pieces',
                'nombre_etages',
                'occupation',
                'materiau_murs',
                'materiau_toiture',
                'materiau_sol',
                'etat_general',
                'acces_voirie',
                'acces_internet',
            ]);
        });
    }
};
