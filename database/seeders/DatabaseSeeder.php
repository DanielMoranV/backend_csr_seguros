<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Daniel Moran Vilchez',
            'email' => 'daniel.moranv94@gmail.com',
            'nick' => 'DMORAN',
            'password' => bcrypt('admin3264'),
            'dni' => '70315050',
            'phone' => '948860381',
            'position' => 'SISTEMAS',
        ]);

        $this->call([
            RolesSeeder::class,
            InsurersTableSeeder::class,
            UserSeeder::class,
        ]);
    }
}
