<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear roles
        $devRole = Role::create(['name' => 'dev']);
        $admin = Role::create(['name' => 'admin']);
        //Asignar el rol "dev" al usuario con ID 1
        $user = User::find(1);
        if ($user) {
            $user->assignRole($devRole);
        }

        // contar cuantos usuarios hay en la tabla users y asignar el rol "admin" todos excepto al usuario con ID 1
        $users = User::all();
        foreach ($users as $user) {
            if ($user->id != 1) {
                $user->assignRole($admin);
            }
        }
    }
}