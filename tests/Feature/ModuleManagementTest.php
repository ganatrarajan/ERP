<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Models\Module;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles, permissions and system modules
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ModuleSeeder::class);
    }

    public function test_super_admin_can_retrieve_system_modules()
    {
        $superAdmin = User::role('Super Admin')->first();

        $response = $this->actingAs($superAdmin)
            ->getJson('/api/modules');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'modules' => [
                    '*' => ['id', 'name', 'slug', 'status']
                ]
            ]);
    }

    public function test_non_super_admin_cannot_retrieve_system_modules()
    {
        $school = School::create([
            'name' => 'Test School',
            'email' => 'test@school.com',
            'status' => 'active'
        ]);

        $schoolAdmin = User::create([
            'name' => 'School Admin User',
            'email' => 'admin@school.com',
            'password' => bcrypt('password'),
            'school_id' => $school->id,
            'status' => 'active'
        ]);
        $schoolAdmin->assignRole('School Admin');

        $response = $this->actingAs($schoolAdmin)
            ->getJson('/api/modules');

        $response->assertStatus(403);
    }

    public function test_super_admin_can_view_and_update_school_modules()
    {
        $superAdmin = User::role('Super Admin')->first();
        
        $school = School::create([
            'name' => 'Module Test School',
            'email' => 'mod@school.com',
            'status' => 'active'
        ]);

        // Verify default module states are mapped/viewable
        $response = $this->actingAs($superAdmin)
            ->getJson("/api/schools/{$school->id}/modules");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'modules' => [
                    '*' => ['id', 'name', 'slug', 'is_active']
                ]
            ]);

        // Find students module
        $studentsModule = Module::where('slug', 'students')->first();

        // Toggle modules (only student module active)
        $updateResponse = $this->actingAs($superAdmin)
            ->putJson("/api/schools/{$school->id}/modules", [
                'module_ids' => [$studentsModule->id]
            ]);

        $updateResponse->assertStatus(200);

        // Verify changes are persistent in database
        $this->assertDatabaseHas('school_modules', [
            'school_id' => $school->id,
            'module_id' => $studentsModule->id,
            'is_active' => true
        ]);
    }

    public function test_module_access_enforcement_middleware()
    {
        $school = School::create([
            'name' => 'Enforced School',
            'email' => 'enforced@school.com',
            'status' => 'active'
        ]);

        $schoolAdmin = User::create([
            'name' => 'School Admin User',
            'email' => 'admin_enforce@school.com',
            'password' => bcrypt('password'),
            'school_id' => $school->id,
            'status' => 'active'
        ]);
        $schoolAdmin->assignRole('School Admin');

        $studentsModule = Module::where('slug', 'students')->first();

        // 1. Module disabled initially
        $school->modules()->sync([$studentsModule->id => ['is_active' => false]]);

        // School Admin has student.view permission but module is disabled -> returns 403
        $response = $this->actingAs($schoolAdmin)
            ->getJson('/api/students');

        $response->assertStatus(403);

        // 2. Enable module
        $school->modules()->sync([$studentsModule->id => ['is_active' => true]]);

        // School Admin has student.view permission and module is enabled -> returns 200
        $response = $this->actingAs($schoolAdmin)
            ->getJson('/api/students');

        $response->assertStatus(200);
    }

    public function test_super_admin_bypasses_module_restriction()
    {
        $superAdmin = User::role('Super Admin')->first();
        
        $school = School::create([
            'name' => 'SA School',
            'email' => 'sa@school.com',
            'status' => 'active'
        ]);

        $studentsModule = Module::where('slug', 'students')->first();
        // Disable students module
        $school->modules()->sync([$studentsModule->id => ['is_active' => false]]);

        // Super Admin makes a request to student route -> returns 200 (bypass)
        $response = $this->actingAs($superAdmin)
            ->getJson('/api/students');

        $response->assertStatus(200);
    }

    public function test_syncing_modules_on_school_creation()
    {
        $superAdmin = User::role('Super Admin')->first();
        $studentsModule = Module::where('slug', 'students')->first();

        $data = [
            'name' => 'New High School',
            'email' => 'newhigh@school.com',
            'phone' => '1234567890',
            'address' => '456 Street',
            'status' => 'active',
            'admin_name' => 'New Admin',
            'admin_email' => 'newadmin@school.com',
            'admin_password' => 'secret123',
            'admin_mobile' => '9876543210',
            'module_ids' => [$studentsModule->id]
        ];

        $response = $this->actingAs($superAdmin)
            ->postJson('/api/schools', $data);

        $response->assertStatus(210);

        $school = School::where('email', 'newhigh@school.com')->first();
        $this->assertNotNull($school);

        // Verify module is enabled for school
        $this->assertDatabaseHas('school_modules', [
            'school_id' => $school->id,
            'module_id' => $studentsModule->id,
            'is_active' => true
        ]);
    }

    public function test_permissions_are_filtered_by_active_school_modules()
    {
        $school = School::create([
            'name' => 'Filtered Perms School',
            'email' => 'filteredperms@school.com',
            'status' => 'active'
        ]);

        $schoolAdmin = User::create([
            'name' => 'School Admin User',
            'email' => 'filteredadmin@school.com',
            'password' => bcrypt('password'),
            'school_id' => $school->id,
            'status' => 'active'
        ]);
        $schoolAdmin->assignRole('School Admin');

        $studentsModule = Module::where('slug', 'students')->first();
        $academicsModule = Module::where('slug', 'academics')->first();

        // 1. Only enable students module (academics and fees are disabled)
        $school->modules()->sync([
            $studentsModule->id => ['is_active' => true],
            $academicsModule->id => ['is_active' => false],
        ]);

        $response = $this->actingAs($schoolAdmin)
            ->getJson('/api/permissions');

        $response->assertStatus(200);
        
        $permissionNames = collect($response->json('permissions'))->pluck('name');

        // Should contain students permissions
        $this->assertTrue($permissionNames->contains('student.view'));

        // Should NOT contain academic permissions since academics module is disabled
        $this->assertFalse($permissionNames->contains('class.view'));

        // 2. Enable academics module as well
        $school->modules()->sync([
            $studentsModule->id => ['is_active' => true],
            $academicsModule->id => ['is_active' => true],
        ]);

        $response2 = $this->actingAs($schoolAdmin)
            ->getJson('/api/permissions');

        $response2->assertStatus(200);
        
        $permissionNames2 = collect($response2->json('permissions'))->pluck('name');

        // Should now contain academic permissions
        $this->assertTrue($permissionNames2->contains('class.view'));
    }

    public function test_school_creation_creates_default_roles()
    {
        $school = School::create([
            'name' => 'Test School Roles',
            'email' => 'testroles@school.com',
            'status' => 'active'
        ]);

        $roles = \App\Models\Role::where('school_id', $school->id)->get();
        $this->assertCount(2, $roles);
        $this->assertTrue($roles->contains('name', 'School Admin'));
        $this->assertTrue($roles->contains('name', 'Teacher'));
    }
}
