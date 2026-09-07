<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Module-level permissions available for assignment to roles.
     *
     * @var array<int, string>
     */
    public const PERMISSIONS = [
        'manage-students',
        'view-student-directory',
        'view-graduates',
        'manage-programmes',
        'manage-semesters',
        'manage-courses',
        'manage-results',
        'view-results',
        'manage-fees',
        'manage-transcripts',
        'view-reports',
        'send-sms',
        'manage-settings',
        'manage-users',
        'manage-roles',
        'view-activity-logs',
        'manage-import-export',
        'manage-biometric',
        'manage-classes',
        'manage-timetable',
        'manage-continuous-assessment',
        'manage-sts',
    ];

    /**
     * Default role => permissions mapping seeded out of the box.
     * Super Admin is granted every permission below regardless of this list.
     *
     * @var array<string, array<int, string>>
     */
    public const DEFAULT_ROLES = [
        'Super Admin' => [],
        'Accountant' => ['manage-fees', 'view-reports', 'view-student-directory', 'view-graduates'],
        'Principal' => ['view-reports', 'view-graduates'],
        'Exams Officer' => ['manage-results', 'manage-courses', 'manage-transcripts', 'manage-classes', 'manage-timetable', 'manage-continuous-assessment', 'view-graduates'],
        'Lecturer' => ['view-results', 'manage-continuous-assessment'],
        'STS Coordinator' => ['manage-sts', 'view-reports'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        foreach (self::DEFAULT_ROLES as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

            $rolePermissions = $roleName === 'Super Admin' ? self::PERMISSIONS : $permissions;
            $role->syncPermissions($rolePermissions);
        }

        // Make sure existing admin accounts retain full access after this rollout.
        $superAdmin = Role::where('name', 'Super Admin')->first();
        User::where('role', User::ROLE_ADMIN)->get()->each(function (User $user) use ($superAdmin) {
            if (!$user->hasRole($superAdmin)) {
                $user->assignRole($superAdmin);
            }
        });
    }
}
