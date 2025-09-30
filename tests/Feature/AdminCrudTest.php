<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create([
            'email' => 'admin@example.com',
        ]);

        $managerRole = Role::where('name', 'manager')->firstOrFail();
        $this->admin->roles()->sync([$managerRole->id]);
    }

    public function test_roles_crud_flow(): void
    {
        Sanctum::actingAs($this->admin);

        $permissionId = Permission::where('name', 'read')->firstOrFail()->id;

        $createResponse = $this->postJson('/api/admin/roles', [
            'name' => 'auditor',
            'display_name' => 'Auditor',
            'description' => 'Reviews records',
            'permissions' => [$permissionId],
        ]);

        $createResponse->assertCreated();

        $roleId = $createResponse->json('id');

        $this->getJson('/api/admin/roles')->assertJsonFragment(['name' => 'auditor']);

        $this->putJson("/api/admin/roles/{$roleId}", [
            'description' => 'Updated description',
        ])->assertOk()->assertJsonFragment(['description' => 'Updated description']);

        $this->deleteJson("/api/admin/roles/{$roleId}")->assertNoContent();

        $this->assertDatabaseMissing('roles', ['id' => $roleId]);
    }

    public function test_permission_crud_flow(): void
    {
        Sanctum::actingAs($this->admin);

        $createResponse = $this->postJson('/api/admin/permissions', [
            'name' => 'archive',
            'display_name' => 'Archive',
        ]);

        $createResponse->assertCreated();
        $permissionId = $createResponse->json('id');

        $this->getJson('/api/admin/permissions')->assertJsonFragment(['name' => 'archive']);

        $this->putJson("/api/admin/permissions/{$permissionId}", [
            'description' => 'Allows archiving',
        ])->assertOk()->assertJsonFragment(['description' => 'Allows archiving']);

        $this->deleteJson("/api/admin/permissions/{$permissionId}")->assertNoContent();

        $this->assertDatabaseMissing('permissions', ['id' => $permissionId]);
    }

    public function test_user_crud_flow(): void
    {
        Sanctum::actingAs($this->admin);

        $team = Team::create([
            'name' => 'Core Team',
            'description' => 'Main delivery team',
        ]);

        $roleId = Role::where('name', 'client')->firstOrFail()->id;

        $createResponse = $this->postJson('/api/admin/users', [
            'name' => 'API User',
            'email' => 'api.user@example.com',
            'password' => 'Password123!',
            'roles' => [$roleId],
            'teams' => [
                ['id' => $team->id, 'role' => 'owner'],
            ],
            'profile' => [
                'first_name' => 'API',
                'last_name' => 'User',
                'phone' => '+1-555-0000',
                'meta' => ['timezone' => 'UTC'],
            ],
        ]);

        $createResponse->assertCreated();
        $userId = $createResponse->json('id');

        $this->assertDatabaseHas('profiles', [
            'user_id' => $userId,
            'first_name' => 'API',
        ]);

        $this->putJson("/api/admin/users/{$userId}", [
            'name' => 'Updated User',
            'profile' => [],
        ])->assertOk()->assertJsonFragment(['name' => 'Updated User']);

        $this->deleteJson("/api/admin/users/{$userId}")->assertNoContent();

        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }

    public function test_profile_crud_flow(): void
    {
        Sanctum::actingAs($this->admin);

        $user = User::factory()->create();

        $createResponse = $this->postJson('/api/admin/profiles', [
            'user_id' => $user->id,
            'first_name' => 'Profile',
            'last_name' => 'Owner',
            'meta' => ['department' => 'QA'],
        ]);

        $createResponse->assertCreated();
        $profileId = $createResponse->json('id');

        $this->putJson("/api/admin/profiles/{$profileId}", [
            'phone' => '1234567890',
        ])->assertOk()->assertJsonFragment(['phone' => '1234567890']);

        $this->deleteJson("/api/admin/profiles/{$profileId}")->assertNoContent();

        $this->assertDatabaseMissing('profiles', ['id' => $profileId]);
    }

    public function test_team_crud_flow(): void
    {
        Sanctum::actingAs($this->admin);

        $member = User::factory()->create();

        $createResponse = $this->postJson('/api/admin/teams', [
            'name' => 'Support Team',
            'description' => 'Handles support tickets',
            'members' => [
                ['id' => $member->id, 'role' => 'support'],
            ],
        ]);

        $createResponse->assertCreated();
        $teamId = $createResponse->json('id');

        $this->putJson("/api/admin/teams/{$teamId}", [
            'description' => '24/7 Support',
        ])->assertOk()->assertJsonFragment(['description' => '24/7 Support']);

        $this->deleteJson("/api/admin/teams/{$teamId}")->assertNoContent();

        $this->assertDatabaseMissing('teams', ['id' => $teamId]);
    }

    public function test_setting_crud_flow(): void
    {
        Sanctum::actingAs($this->admin);

        $createResponse = $this->postJson('/api/admin/settings', [
            'key' => 'app.theme',
            'value' => 'dark',
            'type' => 'string',
        ]);

        $createResponse->assertCreated();
        $settingId = $createResponse->json('id');

        $this->putJson("/api/admin/settings/{$settingId}", [
            'value' => 'light',
        ])->assertOk()->assertJsonFragment(['value' => 'light']);

        $this->deleteJson("/api/admin/settings/{$settingId}")->assertNoContent();

        $this->assertDatabaseMissing('settings', ['id' => $settingId]);
    }
}
