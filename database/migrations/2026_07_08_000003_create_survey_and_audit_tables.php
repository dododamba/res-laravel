<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exécute les migrations pour les enquêtes, les campagnes, les affectations et les logs d'audit.
     */
    public function up(): void
    {
        // 1. Table des Campagnes de Recensement
        Schema::create('campagnes', function (Blueprint $table) {
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
            $table->string('statut', 50)->default('brouillon'); // Enum CampagneStatut
            $table->timestamp('date_ouverture')->nullable();
            $table->timestamp('date_cloture')->nullable();
            $table->string('created_by', 255)->nullable();
            $table->string('updated_by', 255)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // 2. Table des Équipes de Collecte
        Schema::create('equipes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom', 255);
            $table->text('description')->nullable();
            $table->foreignUuid('chef_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignUuid('campagne_id')->constrained('campagnes')->cascadeOnDelete();
            $table->timestamps();
        });

        // Table pivot Équipe/Agents membres (equipe_agent)
        Schema::create('equipe_agent', function (Blueprint $table) {
            $table->foreignUuid('equipe_id')->constrained('equipes')->cascadeOnDelete();
            $table->foreignUuid('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->primary(['equipe_id', 'agent_id']);
        });

        // 3. Table des Affectations Territoriales Temporelles d'Agents (Affectation)
        Schema::create('affectations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignUuid('fonction_id')->constrained('fonctions')->cascadeOnDelete();
            $table->foreignUuid('quartier_id')->nullable()->constrained('quartiers')->nullOnDelete();
            $table->foreignUuid('carre_id')->nullable()->constrained('carres')->nullOnDelete();
            $table->foreignUuid('secteur_id')->nullable()->constrained('secteurs')->nullOnDelete();
            $table->foreignUuid('campagne_id')->nullable()->constrained('campagnes')->nullOnDelete();
            $table->timestamp('date_debut');
            $table->timestamp('date_fin')->nullable();
            $table->string('motif', 255)->nullable();
            $table->string('statut', 50)->default('actif'); // actif, termine, revoque
            $table->string('responsable', 255)->nullable();
            $table->timestamps();
        });

        // 4. Table des Recensements de Ménages
        Schema::create('recensements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('uuid', 36)->unique();
            $table->timestamp('date_recensement')->nullable();
            $table->string('statut', 50)->default('brouillon'); // Enum RecensementStatut
            $table->string('nom_recensement', 255)->nullable();

            // Chef de ménage
            $table->string('chef_nom', 255)->nullable();
            $table->string('chef_prenom', 255)->nullable();
            $table->string('chef_sexe', 10)->nullable();
            $table->integer('chef_age')->nullable();
            $table->string('chef_telephone', 50)->nullable();
            $table->string('chef_telephone2', 50)->nullable();
            $table->string('chef_email', 255)->nullable();

            // Localisation
            $table->foreignUuid('quartier_id')->nullable()->constrained('quartiers')->nullOnDelete();
            $table->foreignUuid('carre_id')->nullable()->constrained('carres')->nullOnDelete();
            $table->foreignUuid('secteur_id')->nullable()->constrained('secteurs')->nullOnDelete();
            $table->foreignUuid('avenue_id')->nullable()->constrained('avenues')->nullOnDelete();
            $table->string('numero_porte', 50)->nullable();
            $table->string('adresse', 255)->nullable();

            // Composition du ménage
            $table->integer('nombre_personnes')->default(0);
            $table->integer('nombre_hommes')->default(0);
            $table->integer('nombre_femmes')->default(0);
            $table->integer('nombre_enfants')->default(0);
            $table->integer('nombre_jeunes')->default(0);
            $table->integer('nombre_handicapes')->default(0);

            // Niveaux d'instruction
            $table->integer('instruction_aucun')->default(0);
            $table->integer('instruction_primaire')->default(0);
            $table->integer('instruction_secondaire')->default(0);
            $table->integer('instruction_superieur')->default(0);

            // Coordonnées GPS
            $table->float('gps_latitude')->nullable();
            $table->float('gps_longitude')->nullable();
            $table->float('gps_precision')->nullable();
            $table->timestamp('gps_date_capture')->nullable();

            // Signatures & Empreinte
            $table->text('signature_chef')->nullable(); // Signature encodée base64
            $table->string('signature_enqueteur', 255)->nullable();
            $table->timestamp('signature_date')->nullable();
            $table->string('empreinte', 255)->nullable();
            $table->text('observations')->nullable();

            // Traçabilité des Agents
            $table->foreignUuid('enqueteur_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->foreignUuid('controleur_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->foreignUuid('validateur_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->foreignUuid('campagne_id')->nullable()->constrained('campagnes')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });

        // Table pivot Recensement/BesoinPrioritaire (recensement_besoin_prioritaire)
        Schema::create('recensement_besoin_prioritaire', function (Blueprint $table) {
            $table->foreignUuid('recensement_id')->constrained('recensements')->cascadeOnDelete();
            $table->foreignUuid('besoin_id')->constrained('besoins_prioritaires')->cascadeOnDelete();
            $table->primary(['recensement_id', 'besoin_id'], 'rec_besoin_primary');
        });

        // 5. Table des Habitations (Maisons)
        Schema::create('maisons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('numero_porte')->nullable();
            $table->string('adresse', 255)->nullable();
            $table->integer('nombre_hommes')->nullable();
            $table->integer('nombre_femmes')->nullable();
            $table->integer('nombre_enfants')->nullable();
            
            // Associations hiérarchiques et d'enquêtes
            $table->foreignUuid('carre_id')->nullable()->constrained('carres')->nullOnDelete();
            $table->foreignUuid('recensement_id')->nullable()->constrained('recensements')->nullOnDelete();
            
            // Identification & Usage
            $table->string('reference_cadastrale', 255)->nullable();
            $table->uuid('usage_principal_id')->nullable(); // Références paramétriques libres
            $table->uuid('type_construction_id')->nullable();
            $table->uuid('statut_foncier_id')->nullable();
            $table->uuid('source_eau_id')->nullable();
            $table->uuid('source_energie_id')->nullable();
            $table->uuid('assainissement_id')->nullable();
            $table->uuid('gestion_dechet_id')->nullable();

            // GPS
            $table->float('gps_latitude')->nullable();
            $table->float('gps_longitude')->nullable();
            $table->float('gps_altitude')->nullable();
            $table->float('gps_precision')->nullable();
            $table->timestamp('gps_date_capture')->nullable();

            // Flux de validation
            $table->string('statut', 50)->default('brouillon'); // Enum MaisonStatut
            $table->foreignUuid('enqueteur_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->foreignUuid('controleur_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->foreignUuid('validateur_id')->nullable()->constrained('agents')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });

        // 6. Table des Opérateurs Économiques
        Schema::create('operateurs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('uuid', 36)->unique();
            $table->string('nom_entreprise', 255)->nullable();
            $table->string('adresse', 255)->nullable();
            $table->string('telephone', 255)->nullable();
            $table->string('lat', 255)->nullable();
            $table->string('lng', 255)->nullable();
            $table->uuid('categorie_id')->nullable(); // Secteur d'activité
            $table->foreignUuid('recensement_id')->nullable()->constrained('recensements')->nullOnDelete();

            // Identification Générale
            $table->string('nom_commercial', 255)->nullable();
            $table->string('promoteur_nom', 255)->nullable();
            $table->string('promoteur_prenom', 255)->nullable();
            $table->string('promoteur_sexe', 10)->nullable();
            $table->string('telephone_secondaire', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('bp', 50)->nullable();
            $table->string('site_web', 255)->nullable();

            // Localisation
            $table->foreignUuid('campagne_id')->nullable()->constrained('campagnes')->nullOnDelete();
            $table->foreignUuid('quartier_id')->nullable()->constrained('quartiers')->nullOnDelete();
            $table->foreignUuid('carre_id')->nullable()->constrained('carres')->nullOnDelete();
            $table->foreignUuid('secteur_id')->nullable()->constrained('secteurs')->nullOnDelete();
            $table->foreignUuid('avenue_id')->nullable()->constrained('avenues')->nullOnDelete();
            $table->string('numero_porte', 50)->nullable();
            $table->text('adresse_descriptive')->nullable();

            // GPS
            $table->float('gps_latitude')->nullable();
            $table->float('gps_longitude')->nullable();
            $table->float('gps_precision')->nullable();
            $table->timestamp('gps_date_capture')->nullable();

            // Administration & Effectifs
            $table->uuid('categorie_activite_id')->nullable();
            $table->string('rccm', 100)->nullable()->unique();
            $table->string('nif', 100)->nullable()->unique();
            $table->string('numero_patente', 100)->nullable();
            $table->string('numero_cnps', 100)->nullable();
            $table->string('numero_licence', 100)->nullable();
            $table->string('taille', 50)->nullable(); // Enum EntrepriseTaille
            
            $table->integer('effectif_hommes')->default(0);
            $table->integer('effectif_femmes')->default(0);
            $table->integer('effectif_total')->default(0);
            $table->integer('effectif_permanents')->default(0);
            $table->integer('effectif_temporaires')->default(0);

            // Flux de validation
            $table->string('statut', 50)->default('brouillon'); // Enum OperateurStatut
            $table->foreignUuid('enqueteur_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->foreignUuid('validateur_id')->nullable()->constrained('agents')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });

        // 7. Table des Logs d'Audit Système Global (AuditLog)
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('user_identifier', 255)->default('anonymous');
            $table->string('action', 100);
            $table->string('object_class', 255)->nullable();
            $table->string('object_id', 255)->nullable();
            $table->json('data_before')->nullable();
            $table->json('data_after')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('os', 100)->nullable();
            $table->string('browser', 100)->nullable();
            $table->string('result', 50)->default('success');
            $table->timestamps();
        });

        // 8. Tables des Historiques d'États (Timeline de workflows)
        
        Schema::create('historique_recensements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('recensement_id')->constrained('recensements')->cascadeOnDelete();
            $table->string('action', 100);
            $table->json('details')->nullable();
            $table->string('user_identifier', 255)->default('system');
            $table->timestamps();
        });

        Schema::create('historique_maisons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('maison_id')->constrained('maisons')->cascadeOnDelete();
            $table->string('action', 100);
            $table->json('details')->nullable();
            $table->string('user_identifier', 255)->default('system');
            $table->timestamps();
        });

        Schema::create('historique_operateurs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('operateur_id')->constrained('operateurs')->cascadeOnDelete();
            $table->string('action', 100);
            $table->json('details')->nullable();
            $table->string('user_identifier', 255)->default('system');
            $table->timestamps();
        });
    }

    /**
     * Annule les migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historique_operateurs');
        Schema::dropIfExists('historique_maisons');
        Schema::dropIfExists('historique_recensements');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('operateurs');
        Schema::dropIfExists('maisons');
        Schema::dropIfExists('recensement_besoin_prioritaire');
        Schema::dropIfExists('recensements');
        Schema::dropIfExists('affectations');
        Schema::dropIfExists('equipe_agent');
        Schema::dropIfExists('equipes');
        Schema::dropIfExists('campagnes');
    }
};
