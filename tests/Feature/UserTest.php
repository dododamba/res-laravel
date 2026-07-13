<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $testUser;
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

        // 2. Create Admin user
        $this->admin = User::create([
            'id' => (string) Str::uuid(),
            'email' => 'admin@recensement.gov',
            'password' => bcrypt('password123'),
            'firstname' => 'System',
            'lastname' => 'Admin',
            'is_verified' => true,
            'is_active' => true,
        ]);
        $this->admin->roles()->attach($this->adminRole->id);

        // 3. Create normal test user
        $this->testUser = User::create([
            'id' => (string) Str::uuid(),
            'email' => 'enqueteur@recensement.gov',
            'password' => bcrypt('password123'),
            'firstname' => 'Émile',
            'lastname' => 'Zola',
            'is_verified' => true,
            'is_active' => true,
        ]);
        $this->testUser->roles()->attach($this->enqueteurRole->id);

        $this->admin->refresh();
        $this->testUser->refresh();
    }

    /**
     * Test: Listing system users works.
     */
    public function test_page_index_user_charge_avec_succes(): void
    {
        $response = $this->actingAs($this->admin)
                         ->get(route('user.index'));

        $response->assertStatus(200);
        $response->assertViewIs('user.index');
        $response->assertViewHas('users');
        $response->assertViewHas('roles');
    }

    /**
     * Test: Accessing user details works.
     */
    public function test_page_show_user_charge_avec_succes(): void
    {
        $response = $this->actingAs($this->admin)
                         ->get(route('user.show', $this->testUser));

        $response->assertStatus(200);
        $response->assertViewIs('user.show');
        $response->assertViewHas('user');
        $response->assertViewHas('allPermissions');
    }

    /**
     * Test: Accessing user edit form works.
     */
    public function test_page_edit_user_charge_avec_succes(): void
    {
        $response = $this->actingAs($this->admin)
                         ->get(route('user.edit', $this->testUser));

        $response->assertStatus(200);
        $response->assertViewIs('user.edit');
        $response->assertViewHas('user');
        $response->assertViewHas('roles');
    }

    /**
     * Test: Updating user details and roles works.
     */
    public function test_modification_user_avec_succes(): void
    {
        $payload = [
            'firstname' => 'Émile Modifié',
            'lastname' => 'Zola Modifié',
            'email' => 'emile.modified@recensement.gov',
            'telephone' => '1234567890',
            'status' => 'active',
            'is_active' => 1,
            'roles' => [$this->adminRole->id], // Promote to admin role!
        ];

        $response = $this->actingAs($this->admin)
                         ->put(route('user.update', $this->testUser), $payload);

        $response->assertRedirect(route('user.show', $this->testUser));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $this->testUser->id,
            'firstname' => 'Émile Modifié',
            'email' => 'emile.modified@recensement.gov',
        ]);

        $this->testUser->refresh();
        $this->assertTrue($this->testUser->hasRole('ROLE_SUPER_ADMIN'));
    }
}
