<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaaSPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles and permissions
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_super_admin_can_login_and_retrieve_details()
    {
        $superAdmin = User::role('Super Admin')->first();

        $response = $this->actingAs($superAdmin)
            ->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('user.email', $superAdmin->email);
    }

    public function test_super_admin_can_create_school_and_admin()
    {
        $superAdmin = User::role('Super Admin')->first();

        $data = [
            'name' => 'Greenwood High',
            'email' => 'greenwood@edu.com',
            'phone' => '1234567890',
            'address' => '123 Street',
            'status' => 'active',
            'admin_name' => 'Greenwood Admin',
            'admin_email' => 'admin_unique@greenwood.com',
            'admin_password' => 'secret123',
            'admin_mobile' => '9876543210'
        ];

        $response = $this->actingAs($superAdmin)
            ->postJson('/api/schools', $data);

        $response->assertStatus(210); // Standard custom code or 201

        $this->assertDatabaseHas('schools', ['name' => 'Greenwood High']);
        $this->assertDatabaseHas('users', ['email' => 'admin_unique@greenwood.com']);
    }

    public function test_school_admin_cannot_access_other_schools()
    {
        $school1 = School::create([
            'name' => 'School One',
            'email' => 's1@test.com',
            'status' => 'active'
        ]);

        $school2 = School::create([
            'name' => 'School Two',
            'email' => 's2@test.com',
            'status' => 'active'
        ]);

        $schoolAdmin = User::create([
            'name' => 'Admin One',
            'email' => 'admin1@test.com',
            'password' => bcrypt('password'),
            'school_id' => $school1->id,
            'status' => 'active'
        ]);
        $schoolAdmin->assignRole('School Admin');

        // Cannot view school 2
        $response = $this->actingAs($schoolAdmin)
            ->getJson("/api/schools/{$school2->id}");
        $response->assertStatus(403);

        // Cannot delete school 2
        $response = $this->actingAs($schoolAdmin)
            ->deleteJson("/api/schools/{$school2->id}");
        $response->assertStatus(403);
    }

    public function test_super_admin_can_impersonate_school_admin()
    {
        $superAdmin = User::role('Super Admin')->first();
        
        $school = School::create([
            'name' => 'Impersonate Target School',
            'email' => 'target@school.com',
            'status' => 'active'
        ]);

        $schoolAdmin = User::create([
            'name' => 'Target Admin',
            'email' => 'targetadmin@school.com',
            'password' => bcrypt('password'),
            'school_id' => $school->id,
            'status' => 'active'
        ]);
        $schoolAdmin->assignRole('School Admin');

        // Impersonate
        $response = $this->actingAs($superAdmin)
            ->postJson("/api/impersonate/login/{$school->id}");

        $response->assertStatus(200)
            ->assertJsonPath('user.email', $schoolAdmin->email)
            ->assertJsonPath('impersonating', true);
    }
}
