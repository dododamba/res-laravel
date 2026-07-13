<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exécute les migrations de la hiérarchie géographique et des tables paramétriques.
     */
    public function up(): void
    {
        // 1. Table des Fonctions d'Agents (Fonction d'affectation)
        Schema::create('fonctions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('uuid', 36)->unique();
            $table->string('nom', 255);
            $table->string('code', 50)->nullable()->unique();
            $table->text('description')->nullable();
            $table->string('slug', 255)->unique();
            $table->integer('ordre_affichage')->default(0);
            $table->string('couleur', 7)->nullable();
            $table->string('icone', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->string('created_by', 255)->nullable();
            $table->string('updated_by', 255)->nullable();
            $table->string('deleted_by', 255)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // 2. Table des Quartiers
        Schema::create('quartiers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('uuid', 36)->unique();
            $table->string('nom', 255);
            $table->string('code', 50)->nullable()->unique();
            $table->text('description')->nullable();
            $table->string('slug', 255)->unique();
            $table->integer('ordre_affichage')->default(0);
            $table->string('couleur', 7)->nullable();
            $table->string('icone', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->string('created_by', 255)->nullable();
            $table->string('updated_by', 255)->nullable();
            $table->string('deleted_by', 255)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // 3. Table des Carrés (Quartier parent)
        Schema::create('carres', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('uuid', 36)->unique();
            $table->foreignUuid('quartier_id')->nullable()->constrained('quartiers')->nullOnDelete();
            $table->string('nom', 255);
            $table->string('code', 50)->nullable()->unique();
            $table->text('description')->nullable();
            $table->string('slug', 255)->unique();
            $table->integer('ordre_affichage')->default(0);
            $table->string('couleur', 7)->nullable();
            $table->string('icone', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->boolean('est_chef')->nullable();
            $table->string('created_by', 255)->nullable();
            $table->string('updated_by', 255)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // 4. Table des Secteurs (Carré parent)
        Schema::create('secteurs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('uuid', 36)->unique();
            $table->foreignUuid('carre_id')->nullable()->constrained('carres')->nullOnDelete();
            $table->string('nom', 255);
            $table->string('code', 50)->nullable()->unique();
            $table->text('description')->nullable();
            $table->string('slug', 255)->unique();
            $table->integer('ordre_affichage')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->string('created_by', 255)->nullable();
            $table->string('updated_by', 255)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // 5. Table des Avenues (Secteur parent)
        Schema::create('avenues', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('uuid', 36)->unique();
            $table->foreignUuid('secteur_id')->nullable()->constrained('secteurs')->nullOnDelete();
            $table->string('nom', 255);
            $table->string('code', 50)->nullable()->unique();
            $table->text('description')->nullable();
            $table->string('slug', 255)->unique();
            $table->integer('ordre_affichage')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->string('created_by', 255)->nullable();
            $table->string('updated_by', 255)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // 6. Table des Besoins Prioritaires exprimés par les ménages
        Schema::create('besoins_prioritaires', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('uuid', 36)->unique();
            $table->string('nom', 255);
            $table->string('code', 50)->nullable()->unique();
            $table->text('description')->nullable();
            $table->string('slug', 255)->unique();
            $table->integer('ordre_affichage')->default(0);
            $table->string('couleur', 7)->nullable();
            $table->string('icone', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->string('created_by', 255)->nullable();
            $table->string('updated_by', 255)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // 7. Clé étrangère différée sur les agents créés dans la migration 01 (Fonction d'affectation)
        Schema::table('agents', function (Blueprint $table) {
            $table->foreign('fonction_id')->references('id')->on('fonctions')->cascadeOnDelete();
        });
    }

    /**
     * Annule les migrations.
     */
    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropForeign(['fonction_id']);
        });

        Schema::dropIfExists('besoins_prioritaires');
        Schema::dropIfExists('avenues');
        Schema::dropIfExists('secteurs');
        Schema::dropIfExists('carres');
        Schema::dropIfExists('quartiers');
        Schema::dropIfExists('fonctions');
    }
};
