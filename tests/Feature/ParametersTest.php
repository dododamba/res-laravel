<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Personne;
use App\Models\Agent;
use App\Models\Affectation;
use App\Models\Parameters\Quartier;
use App\Models\Parameters\Carre;
use App\Models\Parameters\Fonction;
use App\Enums\AgentStatut;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ParametersTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Agent $agent1;
    private Agent $agent2;
    private Fonction $fonctionDelegue;
    private Fonction $fonctionChefCarre;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Bypass CSRF
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        // 2. Create Admin user with PARAM_VIEW / PARAM_CREATE / PARAM_EDIT permissions via gate or role
        $this->admin = User::create([
            'id' => (string) Str::uuid(),
            'email' => 'admin@recensement.gov',
            'password' => bcrypt('password123'),
            'firstname' => 'Admin',
            'lastname' => 'User',
            'is_verified' => true,
            'is_active' => true,
        ]);
        $role = $this->admin->roles()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Admin',
            'slug' => 'ROLE_ADMIN',
            'description' => 'System Administrator'
        ]);

        // Attach system-level permissions to the role to test standard RBAC lookup
        $role->permissions()->create([
            'id' => (string) Str::uuid(),
            'name' => 'PARAM_VIEW',
            'category' => 'Paramétrages',
        ]);
        $role->permissions()->create([
            'id' => (string) Str::uuid(),
            'name' => 'PARAM_MANAGE',
            'category' => 'Paramétrages',
        ]);

        // 3. Create Functions for delegate & block supervisor
        $this->fonctionDelegue = Fonction::create([
            'id' => (string) Str::uuid(),
            'nom' => 'Délégué de Quartier',
            'code' => 'DELEGUE',
            'slug' => 'delegue-de-quartier',
        ]);

        $this->fonctionChefCarre = Fonction::create([
            'id' => (string) Str::uuid(),
            'nom' => 'Chef de Carré',
            'code' => 'CHEF_CARRE',
            'slug' => 'chef-de-carre',
        ]);

        // 4. Create two dummy agents for supervision tests
        $fonctionAgent = Fonction::create([
            'id' => (string) Str::uuid(),
            'nom' => 'Enquêteur Terrain',
            'code' => 'ENQ',
            'slug' => 'enqueteur-terrain',
        ]);

        $personne1 = Personne::create([
            'id' => (string) Str::uuid(),
            'nom' => 'Damba',
            'prenom' => 'Dominique',
            'telephone' => '66112233',
            'email' => 'dominique@gov.td'
        ]);
        $this->agent1 = Agent::create([
            'id' => (string) Str::uuid(),
            'personne_id' => $personne1->id,
            'fonction_id' => $fonctionAgent->id,
            'matricule' => 'AGT-2026-0001',
            'sexe' => 'M',
            'statut' => AgentStatut::ACTIF,
        ]);

        $personne2 = Personne::create([
            'id' => (string) Str::uuid(),
            'nom' => 'Zara',
            'prenom' => 'Fatime',
            'telephone' => '66223344',
            'email' => 'fatime@gov.td'
        ]);
        $this->agent2 = Agent::create([
            'id' => (string) Str::uuid(),
            'personne_id' => $personne2->id,
            'fonction_id' => $fonctionAgent->id,
            'matricule' => 'AGT-2026-0002',
            'sexe' => 'F',
            'statut' => AgentStatut::ACTIF,
        ]);

        $this->admin->refresh();
    }

    /**
     * Test: Listing Quartiers (Index page) works.
     */
    public function test_page_index_quartier_charge_avec_succes(): void
    {
        $response = $this->actingAs($this->admin)
                         ->get(route('quartier.index'));

        $response->assertStatus(200);
        $response->assertViewIs('parameters.quartier.index');
        $response->assertViewHas('entities');
        $response->assertViewHas('stats');
    }

    /**
     * Test: Accessing creation form for Quartier works.
     */
    public function test_page_create_quartier_charge_avec_succes(): void
    {
        $response = $this->actingAs($this->admin)
                         ->get(route('quartier.create'));

        $response->assertStatus(200);
        $response->assertViewIs('parameters.quartier.create');
        $response->assertViewHas('agents');
    }

    /**
     * Test: Storing a new Quartier and assigning its active Délégué.
     */
    public function test_enregistrement_quartier_avec_delegue_avec_succes(): void
    {
        $payload = [
            'nom' => 'Quartier Nord',
            'code' => 'QT-NORD',
            'description' => 'Secteur nord de la ville',
            'couleur' => '#FF5733',
            'icone' => 'bi-geo-alt',
            'ordre_affichage' => 5,
            'delegue_id' => $this->agent1->id,
        ];

        $response = $this->actingAs($this->admin)
                         ->post(route('quartier.store'), $payload);

        $response->assertRedirect(route('quartier.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('quartiers', [
            'nom' => 'Quartier Nord',
            'code' => 'QT-NORD',
        ]);

        $quartier = Quartier::where('code', 'QT-NORD')->first();

        // Check active DELEGUE affectation
        $this->assertDatabaseHas('affectations', [
            'agent_id' => $this->agent1->id,
            'fonction_id' => $this->fonctionDelegue->id,
            'quartier_id' => $quartier->id,
            'statut' => 'actif',
        ]);

        $this->assertEquals($this->agent1->id, $quartier->delegue->id);
    }

    /**
     * Test: Updating a Quartier and transferring Délégué active assignment (temporal tracking).
     */
    public function test_modification_quartier_et_transfert_delegue_avec_succes(): void
    {
        // 1. Create Quartier with agent1 as delegue
        $quartier = Quartier::create([
            'id' => (string) Str::uuid(),
            'nom' => 'Quartier Est',
            'code' => 'QT-EST',
            'slug' => 'quartier-est',
        ]);

        Affectation::create([
            'id' => (string) Str::uuid(),
            'agent_id' => $this->agent1->id,
            'fonction_id' => $this->fonctionDelegue->id,
            'quartier_id' => $quartier->id,
            'date_debut' => now(),
            'statut' => 'actif',
        ]);

        // 2. Perform update: set agent2 as new delegue
        $payload = [
            'nom' => 'Quartier Est Modifié',
            'code' => 'QT-EST-MOD',
            'delegue_id' => $this->agent2->id,
        ];

        $response = $this->actingAs($this->admin)
                         ->put(route('quartier.update', $quartier->id), $payload);

        $response->assertRedirect(route('quartier.index'));
        $response->assertSessionHas('success');

        // Check previous affectation is marked as termine
        $this->assertDatabaseHas('affectations', [
            'agent_id' => $this->agent1->id,
            'quartier_id' => $quartier->id,
            'statut' => 'termine',
        ]);

        // Check new affectation is active
        $this->assertDatabaseHas('affectations', [
            'agent_id' => $this->agent2->id,
            'quartier_id' => $quartier->id,
            'statut' => 'actif',
        ]);

        $this->assertEquals($this->agent2->id, $quartier->refresh()->delegue->id);
    }

    /**
     * Test: Listing Carrés (Index page) works.
     */
    public function test_page_index_carre_charge_avec_succes(): void
    {
        $response = $this->actingAs($this->admin)
                         ->get(route('carre.index'));

        $response->assertStatus(200);
        $response->assertViewIs('parameters.carre.index');
        $response->assertViewHas('entities');
    }

    /**
     * Test: Accessing creation form for Carré works.
     */
    public function test_page_create_carre_charge_avec_succes(): void
    {
        $response = $this->actingAs($this->admin)
                         ->get(route('carre.create'));

        $response->assertStatus(200);
        $response->assertViewIs('parameters.carre.create');
        $response->assertViewHas('quartiers');
        $response->assertViewHas('agents');
    }

    /**
     * Test: Storing a new Carré and assigning Chef de Carré.
     */
    public function test_enregistrement_carre_avec_chef_avec_succes(): void
    {
        $quartier = Quartier::create([
            'id' => (string) Str::uuid(),
            'nom' => 'Quartier Sud',
            'code' => 'QT-SUD',
            'slug' => 'quartier-sud',
        ]);

        $payload = [
            'nom' => 'Carré 42',
            'code' => 'CR-42',
            'quartier_id' => $quartier->id,
            'description' => 'Bloc central de collecte',
            'couleur' => '#2ECC71',
            'icone' => 'bi-grid',
            'chef_carre_id' => $this->agent1->id,
        ];

        $response = $this->actingAs($this->admin)
                         ->post(route('carre.store'), $payload);

        $response->assertRedirect(route('carre.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('carres', [
            'nom' => 'Carré 42',
            'code' => 'CR-42',
            'quartier_id' => $quartier->id,
        ]);

        $carre = Carre::where('code', 'CR-42')->first();

        // Check active CHEF_CARRE affectation
        $this->assertDatabaseHas('affectations', [
            'agent_id' => $this->agent1->id,
            'fonction_id' => $this->fonctionChefCarre->id,
            'carre_id' => $carre->id,
            'statut' => 'actif',
        ]);

        $this->assertEquals($this->agent1->id, $carre->chef_carre->id);
    }
}
