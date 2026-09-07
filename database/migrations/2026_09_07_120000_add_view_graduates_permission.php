<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Add the Graduates module permission to an existing install.
     *
     * Deliberately additive rather than a re-run of RolesAndPermissionsSeeder: that seeder calls
     * syncPermissions(), which would wipe any role permissions tuned through the Roles &
     * Permissions screen since the install. This only ever grants, never revokes.
     *
     * Granted to whoever already looks after students or fees - the graduate register is an
     * academic record that the finance office chases outstanding balances through - plus Super
     * Admin, which is expected to hold everything.
     */
    public function up(): void
    {
        $permission = Permission::firstOrCreate(['name' => 'view-graduates', 'guard_name' => 'web']);

        $roles = Role::where('name', 'Super Admin')
            ->orWhereHas('permissions', fn ($query) => $query->whereIn('name', ['manage-students', 'manage-fees']))
            ->get();

        foreach ($roles as $role) {
            if (!$role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::where('name', 'view-graduates')->where('guard_name', 'web')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
