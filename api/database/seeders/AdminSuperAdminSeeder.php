<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

use App\Models\AdminUser;

class AdminSuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear el rol superadmin si no existe
        $superAdminRole = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'admin']);
        $adminRole      = Role::create(['name' => 'admin', 'guard_name' => 'admin']);
        $permission     = Permission::create(['name' => 'manage-admin', 'guard_name' => 'admin']);
        $adminRole->givePermissionTo('manage-admin');

        // Crear permisos para Zonas de estudio
        Permission::create(['name' => 'manage-study-zones', 'guard_name' => 'admin']);
        Permission::create(['name' => 'create-study-zones', 'guard_name' => 'admin']);
        Permission::create(['name' => 'update-study-zones', 'guard_name' => 'admin']);
        Permission::create(['name' => 'delete-study-zones', 'guard_name' => 'admin']);

        // Crear permisos para Usuarios Admin
        Permission::create(['name' => 'manage-admin-users', 'guard_name' => 'admin']);
        Permission::create(['name' => 'create-admin-users', 'guard_name' => 'admin']);
        Permission::create(['name' => 'update-admin-users', 'guard_name' => 'admin']);
        Permission::create(['name' => 'delete-admin-users', 'guard_name' => 'admin']);

        // Crear permisos para Usuarios App
        Permission::create(['name' => 'manage-app-users', 'guard_name' => 'admin']);
        Permission::create(['name' => 'delete-app-users', 'guard_name' => 'admin']);

        // Crear permisos para Roles
        Permission::create(['name' => 'manage-roles', 'guard_name' => 'admin']);
        Permission::create(['name' => 'create-roles', 'guard_name' => 'admin']);
        Permission::create(['name' => 'update-roles', 'guard_name' => 'admin']);
        Permission::create(['name' => 'delete-roles', 'guard_name' => 'admin']);

        // Crear permisos para observaciones
        Permission::create(['name' => 'manage-observations', 'guard_name' => 'admin']);
        Permission::create(['name' => 'delete-observations', 'guard_name' => 'admin']);

        // Crear el usuario superadmin si no existe
        $superAdmin = AdminUser::firstOrCreate([
            'id'                => Str::uuid(),
            'avatar_id'         => '1',
            'email'             => 'superadmin@scienceforchange.eu',
            'password'          => Hash::make('password'),
            'remember_token'    => Str::random(10),
            'email_verified_at' => now(),
        ]);

        // Crear el usuario admin si no existe
        $admin = AdminUser::firstOrCreate([
            'id'                => Str::uuid(),
            'avatar_id'         => '1',
            'email'             => 'admin@scienceforchange.eu',
            'password'          => Hash::make('password'),
            'remember_token'    => Str::random(10),
            'email_verified_at' => now(),
        ]);

        // Asignamos todos los permisos al rol superadmin
        $superAdminRole->givePermissionTo(Permission::all());

        // Asignar el rol superadmin al usuario
        $superAdmin->assignRole($superAdminRole);

        // Asignar el rol admin al usuario
        $admin->assignRole($adminRole);
    }
}
