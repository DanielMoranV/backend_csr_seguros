<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $json = file_get_contents(database_path('seeders/users.json'));
        $users = json_decode($json, true);

        foreach ($users as $userData) {
            User::create([
                'name' => $userData['name'],
                'dni' => $userData['dni'],
                'position' => $userData['position'],
                'nick' => $userData['nick'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
                'phone' => $userData['phone'],
            ]);
        }
    }
}
