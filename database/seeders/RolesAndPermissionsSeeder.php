<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Load permissions from JSON file
        $jsonPath = database_path('seeders/data/roles_permissions.json');

        if (!file_exists($jsonPath)) {
            $this->command->error('❌ File not found: ' . $jsonPath);
            return;
        }

        $data = json_decode(file_get_contents($jsonPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error('❌ Invalid JSON: ' . json_last_error_msg());
            return;
        }

        // Create permissions from resources
        $allPermissions = [];

        foreach ($data['resources'] as $resource) {
            foreach ($resource['permissions'] as $action) {
                $permissionName = "{$action}_{$resource['name']}";
                Permission::firstOrCreate(['name' => $permissionName]);
                $allPermissions[] = $permissionName;

                $this->command->info("✅ Permission created: {$permissionName}");
            }
        }

        // Create roles and assign permissions
        foreach ($data['roles'] as $roleData) {
            $role = Role::firstOrCreate(['name' => $roleData['name']]);

            // If permissions is '*', assign all permissions
            if ($roleData['permissions'] === '*') {
                $role->syncPermissions($allPermissions);
            } else {
                // Otherwise, assign specific permissions
                $role->syncPermissions($roleData['permissions']);
            }

            $this->command->info("✅ Role created: {$roleData['name']} ({$roleData['display_name']})");
        }

        // Create admin user
        $adminData = $data['admin_user'];
        $admin = User::firstOrCreate(
            ['email' => $adminData['email']],
            [
                'name' => $adminData['name'],
                'phone' => $adminData['phone'],
                'password' => bcrypt($adminData['password']),
                'national_id' => $adminData['national_id'],
                'gender' => $adminData['gender'],
                'email_verified_at' => now(),
                'user_type' => $adminData['user_type'],
            ]
        );

        // Assign role to admin user
        $admin->assignRole($adminData['role']);

        $this->command->info("✅ Admin user created: {$adminData['email']}");
        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("✅ Total permissions created: " . count($allPermissions));
        $this->command->info("✅ Total roles created: " . count($data['roles']));
    }
}
