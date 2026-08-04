<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exécute les migrations pour la gestion des taxes et contributions municipales.
     */
    public function up(): void
    {
        // 1. Table des Taxes Municipales
        Schema::create('taxes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('uuid', 36)->unique();
            $table->string('code', 50)->unique();
            $table->string('nom', 255);
            $table->text('description')->nullable();
            $table->string('categorie', 100)->default('Patente Commerciale');
            $table->decimal('montant', 15, 2)->default(0);
            $table->string('mode_calcul', 50)->default('fixe'); // fixe, pourcentage, surface, volume, effectif
            $table->string('periodicite', 50)->default('annuelle'); // mensuelle, trimestrielle, annuelle, ponctuelle
            $table->decimal('pourcentage', 5, 2)->nullable();
            $table->decimal('surface', 10, 2)->nullable();
            $table->decimal('volume', 10, 2)->nullable();
            $table->boolean('actif')->default(true);
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->integer('ordre')->default(0);
            $table->json('regles_affectation')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // 2. Table des Taxes Affectées aux Opérateurs (TaxeOperateur)
        Schema::create('taxe_operateurs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('uuid', 36)->unique();
            $table->foreignUuid('operateur_id')->constrained('operateurs')->cascadeOnDelete();
            $table->foreignUuid('taxe_id')->constrained('taxes')->cascadeOnDelete();
            $table->integer('annee_fiscale')->default(2026);
            $table->decimal('montant_attendu', 15, 2)->default(0);
            $table->decimal('montant_paye', 15, 2)->default(0);
            $table->decimal('reste_a_payer', 15, 2)->default(0);
            $table->date('date_limite');
            $table->string('statut', 50)->default('A payer'); // A payer, Partiellement payé, Soldé, En retard, Exonéré, Annulé
            $table->softDeletes();
            $table->timestamps();
        });

        // 3. Table des Paiements de Taxes
        Schema::create('paiement_taxes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('uuid', 36)->unique();
            $table->foreignUuid('taxe_operateur_id')->constrained('taxe_operateurs')->cascadeOnDelete();
            $table->timestamp('date_paiement');
            $table->decimal('montant', 15, 2);
            $table->string('mode_paiement', 50)->default('Espèces'); // Espèces, Mobile Money, Banque, Chèque
            $table->string('reference', 255)->nullable();
            $table->string('numero_recu', 100)->unique();
            $table->foreignUuid('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observation')->nullable();
            $table->string('justificatif', 255)->nullable();
            $table->text('signature_agent')->nullable();
            $table->text('signature_client')->nullable();
            $table->string('statut', 50)->default('valide');
            $table->softDeletes();
            $table->timestamps();
        });

        // 4. Table des Exonérations
        Schema::create('exonerations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('uuid', 36)->unique();
            $table->foreignUuid('taxe_operateur_id')->constrained('taxe_operateurs')->cascadeOnDelete();
            $table->text('motif');
            $table->string('autorite', 255);
            $table->string('document', 255)->nullable();
            $table->date('date_exoneration');
            $table->decimal('montant_exonere', 15, 2)->default(0);
            $table->foreignUuid('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        // 5. Table des Recouvrements / Relances
        Schema::create('recouvrements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('uuid', 36)->unique();
            $table->foreignUuid('taxe_operateur_id')->constrained('taxe_operateurs')->cascadeOnDelete();
            $table->timestamp('date_relance');
            $table->foreignUuid('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('commentaires');
            $table->string('statut', 50)->default('En cours'); // En cours, Relance 1, Relance 2, Sommation, Reglé
            $table->date('prochaine_relance')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // 6. Table des Historiques de Paiements / Timeline
        Schema::create('historique_paiements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('taxe_operateur_id')->constrained('taxe_operateurs')->cascadeOnDelete();
            $table->foreignUuid('paiement_id')->nullable()->constrained('paiement_taxes')->cascadeOnDelete();
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
        Schema::dropIfExists('historique_paiements');
        Schema::dropIfExists('recouvrements');
        Schema::dropIfExists('exonerations');
        Schema::dropIfExists('paiement_taxes');
        Schema::dropIfExists('taxe_operateurs');
        Schema::dropIfExists('taxes');
    }
};
