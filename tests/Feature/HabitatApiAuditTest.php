<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Agent;
use App\Models\Personne;
use App\Models\Maison;
use App\Models\Parameters\Quartier;
use App\Models\Parameters\Carre;
use App\Models\Parameters\Fonction;
use App\Models\Parameters\TypeBatiment;
use App\Models\Parameters\CategorieActivite;
use App\Models\Parameters\TypePropriete;
use App\Models\Parameters\SourceEau;
use App\Models\Parameters\SourceEnergie;
use App\Models\Parameters\Assainissement;
use App\Models\Parameters\GestionDechet;
use App\Enums\AgentStatut;
use App\Enums\MaisonStatut;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class HabitatApiAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $enqueteurUser;
    private Agent $enqueteurAgent;
    private Quartier $quartier;
    private Carre $carre;
    private TypeBatiment $typeBatiment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        // 1. Géographie
        $this->quartier = Quartier::create([
            'id' => (string) Str::uuid(),
            'nom' => 'Quartier Mairie 3',
            'code' => 'QM3',
            'slug' => 'quartier-mairie-3'
        ]);

        $this->carre = Carre::create([
            'id' => (string) Str::uuid(),
            'quartier_id' => $this->quartier->id,
            'nom' => 'Carré 12',
            'code' => 'CR-12',
            'slug' => 'carre-12'
        ]);

        // 2. Paramètre référentiel
        $this->typeBatiment = TypeBatiment::create([
            'id' => (string) Str::uuid(),
            'nom' => 'Villa / Maison individuelle',
            'code' => 'VILLA',
            'slug' => 'villa-maison-individuelle'
        ]);

        // 3. Admin & Enquêteur
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
            'description' => 'Admin'
        ]);

        $this->enqueteurUser = User::create([
            'id' => (string) Str::uuid(),
            'email' => 'enqueteur.habitat@recensement.gov',
            'password' => bcrypt('password123'),
            'firstname' => 'Jean',
            'lastname' => 'Dupont',
            'is_verified' => true,
            'is_active' => true,
        ]);
        $this->enqueteurUser->roles()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Enquêteur',
            'slug' => 'ROLE_ENQUETEUR',
            'description' => 'Field collector'
        ]);

        $fonction = Fonction::create([
            'id' => (string) Str::uuid(),
            'nom' => 'Enquêteur',
            'code' => 'ENQ',
            'slug' => 'enqueteur'
        ]);

        $personne = Personne::create([
            'id' => (string) Str::uuid(),
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'telephone' => '0699001122',
            'email' => 'enqueteur.habitat@recensement.gov'
        ]);

        $this->enqueteurAgent = Agent::create([
            'id' => (string) Str::uuid(),
            'personne_id' => $personne->id,
            'fonction_id' => $fonction->id,
            'user_id' => $this->enqueteurUser->id,
            'matricule' => 'AGT-2026-HAB',
            'sexe' => 'M',
            'statut' => AgentStatut::ACTIF,
        ]);

        \App\Models\Affectation::create([
            'id' => (string) Str::uuid(),
            'agent_id' => $this->enqueteurAgent->id,
            'fonction_id' => $fonction->id,
            'quartier_id' => $this->quartier->id,
            'carre_id' => $this->carre->id,
            'date_debut' => now()->subDays(5),
            'statut' => 'actif',
        ]);

        $this->admin->refresh();
        $this->enqueteurUser->refresh();
    }

    /**
     * Test 1: Création directe via POST /api/v1/maisons avec numéro de porte string et champs étendus.
     */
    public function test_creation_maison_api_avec_numero_porte_string_et_champs_etendus(): void
    {
        $payload = [
            'uuid' => (string) Str::uuid(),
            'numeroPorte' => 'PORTE-104-B',
            'adresse' => 'Avenue du 15 Août',
            'carre_id' => $this->carre->id,
            'typeHabitation' => $this->typeBatiment->id,
            'anneeConstruction' => 2015,
            'nombrePieces' => 5,
            'nombreEtages' => 1,
            'materiauMurs' => 'Ciment / Parpaing',
            'materiauToiture' => 'Zinc / Tôle ondulée',
            'gpsLatitude' => -4.325,
            'gpsLongitude' => 15.312,
            'gpsAltitude' => 280,
            'gpsPrecision' => 4,
            'gpsDateCapture' => now()->toIso8601String(),
            'proprietaire_nom' => 'Paul Nguesso',
            'proprietaire_telephone' => '066554433',
            'documents' => [
                [
                    'type' => 'facade',
                    'label' => 'Photo Façade',
                    'preview' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
                ]
            ]
        ];

        $response = $this->actingAs($this->enqueteurUser, 'sanctum')
                         ->postJson('/api/v1/maisons', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('maisons', [
            'id' => $payload['uuid'],
            'numero_porte' => 'PORTE-104-B',
            'adresse' => 'Avenue du 15 Août',
            'annee_construction' => 2015,
            'nombre_pieces' => 5,
            'nombre_etages' => 1,
            'materiau_murs' => 'Ciment / Parpaing',
            'carre_id' => $this->carre->id,
            'statut' => MaisonStatut::SOUMIS->value
        ]);

        $maison = Maison::find($payload['uuid']);
        $this->assertNotNull($maison);
        $this->assertEquals(1, $maison->getMedia('photos_habitation')->count());
    }

    /**
     * Test 2: Synchronisation push en lot avec conversion des libellés texte en UUID et enregistrement des médias.
     */
    public function test_push_sync_maison_avec_libelles_texte_et_photos(): void
    {
        $habitatId = (string) Str::uuid();
        $payload = [
            'habitats' => [
                [
                    'id' => $habitatId,
                    'numero_porte' => 'REF-99',
                    'adresse' => 'Rue des Palmier',
                    'carre_id' => $this->carre->id,
                    'typeHabitation' => 'Villa / Maison individuelle', // libellé texte à résoudre en UUID
                    'usage' => 'Habitation exclusive',
                    'statutFoncier' => 'Titre foncier',
                    'anneeConstruction' => 2020,
                    'nombrePieces' => 4,
                    'gps_latitude' => -4.123,
                    'gps_longitude' => 15.456,
                    'documents' => [
                        [
                            'type' => 'facade',
                            'label' => 'Photo Façade',
                            'preview' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($this->enqueteurUser, 'sanctum')
                         ->postJson('/api/v1/sync', $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('maisons', [
            'id' => $habitatId,
            'numero_porte' => 'REF-99',
            'type_construction_id' => $this->typeBatiment->id,
            'annee_construction' => 2020,
            'nombre_pieces' => 4
        ]);
    }

    /**
     * Test 3: Statistiques d'habitation comptabilisées correctement dans StatisticsService.
     */
    public function test_statistiques_comprennent_les_habitations(): void
    {
        Maison::create([
            'id' => (string) Str::uuid(),
            'numero_porte' => '10',
            'adresse' => 'Rue Test',
            'carre_id' => $this->carre->id,
            'statut' => MaisonStatut::VALIDE
        ]);

        $service = app(\App\Services\StatisticsService::class);
        $globalStats = $service->getGlobalStats($this->admin);

        $this->assertGreaterThanOrEqual(1, $globalStats['total_habitats']);

        $quartierStats = $service->getQuartierStats($this->admin);
        $this->assertNotEmpty($quartierStats['items']);
    }
}
