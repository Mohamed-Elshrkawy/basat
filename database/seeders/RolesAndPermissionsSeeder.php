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
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'supervisor']);
        Role::create(['name' => 'driver']);
        Role::create(['name' => 'rider']);


        $admin = User::create([
            'name' => 'مدير النظام',
            'email' => 'admin@basat.com',
            'phone' => '0500000000',
            'password' => bcrypt('password'),
            'national_id' => '123456789012345678',
            'gender' => 'male',
            'email_verified_at' => now(),
        ]);

        $admin->assignRole('supervisor');
    }
}
