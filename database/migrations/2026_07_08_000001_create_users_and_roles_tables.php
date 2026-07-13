<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exécute les migrations pour les utilisateurs, les rôles, les permissions et les profils agents.
     */
    public function up(): void
    {
        // 1. Table des Personnes Physiques (Civils)
        Schema::create('personnes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('prenom', 100);
            $table->string('nom', 100);
            $table->string('telephone', 15)->nullable();
            $table->string('email', 50)->unique();
            $table->string('role', 20)->default('user');
            $table->timestamps();
        });

        // 2. Table des Rôles RBAC
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->string('description', 255)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // 3. Table des Permissions fines
        Schema::create('permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100)->unique();
            $table->string('description', 255)->nullable();
            $table->string('category', 100)->nullable();
            $table->timestamps();
        });

        // 4. Table des Utilisateurs d'authentification
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('uuid', 36)->unique();
            $table->string('email', 180)->unique();
            $table->string('password');
            $table->string('avatar', 255)->nullable();
            $table->string('firstname', 255)->nullable();
            $table->string('lastname', 255)->nullable();
            $table->boolean('is_verified')->default(false);
            $table->string('slug', 255)->nullable()->unique();
            $table->string('telephone', 255)->nullable();
            $table->string('fonction', 255)->nullable();
            $table->string('status', 50)->default('active'); // active, pending, suspended
            $table->boolean('is_active')->default(true);
            $table->boolean('is_locked')->default(false);
            $table->integer('login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('last_login')->nullable();
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });

        // 5. Table des Profils Agents Territoriaux
        Schema::create('agents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('personne_id')->constrained('personnes')->cascadeOnDelete();
            $table->uuid('fonction_id'); // Clé étrangère vers fonctions (créée en migration 02)
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sexe', 10)->nullable();
            $table->date('date_naissance')->nullable();
            $table->string('lieu_naissance', 255)->nullable();
            $table->string('nationalite', 100)->nullable();
            $table->string('telephone_secondaire', 30)->nullable();
            $table->string('adresse', 255)->nullable();
            $table->string('profession', 255)->nullable();
            $table->string('matricule', 100)->unique();
            $table->string('cni', 50)->nullable();
            $table->string('statut', 50)->default('actif'); // Enum AgentStatut
            $table->date('date_nomination')->nullable();
            $table->date('date_fin_fonction')->nullable();
            $table->text('observations')->nullable();
            $table->string('qr_code', 255)->nullable();
            $table->string('photo', 255)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // 6. Tables de jointures et associations RBAC

        // Rôles affectés aux Utilisateurs (user_roles)
        Schema::create('user_roles', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('role_id')->constrained('roles')->cascadeOnDelete();
            $table->primary(['user_id', 'role_id']);
        });

        // Permissions attribuées aux Rôles (role_permissions)
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignUuid('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignUuid('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        // Surcharges de permissions spécifiques attribuées aux Utilisateurs (user_permissions)
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->boolean('is_granted')->default(true); // true = accordé, false = exclu strictement
            $table->timestamps();
            $table->primary(['user_id', 'permission_id']);
        });

        // 7. Table de stockage des Sessions (Requis par SESSION_DRIVER=database dans le .env)
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('user_id')->nullable()->index(); // Support de l'UUID string des utilisateurs
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Annule les migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('agents');
        Schema::dropIfExists('users');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('personnes');
    }
};
