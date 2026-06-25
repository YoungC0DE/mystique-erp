<?php

namespace Tests\Feature\Rbac;

use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesModulePayload;
use Tests\TestCase;

class PermissionEnforcementTest extends TestCase
{
    use CreatesModulePayload;
    use RefreshDatabase;

    public function test_direct_permission_grants_access(): void
    {
        $user = $this->userWithPermissions(['read']);
        Module::factory()->create();

        $this->actingAsApi($user)->getJson('/api/modules')->assertOk();
    }

    public function test_permission_inherited_via_role_grants_access(): void
    {
        $permissions = $this->seedCrudPermissions();

        $role = Role::factory()->create();
        $role->permissions()->attach($permissions['read']->id);

        $user = User::factory()->create();
        $user->roles()->attach($role->id);
        Module::factory()->create();

        $this->actingAsApi($user->fresh())->getJson('/api/modules')->assertOk();
    }

    public function test_missing_permission_is_denied(): void
    {
        $user = $this->userWithPermissions(['read']);

        $this->actingAsApi($user)
            ->postJson('/api/modules', ['name' => 'Bloqueado'])
            ->assertForbidden();
    }

    public function test_admin_bypasses_all_permission_checks(): void
    {
        $this->mockModuleTableColumns();
        $admin = $this->admin();

        $this->actingAsApi($admin)
            ->postJson('/api/modules', $this->modulePayload('Livre'))
            ->assertCreated();
    }
}
