<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Agent;
use App\Models\Role;
use App\Models\Affectation;
use App\Models\Recensement;
use App\Models\Parameters\Quartier;
use App\Models\Parameters\Carre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HierarchicalStatisticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $agentUser;
    protected Agent $agent;
    protected User $adminUser;
    protected User $unassignedUser;
    protected Quartier $quartierA;
    protected Quartier $quartierB;
    protected Carre $carreA;
    protected Carre $carreB;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Quartiers et carrés
        $this->quartierA = Quartier::create([
            'nom' => 'Quartier Est',
            'code' => 'QE-01',
            'slug' => 'quartier-est'
        ]);

        $this->quartierB = Quartier::create([
            'nom' => 'Quartier Ouest',
            'code' => 'QO-02',
            'slug' => 'quartier-ouest'
        ]);

        $this->carreA = Carre::create([
            'quartier_id' => $this->quartierA->id,
            'nom' => 'Carré E1',
            'code' => 'CR-E1',
            'slug' => 'carre-e1'
        ]);

        $this->carreB = Carre::create([
            'quartier_id' => $this->quartierB->id,
            'nom' => 'Carré O1',
            'code' => 'CR-O1',
            'slug' => 'carre-o1'
        ]);

        $roleEnqueteur = Role::firstOrCreate(
            ['slug' => 'ROLE_ENQUETEUR'],
            ['id' => (string) \Illuminate\Support\Str::uuid(), 'name' => 'Enquêteur']
        );

        $roleAdmin = Role::firstOrCreate(
            ['slug' => 'ROLE_ADMIN'],
            ['id' => (string) \Illuminate\Support\Str::uuid(), 'name' => 'Administrateur']
        );

        // 2. Agent affecté uniquement sur Quartier Est (quartierA)
        $this->agentUser = User::create([
            'email' => 'agent.est@recensement.gov',
            'password' => bcrypt('password123'),
            'firstname' => 'Idriss',
            'lastname' => 'Deby',
            'is_verified' => true,
            'is_active' => true,
        ]);
        $this->agentUser->roles()->attach($roleEnqueteur->id);

        $personneAgent = \App\Models\Personne::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'prenom' => 'Idriss',
            'nom' => 'Deby',
            'email' => 'idriss.deby@recensement.gov',
            'telephone' => '66998877',
        ]);

        $fonctionAgent = \App\Models\Parameters\Fonction::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'nom' => 'Enquêteur Terrain',
            'code' => 'ENQ',
            'slug' => 'enqueteur-terrain-hierarchical',
        ]);

        $this->agent = Agent::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $this->agentUser->id,
            'personne_id' => $personneAgent->id,
            'fonction_id' => $fonctionAgent->id,
            'matricule' => 'AGT-009',
            'statut' => \App\Enums\AgentStatut::ACTIF,
        ]);

        Affectation::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'agent_id' => $this->agent->id,
            'fonction_id' => $fonctionAgent->id,
            'quartier_id' => $this->quartierA->id,
            'carre_id' => $this->carreA->id,
            'statut' => 'actif',
            'date_debut' => now()->subDays(5),
        ]);

        // 3. Admin user
        $this->adminUser = User::create([
            'email' => 'admin.hierarchical@recensement.gov',
            'password' => bcrypt('password123'),
            'firstname' => 'Admin',
            'lastname' => 'Global',
            'is_verified' => true,
            'is_active' => true,
        ]);
        $this->adminUser->roles()->attach($roleAdmin->id);

        // 4. Utilisateur sans affectation
        $this->unassignedUser = User::create([
            'email' => 'sans.affectation@recensement.gov',
            'password' => bcrypt('password123'),
            'firstname' => 'Nouveau',
            'lastname' => 'Recenseur',
            'is_verified' => true,
            'is_active' => true,
        ]);
        $this->unassignedUser->roles()->attach($roleEnqueteur->id);
    }

    /**
     * Test 1 : API Global Stats Niveau 1.
     */
    public function test_api_global_stats(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('api.v1.mobile.statistics.global'));

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.scope', 'global');
    }

    /**
     * Test 2 : API Quartiers Stats Niveau 2 pour l'agent (Uniquement Quartier Est).
     */
    public function test_api_quartiers_stats_scoped_for_agent(): void
    {
        $response = $this->actingAs($this->agentUser)
            ->getJson(route('api.v1.mobile.statistics.quartiers'));

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $this->assertCount(1, $response->json('data.items'));
        $this->assertEquals($this->quartierA->id, $response->json('data.items.0.id'));
    }

    /**
     * Test 3 : L'agent est bloqué avec HTTP 403 Forbidden s'il demande explicitement un quartier hors périmètre.
     */
    public function test_api_quartier_stats_forbidden_outside_scope(): void
    {
        $response = $this->actingAs($this->agentUser)
            ->getJson(route('api.v1.mobile.statistics.quartiers', ['quartier_id' => $this->quartierB->id]));

        $response->assertStatus(403);
        $response->assertJsonPath('status', 'error');
    }

    /**
     * Test 4 : L'agent peut lire les Carrés Niveau 3 de son Quartier A.
     */
    public function test_api_carres_stats_authorized_for_agent(): void
    {
        $response = $this->actingAs($this->agentUser)
            ->getJson(route('api.v1.mobile.statistics.quartier.carres', ['quartier' => $this->quartierA->id]));

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.scope', 'carres');
        $this->assertCount(1, $response->json('data.items'));
        $this->assertEquals($this->carreA->id, $response->json('data.items.0.id'));
    }

    /**
     * Test 5 : L'agent est bloqué avec HTTP 403 Forbidden s'il demande les Carrés d'un Quartier B non autorisé.
     */
    public function test_api_carres_stats_forbidden_outside_scope(): void
    {
        $response = $this->actingAs($this->agentUser)
            ->getJson(route('api.v1.mobile.statistics.quartier.carres', ['quartier' => $this->quartierB->id]));

        $response->assertStatus(403);
    }

    /**
     * Test 6 : Utilisateur sans affectation reçoit un tableau vide sans erreur 500.
     */
    public function test_unassigned_user_gets_empty_stats(): void
    {
        $response = $this->actingAs($this->unassignedUser)
            ->getJson(route('api.v1.mobile.statistics.quartiers'));

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $this->assertCount(0, $response->json('data.items'));
    }

    /**
     * Test 7 : Back Office Web Statistics Route (/statistics).
     */
    public function test_web_backoffice_statistics_index(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('statistics.index'));

        $response->assertStatus(200);
        $response->assertSee('Statistiques Territoriales Hiérarchiques');
    }
}
