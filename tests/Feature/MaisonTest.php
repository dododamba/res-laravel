<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Personne;
use App\Models\Agent;
use App\Models\Maison;
use App\Models\Parameters\Quartier;
use App\Models\Parameters\Carre;
use App\Models\Parameters\Fonction;
use App\Enums\MaisonStatut;
use App\Enums\AgentStatut;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MaisonTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $enqueteurUser;
    private Agent $enqueteurAgent;
    private Carre $carre;
    private Fonction $fonction;

    protected function setUp(): void
    {
        parent::setUp();

        // Selective bypass of ValidateCsrfToken middleware
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        // 1. Create geography
        $quartier = Quartier::create([
            'id' => (string) Str::uuid(),
            'nom' => 'Quartier Test',
            'code' => 'QT-TEST',
            'slug' => 'quartier-test'
        ]);

        $this->carre = Carre::create([
            'id' => (string) Str::uuid(),
            'quartier_id' => $quartier->id,
            'nom' => 'Carré A1',
            'code' => 'CR-A1',
            'slug' => 'carre-a1'
        ]);

        // 2. Create Fonction for Agent
        $this->fonction = Fonction::create([
            'id' => (string) Str::uuid(),
            'nom' => 'Enquêteur Terrain',
            'code' => 'ENQ',
            'slug' => 'enqueteur-terrain',
        ]);

        // 3. Create Admin user
        $this->admin = User::create([
            'id' => (string) Str::uuid(),
            'email' => 'admin@recensement.gov',
            'password' => bcrypt('password123'),
            'firstname' => 'Admin',
            'lastname' => 'User',
            'is_verified' => true,
            'is_active' => true,
        ]);
        $this->admin->roles()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Admin',
            'slug' => 'ROLE_ADMIN',
            'description' => 'System Administrator'
        ]);

        // 4. Create Enqueteur user and agent
        $this->enqueteurUser = User::create([
            'id' => (string) Str::uuid(),
            'email' => 'enqueteur@recensement.gov',
            'password' => bcrypt('password123'),
            'firstname' => 'Émile',
            'lastname' => 'Zola',
            'is_verified' => true,
            'is_active' => true,
        ]);
        $this->enqueteurUser->roles()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Enquêteur',
            'slug' => 'ROLE_ENQUETEUR',
            'description' => 'Field collector'
        ]);

        $personne = Personne::create([
            'id' => (string) Str::uuid(),
            'nom' => 'Zola',
            'prenom' => 'Émile',
            'telephone' => '0612345678',
            'email' => 'enqueteur@recensement.gov'
        ]);

        $this->enqueteurAgent = Agent::create([
            'id' => (string) Str::uuid(),
            'personne_id' => $personne->id,
            'fonction_id' => $this->fonction->id,
            'user_id' => $this->enqueteurUser->id,
            'matricule' => 'AGT-2026-0001',
            'sexe' => 'M',
            'statut' => AgentStatut::ACTIF,
        ]);

        // Refresh users to ensure the in-memory models load newly created relationships (Agent, Role)
        $this->admin->refresh();
        $this->enqueteurUser->refresh();
    }

    /**
     * Test: Listing houses (Maison index) works.
     */
    public function test_page_index_maison_charge_avec_succes(): void
    {
        $response = $this->actingAs($this->admin)
                         ->get(route('maison.index'));

        $response->assertStatus(200);
        $response->assertViewIs('maison.index');
    }

    /**
     * Test: Accessing the creation form works.
     */
    public function test_page_create_maison_charge_avec_succes(): void
    {
        $response = $this->actingAs($this->enqueteurUser)
                         ->get(route('maison.create'));

        $response->assertStatus(200);
        $response->assertViewIs('maison.create');
        $response->assertViewHas('carres');
    }

    /**
     * Test: Submitting a valid payload stores a new Maison.
     */
    public function test_enregistrement_maison_avec_succes(): void
    {
        $payload = [
            'numero_porte' => 42,
            'adresse' => 'Rue des Poètes',
            'carre_id' => $this->carre->id,
            'nombre_hommes' => 2,
            'nombre_femmes' => 2,
            'nombre_enfants' => 1,
            'gps_latitude' => -4.321,
            'gps_longitude' => 15.301,
        ];

        $response = $this->actingAs($this->enqueteurUser)
                         ->post(route('maison.store'), $payload);

        $response->assertRedirect(route('maison.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('maisons', [
            'numero_porte' => 42,
            'adresse' => 'Rue des Poètes',
            'carre_id' => $this->carre->id,
            'statut' => MaisonStatut::BROUILLON->value,
            'enqueteur_id' => $this->enqueteurAgent->id,
        ]);
    }

    /**
     * Test: Accessing show page displays details.
     */
    public function test_page_show_maison_charge_avec_succes(): void
    {
        $maison = Maison::create([
            'id' => (string) Str::uuid(),
            'numero_porte' => 12,
            'adresse' => 'Rue Royale',
            'carre_id' => $this->carre->id,
            'nombre_hommes' => 1,
            'nombre_femmes' => 1,
            'nombre_enfants' => 0,
            'enqueteur_id' => $this->enqueteurAgent->id,
            'statut' => MaisonStatut::BROUILLON,
        ]);

        $response = $this->actingAs($this->enqueteurUser)
                         ->get(route('maison.show', $maison));

        $response->assertStatus(200);
        $response->assertViewIs('maison.show');
        $response->assertViewHas('maison');
    }

    /**
     * Test: Accessing edit form displays values.
     */
    public function test_page_edit_maison_charge_avec_succes(): void
    {
        $maison = Maison::create([
            'id' => (string) Str::uuid(),
            'numero_porte' => 12,
            'adresse' => 'Rue Royale',
            'carre_id' => $this->carre->id,
            'nombre_hommes' => 1,
            'nombre_femmes' => 1,
            'nombre_enfants' => 0,
            'enqueteur_id' => $this->enqueteurAgent->id,
            'statut' => MaisonStatut::BROUILLON,
        ]);

        $response = $this->actingAs($this->enqueteurUser)
                         ->get(route('maison.edit', $maison));

        $response->assertStatus(200);
        $response->assertViewIs('maison.edit');
        $response->assertViewHas('maison');
        $response->assertViewHas('carres');
    }

    /**
     * Test: Updating a Maison works.
     */
    public function test_modification_maison_avec_succes(): void
    {
        $maison = Maison::create([
            'id' => (string) Str::uuid(),
            'numero_porte' => 12,
            'adresse' => 'Rue Royale',
            'carre_id' => $this->carre->id,
            'nombre_hommes' => 1,
            'nombre_femmes' => 1,
            'nombre_enfants' => 0,
            'enqueteur_id' => $this->enqueteurAgent->id,
            'statut' => MaisonStatut::BROUILLON,
        ]);

        $payload = [
            'numero_porte' => 14,
            'adresse' => 'Rue Royale Modifiée',
            'carre_id' => $this->carre->id,
            'nombre_hommes' => 2,
            'nombre_femmes' => 2,
            'nombre_enfants' => 3,
            'gps_latitude' => -4.567,
            'gps_longitude' => 15.678,
        ];

        $response = $this->actingAs($this->enqueteurUser)
                         ->put(route('maison.update', $maison), $payload);

        $response->assertRedirect(route('maison.show', $maison));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('maisons', [
            'id' => $maison->id,
            'numero_porte' => 14,
            'adresse' => 'Rue Royale Modifiée',
            'nombre_enfants' => 3,
        ]);
    }

    /**
     * Test: Workflow transition from Brouillon to Soumis.
     */
    public function test_transition_workflow_brouillon_a_soumis(): void
    {
        $maison = Maison::create([
            'id' => (string) Str::uuid(),
            'numero_porte' => 12,
            'adresse' => 'Rue Royale',
            'carre_id' => $this->carre->id,
            'nombre_hommes' => 1,
            'nombre_femmes' => 1,
            'nombre_enfants' => 0,
            'enqueteur_id' => $this->enqueteurAgent->id,
            'statut' => MaisonStatut::BROUILLON,
        ]);

        $response = $this->actingAs($this->enqueteurUser)
                         ->post(route('maison.transition', $maison), [
                             'target_status' => 'soumis',
                         ]);

        $response->assertRedirect(route('maison.show', $maison));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('maisons', [
            'id' => $maison->id,
            'statut' => MaisonStatut::SOUMIS->value,
        ]);
    }
}
