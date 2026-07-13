<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Parameters\Quartier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $enqueteur;
    private Role $adminRole;
    private Role $enqueteurRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        // 1. Create Roles
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

        // 2. Create Users
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

        $this->enqueteur = User::create([
            'id' => (string) Str::uuid(),
            'email' => 'recenseur@recensement.gov',
            'password' => bcrypt('password123'),
            'firstname' => 'Recenseur',
            'lastname' => 'User',
            'is_verified' => true,
            'is_active' => true,
        ]);
        $this->enqueteur->roles()->attach($this->enqueteurRole->id);
    }

    /**
     * Test: Unauthenticated request is blocked.
     */
    public function test_api_admin_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/admin/quartiers');
        $response->assertStatus(401);
    }

    /**
     * Test: Authenticated non-admin request is forbidden.
     */
    public function test_api_admin_blocks_non_admin(): void
    {
        $response = $this->actingAs($this->enqueteur)
                         ->getJson('/api/v1/admin/quartiers');
        $response->assertStatus(403);
    }

    /**
     * Test: Authenticated admin request is allowed and retrieves data.
     */
    public function test_api_admin_allows_admin_to_list(): void
    {
        Quartier::create([
            'id' => (string) Str::uuid(),
            'nom' => 'Quartier Test',
            'code' => 'Q_TEST',
            'ordre_affichage' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
                         ->getJson('/api/v1/admin/quartiers');

        $response->assertStatus(200);
        $response->assertJsonFragment(['nom' => 'Quartier Test']);
    }

    /**
     * Test: Creating a Quartier works.
     */
    public function test_api_admin_can_create_quartier(): void
    {
        $payload = [
            'nom' => 'Quartier Nouveau',
            'code' => 'Q_NEW',
            'ordre_affichage' => 10,
            'couleur' => '#ff0000',
            'icone' => 'flag',
        ];

        $response = $this->actingAs($this->admin)
                         ->postJson('/api/v1/admin/quartiers', $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('quartiers', ['nom' => 'Quartier Nouveau', 'code' => 'Q_NEW']);
    }

    /**
     * Test: Toggling, Duplicating, and Deleting a Quartier works.
     */
    public function test_api_admin_can_toggle_duplicate_and_delete_quartier(): void
    {
        $quartier = Quartier::create([
            'id' => (string) Str::uuid(),
            'nom' => 'Quartier Actions',
            'code' => 'Q_ACT',
            'ordre_affichage' => 1,
            'is_active' => true,
        ]);

        // Toggle
        $response = $this->actingAs($this->admin)
                         ->postJson("/api/v1/admin/quartiers/{$quartier->id}/toggle");
        $response->assertStatus(200);
        $this->assertDatabaseHas('quartiers', ['id' => $quartier->id, 'is_active' => false]);

        // Duplicate
        $response = $this->actingAs($this->admin)
                         ->postJson("/api/v1/admin/quartiers/{$quartier->id}/duplicate");
        $response->assertStatus(200);
        $this->assertDatabaseHas('quartiers', ['nom' => 'Quartier Actions (Copie)']);

        // Delete
        $response = $this->actingAs($this->admin)
                         ->deleteJson("/api/v1/admin/quartiers/{$quartier->id}");
        $response->assertStatus(200);
        
        $quartier->refresh();
        $this->assertNotNull($quartier->deleted_at);
    }
}
