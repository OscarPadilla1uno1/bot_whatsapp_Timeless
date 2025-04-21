<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario administrador
        $admin = User::firstOrCreate([
            'email' => 'admin@example.com'
        ], [
            'name' => 'Admin',
            'password' => Hash::make('Administrador')
        ]);
        $admin->givePermissionTo('Administrador'); // ✅ Asignar permiso "admin"

        // Crear usuario motorista
        $motorista = User::firstOrCreate([
            'email' => 'motorista@example.com'
        ], [
            'name' => 'Motorista',
            'password' => Hash::make('passwordmotor')
        ]);
        $motorista->givePermissionTo('Motorista'); // ✅ Asignar permiso "dashboard_Motorista"

        // Crear usuario cocina
        $cocina = User::firstOrCreate([
            'email' => 'cocina@example.com'
        ], [
            'name' => 'Cocina',
            'password' => Hash::make('passwordcocina')
        ]);
        $cocina->givePermissionTo('Cocina'); // ✅ Asignar permiso "dashboard_cocina"
    }
}
