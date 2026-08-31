<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Agent;
use App\Models\Affectation;
use App\Models\Recensement;
use App\Models\Parameters\Quartier;
use App\Models\Parameters\Carre;
use App\Models\Parameters\BesoinPrioritaire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentScopeTest extends TestCase
{
    use RefreshDatabase;

    protected User $agentUser;
    protected Agent $agent;
    protected User $adminUser;
    protected Quartier $quartierA;
    protected Quartier $quartierB;
    protected Carre $carreA;
    protected Carre $carreB;
    protected BesoinPrioritaire $besoin;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Création des quartiers et carrés
        $this->quartierA = Quartier::create([
            'nom' => 'Quartier Nord',
            'code' => 'QN-01',
            'slug' => 'quartier-nord'
        ]);

        $this->quartierB = Quartier::create([
            'nom' => 'Quartier Sud',
            'code' => 'QS-02',
            'slug' => 'quartier-sud'
        ]);

        $this->carreA = Carre::create([
            'quartier_id' => $this->quartierA->id,
            'nom' => 'Carré N1',
            'code' => 'CR-N1',
            'slug' => 'carre-n1'
        ]);

        $this->carreB = Carre::create([
            'quartier_id' => $this->quartierB->id,
            'nom' => 'Carré S1',
            'code' => 'CR-S1',
            'slug' => 'carre-s1'
        ]);

        $this->besoin = BesoinPrioritaire::create([
            'nom' => 'Accès Eau Potable',
            'code' => 'EAU',
            'slug' => 'eau-potable-test'
        ]);

        // 2. Création de l'utilisateur Agent et de sa fiche Agent
        $this->agentUser = User::create([
            'email' => 'agent.nord@recensement.gov',
            'password' => bcrypt('password123'),
            'firstname' => 'Moussa',
            'lastname' => 'Koko',
            'is_verified' => true,
            'is_active' => true,
        ]);

        $this->agentUser->roles()->create([
            'name' => 'Enquêteur',
            'slug' => 'ROLE_ENQUETEUR',
        ]);

        $personneAgent = \App\Models\Personne::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'prenom' => 'Moussa',
            'nom' => 'Koko',
            'email' => 'moussa.koko@recensement.gov',
            'telephone' => '66001122',
        ]);

        $fonctionAgent = \App\Models\Parameters\Fonction::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'nom' => 'Enquêteur Terrain',
            'code' => 'ENQ',
            'slug' => 'enqueteur-terrain',
        ]);

        $this->agent = Agent::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $this->agentUser->id,
            'personne_id' => $personneAgent->id,
            'fonction_id' => $fonctionAgent->id,
            'matricule' => 'AGT-001',
            'statut' => \App\Enums\AgentStatut::ACTIF,
        ]);

        // Affectation active de l'agent UNIQUEMENT sur le Quartier Nord (quartierA)
        Affectation::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'agent_id' => $this->agent->id,
            'fonction_id' => $fonctionAgent->id,
            'quartier_id' => $this->quartierA->id,
            'carre_id' => $this->carreA->id,
            'statut' => 'actif',
            'date_debut' => now()->subDays(5),
        ]);

        // 3. Création de l'utilisateur Administrateur
        $this->adminUser = User::create([
            'email' => 'admin@recensement.gov',
            'password' => bcrypt('password123'),
            'firstname' => 'Super',
            'lastname' => 'Admin',
            'is_verified' => true,
            'is_active' => true,
        ]);

        $this->adminUser->roles()->create([
            'name' => 'Administrateur',
            'slug' => 'ROLE_ADMIN',
        ]);
    }

    /**
     * Test 1 : Un agent peut créer un recensement dans son quartier affecté (Quartier A).
     */
    public function test_agent_can_create_recensement_in_authorized_quartier(): void
    {
        $payload = [
            'chefNom' => 'Abakar',
            'chefPrenom' => 'Hassan',
            'chefSexe' => 'M',
            'chefAge' => 40,
            'chefTelephone' => '661122334',
            'quartier_id' => $this->quartierA->id,
            'carre_id' => $this->carreA->id,
            'numeroPorte' => '05',
            'adresse' => 'Avenue Nord 10',
            'nombrePersonnes' => 2,
            'nombreHommes' => 1,
            'nombreFemmes' => 1,
            'nombreEnfants' => 0,
            'nombreJeunes' => 0,
            'nombreHandicapes' => 0,
            'instructionAucun' => 0,
            'instructionPrimaire' => 1,
            'instructionSecondaire' => 0,
            'instructionSuperieur' => 0,
            'priorites' => [$this->besoin->id]
        ];

        $response = $this->actingAs($this->agentUser)
            ->postJson(route('api.v1.recensements.create'), $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('status', 'success');
        $this->assertDatabaseHas('recensements', ['chef_nom' => 'Abakar']);
    }

    /**
     * Test 2 : Un agent est BLOQUÉ (403 Forbidden) s'il tente d'enregistrer un recensement hors zone (Quartier B).
     */
    public function test_agent_cannot_create_recensement_outside_authorized_scope(): void
    {
        $payload = [
            'chefNom' => 'Mahamat',
            'chefPrenom' => 'Ali',
            'chefSexe' => 'M',
            'chefAge' => 38,
            'chefTelephone' => '664455667',
            'quartier_id' => $this->quartierB->id, // Non autorisé !
            'carre_id' => $this->carreB->id,
            'numeroPorte' => '12',
            'adresse' => 'Avenue Sud 20',
            'nombrePersonnes' => 2,
            'nombreHommes' => 1,
            'nombreFemmes' => 1,
            'nombreEnfants' => 0,
            'nombreJeunes' => 0,
            'nombreHandicapes' => 0,
            'instructionAucun' => 0,
            'instructionPrimaire' => 1,
            'instructionSecondaire' => 0,
            'instructionSuperieur' => 0,
            'priorites' => [$this->besoin->id]
        ];

        $response = $this->actingAs($this->agentUser)
            ->postJson(route('api.v1.recensements.create'), $payload);

        $response->assertStatus(403);
        $response->assertJsonPath('status', 'error');
    }

    /**
     * Test 3 : L'API de recherche bloque une recherche ciblée sur un quartier non autorisé.
     */
    public function test_search_api_blocks_unauthorized_quartier_search(): void
    {
        $response = $this->actingAs($this->agentUser)
            ->getJson(route('api.v1.mobile.search', ['quartier_id' => $this->quartierB->id]));

        $response->assertStatus(403);
    }

    /**
     * Test 4 : Les statistiques pour l'agent retournent uniquement son périmètre d'affectation.
     */
    public function test_statistics_api_returns_scoped_data_for_agent(): void
    {
        $response = $this->actingAs($this->agentUser)
            ->getJson(route('api.v1.mobile.dashboard.statistics'));

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.scope.type', 'quartier');

        $byQuartier = $response->json('data.byQuartier');
        $this->assertCount(1, $byQuartier);
        $this->assertEquals((string)$this->quartierA->id, $byQuartier[0]['quartier_id']);
    }

    /**
     * Test 5 : L'administrateur obtient des statistiques globales sur tous les quartiers.
     */
    public function test_statistics_api_returns_global_data_for_admin(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('api.v1.mobile.dashboard.statistics'));

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.scope.type', 'global');

        $byQuartier = $response->json('data.byQuartier');
        $this->assertCount(2, $byQuartier);
    }
}
