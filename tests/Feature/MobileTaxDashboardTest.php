<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Personne;
use App\Models\User;
use App\Models\Parameters\Fonction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MobileTaxDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Agent $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $personne = Personne::create([
            'id' => (string) Str::uuid(),
            'nom' => 'Agent',
            'prenom' => 'DashboardTest',
            'email' => 'agent.dash@example.com',
            'genre' => 'M',
        ]);

        $fonctionAgent = Fonction::create([
            'id' => (string) Str::uuid(),
            'nom' => 'Enquêteur Terrain',
            'code' => 'ENQ_DASH',
            'slug' => 'enqueteur-terrain-dash',
        ]);

        $this->agent = Agent::create([
            'id' => (string) Str::uuid(),
            'personne_id' => $personne->id,
            'fonction_id' => $fonctionAgent->id,
            'matricule' => 'AG-DASH-01',
            'statut' => 'actif',
        ]);

        $this->user = User::create([
            'name' => 'Agent Dashboard User',
            'email' => 'agent.dash@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->agent->update(['user_id' => $this->user->id]);
    }

    /** @test */
    public function authenticated_agent_can_fetch_tax_dashboard()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/mobile/tax-dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'annee',
                    'agent_nom',
                    'affectation',
                    'kpis' => [
                        'total_encaisse',
                        'nombre_paiements',
                        'collecte_du_jour',
                        'paiements_du_jour',
                        'collecte_du_mois',
                        'operateurs_recouvres',
                        'operateurs_en_retard',
                    ],
                ]
            ]);
    }
}
