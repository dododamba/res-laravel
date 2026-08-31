<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Recensement;
use App\Models\Taxe;
use App\Enums\RecensementStatut;
use App\Models\Parameters\Quartier;
use App\Models\Parameters\Carre;
use App\Models\Parameters\CategorieOperateur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecensementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test : Un enquêteur authentifié peut soumettre un recensement de ménage valide.
     */
    public function test_un_enqueteur_peut_soumettre_un_recensement_valide(): void
    {
        // 1. Initialisation des données (Arrange)
        $user = User::create([
            'email' => 'enqueteur@recensement.gov',
            'password' => 'password123',
            'firstname' => 'Émile',
            'lastname' => 'Zola',
            'is_verified' => true,
            'is_active' => true,
        ]);

        $role = $user->roles()->create([
            'name' => 'Enquêteur',
            'slug' => 'ROLE_ENQUETEUR',
            'description' => 'Agent de collecte terrain'
        ]);

        $quartier = Quartier::create([
            'nom' => 'Quartier Test',
            'code' => 'QT-TEST',
            'slug' => 'quartier-test'
        ]);

        $carre = Carre::create([
            'quartier_id' => $quartier->id,
            'nom' => 'Carré A1',
            'code' => 'CR-A1',
            'slug' => 'carre-a1'
        ]);

        $fonction = \App\Models\Parameters\Fonction::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'nom' => 'Enquêteur',
            'code' => 'ENQ',
            'slug' => 'enqueteur'
        ]);

        $personne = \App\Models\Personne::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'nom' => 'Zola',
            'prenom' => 'Émile',
            'email' => 'enqueteur@recensement.gov'
        ]);

        $agent = \App\Models\Agent::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'personne_id' => $personne->id,
            'fonction_id' => $fonction->id,
            'matricule' => 'AGT-REC-01',
            'statut' => \App\Enums\AgentStatut::ACTIF,
        ]);

        \App\Models\Affectation::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'agent_id' => $agent->id,
            'fonction_id' => $fonction->id,
            'quartier_id' => $quartier->id,
            'carre_id' => $carre->id,
            'statut' => 'actif',
            'date_debut' => now()->subDays(1),
        ]);

        $besoin = \App\Models\Parameters\BesoinPrioritaire::create([
            'nom' => 'Accès Eau Potable',
            'code' => 'EAU',
            'slug' => 'eau-potable'
        ]);

        $payload = [
            'chefNom' => 'Zola',
            'chefPrenom' => 'Émile',
            'chefSexe' => 'M',
            'chefAge' => 45,
            'chefTelephone' => '0612345678',
            'quartier_id' => $quartier->id,
            'carre_id' => $carre->id,
            'numeroPorte' => '12B',
            'adresse' => 'Rue des Ecrivains',
            'nombrePersonnes' => 3,
            'nombreHommes' => 1,
            'nombreFemmes' => 2,
            'nombreEnfants' => 1,
            'nombreJeunes' => 1,
            'nombreHandicapes' => 0,
            'instructionAucun' => 0,
            'instructionPrimaire' => 1,
            'instructionSecondaire' => 2,
            'instructionSuperieur' => 0,
            'priorites' => [$besoin->id] // Répond à la règle min:1 validation
        ];

        // 2. Exécution de la requête en tant qu'utilisateur connecté (Act)
        $response = $this->actingAs($user)
                         ->postJson(route('api.v1.recensements.create'), $payload);

        // 3. Assertions sur la réponse JSON et la persistance en base de données (Assert)
        $response->assertStatus(201); // Code de succès unifié 201 Created
        $response->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('recensements', [
            'chef_nom' => 'Zola',
            'statut' => RecensementStatut::SOUMIS->value,
        ]);
    }

    /**
     * Test : Un recensement incohérent démographiquement est rejeté par la validation.
     */
    public function test_un_recensement_dont_le_total_ne_correspond_pas_a_la_somme_hommes_et_femmes_est_rejete(): void
    {
        // 1. Initialisation
        $user = User::create([
            'email' => 'enqueteur2@recensement.gov',
            'password' => 'password123',
            'firstname' => 'Albert',
            'lastname' => 'Camus',
            'is_verified' => true,
            'is_active' => true,
        ]);

        $user->roles()->create([
            'name' => 'Enquêteur',
            'slug' => 'ROLE_ENQUETEUR',
        ]);

        $payload = [
            'nombrePersonnes' => 10, // Incohérent : total 10 alors que Hommes(2) + Femmes(3) = 5
            'nombreHommes' => 2,
            'nombreFemmes' => 3,
            'nombreEnfants' => 0,
            'nombreJeunes' => 0,
            'nombreHandicapes' => 0,
            'instructionAucun' => 0,
            'instructionPrimaire' => 0,
            'instructionSecondaire' => 0,
            'instructionSuperieur' => 0,
        ];

        // 2. Requête
        $response = $this->actingAs($user)
                         ->postJson(route('api.v1.recensements.create'), $payload);

        // 3. Doit être bloqué par la validation (422 Unprocessable Entity)
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['nombrePersonnes']);
    }

    public function test_un_operateur_cree_recoit_automatiquement_des_taxes_affectees(): void
    {
        $user = User::create([
            'email' => 'operator@recensement.gov',
            'password' => 'password123',
            'firstname' => 'Amina',
            'lastname' => 'Djim',
            'is_verified' => true,
            'is_active' => true,
        ]);

        $user->roles()->create([
            'name' => 'Enquêteur',
            'slug' => 'ROLE_ENQUETEUR',
        ]);

        $categorie = CategorieOperateur::create([
            'nom' => 'Commerce de détail',
            'code' => 'COMMERCE_DETAIL',
            'description' => 'Boutiques et épiceries',
            'is_active' => true,
        ]);

        Taxe::create([
            'code' => 'PAT-COMM',
            'nom' => 'Patente Commerciale et Industrielle',
            'description' => 'Taxe de base',
            'categorie' => 'Patente Commerciale',
            'montant' => 50000,
            'actif' => true,
            'ordre' => 1,
        ]);

        $payload = [
            'nomEntreprise' => 'Boutique Demo',
            'nomCommercial' => 'Boutique Demo',
            'categorie_id' => $categorie->id,
            'telephone' => '77000001',
            'adresse' => 'Avenue de la Paix',
            'quartier_id' => null,
            'carre_id' => null,
            'rccm' => 'RC-001',
            'nif' => 'NIF-001',
        ];

        $response = $this->actingAs($user)
            ->postJson(route('api.v1.operateurs.create'), $payload);

        $this->assertEquals(201, $response->status(), 'Erreur lors de la création : ' . json_encode($response->json()));

        $operateur = \App\Models\Operateur::where('rccm', 'RC-001')->firstOrFail();
        $this->assertGreaterThan(0, $operateur->taxesAffectees()->count());
        $this->assertDatabaseHas('taxe_operateurs', [
            'operateur_id' => $operateur->id,
            'taxe_id' => Taxe::where('code', 'PAT-COMM')->first()->id,
        ]);
    }
}
