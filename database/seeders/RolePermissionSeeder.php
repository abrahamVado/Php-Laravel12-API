<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = collect([
            'read' => 'Permite visualizar recursos',
            'write' => 'Permite crear nuevos recursos',
            'delete' => 'Permite eliminar recursos',
            'update' => 'Permite actualizar recursos',
        ])->mapWithKeys(function (string $description, string $name) {
            $permission = Permission::updateOrCreate(
                ['name' => $name],
                [
                    'display_name' => ucfirst($name),
                    'description' => $description,
                ]
            );

            return [$name => $permission->id];
        });

        $rolePermissions = [
            'client' => ['read'],
            'support' => ['read', 'update'],
            'manager' => ['read', 'update', 'write'],
            'gerente' => ['read', 'update', 'write', 'delete'],
            'subgerente' => ['read', 'update'],
        ];

        foreach ($rolePermissions as $roleName => $permissionNames) {
            $role = Role::updateOrCreate(
                ['name' => $roleName],
                [
                    'display_name' => ucfirst($roleName),
                    'description' => 'Rol base: ' . $roleName,
                ]
            );

            $role->permissions()->sync($permissions->only($permissionNames)->values()->all());
        }
    }
}
