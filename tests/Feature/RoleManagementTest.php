<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Models\Role;
use Spatie\Permission\Models\Permission;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $schoolAdmin1;
    protected User $schoolAdmin2;
    protected School $school1;
    protected School $school2;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed basic permissions and modules
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\ModuleSeeder::class);

        // Fetch School Admin 1 and their School
        $this->schoolAdmin1 = User::whereHas('roles', function ($query) {
            $query->where('name', 'School Admin');
        })->first();
        $this->school1 = School::find($this->schoolAdmin1->school_id);

        // Create School 2 and a School Admin 2
        $this->school2 = School::create([
            'name' => 'School 2',
            'email' => 'school2@edu.com',
            'status' => 'active'
        ]);

        $this->schoolAdmin2 = User::create([
            'school_id' => $this->school2->id,
            'name' => 'Admin 2',
            'email' => 'admin2@school2.com',
            'password' => bcrypt('password'),
            'status' => 'active'
        ]);

        // Fetch pre-created School Admin role for School 2
        $roleAdmin2 = Role::where('school_id', $this->school2->id)
            ->where('name', 'School Admin')
            ->first();
        $this->schoolAdmin2->assignRole($roleAdmin2);
    }

    public function test_can_update_school_admin_role_without_duplicate_error()
    {
        $role1 = Role::where('school_id', $this->school1->id)
            ->where('name', 'School Admin')
            ->first();

        // Admin of School 1 updates their own School Admin role (submitting the same name)
        $response = $this->actingAs($this->schoolAdmin1)
            ->putJson("/api/roles/{$role1->id}", [
                'name' => 'School Admin',
                'permissions' => ['role.view', 'role.edit']
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('role.name', 'School Admin');
    }

    public function test_different_schools_can_create_identically_named_custom_roles()
    {
        // School 1 creates a custom role "Receptionist"
        $response1 = $this->actingAs($this->schoolAdmin1)
            ->postJson('/api/roles', [
                'name' => 'Receptionist',
                'permissions' => ['user.view']
            ]);
        $response1->assertStatus(201);

        // School 2 creates a custom role "Receptionist"
        $response2 = $this->actingAs($this->schoolAdmin2)
            ->postJson('/api/roles', [
                'name' => 'Receptionist',
                'permissions' => ['user.view']
            ]);
        $response2->assertStatus(201);

        // Assert database has both scoped correctly
        $this->assertDatabaseHas('roles', [
            'school_id' => $this->school1->id,
            'name' => 'Receptionist'
        ]);

        $this->assertDatabaseHas('roles', [
            'school_id' => $this->school2->id,
            'name' => 'Receptionist'
        ]);
    }
}
