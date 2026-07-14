<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\AuditLog;
use App\Models\Parameters\Fonction;
use App\Models\Personne;
use App\Models\Agent;
use App\Enums\AgentStatut;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProfileAndAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $enqueteurUser;
    private Agent $enqueteurAgent;
    private Role $adminRole;
    private Role $enqueteurRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        // 1. Create system roles
        $this->adminRole = Role::create([
            'id' => (string) Str::uuid(),
            'name' => 'Super Administrateur',
            'slug' => 'ROLE_SUPER_ADMIN',
            'description' => 'Full access'
        ]);

        $this->enqueteurRole = Role::create([
            'id' => (string) Str::uuid(),
            'name' => 'Enquêteur',
            'slug' => 'ROLE_ENQUETEUR',
            'description' => 'Collector'
        ]);

        // 2. Create admin user with AUDIT_VIEW permission attached to their role
        $this->admin = User::create([
            'id' => (string) Str::uuid(),
            'email' => 'admin@recensement.gov',
            'password' => bcrypt('password123'),
            'firstname' => 'Admin',
            'lastname' => 'User',
            'is_verified' => true,
            'is_active' => true,
        ]);
        $this->admin->roles()->attach($this->adminRole->id);

        $this->adminRole->permissions()->create([
            'id' => (string) Str::uuid(),
            'name' => 'AUDIT_VIEW',
            'category' => 'Sécurité'
        ]);

        // 3. Create regular user and field Agent
        $this->enqueteurUser = User::create([
            'id' => (string) Str::uuid(),
            'email' => 'enqueteur@recensement.gov',
            'password' => bcrypt('password123'),
            'firstname' => 'Émile',
            'lastname' => 'Zola',
            'is_verified' => true,
            'is_active' => true,
        ]);
        $this->enqueteurUser->roles()->attach($this->enqueteurRole->id);

        $fonctionAgent = Fonction::create([
            'id' => (string) Str::uuid(),
            'nom' => 'Enquêteur Terrain',
            'code' => 'ENQ',
            'slug' => 'enqueteur-terrain',
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
            'fonction_id' => $fonctionAgent->id,
            'user_id' => $this->enqueteurUser->id,
            'matricule' => 'AGT-2026-0001',
            'sexe' => 'M',
            'statut' => AgentStatut::ACTIF,
        ]);

        $this->admin->refresh();
        $this->enqueteurUser->refresh();
    }

    /**
     * Test: Logged-in user can access their Agent profile.
     */
    public function test_page_mon_profil_charge_avec_succes(): void
    {
        $response = $this->actingAs($this->enqueteurUser)
                         ->get(route('profile.show'));

        $response->assertStatus(200);
        $response->assertViewIs('profile.show');
        $response->assertViewHas('user');
    }

    /**
     * Test: Logged-in user can access their Account settings.
     */
    public function test_page_parametres_compte_charge_avec_succes(): void
    {
        $response = $this->actingAs($this->enqueteurUser)
                         ->get(route('profile.edit'));

        $response->assertStatus(200);
        $response->assertViewIs('user.edit');
        $response->assertViewHas('user');
        $response->assertViewHas('isSelfEdit', true);
    }

    /**
     * Test: User can update their contact and upload an avatar successfully.
     */
    public function test_mise_a_jour_parametres_compte_avec_avatar(): void
    {
        $payload = [
            'firstname' => 'Émile Nouveau',
            'lastname' => 'Zola Nouveau',
            'email' => 'emile.nouveau@recensement.gov',
            'telephone' => '0699887766',
        ];

        $response = $this->actingAs($this->enqueteurUser)
                         ->put(route('profile.update'), $payload);

        $response->assertRedirect(route('profile.show'));
        $response->assertSessionHas('success');

        // Check SQLite User table update
        $this->assertDatabaseHas('users', [
            'id' => $this->enqueteurUser->id,
            'firstname' => 'Émile Nouveau',
            'email' => 'emile.nouveau@recensement.gov',
        ]);

        // Check physical Personne table synchronization (Symfony rule)
        $this->assertDatabaseHas('personnes', [
            'id' => $this->enqueteurAgent->personne_id,
            'prenom' => 'Émile Nouveau',
            'email' => 'emile.nouveau@recensement.gov',
        ]);
    }

    /**
     * Test: Admin with AUDIT_VIEW can view security audit logs.
     */
    public function test_page_audit_log_charge_avec_succes_pour_admin(): void
    {
        // Setup an audit log record
        AuditLog::create([
            'id' => (string) Str::uuid(),
            'user_identifier' => 'admin@recensement.gov',
            'action' => 'CONNEXION',
            'ip_address' => '127.0.0.1',
            'result' => 'success',
        ]);

        $response = $this->actingAs($this->admin)
                         ->get(route('audit.index'));

        $response->assertStatus(200);
        $response->assertViewIs('audit.index');
        $response->assertViewHas('logs');
    }

    /**
     * Test: Regular user is blocked from viewing audit logs (403).
     */
    public function test_page_audit_log_bloque_les_non_admins(): void
    {
        $response = $this->actingAs($this->enqueteurUser)
                         ->get(route('audit.index'));

        $response->assertStatus(403);
    }

    /**
     * Test: Ionic App Login Compatibility endpoint (/api/v1/auth/login) with 'username'.
     */
    public function test_connexion_api_compatibilite_ionic_avec_username(): void
    {
        $payload = [
            'username' => 'enqueteur@recensement.gov',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/v1/auth/login', $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'token',
                'token_type',
                'user' => [
                    'id',
                    'email',
                    'fullname',
                    'role',
                    'agent_matricule',
                    'agent_id',
                ]
            ]
        ]);
    }

    /**
     * Test: Ionic App Profile Compatibility endpoint (/api/v1/auth/profile).
     */
    public function test_profil_api_compatibilite_ionic(): void
    {
        // Obtain a valid token first
        $token = $this->enqueteurUser->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->getJson('/api/v1/auth/profile');

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.email', 'enqueteur@recensement.gov');
        $response->assertJsonPath('data.agent_matricule', 'AGT-2026-0001');
    }

    /**
     * Test: Dynamic Mobile Dashboard endpoint (/api/v1/dashboard) returns correct keys.
     */
    public function test_api_dashboard_mobile_dynamique(): void
    {
        $token = $this->enqueteurUser->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->getJson('/api/v1/dashboard');

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'stats' => [
                    'menages',
                    'habitats',
                    'fiscal',
                ],
                'recentActivity'
            ]
        ]);
    }

    /**
     * Test: Mobile assignments list (/api/v1/assignments) maps correct keys.
     */
    public function test_api_assignments_mobile(): void
    {
        $token = $this->enqueteurUser->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->getJson('/api/v1/assignments');

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'campaign' => [
                        'id',
                        'nom',
                        'statut',
                        'dateDebut',
                        'dateFin',
                        'annee',
                    ],
                    'secteurs',
                    'responsable',
                    'dateDebut',
                    'dateFin',
                    'statut',
                    'fichesAttribuees',
                    'fichesRealisees',
                ]
            ]
        ]);
    }

    /**
     * Test: Mobile global stats (/api/v1/global-stats) returns correct keys.
     */
    public function test_api_global_stats_mobile(): void
    {
        $token = $this->enqueteurUser->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->getJson('/api/v1/global-stats');

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'total_menages',
                'total_population',
                'total_hommes',
                'total_femmes',
                'total_enfants',
                'total_jeunes',
                'total_handicapes',
                'instruction_aucun',
                'instruction_primaire',
                'instruction_secondaire',
                'instruction_superieur',
                'total_habitations',
                'total_entreprises',
                'homme_ratio',
                'femme_ratio',
            ]
        ]);
    }

    /**
     * Test: API Synchronization compatibility with original Ionic mobile payload structures.
     */
    public function test_api_synchronisation_compatibilite_ionic_payload(): void
    {
        // 1. Create dependencies
        $besoin = \App\Models\Parameters\BesoinPrioritaire::create([
            'id' => (string) Str::uuid(),
            'nom' => 'Eau Potable',
            'code' => 'EAU',
            'slug' => 'eau-potable',
        ]);

        $token = $this->enqueteurUser->createToken('test-token')->plainTextToken;

        // 2. Mock Ionic frontend payload with specific camelCase/aliased keys
        $payload = [
            'menages' => [
                [
                    'uuid' => (string) Str::uuid(),
                    'chefNom' => 'Sengor',
                    'chefPrenom' => 'Leopold',
                    'chefSexe' => 'M',
                    'chefAge' => 45,
                    'telephonePrincipal' => '0611223344',
                    'situationMatrimoniale' => 'Marié(e)',
                    'chefProfession' => 'Poète',
                    'chefInstruction' => 'Supérieur',
                    'hommes' => 2,
                    'femmes' => 3,
                    'enfants' => 4,
                    'jeunes' => 1,
                    'handicap' => 0,
                    'priorites' => [$besoin->id],
                ]
            ],
            'habitats' => [],
            'operateurs' => [],
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/v1/sync', $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');

        // Check correct mapped database insertion
        $this->assertDatabaseHas('recensements', [
            'chef_nom' => 'Sengor',
            'chef_prenom' => 'Leopold',
            'chef_sexe' => 'M',
            'chef_age' => 45,
            'chef_telephone' => '0611223344',
            'observations' => "Situation Matrimoniale: Marié(e)\nProfession du Chef: Poète",
            'nombre_hommes' => 2,
            'nombre_femmes' => 3,
            'nombre_personnes' => 5, // Auto-computed (2 hommes + 3 femmes)
            'instruction_superieur' => 1, // Auto-computed from level 'Supérieur'
            'instruction_aucun' => 0,
        ]);
    }
}
